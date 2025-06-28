<?php
// checkout.php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$cartItems = getCartItems($user_id);
$cartTotalQuantity = getCartTotalQuantity($user_id);

if (empty($cartItems)) {
    $_SESSION['inquiry_message'] = "Your cart is empty. Please add items before checking out.";
    $_SESSION['inquiry_type'] = "warning";
    header("Location: products.php");
    exit();
}

$overallCartTotal = 0;
foreach ($cartItems as $item) {
    $overallCartTotal += $item['subtotal'];
}

// You would ideally pre-fill buyer info from user's profile if logged in
$buyerName = isset($_SESSION['username']) ? $_SESSION['username'] : ''; // Placeholder
$buyerEmail = ''; // Fetch from user's profile if possible
$buyerAddress = ''; // Will come from address management
$buyerPhone = ''; // Will come from user's profile

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .checkout-form {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .checkout-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .checkout-form input[type="text"],
        .checkout-form input[type="email"],
        .checkout-form textarea {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .checkout-form button {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
        }
        .order-summary {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #eee;
            background-color: #f9f9f9;
        }
        .order-summary ul {
            list-style: none;
            padding: 0;
        }
        .order-summary li {
            margin-bottom: 5px;
        }
        .order-summary .total {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <header>
        <h1>Checkout</h1>
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
        <?php
        if (isset($_SESSION['inquiry_message'])) {
            $message_type = $_SESSION['inquiry_type'] ?? 'info';
            echo '<div class="alert alert-' . htmlspecialchars($message_type) . '">' . htmlspecialchars($_SESSION['inquiry_message']) . '</div>';
            unset($_SESSION['inquiry_message']);
            unset($_SESSION['inquiry_type']);
        }
        ?>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <ul>
                <?php foreach ($cartItems as $item): ?>
                    <li>
                        <?php echo htmlspecialchars($item['title']); ?> x <?php echo $item['quantity']; ?>
                        - $<?php echo number_format($item['subtotal'], 2); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="total">Total: $<?php echo number_format($overallCartTotal, 2); ?></p>
        </div>

        <form action="purchase.php" method="POST" class="checkout-form">
            <h2>Buyer Information</h2>
            <label for="buyerName">Full Name:</label>
            <input type="text" id="buyerName" name="buyerName" value="<?php echo htmlspecialchars($buyerName); ?>" required>

            <label for="buyerEmail">Email:</label>
            <input type="email" id="buyerEmail" name="buyerEmail" value="<?php echo htmlspecialchars($buyerEmail); ?>" required>

            <label for="buyerAddress">Shipping Address:</label>
            <textarea id="buyerAddress" name="buyerAddress" rows="4" required><?php echo htmlspecialchars($buyerAddress); ?></textarea>

            <label for="buyerPhone">Phone Number:</label>
            <input type="text" id="buyerPhone" name="buyerPhone" value="<?php echo htmlspecialchars($buyerPhone); ?>" required>

            <p>Payment Method: (Will be added later)</p>

            <button type="submit">Place Order</button>
        </form>
    </main>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Placeholder for any future AJAX on checkout page
    </script>
</body>
</html>