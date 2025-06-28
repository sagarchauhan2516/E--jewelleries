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

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);

    try {
        // Get image_url before deleting the record
        $stmt_get_image = $conn->prepare("SELECT image_url FROM products WHERE product_id = :product_id");
        $stmt_get_image->execute([':product_id' => $product_id]);
        $product_to_delete = $stmt_get_image->fetch(PDO::FETCH_ASSOC);

        if ($product_to_delete) {
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $product_id]);

            // Delete the actual image file from the server
            if (!empty($product_to_delete['image_url']) && file_exists($product_to_delete['image_url'])) {
                unlink($product_to_delete['image_url']);
            }
            $message = "Product deleted successfully!";
        } else {
            $error = "Product not found.";
        }
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
        error_log("Delete product error: " . $e->getMessage());
    }
} else {
    $error = "No product ID specified.";
}

$conn = null;

// Redirect back to admin panel with message
if ($message) {
    $_SESSION['admin_message'] = $message;
    $_SESSION['admin_message_type'] = 'success';
} elseif ($error) {
    $_SESSION['admin_message'] = $error;
    $_SESSION['admin_message_type'] = 'danger';
}
header('Location: admin.php');
exit;
?>