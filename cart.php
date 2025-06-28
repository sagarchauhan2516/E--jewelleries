<?php
// cart.php
session_start();
require_once 'db.php';
require_once 'cart_functions.php';

error_log("Cart Page: --- Entering cart.php ---"); // New log line
error_log("Cart Page: Session user_id: " . ($_SESSION['user_id'] ?? 'NULL'));
error_log("Cart Page: Session username: " . ($_SESSION['username'] ?? 'NULL'));

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$cartItems = getCartItems($user_id);
$cartTotalQuantity = getCartTotalQuantity($user_id);

error_log("Cart Page: getCartItems returned " . count($cartItems) . " items.");
error_log("Cart Page: Full cartItems array: " . print_r($cartItems, true)); // THIS IS KEY
error_log("Cart Page: Cart total quantity: " . $cartTotalQuantity);
error_log("Cart Page: --- Exiting PHP processing in cart.php ---"); // New log line

$overallCartTotal = 0;
foreach ($cartItems as $item) {
    $overallCartTotal += $item['subtotal'];
}

// Get username for display, default to 'Guest' if not logged in
$username_display = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - E-Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/main.css"> <link rel="stylesheet" href="assets/css/cart.css"> </head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html">
                <img src="images/logo.png" alt="E-Jewellery Shop Logo" width="30" height="30" class="d-inline-block align-text-top me-2">
                <span class="fw-bold" id="navbar-brand-text">E - Jewellery Shop</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
            <a class="nav-link" href="pages/try-on.html">Virtual Try-On</a>
          </li>       
                    <li class="nav-item">
                        <a class="nav-link" href="pages/Jewellery Size Guide.html">Size Guide</a>
                    </li>         
                    <li class="nav-item">
                        <a class="nav-link" href="pages/about_us.html">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/contact.html">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Our Collections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="badge bg-warning text-dark ms-1" id="cart-count"><?php echo htmlspecialchars($cartTotalQuantity); ?></span>
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="nav-link text-black me-3">Welcome, <?php echo $username_display; ?>!</span>
                        <a href="logout.php" class="btn btn-warning me-2">Logout</a>
                    <?php else: ?>
                        <a href="pages/Registration.html" class="btn btn-outline-warning me-2 text-dark">Register</a>
                        <a href="pages/login.html" class="btn btn-warning me-2">Login</a>
                    <?php endif; ?>
                    <div class="profile-icon ms-2">
                        <a href="profile.php">
                            <img src="images/default_avatar.jpg" alt="Profile" id="profilePic">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <h2 class="cart-page-title">Your Shopping Cart</h2>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-items-list">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                            <p>Subtotal: ₹<span class="item-subtotal"><?php echo number_format($item['subtotal'], 2); ?></span></p>
                        </div>
                        <div class="cart-item-quantity">
                            <label for="qty-<?php echo htmlspecialchars($item['product_id']); ?>">Qty:</label>
                            <input type="number" id="qty-<?php echo htmlspecialchars($item['product_id']); ?>" class="quantity-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1">
                        </div>
                        <div class="cart-item-actions">
                            <button class="update-quantity-btn">Update</button>
                            <button class="remove-item-btn">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                Overall Cart Total: ₹<span id="overall-cart-total"><?php echo number_format($overallCartTotal, 2); ?></span>
            </div>

            <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>

        <?php else: ?>
            <p class="empty-cart-message">Your cart is empty. Go to <a href="products.php">our collections</a> to add some items!</p>
        <?php endif; ?>
    </main>

    <footer class="bg-dark text-light pt-4 pb-2 mt-5">
  <div class="container">
    <div class="row">
      <!-- About -->
      <div class="col-md-4 mb-3">
        <h5 class="text-warning">E-Jewellery Shop</h5>
        <p>Explore a stunning collection of gold, diamond and designer jewellery from the comfort of your home.</p>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4 mb-3">
        <h5 class="text-warning">Quick Links</h5>
        <ul class="list-unstyled">
          <li><a href="index.html" class="text-light text-decoration-none">Home</a></li>
          <li><a href="pages/try-on.html" class="text-light text-decoration-none">Try Jewelleries</a></li>
          <li><a href="create_design.php" class="text-light text-decoration-none">GemCraft</a></li>
          <li><a href="products.php" class="text-light text-decoration-none">Our Collections</a></li>
          
          <li><a href="pages/Jewellery Size Guide.html" class="text-light text-decoration-none">Jewellery Size Guide</a></li>
          <li><a href="admin.php" class="text-light text-decoration-none">Go to Admin Controls</a></li> 
          <li><a href="pages/about_us.html" class="text-light text-decoration-none">About Us</a></li>
          <li><a href="pages/contact.html" class="text-light text-decoration-none">Contact Us</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-md-4 mb-3">
        <h5 class="text-warning">Contact Us</h5>
        <p><i class="fa-solid fa-envelope me-2"></i>support@ejewelleries.com</p>
        <p><i class="fa-solid fa-phone me-2"></i>+91 0000000000</p>
        <p><i class="fa-solid fa-location-dot me-2"></i>123 Gold Street, City, India</p>
        <div>
          <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
          <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
        </div>
      </div>
    </div>

    <hr class="border-light" />
    <p class="text-center mb-0">&copy; 2025 Digital Diamonds. All rights reserved.</p>
  </div>
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function updateCartDisplay(response) {
                if (response.success) {
                    $('#cart-count').text(response.cart_total_quantity);
                    // Reload the page to reflect all changes (subtotals, total, item removal)
                    window.location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }

            $('.update-quantity-btn').on('click', function() {
                var itemDiv = $(this).closest('.cart-item');
                var productId = itemDiv.data('product-id');
                var quantityInput = itemDiv.find('.quantity-input');
                var quantity = quantityInput.val();

                // Client-side validation for quantity
                if (quantity < 1 || isNaN(quantity)) {
                    alert('Quantity must be at least 1.');
                    return;
                }

                $.ajax({
                    url: 'update_cart.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'update_quantity',
                        product_id: productId,
                        quantity: quantity
                    },
                    success: updateCartDisplay,
                    error: function(xhr, status, error) {
                        console.error("AJAX Error:", status, error, xhr.responseText);
                        alert('An error occurred while updating cart.');
                    }
                });
            });

            $('.remove-item-btn').on('click', function() {
                var itemDiv = $(this).closest('.cart-item');
                var productId = itemDiv.data('product-id');

                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    $.ajax({
                        url: 'update_cart.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'remove',
                            product_id: productId
                        },
                        success: updateCartDisplay,
                        error: function(xhr, status, error) {
                            console.error("AJAX Error:", status, error, xhr.responseText);
                            alert('An error occurred while removing item.');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>