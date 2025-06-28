<?php
// order_confirmation.php
session_start();
require_once 'db.php';
require_once 'cart_functions.php'; // For consistency, though not strictly needed here for cart functions

$order_id = isset($_GET['order_id']) ? filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT) : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$orderDetails = null;
$orderItems = [];

if ($order_id && $user_id) {
    $conn = getDBConnection();
    if ($conn) {
        try {
            // Fetch order details
            $stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id");
            $stmt_order->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt_order->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_order->execute();
            $orderDetails = $stmt_order->fetch(PDO::FETCH_ASSOC);

            // Fetch order items
            if ($orderDetails) {
                $stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $stmt_items->bindParam(':order_id', $order_id, PDO::PARAM_INT);
                $stmt_items->execute();
                $orderItems = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            error_log("Error fetching order confirmation: " . $e->getMessage());
            $_SESSION['inquiry_message'] = "Error retrieving order details.";
            $_SESSION['inquiry_type'] = "danger";
            header("Location: products.php");
            exit();
        }
    }
} else {
    $_SESSION['inquiry_message'] = "Invalid order ID or not logged in to view order.";
    $_SESSION['inquiry_type'] = "danger";
    header("Location: products.php");
    exit();
}

$cartTotalQuantity = getCartTotalQuantity($user_id); // For header display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .confirmation-box {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            border: 1px solid #d4edda;
            border-radius: 8px;
            background-color: #d4edda; /* Light green for success */
            color: #155724;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-box h2 {
            color: #155724;
            margin-bottom: 20px;
        }
        .order-details, .item-details {
            text-align: left;
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
        }
        .item-details ul {
            list-style: none;
            padding: 0;
        }
        .item-details li {
            border-bottom: 1px dashed #eee;
            padding: 8px 0;
        }
        .item-details li:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <header>
        <h1>Order Confirmed!</h1>
        <nav>
            <a href="index.php">Home</a> |
            <a href="products.php">Products</a> |
            <a href="cart.php">Cart (<span id="cart-count"><?php echo $cartTotalQuantity; ?></span>)</a> |
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span> |
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a> |
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <div class="confirmation-box">
            <?php if ($orderDetails): ?>
                <h2>Thank you for your order!</h2>
                <p>Your order has been placed successfully. Below are your order details:</p>
                
                <div class="order-details">
                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($orderDetails['id']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars($orderDetails['order_date']); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($orderDetails['total_amount'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($orderDetails['status']); ?></p>
                    </div>

                <div class="item-details">
                    <h3>Items in your order:</h3>
                    <ul>
                        <?php foreach ($orderItems as $item): ?>
                            <li>
                                <?php echo htmlspecialchars($item['product_title']); ?> (Product ID: <?php echo htmlspecialchars($item['product_id']); ?>) <br>
                                Quantity: <?php echo htmlspecialchars($item['quantity']); ?> @ $<?php echo number_format($item['price_at_purchase'], 2); ?> each
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <p>We'll send a confirmation email to your registered email address shortly.</p>
                <p>You can view your order history in your <a href="dashboard.php">account dashboard</a> (if you implement one).</p>
                <a href="products.php" class="checkout-button" style="background-color: #007bff;">Continue Shopping</a>

            <?php else: ?>
                <p>Unable to retrieve order details. Please check your order history or contact support.</p>
                <a href="products.php" class="checkout-button" style="background-color: #007bff;">Browse Products</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>