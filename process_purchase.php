<?php
session_start();
require_once 'db.php';
require_once 'cart_functions.php'; // Include cart functions

// Enable detailed error reporting for development (REMOVE FOR PRODUCTION!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect buyer details from the form (these would typically come from a checkout form)
    // For now, these are placeholders or from product.php, you'll update this for checkout.
    $buyerName = isset($_POST['buyerName']) ? htmlspecialchars(trim($_POST['buyerName'])) : '';
    $buyerEmail = isset($_POST['buyerEmail']) ? filter_var(trim($_POST['buyerEmail']), FILTER_SANITIZE_EMAIL) : '';
    $buyerAddress = isset($_POST['buyerAddress']) ? htmlspecialchars(trim($_POST['buyerAddress'])) : ''; // This will be from Address Management soon
    $buyerPhone = isset($_POST['buyerPhone']) ? htmlspecialchars(trim($_POST['buyerPhone'])) : '';

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // --- Validation for buyer details (you might move this to checkout.php validation) ---
    if (empty($buyerName) || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL) || empty($buyerAddress) || empty($buyerPhone)) {
        $_SESSION['inquiry_message'] = "Please fill in all required buyer information correctly.";
        $_SESSION['inquiry_type'] = "danger";
        header("Location: checkout.php"); // Redirect to checkout if buyer info is missing
        exit();
    }

    // --- IMPORTANT: Get cart items for processing ---
    $cartItems = getCartItems($user_id);

    if (empty($cartItems)) {
        $_SESSION['inquiry_message'] = "Your cart is empty. Please add items before proceeding to purchase.";
        $_SESSION['inquiry_type'] = "danger";
        header("Location: products.php");
        exit();
    }

    // Early user_id check (should be handled on checkout page too)
    if ($user_id === null) {
        $_SESSION['show_login_popup'] = true;
        $_SESSION['inquiry_message'] = "Please log in to complete your purchase.";
        $_SESSION['inquiry_type'] = "info";
        header("Location: products.php"); // Or back to login page
        exit();
    }

    $conn = getDBConnection();
    if (!$conn) {
        $_SESSION['inquiry_message'] = "Database connection error. Please try again later.";
        $_SESSION['inquiry_type'] = "danger";
        header("Location: products.php");
        exit();
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction(); // Start transaction for atomicity

    try {
        $total_amount = 0;
        $productTitles = []; // To list purchased product titles in success message

        // First, check availability and calculate total, and lock products
        // This loop processes each item in the cart
        foreach ($cartItems as $item) {
            $stmt_product = $conn->prepare("SELECT product_id, title, material, weight, price, is_available FROM products WHERE product_id = :product_id FOR UPDATE");
            $stmt_product->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt_product->execute();
            $product_data = $stmt_product->fetch(PDO::FETCH_ASSOC);

            if (!$product_data || $product_data['is_available'] == 0) {
                $conn->rollBack();
                $_SESSION['inquiry_message'] = "Sorry, one or more products in your cart ('" . htmlspecialchars($item['title']) . "') are no longer available. Please review your cart.";
                $_SESSION['inquiry_type'] = "danger";
                header("Location: cart.php"); // Redirect back to cart
                exit();
            }

            // Ensure price consistency (optional but good practice to use DB price)
            $actualPrice = $product_data['price'];
            $total_amount += ($actualPrice * $item['quantity']);
            $productTitles[] = htmlspecialchars($product_data['title']);
        }


        // 1. Insert into 'orders' table
        $shipping_address_id = null; // Will come from address management later
        $payment_method_id = null;   // Will come from payment gateway later
        // Status might be 'Pending Payment' at this stage if using a gateway
        $order_status = 'Pending'; // For now, directly pending

        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, order_date, total_amount, status, shipping_address_id, payment_method_id) VALUES (:user_id, NOW(), :total_amount, :status, :shipping_address_id, :payment_method_id)");
        
        $stmt_order->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_order->bindParam(':total_amount', $total_amount);
        $stmt_order->bindParam(':status', $order_status, PDO::PARAM_STR);
        $stmt_order->bindParam(':shipping_address_id', $shipping_address_id, ($shipping_address_id === null) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt_order->bindParam(':payment_method_id', $payment_method_id, ($payment_method_id === null) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        $stmt_order->execute();
        $order_id = $conn->lastInsertId();

        // 2. Insert into 'order_items' table for each cart item
        foreach ($cartItems as $item) {
            // Re-fetch current data from DB for safety, especially price and descriptive fields
            $stmt_product_data_for_item = $conn->prepare("SELECT title, material, weight, price FROM products WHERE product_id = :product_id");
            $stmt_product_data_for_item->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt_product_data_for_item->execute();
            $product_snapshot = $stmt_product_data_for_item->fetch(PDO::FETCH_ASSOC);

            if (!$product_snapshot) {
                $conn->rollBack();
                $_SESSION['inquiry_message'] = "Error: Product details not found for an item in your cart during final processing.";
                $_SESSION['inquiry_type'] = "danger";
                header("Location: cart.php");
                exit();
            }

            $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, product_title, material_at_purchase, weight_at_purchase) VALUES (:order_id, :product_id, :quantity, :price_at_purchase, :product_title, :material_at_purchase, :weight_at_purchase)");
            $stmt_order_item->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_order_item->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt_order_item->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
            $stmt_order_item->bindParam(':price_at_purchase', $product_snapshot['price']); // Use snapshot price
            $stmt_order_item->bindParam(':product_title', $product_snapshot['title'], PDO::PARAM_STR);
            $stmt_order_item->bindParam(':material_at_purchase', $product_snapshot['material'], PDO::PARAM_STR);
            $stmt_order_item->bindParam(':weight_at_purchase', $product_snapshot['weight']);
            $stmt_order_item->execute();

            // 3. Update product availability (reduce stock, or mark as unavailable if 0)
            // Assuming current product system is 'is_available' 0/1. For stock, you'd decrement quantity.
            // For now, if 'quantity' becomes 0, mark as unavailable.
            $stmt_update_product = $conn->prepare("UPDATE products SET is_available = 0 WHERE product_id = :product_id");
            $stmt_update_product->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
            $stmt_update_product->execute();
        }

        // --- Clear the user's cart after successful order creation ---
        if ($user_id) {
            $clearCartStmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
            $clearCartStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $clearCartStmt->execute();
        } else {
            unset($_SESSION['cart']); // Clear session cart for guest
        }

        $conn->commit();
        $purchasedItemsList = implode(', ', $productTitles);
        $_SESSION['inquiry_message'] = "Your purchase for: " . $purchasedItemsList . " was successful! Order ID: $order_id. We'll contact you shortly regarding shipping.";
        $_SESSION['inquiry_type'] = "success";
        header("Location: order_confirmation.php?order_id=$order_id"); // Redirect to confirmation page
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Purchase processing failed: " . $e->getMessage());

        if ($e->getCode() === '23000' && (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1452)) {
            $_SESSION['inquiry_message'] = "An internal error occurred during your purchase: Invalid user or product data. Please try again later or contact support.";
        } else {
            $_SESSION['inquiry_message'] = "An unexpected error occurred during your purchase. Please try again later. Debug Info: " . $e->getMessage() . " (Code: " . $e->getCode() . ")";
        }
        $_SESSION['inquiry_type'] = "danger";
        header("Location: cart.php"); // Redirect back to cart or checkout page
        exit();
    } finally {
        $conn = null;
    }
} else {
    $_SESSION['inquiry_message'] = "Invalid request method.";
    $_SESSION['inquiry_type'] = "danger";
    header("Location: products.php"); // Or cart.php
    exit();
}