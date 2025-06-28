<?php
session_start();
require_once 'db.php';

$conn = getDBConnection();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';
$product = null;

// Fetch product data if ID is provided
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $error = "Product not found.";
        }
    } catch (PDOException $e) {
        $error = "Error fetching product details: " . $e->getMessage();
        error_log("Edit product fetch error: " . $e->getMessage());
    }
} elseif (isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    // Handle form submission for editing
    $product_id = intval($_POST['product_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $weight = floatval($_POST['weight']);
    $material = trim($_POST['material']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    $current_image_url = $_POST['current_image_url']; // Hidden field from form

    $image_url_to_store = $current_image_url;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/Products_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $image_name = uniqid() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $upload_dir . $image_name;
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $image_url_to_store = $target_file;
            // Optionally delete old image if new one is uploaded and it exists
            if (!empty($current_image_url) && file_exists($current_image_url)) {
                unlink($current_image_url);
            }
        } else {
            $error = "Failed to upload new image.";
        }
    }

    if (empty($error)) {
        try {
            $stmt = $conn->prepare("UPDATE products SET image_url = :image_url, title = :title, description = :description, category = :category, price = :price, weight = :weight, material = :material, stock_quantity = :stock_quantity, is_available = :is_available, updated_at = NOW() WHERE product_id = :product_id");
            $stmt->execute([
                ':image_url' => $image_url_to_store,
                ':title' => $title,
                ':description' => $description,
                ':category' => $category,
                ':price' => $price,
                ':weight' => $weight,
                ':material' => $material,
                ':stock_quantity' => $stock_quantity,
                ':is_available' => $is_available,
                ':product_id' => $product_id
            ]);
            $message = "Product updated successfully!";

            // Re-fetch product data to show updated values after submission
            $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Edit product database error: " . $e->getMessage());
        }
    }
} else {
    $error = "No product ID specified for editing.";
}

$conn = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        textarea.form-control {
            resize: vertical;
        }
        .btn-submit {
            background-color: #FFA500;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #e69500;
        }
        .btn-back {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #5a6268;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .current-image {
            max-width: 150px;
            height: auto;
            display: block;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
        }
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
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .checkbox-group input {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="E-Jewellery Shop Logo" width="30" height="30" class="d-inline-block align-text-top">
            </a>
            <a class="navbar-brand fw-bold" href="index.php" id="navbar-brand">E - Jewellery Shop </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Our Collections</a>
                    </li>
                    <li class="nav-item">
            <a class="nav-link" href="pages/try-on.html">Virtual Try-On</a>
          </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Jewellery Size">Size Guide</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/about.html">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pages/contact.html">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="admin.php">Admin Panel</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin_logout.php" class="btn btn-warning me-2">Logout</a>
                    <?php else: ?>
                        <a href="pages/Registration.html" class="btn btn-outline-warning me-2 text-dark">Register</a>
                        <a href="admin_login.php" class="btn btn-warning me-2">Login</a>
                    <?php endif; ?>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-flex align-items-center">
                        <div class="profile-icon ms-2">
                            <a href="profile.php">
                                <img src="images/WhatsApp Image 2025-05-10 at 22.33.47_8dd887ca.jpg" alt="Profile" id="profilePic" class="rounded-circle" width="40" height="40">
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="form-container">
        <h1>Edit Jewellery Product</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($product): ?>
            <form action="edit_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>">

                <div class="form-group">
                    <label for="title">Product Title:</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category:</label>
                    <select id="category" name="category" class="form-control" required>
                        <?php
                        $categories = ['rings', 'necklaces', 'earrings', 'bracelets', 'pendants', 'bangles', 'sets', 'custom'];
                        foreach ($categories as $cat) {
                            $selected = ($product['category'] === $cat) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($cat)."\" $selected>".ucwords(str_replace('_', ' ', $cat))."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price (₹):</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="weight">Weight (grams):</label>
                    <input type="number" id="weight" name="weight" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($product['weight']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="material">Material:</label>
                    <select id="material" name="material" class="form-control" required>
                        <?php
                        $materials = ['gold', 'silver', 'platinum', 'diamond', 'gemstone', 'pearl', 'alloy'];
                        foreach ($materials as $mat) {
                            $selected = ($product['material'] === $mat) ? 'selected' : '';
                            echo "<option value=\"".htmlspecialchars($mat)."\" $selected>".ucwords(str_replace('_', ' ', $mat))."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity:</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_available" name="is_available" value="1" <?php echo ($product['is_available'] == 1) ? 'checked' : ''; ?>>
                    <label for="is_available">Is Available</label>
                </div>

                <div class="form-group">
                    <label for="product_image">Current Image:</label>
                    <?php if (!empty($product['image_url']) && file_exists($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Product Image" class="current-image">
                    <?php else: ?>
                        <p>No current image available.</p>
                    <?php endif; ?>
                    <label for="product_image">Upload New Image (optional):</label>
                    <input type="file" id="product_image" name="product_image" class="form-control" accept="image/*">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Update Product</button>
                    <a href="admin.php" class="btn-back">Back to Products</a>
                </div>
            </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>