<?php
// add_to_cart.php
session_start();
require_once 'cart_function.php'; // Include the cart functions

header('Content-Type: application/json'); // Respond with JSON

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1; // Default to 1 if not specified

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // --- DEBUGGING START ---
    error_log("add_to_cart.php received: product_id=" . $productId . ", quantity=" . $quantity . ", user_id=" . ($user_id ?? 'NULL'));
    // --- DEBUGGING END ---

    if (addToCart($productId, $quantity, $user_id)) {
        $response['success'] = true;
        $response['message'] = 'Item added to cart!';
        $response['cart_total_quantity'] = getCartTotalQuantity($user_id); // Update cart count
    } else {
        $response['message'] = 'Failed to add item to cart. Product might be unavailable or invalid.';
        // --- DEBUGGING START ---
        error_log("addToCart function returned false for product_id: " . $productId);
        // You might want to get more detailed error from addToCart if it provides any
        // For example, if addToCart returns an array with 'success' and 'error_message'
        // --- DEBUGGING END ---
    }
} else {
    $response['message'] = 'Invalid request method.';
    error_log("add_to_cart.php received non-POST request.");
}

echo json_encode($response);
exit();