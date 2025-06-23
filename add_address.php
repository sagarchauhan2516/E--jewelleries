<?php
// C:\xampp\htdocs\E-Jewelleries\add_address.php

session_start();
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.'];

if (!isLoggedIn()) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loggedInUserId = getUserId();

    // Sanitize inputs
    $full_name = htmlspecialchars(trim($_POST['full_name'] ?? ''));
    $street_address = htmlspecialchars(trim($_POST['street_address'] ?? ''));
    $city = htmlspecialchars(trim($_POST['city'] ?? ''));
    $state = htmlspecialchars(trim($_POST['state'] ?? ''));
    $postal_code = htmlspecialchars(trim($_POST['postal_code'] ?? ''));
    $phone_number = htmlspecialchars(trim($_POST['phone_number'] ?? ''));
    $type = htmlspecialchars(trim($_POST['address_type'] ?? 'shipping')); // Default to shipping
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Basic validation
    if (empty($full_name) || empty($street_address) || empty($city) || empty($state) || empty($postal_code) || empty($phone_number)) {
        $response['message'] = 'Please fill in all required address fields.';
        echo json_encode($response);
        exit();
    }

    $conn = getDBConnection();
    if ($conn) {
        try {
            $conn->beginTransaction(); // Start transaction

            // If new address is set as default, unset previous defaults for this user
            if ($is_default) {
                $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND is_default = 1");
                $stmt->execute([$loggedInUserId]);
            }

            // Insert new address
            $sql = "INSERT INTO addresses (user_id, full_name, street_address, city, state, postal_code, phone_number, type, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$loggedInUserId, $full_name, $street_address, $city, $state, $postal_code, $phone_number, $type, $is_default]);

            $conn->commit(); // Commit transaction

            $response = ['status' => 'success', 'message' => 'Address added successfully!'];

        } catch (PDOException $e) {
            $conn->rollBack(); // Rollback on error
            error_log("Add address DB error: " . $e->getMessage());
            $response['message'] = 'Database error while adding address.';
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
