<?php
session_start();
require_once 'db.php'; // Include your database connection file

$conn = getDBConnection();

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        try {
            // Corrected table name to 'users' and password column to 'password_hash'
            // based on your provided database schema images
            $stmt = $conn->prepare("SELECT id, username, password, role FROM admin_users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if user exists and password verifies
            if ($user && password_verify($password, $user['password'])) { // Use password_hash from DB
                // User authenticated
                if ($user['role'] === 'admin') { // Check if the role is 'admin'
                    // Set session variables with consistent names for admin.php
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];

                    header('Location: admin.php'); // Redirect to admin panel
                    exit;
                } else {
                    $login_error = "Access denied: You do not have admin privileges.";
                }
            } else {
                $login_error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $login_error = "Database error during login. Please try again later.";
            error_log("Admin login database error: " . $e->getMessage());
        }
    }
}

$conn = null; // Close connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - E-Jewellery Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
    
    <style>
        body {
            display: flex;
            flex-direction: column; /* Changed to column to stack navbar and content */
            justify-content: flex-start; /* Align items to the start of the cross axis */
            align-items: center;
            min-height: 100vh;
            background-color: #f4f4f4;
            margin: 0; /* Remove default body margin */
        }
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            margin-top: 50px; /* Add some space below the navbar */
        }
        .login-container h1 {
            margin-bottom: 30px;
            color: #FFA500;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .btn-login {
            background-color: #FFA500;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }
        .btn-login:hover {
            background-color: #e69500;
        }
        .error-message {
            color: #dc3545;
            margin-top: 15px;
        }
        /* Styles for the profile icon in the navbar */
        .profile-icon img {
            width: 40px; /* Adjust size as needed */
            height: 40px; /* Adjust size as needed */
            border-radius: 50%; /* Makes the image circular */
            object-fit: cover;
            border: 2px solid #FFA500; /* Optional: adds a border */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light w-100">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="images/logo.png" alt="" width="30" height="30" class="d-inline-block align-text-top">
                <span class="navbar-brand fw-bold" id="navbar-brand">E - Jewellery Shop </span>
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
                
               
            </div>
        </div>
    </nav>

    <div class="login-container">
        <h1>Admin Login</h1>
        <?php if ($login_error): ?>
            <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form action="admin_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>