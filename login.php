<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection function
require_once 'db.php';

// --- FIX 1: Call the function to get the PDO connection object ---
$conn = getDBConnection(); // Assign the returned PDO object to $conn

// Check if connection was successful (getDBConnection() already handles die(), but good practice)
if (!$conn) {
    echo "Database connection failed. Please try again later.";
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect or display an error if not a POST request
    header("Location: login_form.html"); // Redirect to your login form
    exit();
}

// Sanitize inputs
$username = htmlspecialchars(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? ''; // Password will be hashed, so no htmlspecialchars here yet

// Basic validation
if (empty($username) || empty($password)) {
    echo "❌ Please enter both username and password.";
    exit();
}

try {
    // --- FIX 2: Use PDO prepared statements for security and compatibility ---
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);

    // Fetch the user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if a user was found
    if ($user) {
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id']; // Store user ID
            $_SESSION['username'] = $user['username'];
            // You might want to set a login timestamp or other session data

            // Redirect to profile page
            header("Location: profile.php"); // or index.html
            exit();
        } else {
            echo "❌ Incorrect password.";
        }
    } else {
        echo "❌ User not found.";
    }

} catch (PDOException $e) {
    // Catch PDO exceptions (database errors)
    echo "Database error: " . $e->getMessage();
    // In a production environment, you might log the error instead of displaying it.
} catch (Exception $e) {
    // Catch any other general exceptions
    echo "An unexpected error occurred: " . $e->getMessage();
}
?>