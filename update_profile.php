<?php
// C:\xampp\htdocs\E-Jewelleries\update_profile.php

session_start();
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json'); // Respond with JSON

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if (!isLoggedIn()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loggedInUserId = getUserId();

    // Sanitize and validate inputs
    $first_name = htmlspecialchars(trim($_POST['first_name'] ?? ''));
    $last_name = htmlspecialchars(trim($_POST['last_name'] ?? ''));
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $dob = htmlspecialchars(trim($_POST['dob'] ?? '')); // YYYY-MM-DD format
    $gender = htmlspecialchars(trim($_POST['gender'] ?? ''));

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        exit();
    }

    $conn = getDBConnection();
    if ($conn) {
        try {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $loggedInUserId]);
            if ($stmt->fetch()) {
                $response['message'] = 'This email is already registered by another user.';
                echo json_encode($response);
                exit();
            }

            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, dob = ?, gender = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$first_name, $last_name, $email, $phone, $dob, $gender, $loggedInUserId]);

            if ($stmt->rowCount() > 0) {
                // Update session variables if changed (optional, but good practice for consistency)
                $_SESSION['username'] = $first_name . ' ' . $last_name; // Assuming username is first_name + last_name
                $_SESSION['email'] = $email;

                $response = ['status' => 'success', 'message' => 'Profile updated successfully!'];
            } else {
                // No rows affected means data was the same or an issue
                $response['message'] = 'No changes detected or profile could not be updated.';
            }

        } catch (PDOException $e) {
            error_log("Profile update DB error: " . $e->getMessage());
            $response['message'] = 'Database error during profile update.';
        }
    } else {
        $response['message'] = 'Database connection failed.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();
?>