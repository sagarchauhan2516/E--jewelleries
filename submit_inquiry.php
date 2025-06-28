<?php
session_start();
require_once 'db.php'; // Include your database connection file

// Get the database connection using the function from db.php
// This is the CRUCIAL CHANGE
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $yourName = isset($_POST['yourName']) ? htmlspecialchars(trim($_POST['yourName'])) : '';
    $contactNumber = isset($_POST['contactNumber']) ? htmlspecialchars(trim($_POST['contactNumber'])) : '';
    $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
    $productOfInterest = isset($_POST['productOfInterest']) ? htmlspecialchars(trim($_POST['productOfInterest'])) : '';
    $desiredQuantity = isset($_POST['desiredQuantity']) ? htmlspecialchars(trim($_POST['desiredQuantity'])) : '';
    $message = isset($_POST['message']) ? htmlspecialchars(trim($_POST['message'])) : '';

    // Basic validation
    if (empty($yourName) || empty($contactNumber) || empty($email) || empty($productOfInterest)) {
        $_SESSION['inquiry_message'] = "Please fill in all required fields (Name, Contact, Email, Product).";
        $_SESSION['inquiry_type'] = "danger"; // For styling the message
        header("Location: products.php"); // Redirect back to products page
        exit();
    }

    // Prepare an SQL INSERT statement
    // Ensure 'inquiries' table exists with these columns (or adjust column names)
    // As previously suggested, table columns would be:
    // `inquiry_id`, `your_name`, `contact_number`, `email`, `product_of_interest`, `desired_quantity`, `message`, `inquiry_date`
    $sql = "INSERT INTO inquiries (your_name, contact_number, email, product_of_interest, desired_quantity, message)
            VALUES (:your_name, :contact_number, :email, :product_of_interest, :desired_quantity, :message)";

    try {
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':your_name', $yourName);
        $stmt->bindParam(':contact_number', $contactNumber);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':product_of_interest', $productOfInterest);
        $stmt->bindParam(':desired_quantity', $desiredQuantity);
        $stmt->bindParam(':message', $message);

        // Execute the statement
        if ($stmt->execute()) {
            $_SESSION['inquiry_message'] = "Your inquiry has been submitted successfully! We will get back to you soon.";
            $_SESSION['inquiry_type'] = "success";
        } else {
            $_SESSION['inquiry_message'] = "Error submitting your inquiry. Please try again.";
            $_SESSION['inquiry_type'] = "danger";
            error_log("Inquiry submission failed for: " . $email . " - Error: " . implode(" - ", $stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        $_SESSION['inquiry_message'] = "An unexpected database error occurred. Please try again later.";
        $_SESSION['inquiry_type'] = "danger";
        error_log("PDO Exception in submit_inquiry.php: " . $e->getMessage());
    }

    // Close the connection (optional for PDO at end of script)
    $conn = null;

    // Redirect back to the products page (or a thank you page)
    header("Location: products.php");
    exit();

} else {
    // If someone tries to access this page directly without POST request
    header("Location: products.php");
    exit();
}
?>