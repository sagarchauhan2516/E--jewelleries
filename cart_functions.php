<?php
// cart_functions.php - Adapted for PDO

// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include your PDO database connection function
require_once 'db.php'; // This file should contain getDBConnection()

/**
 * Helper function to get the PDO connection.
 * We'll call this inside each function to ensure we always have a valid connection.
 * This also means we don't need 'global $conn;' everywhere.
 */
function get_pdo_connection() {
    return getDBConnection(); // Calls the function from db.php
}

/**
 * Adds a product to the user's cart in the database, or updates quantity if already exists.
 *
 * @param int $user_id The ID of the logged-in user.
 * @param int $product_id The ID of the product to add.
 * @param int $quantity The quantity to add.
 * @return bool True on success, false on failure.
 */
function addToCart($user_id, $product_id, $quantity) {
    $conn = get_pdo_connection();

    if (!$user_id || !$product_id || $quantity <= 0) {
        return false; // Invalid input
    }

    try {
        // Check if the item already exists in the cart for this user
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Item exists, update quantity
            $new_quantity = $row['quantity'] + $quantity;
            $update_stmt = $conn->prepare("UPDATE cart SET quantity = :new_quantity WHERE user_id = :user_id AND product_id = :product_id");
            $update_stmt->bindParam(':new_quantity', $new_quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            return $update_stmt->execute();
        } else {
            // Item does not exist, insert new row
            $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
            $insert_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            return $insert_stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("PDO Error in addToCart: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the quantity of a product in the user's cart in the database.
 * If new_quantity is 0, the item is removed.
 *
 * @param int $user_id The ID of the logged-in user.
 * @param int $product_id The ID of the product to update.
 * @param int $new_quantity The new quantity.
 * @return bool True on success, false on failure.
 */
function updateCartQuantity($user_id, $product_id, $new_quantity) {
    $conn = get_pdo_connection();

    if (!$user_id || !$product_id || $new_quantity < 0) {
        return false; // Invalid input
    }

    try {
        if ($new_quantity == 0) {
            // If quantity is 0, remove the item
            return removeCartItem($user_id, $product_id);
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity = :new_quantity WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':new_quantity', $new_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            return $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("PDO Error in updateCartQuantity: " . $e->getMessage());
        return false;
    }
}

/**
 * Removes a product from the user's cart in the database.
 *
 * @param int $user_id The ID of the logged-in user.
 * @param int $product_id The ID of the product to remove.
 * @return bool True on success, false on failure.
 */
function removeCartItem($user_id, $product_id) {
    $conn = get_pdo_connection();

    if (!$user_id || !$product_id) {
        return false; // Invalid input
    }

    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("PDO Error in removeCartItem: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieves all items in a user's cart with product details and calculated subtotal.
 *
 * @param int $user_id The ID of the logged-in user.
 * @return array An array of cart items, each with product details and subtotal.
 */
function getCartItems($user_id) {
    $conn = get_pdo_connection();
    $cartItems = [];

    if (!$user_id) {
        return $cartItems; // Return empty if no user ID
    }

    try {
        // Join cart table with products table to get product details
        $stmt = $conn->prepare("
            SELECT
                c.product_id,
                c.quantity,
                p.title,
                p.price,
                p.image_path
            FROM
                cart c
            JOIN
                products p ON c.product_id = p.product_id
            WHERE
                c.user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['subtotal'] = $row['quantity'] * $row['price'];
            $cartItems[] = $row;
        }

        return $cartItems;
    } catch (PDOException $e) {
        error_log("PDO Error in getCartItems: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets the total quantity of all items in a user's cart.
 *
 * @param int $user_id The ID of the logged-in user.
 * @return int The total quantity.
 */
function getCartTotalQuantity($user_id) {
    $conn = get_pdo_connection();
    $totalQuantity = 0;

    if (!$user_id) {
        return $totalQuantity;
    }

    try {
        $stmt = $conn->prepare("SELECT SUM(quantity) AS total_qty FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['total_qty'] !== null) {
            $totalQuantity = (int)$row['total_qty'];
        }

        return $totalQuantity;
    } catch (PDOException $e) {
        error_log("PDO Error in getCartTotalQuantity: " . $e->getMessage());
        return 0;
    }
}

/**
 * Clears all items from a user's cart.
 *
 * @param int $user_id The ID of the logged-in user.
 * @return bool True on success, false on failure.
 */
function clearUserCart($user_id) {
    $conn = get_pdo_connection();

    if (!$user_id) {
        return false;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("PDO Error in clearUserCart: " . $e->getMessage());
        return false;
    }
}

?>