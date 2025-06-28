<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection function
require_once 'db.php';

$conn = getDBConnection();

if (!$conn) {
    echo "Database connection failed. Please try again later.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit();
}

$username = htmlspecialchars(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo "❌ Please enter both username and password.";
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // --- NEW: Cart Functionality Integration ---
            // If you have a database-backed cart, you might load it here.
            // For now, let's assume a session-based cart initially.
            // Initialize cart in session if it doesn't exist
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            // If you had guest cart items, you might want to merge them here.
            // Example: If a guest added items, and then logged in.
            // This requires a more complex logic, usually involving client-side JS or
            // checking a temporary guest cart storage before login.
            // For simplicity, we'll assume a fresh or empty cart for new logins
            // unless you explicitly load from DB.

            // Redirect to profile page or a page that will display cart
            header("Location: profile.php"); // Or wherever you want to direct after login
            exit();
        } else {
            echo "❌ Incorrect password.";
        }
    } else {
        echo "❌ User not found.";
    }

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
} catch (Exception $e) {
    echo "An unexpected error occurred: " . $e->getMessage();
}
?>