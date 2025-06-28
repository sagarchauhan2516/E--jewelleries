<?php
session_start();
require_once 'db.php'; // Include your database connection file

$conn = getDBConnection();
$search_query = '';
$search_results = [];
$error_message = '';

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $search_query = trim($_GET['query']);

    try {
        // Prepare a SQL statement to search for products
        // This example searches in product name and description
        // You'll need to adapt 'products' table and column names to your actual schema
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_name LIKE :query OR description LIKE :query");
        $stmt->bindValue(':query', '%' . $search_query . '%', PDO::PARAM_STR);
        $stmt->execute();
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "Database error during search. Please try again later.";
        error_log("Search database error: " . $e->getMessage()); // Log the actual error
    }
} else {
    // If no query is provided, you might want to show all products or a message
    $error_message = "Please enter a search term.";
}

$conn = null; // Close connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - E-Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/index.css"> <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .search-results-section {
            padding: 50px 0;
            min-height: 60vh;
        }
        .search-results-section h2 {
            margin-bottom: 30px;
            color: #FFA500;
        }
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s ease-in-out;
            height: 100%; /* Ensure uniform height */
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            max-width: 100%;
            height: 200px; /* Fixed height for consistency */
            object-fit: contain; /* Ensures entire image is visible */
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .product-card h5 {
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .product-card p {
            color: #666;
            font-size: 0.95em;
        }
        .no-results {
            text-align: center;
            font-size: 1.2em;
            color: #888;
            margin-top: 50px;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-top: 20px;
            font-size: 1.1em;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
            </a>
            <a class="navbar-brand fw-bold" href="index.php" id="navbar-brand">E - Jewellery Shop </a>

            <form class="d-flex" action="search.php" method="GET">
                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search" name="query" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-outline-warning" type="submit">Search</button>
            </form>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
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
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="admin_logout.php" class="btn btn-warning me-2">Logout</a>
                    <?php else: ?>
                        <a href="pages/Registration.html" class="btn btn-outline-warning me-2 text-dark">Register</a>
                        <a href="admin_login.php" class="btn btn-warning me-2">Login</a>
                    <?php endif; ?>
                </div>
                <?php
                // Define a default profile picture path
                $profilePicSrc = 'images/default_avatar.jpg'; // Ensure this image exists!
                // Check if a user is logged in and their profile picture path is available in the session
                if (isset($_SESSION['user_id']) && isset($_SESSION['profile_picture_path'])) {
                    if (!empty($_SESSION['profile_picture_path'])) {
                        $profilePicSrc = $_SESSION['profile_picture_path'];
                    }
                }
                ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-flex align-items-center">
                        <div class="profile-icon ms-2">
                            <a href="profile.php">
                                <img src="<?php echo htmlspecialchars($profilePicSrc); ?>" alt="Profile" id="profilePic" class="rounded-circle" width="40" height="40">
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="ticker-container">
        <div class="ticker" id="priceTicker">Loading gold and silver prices...</div>
    </div>

    <section class="search-results-section container my-5">
        <h2 class="text-center">Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>

        <?php if ($error_message): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (empty($search_results)): ?>
            <p class="no-results">No products found matching your search.</p>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($search_results as $product): ?>
                    <div class="col">
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image_path'] ?? 'images/placeholder.jpg'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <h5><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></p>
                            <p class="fw-bold text-success">₹<?php echo number_format($product['price'], 2); ?></p>
                            <a href="product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-warning">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

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
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
    <script src="assets/js/main.js"></script>

</body>
</html>