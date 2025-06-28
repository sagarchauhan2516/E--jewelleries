<?php
session_start();
require_once 'db.php'; // Include your database connection file

// Get the database connection using the function from db.php
$conn = getDBConnection();

// --- 1. Authentication Check ---
// Assuming you have a 'users' table with a 'role' column (e.g., 'admin', 'user')
// Ensure admin_login.php sets $_SESSION['user_id'] and $_SESSION['user_role'] correctly
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php'); // Redirect to admin login page
    exit;
}

$message = '';
$error = '';

// --- 2. Handle CRUD Operations (for potential actions on this page, though often done via separate pages) ---
// Note: Your current code seems to handle actions on separate pages (add_product.php, edit_product.php, delete_product.php).
// This block would primarily be for processing any form submissions *on this very page* if you were to add them later.
// For now, it's fine as is, but we'll focus on the 'display' part for admin.php.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        try {
            // These actions are typically handled by separate files (add_product.php, edit_product.php, delete_product.php)
            // But if you re-integrate forms here, this logic would activate.
            if ($action === 'add_product') {
                // This logic belongs in add_product.php
            } elseif ($action === 'edit_product') {
                // This logic belongs in edit_product.php
            } elseif ($action === 'delete_product') {
                // Logic for deleting a product directly from this page (if using a form POST)
                $product_id = intval($_POST['product_id']); // Using product_id as per your table
                $stmt_get_image = $conn->prepare("SELECT image_url FROM products WHERE product_id = :product_id");
                $stmt_get_image->execute([':product_id' => $product_id]);
                $product_to_delete = $stmt_get_image->fetch(PDO::FETCH_ASSOC);

                if ($product_to_delete) {
                    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
                    $stmt->execute([':product_id' => $product_id]);

                    if (!empty($product_to_delete['image_url']) && file_exists($product_to_delete['image_url'])) {
                        unlink($product_to_delete['image_url']);
                    }
                    $message = "Product deleted successfully!";
                } else {
                    $error = "Product not found.";
                }
            }
        } catch (Exception $e) {
            $error = "Operation failed: " . $e->getMessage();
            error_log("Admin operation failed: " . $e->getMessage()); // Log the error for debugging
        }
    }
}

// --- 3. Fetch Products for Display ---
$products = [];
try {
    // Fetch all columns needed for display and potential editing
    $stmt = $conn->prepare("SELECT product_id, image_url, title, description, category, price, weight, material, stock_quantity, is_available FROM products ORDER BY product_id DESC");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error fetching products: " . $e->getMessage();
    error_log("Error fetching products in admin.php: " . $e->getMessage());
}

// Close the connection
$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Panel - E-Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Admin specific styles to make the table look better and manage content */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #FFA500;
            text-align: center;
            margin-bottom: 30px;
        }
        .add-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: background-color 0.3s ease;
        }
        .add-btn:hover {
            background-color: #218838;
        }
        .table-wrapper {
            overflow-x: auto; /* Ensures table is scrollable on smaller screens */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: middle;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .thumb {
            max-width: 80px;
            height: auto;
            border-radius: 4px;
        }
        .actions-column {
            white-space: nowrap; /* Keep buttons on one line */
        }
        .edit-btn, .delete-btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }
        .edit-btn {
            background-color: #007bff;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
        .delete-btn {
            background-color: #dc3545;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .capitalize {
            text-transform: capitalize;
        }
        /* Style for messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        /* Header and Footer styles (copied from E-Jewellery) */
        .navbar-brand {
            font-weight: bold;
            color: #ca9307 !important; /* Adjust if needed */
            font-family: "Lucida Handwriting", cursive; 
        }
        .navbar-brand img {
            margin-right: 5px;
        }
        
        footer {
            background-color: #343a40; /* Dark background */
            color: #f8f9fa; /* Light text */
            padding: 20px 0;
            text-align: center;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

   <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="images/logo.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            <span class="navbar-brand fw-bold" id="navbar-brand">E - Jewellery Shop</span>
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
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): // Assuming 'username' is stored in session after login ?>
                    <li class="nav-item d-flex align-items-center">
                        <span class="navbar-text me-3">
                            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-warning text-dark" href="admin_logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-warning me-2 text-dark" href="register.php">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-warning text-dark" href="login.php">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>


<main class="admin-container">
    <h1>Admin - Manage Jewellery Products</h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <a class="add-btn" href="add_product.php">Add New Product</a>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Weight</th>
                    <th>Material</th>
                    <th>Stock</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image_url']) && file_exists($product['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Product Image" class="thumb" />
                                <?php else: ?>
                                    <span>No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['title']); ?></td>
                            <td class="capitalize"><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>₹<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['weight']); ?> gm</td>
                            <td class="capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $product['material'])); ?></td>
                            <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                            <td><?php echo ($product['is_available'] ? 'Yes' : 'No'); ?></td>
                            <td class="actions-column">
                                <a class="edit-btn" href="edit_product.php?id=<?php echo $product['product_id']; ?>">Edit</a>
                                <form action="admin.php" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($product['title']); ?>?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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
</body>
</html>