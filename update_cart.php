<?php
// update_cart.php
session_start();
require_once 'cart_functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : ''; // 'update_quantity' or 'remove'
    $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 0; // Only for 'update_quantity'

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if ($action === 'update_quantity') {
        if (updateCartItemQuantity($productId, $quantity, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Cart updated successfully.';
            $response['cart_total_quantity'] = getCartTotalQuantity($user_id);
            $response['cart_items'] = getCartItems($user_id); // Optionally send updated cart items
        } else {
            $response['message'] = 'Failed to update cart quantity.';
        }
    } elseif ($action === 'remove') {
        if (removeCartItem($productId, $user_id)) {
            $response['success'] = true;
            $response['message'] = 'Item removed from cart.';
            $response['cart_total_quantity'] = getCartTotalQuantity($user_id);
            $response['cart_items'] = getCartItems($user_id); // Optionally send updated cart items
        } else {
            $response['message'] = 'Failed to remove item from cart.';
        }
    } else {
        $response['message'] = 'Invalid cart action.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();