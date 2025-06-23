<?php
// C:\xampp\htdocs\E-Jewelleries\add_payment_method.php

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
    $cardholder_name = htmlspecialchars(trim($_POST['cardholder_name'] ?? ''));
    $card_number = htmlspecialchars(trim($_POST['card_number'] ?? ''));
    $expiry_month = htmlspecialchars(trim($_POST['expiry_month'] ?? ''));
    $expiry_year = htmlspecialchars(trim($_POST['expiry_year'] ?? '')); // Should be 2-digit or 4-digit
    $cvv = htmlspecialchars(trim($_POST['cvv'] ?? '')); // Should not be stored in production!
    $card_type = htmlspecialchars(trim($_POST['card_type'] ?? 'Other'));
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // Basic validation
    if (empty($cardholder_name) || empty($card_number) || empty($expiry_month) || empty($expiry_year) || empty($cvv)) {
        $response['message'] = 'Please fill in all required card details.';
        echo json_encode($response);
        exit();
    }

    // Further validation (e.g., number format, length for card_number, expiry date)
    if (!ctype_digit($card_number) || strlen($card_number) < 13 || strlen($card_number) > 19) {
        $response['message'] = 'Invalid card number format.';
        echo json_encode($response);
        exit();
    }

    if (!ctype_digit($expiry_month) || $expiry_month < 1 || $expiry_month > 12) {
        $response['message'] = 'Invalid expiry month.';
        echo json_encode($response);
        exit();
    }

    // Assume expiry_year is 2-digit (e.g., 25 for 2025). Convert to 4-digit for comparison.
    // This simple logic assumes current year is 20XX
    if (strlen($expiry_year) == 2) {
        $expiry_year_full = 2000 + (int)$expiry_year; // e.g., 25 -> 2025
    } else {
        $expiry_year_full = (int)$expiry_year;
    }

    $current_year = (int)date('Y');
    $current_month = (int)date('m');

    if ($expiry_year_full < $current_year || ($expiry_year_full == $current_year && $expiry_month < $current_month)) {
        $response['message'] = 'Card has expired.';
        echo json_encode($response);
        exit();
    }


    // Extract last four digits for display
    $last_four_digits = substr($card_number, -4);

    $conn = getDBConnection();
    if ($conn) {
        try {
            $conn->beginTransaction(); // Start transaction

            // If new method is set as default, unset previous defaults for this user
            if ($is_default) {
                $stmt = $conn->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ? AND is_default = 1");
                $stmt->execute([$loggedInUserId]);
            }

            // Store tokenized_card_data (placeholder for actual tokenization)
            // For this example, we're just storing the raw card number (BAD PRACTICE!)
            // YOU MUST REPLACE THIS WITH ACTUAL TOKENIZATION FROM A PAYMENT GATEWAY.
            $tokenized_card_data = "mock_token_for_" . hash('sha256', $card_number); // This is NOT secure tokenization

            // Insert new payment method
            $sql = "INSERT INTO payment_methods (user_id, card_type, last_four_digits, expiry_month, expiry_year, cardholder_name, tokenized_card_data, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$loggedInUserId, $card_type, $last_four_digits, $expiry_month, $expiry_year_full, $cardholder_name, $tokenized_card_data, $is_default]);

            $conn->commit(); // Commit transaction

            $response = ['status' => 'success', 'message' => 'Payment method added successfully!'];

        } catch (PDOException $e) {
            $conn->rollBack(); // Rollback on error
            error_log("Add payment method DB error: " . $e->getMessage());
            $response['message'] = 'Database error while adding payment method.';
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