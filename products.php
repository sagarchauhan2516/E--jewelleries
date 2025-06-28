<?php
session_start();
require_once 'db.php';

$conn = getDBConnection();

$search_query = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$selected_category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'all';

// Fetch all unique categories from the database
$all_categories = [];
$category_stmt = $conn->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
if ($category_stmt) {
    try {
        $category_stmt->execute();
        $category_result = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($category_result as $row) {
            $all_categories[] = $row['category'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch categories: " . $e->getMessage());
    }
} else {
    error_log("Failed to prepare category statement: " . $conn->errorInfo()[2]);
}

// Build the SQL query for filtered products
$sql = "SELECT product_id, title, description, category, image_url, price, weight, material, is_available FROM products WHERE 1=1";
$params = [];

if ($search_query !== '') {
    $sql .= " AND (title LIKE :search1 OR description LIKE :search2)";
    $params[':search1'] = "%" . $search_query . "%";
    $params[':search2'] = "%" . $search_query . "%";
}

if ($selected_category !== 'all') {
    $sql .= " AND category = :category";
    $params[':category'] = $selected_category;
}

$sql .= " ORDER BY category, title ASC";

$filtered_products = [];


$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare product statement: " . $conn->errorInfo()[2]);
    $error_message = "Could not load products. Please try again later.";
} else {
    try {
        $stmt->execute($params);
        $filtered_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to execute product query: " . $e->getMessage());
        $error_message = "Could not retrieve products. Please try again later.";
    }
}

$conn = null;

// Calculate cart item count for navbar
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_item_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Jewellery Shop - Our Collection</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/product.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* Your existing CSS styles */
        .filters-search-section {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 50px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            gap: 20px;
        }

        .filters-search-section .form-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 8px;
        }

        .filters-search-section .form-control,
        .filters-search-section .form-select {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ccc;
            padding: 10px 15px;
            font-size: 1em;
            box-sizing: border-box;
        }

        .filters-search-section .form-control:focus,
        .filters-search-section .form-select:focus {
            border-color: #FFA500;
            box-shadow: 0 0 0 0.25rem rgba(255, 165, 0, 0.25);
        }

        .filters-search-section .btn-primary {
            background-color: #FFA500;
            border-color: #FFA500;
            color: white;
            width: 100%;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1.05em;
            box-sizing: border-box;
        }

        .filters-search-section .btn-primary:hover {
            background-color: #e69500;
            border-color: #e69500;
        }

        .filters-search-section .btn-outline-secondary {
            background-color: transparent;
            color: #6c757d;
            border-color: #6c757d;
            padding: 10px 20px;
            width: 100%;
            border-radius: 8px;
            font-size: 1.05em;
            box-sizing: border-box;
        }

        .filters-search-section .btn-outline-secondary:hover {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .filters-search-section .row {
            display: flex;
            flex-wrap: wrap;
        }

        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px;
            justify-content: center;
        }

        .product-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding-bottom: 20px;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .product-image-container {
            width: 100%;
            height: 250px;
            overflow: hidden;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f8f8;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
        }

        .product-title {
            font-size: 1.4em;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-description {
            font-size: 0.95em;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        /* NEW: Styles for product details */
        .product-details {
            font-size: 0.9em;
            color: #444;
            margin-bottom: 15px;
            text-align: left; /* Align text within details */
            width: 100%;
            padding-left: 10px; /* Indent slightly */
        }
        .product-details p {
            margin-bottom: 5px; /* Spacing between detail lines */
        }
        .product-details strong {
            color: #333;
        }
        .in-stock {
            color: #28a745; /* Green for in stock */
            font-weight: bold;
        }
        .out-of-stock {
            color: #dc3545; /* Red for out of stock */
            font-weight: bold;
        }

        /* Updated button styles for cart and buy now */
        .product-actions {
            display: flex;
            gap: 10px; /* Space between buttons */
            margin-top: auto; /* Push to bottom of card */
            width: 100%;
            justify-content: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .product-actions .btn {
            flex: 1 1 auto; /* Allow buttons to grow and shrink */
            min-width: 120px; /* Minimum width for buttons */
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.95em;
        }

        .btn-add-to-cart {
            background-color: #4CAF50; /* Green for Add to Cart */
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        .btn-add-to-cart:hover {
            background-color: #45a049;
        }
        .btn-buy-now {
            background-color: #FFA500;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        .btn-buy-now:hover {
            background-color: #e69500;
        }
        .btn-out-of-stock {
            background-color: #6c757d; /* Grey for out of stock */
            color: white;
            border: none;
            cursor: not-allowed;
        }
        .btn-out-of-stock:hover {
            background-color: #6c757d; /* No change on hover */
        }


        .section-title {
            text-align: center;
            margin-top: 60px;
            margin-bottom: 40px;
            font-size: 2.5em;
            color: #333;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: #FFA500;
            border-radius: 2px;
        }

        /* Existing Buy Now Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            animation: fadeIn 0.3s;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 15px;
        }

        .close-button:hover,
        .close-button:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .modal-content form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
            margin-top: 15px;
        }

        .modal-content form input[type="text"],
        .modal-content form input[type="email"],
        .modal-content form input[type="tel"],
        .modal-content form textarea,
        .modal-content form input[type="password"] { /* Added password for login modal */
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .modal-content form input[type="text"]:focus,
        .modal-content form input[type="email"]:focus,
        .modal-content form input[type="tel"]:focus,
        .modal-content form textarea:focus,
        .modal-content form input[type="password"]:focus { /* Added password for login modal */
            border-color: #FFA500;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(255, 165, 0, 0.25);
        }

        .modal-content form button[type="submit"] {
            background-color: #FFA500;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 20px;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .modal-content form button[type="submit"]:hover {
            background-color: #e69500;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .alert-info, .alert-warning {
            padding: 1rem;
            margin-top: 20px;
            margin-bottom: 20px;
            border-radius: .25rem;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }

        /* NEW: Login Modal Specific Styles */
        #loginOverlay {
            display: none; /* Ensure it's hidden by default */
            position: fixed;
            z-index: 999; /* Below login modal, above content */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        #loginModal {
            display: none; /* Ensure it's hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6); /* This will be overridden by #loginOverlay's bg */
            justify-content: center;
            align-items: center;
        }

        #loginModal .modal-content {
            max-width: 450px; /* Slightly smaller for login */
            padding: 25px;
        }
        #loginModal .modal-content h2 {
            margin-bottom: 15px;
            font-size: 2em;
        }
        #loginModal .modal-content p {
            margin-bottom: 15px;
            font-size: 0.95em;
        }
        #loginModal .modal-content .form-group {
            margin-bottom: 10px;
        }
        #loginModal .modal-content .mt-3 {
            margin-top: 15px;
        }
    </style>
</head>
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
            <a class="nav-link active" aria-current="page" href="index.html">Home</a>
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
            <a class="nav-link" href="cart.php">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="badge bg-warning text-dark ms-1" id="cartItemCount"><?php echo $cart_item_count; ?></span>
            </a>
          </li>
        </ul>
        <div class="d-flex">
          <a href="pages/Registration.html" class="btn btn-outline-warning me-2 text-dark">Register</a>
          <a href="pages/login.html" class="btn btn-warning me-2">Login</a>
        </div>
        <div class="d-flex align-items-center">
          <div class="profile-icon ms-2">
            <a href="profile.php">
              <img src="images/default_avatar.jpg" alt="Profile" id="profilePic">
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

    <?php
    // Check if there's a general inquiry message (not the login popup trigger)
    if (isset($_SESSION['inquiry_message']) && (!isset($_SESSION['show_login_popup']) || !$_SESSION['show_login_popup'])) {
        $message = $_SESSION['inquiry_message'];
        $type = $_SESSION['inquiry_type'];
        $safe_message = htmlspecialchars($message);
        $safe_type = htmlspecialchars($type);

        echo "<div class='container mt-3'>";
        echo "     <div class='alert alert-$safe_type alert-dismissible fade show' role='alert'>";
        echo "         $safe_message";
        echo "         <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "     </div>";
        echo "</div>";

        unset($_SESSION['inquiry_message']);
        unset($_SESSION['inquiry_type']);
    }
    ?>

    <main class="container my-5">
        <h1 class="text-center mb-5 section-title">Our Jewellery Collection</h1>

        <section class="filters-search-section container" data-aos="fade-up">
            <form action="products.php" method="GET" class="row g-4 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="search" class="form-label visually-hidden">Search Jewellery</label>
                    <input type="text" class="form-control" id="search" name="search"
                                 placeholder="Search jewellery..." value="<?php echo htmlspecialchars($search_query); ?>" aria-label="Search Jewellery">
                </div>

                <div class="col-12 col-md-4">
                    <label for="category" class="form-label visually-hidden">Filter by Jewellery Type</label>
                    <select class="form-select" id="category" name="category" aria-label="Filter by Jewellery Type">
                        <option value="all">All Categories</option>
                        <?php foreach ($all_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"
                                <?php if ($selected_category === $category) echo 'selected'; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $category)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-primary w-100" aria-label="Apply Filters">Apply Filters</button>
                </div>

                <div class="col-6 col-md-1">
                    <a href="products.php" class="btn btn-outline-secondary w-100" aria-label="Reset Filters">Reset</a>
                </div>
            </form>
        </section>

        <?php if (isset($error_message)): ?>
            <p class="text-center alert alert-danger"><?php echo $error_message; ?></p>
        <?php elseif (!empty($filtered_products)): ?>
            <?php
            // Group filtered products by category for display
            $products_by_category_filtered = [];
            foreach ($filtered_products as $product) {
                $category_name = isset($product['category']) ? $product['category'] : 'Uncategorized';
                if (!isset($products_by_category_filtered[$category_name])) {
                    $products_by_category_filtered[$category_name] = [];
                }
                $products_by_category_filtered[$category_name][] = $product;
            }

            // Determine which categories to display based on filters
            $categories_to_display = ($selected_category === 'all' && empty($search_query)) ? $all_categories : array_keys($products_by_category_filtered);
            sort($categories_to_display); // Ensure consistent order

            foreach ($categories_to_display as $category):
                $display_category_name = ucwords(str_replace('_', ' ', $category));
                // Only display category section if there are products in it
                if (isset($products_by_category_filtered[$category]) && !empty($products_by_category_filtered[$category])):
                ?>
                <h2 class="section-title" data-aos="flip-up"><?php echo $display_category_name; ?></h2>
                <div class="product-grid" data-aos="fade-up" data-aos-delay="200">
                    <?php foreach ($products_by_category_filtered[$category] as $product): ?>
                        <div class="product-card" data-aos="zoom-in" data-aos-easing="ease-out-back" data-aos-delay="100">
                            <div class="product-image-container">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>

                                <div class="product-details">
                                    <p><strong>Price:</strong> ₹<?php echo number_format($product['price'], 2); ?></p>
                                    <p><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight']); ?> gm</p>
                                    <p><strong>Material:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $product['material']))); ?></p>
                                    <p><strong>Availability:</strong>
                                        <?php if ($product['is_available'] == 1): ?>
                                            <span class="in-stock">In Stock</span>
                                        <?php else: ?>
                                            <span class="out-of-stock">Out of Stock</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="product-actions">
                                    <?php if ($product['is_available'] == 1): ?>
                                        <div class="input-group" style="width: auto; max-width: 120px;">
                                            <input type="number" class="form-control form-control-sm product-quantity" value="1" min="1" data-product-id="<?php echo $product['product_id']; ?>" aria-label="Quantity">
                                        </div>
                                        <button class="btn btn-sm btn-add-to-cart" data-product-id="<?php echo $product['product_id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['title']); ?>"
                                                data-product-price="<?php echo $product['price']; ?>"
                                                data-product-image="<?php echo htmlspecialchars($product['image_url']); ?>">
                                            <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                        </button>
                                        <button class="btn btn-sm btn-buy-now" onclick="openBuyNowForm('<?php echo htmlspecialchars(addslashes($product['title'])); ?>', '<?php echo htmlspecialchars($product['product_id']); ?>', <?php echo $product['price']; ?>, <?php echo $product['is_available']; ?>)">
                                            <i class="fas fa-shopping-bag me-1"></i> Buy Now
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-out-of-stock" disabled>
                                            <i class="fas fa-exclamation-circle me-1"></i> Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php
                endif; // End if (isset($products_by_category_filtered[$category]) && !empty($products_by_category_filtered[$category]))
            endforeach;
            ?>
            <?php
            // If no products were found after filtering, show a generic message
            if (empty($filtered_products)) {
                echo '<p class="text-center alert alert-warning">No products found matching your criteria.</p>';
            }
            ?>
        <?php else: ?>
            <p class="text-center alert alert-warning">No products found in the database.</p>
        <?php endif; ?>
    </main>

    <div id="buyNowModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeBuyNowModal()" aria-label="Close Buy Now form">&times;</span>
            <h2 id="buyNowProductTitle"></h2>
            <form id="buyNowForm" action="process_purchase.php" method="POST">
                <input type="hidden" id="buyNowProductId" name="productId">
                <input type="hidden" id="buyNowProductPrice" name="productPrice">

                <p>You are about to purchase: <strong id="confirmProductTitle"></strong></p>
                <p>Price: <strong id="confirmProductPrice"></strong></p>
                <p>Availability: <strong id="confirmProductAvailability"></strong></p>

                <div id="outOfStockMessage" class="alert alert-warning" style="display: none;">
                    This item is currently out of stock. You can still proceed to register your interest.
                </div>

                <label for="buyerName">Your Name:</label>
                <input type="text" id="buyerName" name="buyerName" required aria-label="Your Name">

                <label for="buyerEmail">Email Address:</label>
                <input type="email" id="buyerEmail" name="buyerEmail" required aria-label="Email Address">

                <label for="buyerAddress">Shipping Address:</label>
                <textarea id="buyerAddress" name="buyerAddress" rows="3" required aria-label="Shipping Address"></textarea>

                <label for="buyerPhone">Contact Number:</label>
                <input type="tel" id="buyerPhone" name="buyerPhone" required aria-label="Contact Number">

                <button type="submit" id="proceedToCheckoutButton">Proceed to Checkout</button>
            </form>
        </div>
    </div>

    <div id="loginOverlay" class="modal-overlay"></div>
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeLoginModal()" aria-label="Close Login form">&times;</span>
            <h2>Please Log In</h2>
            <p id="loginModalMessage">You need to be logged in to complete your purchase.</p>
            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label for="modalUsername">Username / Email:</label>
                    <input type="text" class="form-control" id="modalUsername" name="username" required aria-label="Username or Email">
                </div>
                <div class="form-group">
                    <label for="modalPassword">Password:</label>
                    <input type="password" class="form-control" id="modalPassword" name="password" required aria-label="Password">
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <p class="mt-3">Don't have an account? <a href="pages/Registration.html">Register here</a></p>
        </div>
    </div>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: false
        });

        // Get elements for the existing Buy Now Modal
        var buyNowModal = document.getElementById("buyNowModal");
        var buyNowCloseButton = buyNowModal.querySelector(".close-button");
        var buyNowProductTitleDisplay = document.getElementById("buyNowProductTitle");
        var confirmProductTitle = document.getElementById("confirmProductTitle");
        var confirmProductPrice = document.getElementById("confirmProductPrice");
        var confirmProductAvailability = document.getElementById("confirmProductAvailability");
        var buyNowProductIdInput = document.getElementById("buyNowProductId");
        var buyNowProductPriceInput = document.getElementById("buyNowProductPrice");
        var outOfStockMessage = document.getElementById("outOfStockMessage");
        var proceedToCheckoutButton = document.getElementById("proceedToCheckoutButton");

        // Get elements for the NEW Login Modal
        var loginModal = document.getElementById("loginModal");
        var loginOverlay = document.getElementById("loginOverlay");
        var loginModalCloseButton = loginModal.querySelector(".close-button");
        var loginModalMessage = document.getElementById("loginModalMessage");


        // Function to open the Buy Now form modal
        function openBuyNowForm(productName, productId, productPrice, isAvailable) {
            buyNowProductTitleDisplay.textContent = "Buy " + productName;
            confirmProductTitle.textContent = productName;
            confirmProductPrice.textContent = "₹" + productPrice.toFixed(2);
            buyNowProductIdInput.value = productId;
            buyNowProductPriceInput.value = productPrice;

            if (isAvailable == 1) {
                confirmProductAvailability.className = "in-stock";
                confirmProductAvailability.textContent = "In Stock";
                outOfStockMessage.style.display = "none";
                proceedToCheckoutButton.disabled = false;
            } else {
                confirmProductAvailability.className = "out-of-stock";
                confirmProductAvailability.textContent = "Out of Stock";
                outOfStockMessage.style.display = "block";
                proceedToCheckoutButton.disabled = true;
            }

            buyNowModal.style.display = "flex"; // Use flex to center with align-items/justify-content
            document.body.style.overflow = "hidden"; // Prevent scrolling
        }

        // Function to close the Buy Now form modal
        function closeBuyNowModal() {
            buyNowModal.style.display = "none";
            document.body.style.overflow = ""; // Restore scrolling
        }

        // Click handler for Buy Now modal close button
        buyNowCloseButton.onclick = function() {
            closeBuyNowModal();
        }

        // Click handler for Buy Now modal overlay
        window.onclick = function(event) {
            if (event.target == buyNowModal) {
                closeBuyNowModal();
            }
        }

        // NEW: Add to Cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartButtons = document.querySelectorAll('.btn-add-to-cart');
            const cartItemCountSpan = document.getElementById('cartItemCount');

            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    const productPrice = this.dataset.productPrice;
                    const productImage = this.dataset.productImage;
                    const quantityInput = this.closest('.product-card').querySelector('.product-quantity');
                    const quantity = parseInt(quantityInput.value);

                    if (quantity < 1) {
                        alert('Please enter a valid quantity (at least 1).');
                        return;
                    }

                    // Send data to add_to_cart.php using AJAX
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}&quantity=${quantity}&product_name=${encodeURIComponent(productName)}&product_price=${productPrice}&product_image=${encodeURIComponent(productImage)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert(data.message);
                            // Update cart count in navbar
                            updateCartCount();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while adding to cart.');
                    });
                });
            });

            // Function to update cart count via AJAX (or from session on page load)
            function updateCartCount() {
                fetch('get_cart_count.php') // Create this new PHP file
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            cartItemCountSpan.textContent = data.count;
                        } else {
                            console.error('Failed to get cart count:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching cart count:', error);
                    });
            }

            // Initial cart count update on page load
            updateCartCount();
        });
    </script>
</body>
</html>