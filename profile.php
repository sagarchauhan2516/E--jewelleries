<?php
// C:\xampp\htdocs\E-Jewelleries\pages\profile.php

session_start(); // Always start session at the very beginning
require_once 'db.php';     // Correct path to db.php in the parent directory
require_once 'functions.php'; // Correct path to functions.php in the parent directory

// Check if the user is logged in
redirectToLoginIfNotLoggedIn(); // This will redirect if not logged in

// --- Get the logged-in user's ID and Username ---
$loggedInUserId = getUserId();
$loggedInUsername = getUsername(); // Assumes getUsername() is defined in functions.php

if ($loggedInUserId === null) {
    // This should ideally be caught by redirectToLoginIfNotLoggedIn(),
    // but it's a good safeguard or for specific error handling.
    // This line might be redundant if redirectToLoginIfNotLoggedIn() works perfectly.
    echo "User not logged in or session expired.";
    exit();
}

// --- Fetch user-specific data from the database ---
$conn = getDBConnection(); // Get the database connection (from db.php)

$userData = [];
$balance = '0.00';
$walletTransactions = []; // Added for transactions
$orders = [];
$rewards = []; // Renamed from $rewards for clarity, assuming these are user_rewards
$addresses = []; // To store user addresses
$paymentMethods = []; // To store user payment methods

if ($conn) {
    try {
        // Fetch user's personal information AND profile picture path
        $stmt = $conn->prepare("SELECT first_name, last_name, email, phone, city, state, address, zip, profile_picture_path, dob, gender FROM users WHERE id = ?");
        $stmt->execute([$loggedInUserId]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch user's wallet balance
        $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$loggedInUserId]);
        $walletData = $stmt->fetch(PDO::FETCH_ASSOC);
        $balance = $walletData ? $walletData['balance'] : '0.00';

        // Fetch user's wallet transactions
        $stmt = $conn->prepare("SELECT transaction_date, description, amount, type FROM wallet_transactions WHERE user_id = ? ORDER BY transaction_date DESC");
        $stmt->execute([$loggedInUserId]);
        $walletTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Fetch user's rewards (active ones) - joining with rewards table to get details
        $stmt = $conn->prepare("SELECT ur.status, ur.assigned_date, ur.used_date, r.reward_name, r.description, r.expiry_date
                                 FROM user_rewards ur
                                 JOIN rewards r ON ur.reward_id = r.id
                                 WHERE ur.user_id = ?
                                 ORDER BY r.expiry_date ASC");
        $stmt->execute([$loggedInUserId]);
        $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Fetch user's orders (example - adjust table/column names as per your DB)
        // You'll likely need to join with product tables for actual product details
        $stmt = $conn->prepare("SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->execute([$loggedInUserId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // Fetch user's addresses
        $stmt = $conn->prepare("SELECT id, type, full_name, street_address, city, state, postal_code, phone_number, is_default FROM addresses WHERE user_id = ? ORDER BY is_default DESC, type ASC");
        $stmt->execute([$loggedInUserId]);
        $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch user's payment methods
        $stmt = $conn->prepare("SELECT id, card_type, last_four_digits, expiry_month, expiry_year, cardholder_name, is_default FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC, card_type ASC");
        $stmt->execute([$loggedInUserId]);
        $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);


    } catch (PDOException $e) {
        // Handle database errors
        error_log("Profile page database error: " . $e->getMessage()); // Log error for debugging
        echo "Error loading user data. Please try again later.";
        exit();
    }
} else {
    echo "Database connection failed.";
    exit();
}

// Determine the profile picture path to display
$defaultAvatarPath = 'uploads/profile_pictures/default_avatar.png'; // Assuming your default is 'default_avatar.png'
$profilePicSrc = $defaultAvatarPath; // Start with the default

if (!empty($userData['profile_picture_path'])) {
    $uploadedFileName = htmlspecialchars($userData['profile_picture_path']);
    $filePath = 'uploads/profile_pictures/' . $uploadedFileName;

    // Check if the physical file exists
    // __DIR__ is C:\xampp\htdocs\E-Jewelleries\pages
    // So __DIR__ . '/' . $filePath resolves to C:\xampp\htdocs\E-Jewelleries\pages\..\uploads\profile_pictures\filename
    // which correctly points to C:\xampp\htdocs\E-Jewelleries\uploads\profile_pictures\filename
    if (file_exists(__DIR__ . '/' . $filePath)) {
        $profilePicSrc = $filePath;
    }
}

// For DOB and Gender for Personal Info section
$dob = $userData['dob'] ?? '';
$gender = $userData['gender'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Profile | E-Jewellery shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjLqSgpeHfU6ZPMuH/SMy" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/profile.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
  
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.html" id="navbar-brand-text">E-Jewellery Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
               
                <ul class="navbar-nav ms-auto">
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
                        <a class="nav-link" href="cart.php">Cart (<span id="cart-item-count">0</span>)</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Welcome, <?php echo htmlspecialchars($loggedInUsername); ?>!</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-warning mx-2" href="register.php">Register</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-warning" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link profile-icon" href="profile.php">
                            <img src="<?php echo $profilePicSrc; ?>" alt="Profile">
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-3">
        <aside class="sidebar">
            <div class="profile-summary">
                <form id="avatarUploadForm" action="upload_profile_picture.php" method="POST" enctype="multipart/form-data">
                    <label for="avatarUpload">
                        <img id="avatarPreview" src="<?php echo $profilePicSrc; ?>" alt="Avatar" style="cursor:pointer;">
                        <input type="file" id="avatarUpload" name="profile_picture" style="display: none;" accept="image/*">
                    </label>
                    <button type="submit" style="display: none;" id="uploadButton">Upload</button>
                    <p style="margin-top: 10px; font-size: 0.9em; color: #888;">Click image to change.</p>
                </form>
                <?php
                // Display upload success/error messages
                if (isset($_SESSION['upload_success'])) {
                    echo '<div style="background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; padding: 10px; margin-bottom: 20px; border-radius: 5px;">' . htmlspecialchars($_SESSION['upload_success']) . '</div>';
                    unset($_SESSION['upload_success']); // Clear the message after displaying
                }
                if (isset($_SESSION['upload_error'])) {
                    echo '<div style="background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; padding: 10px; margin-bottom: 20px; border-radius: 5px;">' . htmlspecialchars($_SESSION['upload_error']) . '</div>';
                    unset($_SESSION['upload_error']); // Clear the message after displaying
                }
                ?>
                <h2><?php echo htmlspecialchars($userData['first_name'] ?? '') . ' ' . htmlspecialchars($userData['last_name'] ?? ''); ?></h2>
            </div>
            <nav>
                <ul>
                    <li class="active" data-section="wallet">My Wallet</li>
                    <li data-section="rewards">My Rewards</li>
                    <li data-section="orders">My Orders</li>
                    <li data-section="personal">Personal Information</li>
                    <li data-section="addresses">Addresses</li>
                    <li data-section="payment-methods">Payment Methods</li>
                    <li data-section="faqs">FAQs</li>
                    <li data-section="signout">Sign Out</li>
                </ul>
            </nav>
        </aside>

        <main>
            <section id="wallet" class="tab-content active">
                <h1>My Wallet</h1>
                <div style="background:#fff; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.05);">
                    <h2>Current Balance</h2>
                    <p style="font-size: 24px; font-weight: bold; color: #d4a017;">₹ <?php echo number_format($balance, 2); ?></p>

                    <div style="margin: 20px 0;">
                        <button style="padding:10px 20px; background-color:#d4a017; color:#fff; border:none; border-radius:5px; margin-right:10px;">Add Money</button>
                        <button style="padding:10px 20px; background-color:#555; color:#fff; border:none; border-radius:5px;">Withdraw</button>
                    </div>

                    <h3>Recent Transactions</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <thead>
                            <tr style="background-color: #f1f1f1;">
                                <th style="padding: 10px; text-align: left;">Date</th>
                                <th style="padding: 10px; text-align: left;">Description</th>
                                <th style="padding: 10px; text-align: right;">Amount</th>
                                <th style="padding: 10px; text-align: left;">Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($walletTransactions)): ?>
                                <?php foreach ($walletTransactions as $transaction): ?>
                                    <tr>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars(date('d M Y', strtotime($transaction['transaction_date']))); ?></td>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars($transaction['description'] ?? 'N/A'); ?></td>
                                        <td style="padding: 10px; text-align: right;">
                                            <?php
                                            $sign = ($transaction['type'] == 'credit') ? '+' : '-';
                                            echo $sign . ' ₹' . number_format($transaction['amount'], 2);
                                            ?>
                                        </td>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars(ucfirst($transaction['type'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="padding: 10px; text-align: center;">No recent transactions.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="rewards" class="tab-content">
                <h1>My Rewards</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">

                    <h2>Available Rewards</h2>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
                        <?php
                        $hasActiveRewards = false;
                        foreach ($rewards as $reward) {
                            if ($reward['status'] == 'active' && (empty($reward['expiry_date']) || strtotime($reward['expiry_date']) > time())) {
                                $hasActiveRewards = true;
                                ?>
                                <div style="flex: 1 1 250px; padding: 15px; border: 1px solid #eee; border-radius: 8px; background-color: #fef6e0;">
                                    <h3 style="margin: 0;"><?php echo htmlspecialchars($reward['reward_name'] ?? 'N/A'); ?></h3>
                                    <p style="margin: 5px 0;"><?php echo htmlspecialchars($reward['description'] ?? ''); ?></p>
                                    <small>Expires: <?php echo htmlspecialchars($reward['expiry_date'] ? date('d M Y', strtotime($reward['expiry_date'])) : 'N/A'); ?></small>
                                    <button style="margin-top: 10px; padding: 6px 12px; background-color: #d4a017; color: white; border: none; border-radius: 4px;">Apply Now</button>
                                </div>
                                <?php
                            }
                        }
                        if (!$hasActiveRewards): ?>
                            <p>No available rewards at the moment.</p>
                        <?php endif; ?>
                    </div>

                    <h3>Reward History</h3>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                        <thead>
                            <tr style="background-color: #f1f1f1;">
                                <th style="padding: 10px; text-align: left;">Date Assigned</th>
                                <th style="padding: 10px; text-align: left;">Reward</th>
                                <th style="padding: 10px; text-align: left;">Status</th>
                                <th style="padding: 10px; text-align: left;">Used Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rewards)): ?>
                                <?php foreach ($rewards as $reward): ?>
                                    <tr>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars(date('d M Y', strtotime($reward['assigned_date']))); ?></td>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars($reward['reward_name'] ?? 'N/A'); ?></td>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars(ucfirst($reward['status'])); ?></td>
                                        <td style="padding: 10px;"><?php echo htmlspecialchars($reward['used_date'] ? date('d M Y', strtotime($reward['used_date'])) : 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="padding: 10px; text-align: center;">No reward history.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>


            <section id="orders" class="tab-content">
                <h1>My Orders</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h3 style="margin: 0;">Order #<?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></h3>
                                            <small>Placed on: <?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></small>
                                        </div>
                                        <?php
                                            $status_class = '';
                                            switch ($order['status']) {
                                                case 'Delivered':
                                                    $status_class = 'background-color: #dff0d8; color: #3c763d;'; // green
                                                    break;
                                                case 'Pending':
                                                case 'Processing':
                                                case 'Shipped':
                                                    $status_class = 'background-color: #fcf8e3; color: #8a6d3b;'; // yellow/orange
                                                    break;
                                                case 'Cancelled':
                                                    $status_class = 'background-color: #f2dede; color: #a94442;'; // red
                                                    break;
                                                default:
                                                    $status_class = 'background-color: #f0f0f0; color: #555;'; // grey
                                            }
                                        ?>
                                        <span style="padding: 5px 10px; border-radius: 4px; <?php echo $status_class; ?>"><?php echo htmlspecialchars($order['status'] ?? 'N/A'); ?></span>
                                    </div>
                                    <hr style="margin: 10px 0;">
                                    <?php
                                    // Fetch order items for this specific order
                                    $orderItems = [];
                                    try {
                                        $stmtItems = $conn->prepare("SELECT oi.quantity, oi.price, p.product_name, p.image_path
                                                                     FROM order_items oi
                                                                     JOIN products p ON oi.product_id = p.id
                                                                     WHERE oi.order_id = ?");
                                        $stmtItems->execute([$order['id']]);
                                        $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
                                    } catch (PDOException $e) {
                                        error_log("Error fetching order items: " . $e->getMessage());
                                        // Handle gracefully
                                    }
                                    ?>
                                    <?php if (!empty($orderItems)): ?>
                                        <?php foreach ($orderItems as $item): ?>
                                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 5px;">
                                                <?php
                                                // Construct image path for product
                                                $productImageSrc = '../images/default_product.jpg'; // Default product image. Corrected path.
                                                if (!empty($item['image_path'])) {
                                                    $productUploadedPath = '../uploads/products/' . htmlspecialchars($item['image_path']); // Corrected path.
                                                    // Assuming product images are in E-Jewelleries/uploads/products/
                                                    // The current file (profile.php) is in C:\xampp\htdocs\E-Jewelleries\pages
                                                    // So, to reach 'uploads', we need to go up one directory (../)
                                                    if (file_exists(__DIR__ . '/' . $productUploadedPath)) { // This check should be relative to profile.php
                                                        $productImageSrc = $productUploadedPath;
                                                    }
                                                }
                                                ?>
                                                <img src="<?php echo $productImageSrc; ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></strong>
                                                    <p style="margin: 5px 0;">Qty: <?php echo htmlspecialchars($item['quantity']); ?> &nbsp;|&nbsp; ₹<?php echo number_format($item['price'], 2); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No items found for this order.</p>
                                    <?php endif; ?>
                                    <p style="text-align: right; margin-top: 10px;">Total: <strong>₹<?php echo number_format($order['total_amount'] ?? 0, 2); ?></strong></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>You have not placed any orders yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>


            <section id="personal" class="tab-content">
                <h1>Personal Information</h1>
                <p class="subtext">Manage your personal details and preferences.</p>
                <form id="profileForm" method="POST" action="update_profile.php">
                    <div class="input-group">
                        <input type="text" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($userData['first_name'] ?? ''); ?>" required>
                        <input type="text" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($userData['last_name'] ?? ''); ?>" required>
                    </div>
                    <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                    <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                    <input type="date" name="dob" placeholder="Date of Birth" value="<?php echo htmlspecialchars($dob); ?>">
                    <div class="gender-toggle">
                        <button type="button" class="gender-btn <?php echo ($gender == 'Male') ? 'active' : ''; ?>" onclick="selectGender('Male')">Male</button>
                        <button type="button" class="gender-btn <?php echo ($gender == 'Female') ? 'active' : ''; ?>" onclick="selectGender('Female')">Female</button>
                        <input type="hidden" name="gender" id="genderInput" value="<?php echo htmlspecialchars($gender); ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">Save</button>
                        <button type="reset" class="cancel-btn">Cancel</button>
                    </div>
                </form>
            </section>

            <section id="addresses" class="tab-content">
                <h1>My Addresses</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (!empty($addresses)): ?>
                            <?php foreach ($addresses as $address): ?>
                                <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px;">
                                    <strong><?php echo htmlspecialchars($address['type'] ?? 'Address'); ?> <?php echo ($address['is_default'] ? ' (Default)' : ''); ?></strong>
                                    <p style="margin: 8px 0;">
                                        <?php echo htmlspecialchars($address['full_name'] ?? '') . '<br>'; ?>
                                        <?php echo htmlspecialchars($address['street_address'] ?? '') . '<br>'; ?>
                                        <?php echo htmlspecialchars($address['city'] ?? '') . ', ' . htmlspecialchars($address['state'] ?? '') . ', ' . htmlspecialchars($address['postal_code'] ?? ''); ?>
                                    </p>
                                    <p>Phone: <?php echo htmlspecialchars($address['phone_number'] ?? 'N/A'); ?></p>
                                    <button style="padding: 5px 10px; background-color: #d4a017; color: #fff; border: none; border-radius: 4px;">Edit</button>
                                    <button style="padding: 5px 10px; background-color: #ccc; border: none; border-radius: 4px; margin-left: 5px;">Delete</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No addresses saved yet.</p>
                        <?php endif; ?>
                    </div>

                    <hr style="margin: 30px 0;">
                    <h3>Add New Address</h3>
                    <form id="addAddressForm" style="display: flex; flex-direction: column; gap: 10px; max-width: 500px;" method="POST" action="add_address.php">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                        <input type="text" name="street_address" placeholder="Street Address" required>
                        <input type="text" name="city" placeholder="City" required>
                        <input type="text" name="state" placeholder="State" required>
                        <input type="text" name="postal_code" placeholder="Postal Code" required>
                        <input type="tel" name="phone_number" placeholder="Phone Number" required>
                        <select name="address_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="shipping">Shipping</option>
                            <option value="billing">Billing</option>
                            <option value="other">Other</option>
                        </select>
                        <label style="display: flex; align-items: center; gap: 5px; margin-top: 5px;">
                            <input type="checkbox" name="is_default" value="1"> Set as default
                        </label>
                        <div>
                            <button type="submit" style="padding: 8px 16px; background-color: #d4a017; color: white; border: none; border-radius: 5px;">Save Address</button>
                        </div>
                    </form>
                </div>
            </section>


            <section id="payment-methods" class="tab tab-content">
                <h1>Payment Methods</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (!empty($paymentMethods)): ?>
                            <?php foreach ($paymentMethods as $method): ?>
                                <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px;">
                                    <strong><?php echo htmlspecialchars($method['card_type'] ?? 'Card'); ?> Ending in <?php echo htmlspecialchars($method['last_four_digits'] ?? '****'); ?> <?php echo ($method['is_default'] ? ' (Default)' : ''); ?></strong>
                                    <p>Expiry: <?php echo htmlspecialchars(str_pad($method['expiry_month'] ?? '', 2, '0', STR_PAD_LEFT) . '/' . substr($method['expiry_year'] ?? '', -2)); ?></p>
                                    <button style="padding: 5px 10px; background-color: #d4a017; color: #fff; border: none; border-radius: 4px;">Edit</button>
                                    <button style="padding: 5px 10px; background-color: #ccc; border: none; border-radius: 4px; margin-left: 5px;">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No payment methods saved yet.</p>
                        <?php endif; ?>
                    </div>

                    <hr style="margin: 30px 0;">
                    <h3>Add New Card</h3>
                    <form method="POST" action="add_payment_method.php">
                        <input type="text" name="cardholder_name" placeholder="Cardholder Name" required>
                        <input type="text" name="card_number" placeholder="Card Number" required>
                        <div style="display: flex; gap: 10px;">
                            <input type="text" name="expiry_month" placeholder="MM" maxlength="2" required style="width: 50%;">
                            <input type="text" name="expiry_year" placeholder="YY" maxlength="2" required style="width: 50%;">
                            <input type="text" name="cvv" placeholder="CVV" maxlength="4" required>
                        </div>
                        <select name="card_type" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 10px;">
                            <option value="Visa">Visa</option>
                            <option value="Mastercard">Mastercard</option>
                            <option value="Amex">American Express</option>
                            <option value="Discover">Discover</option>
                            <option value="Other">Other</option>
                        </select>
                           <label style="display: flex; align-items: center; gap: 5px; margin-top: 5px;">
                               <input type="checkbox" name="is_default" value="1"> Set as default
                           </label>
                        <button type="submit" style="padding: 8px 16px; background-color: #d4a017; color: white; border: none; border-radius: 5px; margin-top: 10px;">Save Card</button>
                    </form>
                </div>
            </section>


            <section id="faqs" class="tab-content">
                <h1>Frequently Asked Questions (FAQs)</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                    <h2>General Questions</h2>

                    <div style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">What is E-Jewellery?</h3>
                        <p>E-Jewellery is an online platform where you can explore and purchase high-quality jewellery for all occasions.</p>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">How can I track my order?</h3>
                        <p>Once your order has shipped, you will receive a tracking number to monitor the status of your delivery.</p>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">How do I add a payment method?</h3>
                        <p>You can add or update your payment method in the "Payment Methods" section of your profile.</p>
                    </div>

                    <h2>Shipping & Returns</h2>

                    <div style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">What are the shipping options?</h3>
                        <p>We offer standard and expedited shipping options. You can select your preferred shipping method during checkout.</p>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">How do I return an item?</h3>
                        <p>To return an item</p>
                </div>
            </section>

            <section id="signout" class="tab-content">
                <h1>Sign Out</h1>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                    <p>Are you sure you want to sign out?</p>
                    <a href="logout.php" style="display: inline-block; padding: 10px 20px; background-color: #d4a017; color: white; text-decoration: none; border-radius: 5px;">Yes, Sign Out</a>
                </div>
            </section>
        </main>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcqNpVEjE9S/6o" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../assets/js/main.js"></script> <script>
        // Profile specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar nav ul li');
            const tabContents = document.querySelectorAll('.tab-content');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all links and contents
                    sidebarLinks.forEach(item => item.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to clicked link
                    this.classList.add('active');

                    // Show corresponding tab content
                    const targetSectionId = this.dataset.section;
                    document.getElementById(targetSectionId).classList.add('active');
                });
            });

            // Initial display: show the first section (wallet)
            if (sidebarLinks.length > 0) {
                sidebarLinks[0].classList.add('active');
                tabContents[0].classList.add('active');
            }

            // Handle profile picture upload preview
            const avatarUpload = document.getElementById('avatarUpload');
            const avatarPreview = document.getElementById('avatarPreview');
            const uploadButton = document.getElementById('uploadButton');

            if (avatarUpload && avatarPreview) {
                avatarUpload.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            avatarPreview.src = e.target.result;
                            uploadButton.style.display = 'block'; // Show upload button when a file is selected
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }

            // Gender selection for Personal Information
            window.selectGender = function(gender) {
                document.querySelectorAll('.gender-btn').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');
                document.getElementById('genderInput').value = gender;
            };

            // Dynamic Cart Count Update
            function updateCartCount() {
                $.ajax({
                    url: '../api/get_cart_count.php', // Adjust path if necessary
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#cart-item-count').text(response.count);
                        } else {
                            console.error('Failed to get cart count:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                    }
                });
            }

            // Call it on page load
            updateCartCount();

            // Optionally, refresh cart count periodically or after actions that change cart
            // setInterval(updateCartCount, 30000); // Update every 30 seconds
        });
    </script>
</body>
</html>