<?php
// cart_functions.php

// Ensure session is started if this file is included standalone
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // Your database connection file

/**
 * Adds an item to the cart.
 * If user is logged in, saves to database. Otherwise, saves to session.
 * @param int $productId
 * @param int $quantity
 * @param int|null $userId
 * @return bool True on success, false on failure.
 */
function addToCart($productId, $quantity, $userId = null) {
    $conn = getDBConnection();
    if (!$conn) {
        error_log("Cart: addToCart - Database connection failed.");
        return false;
    }

    // Sanitize and validate inputs
    $productId = filter_var($productId, FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($quantity, FILTER_SANITIZE_NUMBER_INT);

    if ($productId === false || $quantity === false || $productId <= 0 || $quantity <= 0) {
        error_log("Cart: addToCart - Invalid Product ID ($productId) or Quantity ($quantity).");
        return false;
    }

    try {
        // 1. Check if product exists and is available
        $stmt_product = $conn->prepare("SELECT product_id, title, price, is_available FROM products WHERE product_id = :product_id");
        $stmt_product->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $stmt_product->execute();
        $product = $stmt_product->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            error_log("Cart: addToCart - Product ID $productId not found in products table.");
            return false; // Product not found
        }
        if ($product['is_available'] == 0) {
            error_log("Cart: addToCart - Product ID $productId is not available (is_available = 0).");
            return false; // Product not available
        }

        // 2. Add to cart based on user login status
        if ($userId) {
            // Logged-in user: persist to database
            $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cartItem) {
                // Update quantity if item already in cart
                $newQuantity = $cartItem['quantity'] + $quantity;
                $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = :newQuantity WHERE id = :id");
                $updateStmt->bindParam(':newQuantity', $newQuantity, PDO::PARAM_INT);
                $updateStmt->bindParam(':id', $cartItem['id'], PDO::PARAM_INT);
                $result = $updateStmt->execute();
                if (!$result) {
                    error_log("Cart: addToCart - Failed to update cart_item for user $userId, product $productId.");
                }
                return $result;
            } else {
                // Add new item to cart
                $insertStmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $insertStmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $result = $insertStmt->execute();
                if (!$result) {
                    error_log("Cart: addToCart - Failed to insert new cart_item for user $userId, product $productId.");
                }
                return $result;
            }
        } else {
            // Guest user: save to session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            // Temporarily store product details to avoid fetching them again if needed for display
            // You might want to fetch and store title, price, image_path here for session cart
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    // You might want to add other details from the $product array here:
                    'title' => $product['title'],
                    'price' => $product['price']
                    // 'image_path' => $product['image_path'] // if you fetch it
                ];
            }
            return true;
        }
    } catch (PDOException $e) {
        error_log("Cart: addToCart - PDOException: " . $e->getMessage() . " (Product ID: $productId, User ID: " . ($userId ?? 'Guest') . ")");
        return false; // Database error
    }
}

/**
 * Updates the quantity of an item in the cart.
 * @param int $productId
 * @param int $quantity
 * @param int|null $userId
 * @return bool
 */
function updateCartItemQuantity($productId, $quantity, $userId = null) {
    $productId = filter_var($productId, FILTER_SANITIZE_NUMBER_INT);
    $quantity = filter_var($quantity, FILTER_SANITIZE_NUMBER_INT);

    if ($productId === false || $productId <= 0 || $quantity === false || $quantity < 0) {
        error_log("Cart: updateCartItemQuantity - Invalid Product ID ($productId) or Quantity ($quantity).");
        return false;
    }

    try {
        if ($userId) {
            $conn = getDBConnection();
            if (!$conn) {
                error_log("Cart: updateCartItemQuantity - Database connection failed for user $userId.");
                return false;
            }

            if ($quantity === 0) {
                return removeCartItem($productId, $userId); // Remove if quantity is 0
            }

            $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error_log("Cart: updateCartItemQuantity - Failed to update quantity for user $userId, product $productId to $quantity.");
            }
            return $result;
        } else {
            if (!isset($_SESSION['cart'][$productId])) {
                error_log("Cart: updateCartItemQuantity - Product $productId not found in session cart.");
                return false;
            }
            if ($quantity === 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId]['quantity'] = $quantity;
            }
            return true;
        }
    } catch (PDOException $e) {
        error_log("Cart: updateCartItemQuantity - PDOException: " . $e->getMessage() . " (Product ID: $productId, User ID: " . ($userId ?? 'Guest') . ")");
        return false;
    }
}

/**
 * Removes an item from the cart.
 * @param int $productId
 * @param int|null $userId
 * @return bool
 */
function removeCartItem($productId, $userId = null) {
    $productId = filter_var($productId, FILTER_SANITIZE_NUMBER_INT);
    if ($productId === false || $productId <= 0) {
        error_log("Cart: removeCartItem - Invalid Product ID ($productId).");
        return false;
    }

    try {
        if ($userId) {
            $conn = getDBConnection();
            if (!$conn) {
                error_log("Cart: removeCartItem - Database connection failed for user $userId.");
                return false;
            }

            $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $result = $stmt->execute();
            if (!$result) {
                error_log("Cart: removeCartItem - Failed to delete item for user $userId, product $productId.");
            }
            return $result;
        } else {
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                return true;
            }
            error_log("Cart: removeCartItem - Product $productId not found in session cart for guest user.");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Cart: removeCartItem - PDOException: " . $e->getMessage() . " (Product ID: $productId, User ID: " . ($userId ?? 'Guest') . ")");
        return false;
    }
}

/**
 * Gets all items in the cart for display. Fetches full product details.
 * @param int|null $userId
 * @return array An array of cart items with product details.
 */
function getCartItems($userId = null) {
    error_log("getCartItems called for userId: " . ($userId ?? 'Guest'));
    $cart = [];
    $rawCartItems = [];

    try {
        if ($userId) {
            // ... (your existing code for logged-in user) ...
            error_log("getCartItems: Fetched " . count($rawCartItems) . " raw cart items from DB for user " . $userId);
        } else {
            // ... (your existing code for guest user) ...
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                error_log("getCartItems: Session cart contains " . count($_SESSION['cart']) . " unique products.");
                foreach ($_SESSION['cart'] as $item) {
                    $rawCartItems[] = $item;
                }
                error_log("getCartItems: Converted session cart to " . count($rawCartItems) . " raw cart items array.");
            } else {
                 error_log("getCartItems: Session cart is empty or not set.");
            }
        }

        if (empty($rawCartItems)) {
            error_log("getCartItems: No raw cart items found after initial fetch/session check. Returning empty array.");
            return [];
        }

        // Collect all product IDs to fetch details in one query
        $productIds = array_column($rawCartItems, 'product_id');
        error_log("getCartItems: Product IDs to fetch details for: " . implode(', ', $productIds));

        $uniqueProductIds = array_unique($productIds);
        if (empty($uniqueProductIds)) {
             error_log("getCartItems: Unique product IDs array is empty. Returning empty array.");
             return [];
        }


        $placeholders = implode(',', array_fill(0, count($uniqueProductIds), '?'));

        $conn = getDBConnection();
        if (!$conn) {
            error_log("Cart: getCartItems - Database connection failed for fetching product details.");
            return [];
        }

        $stmt_products = $conn->prepare("SELECT product_id, title, description, price, image_path FROM products WHERE product_id IN ($placeholders)");
        foreach ($uniqueProductIds as $k => $id) {
            $stmt_products->bindValue(($k + 1), $id, PDO::PARAM_INT);
        }
        $stmt_products->execute();
        $productsDetails = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
        error_log("getCartItems: Fetched " . count($productsDetails) . " product details from 'products' table.");


        $productsMap = [];
        foreach ($productsDetails as $product) {
            $productsMap[$product['product_id']] = $product;
        }
        error_log("getCartItems: Mapped products. Map count: " . count($productsMap));


        foreach ($rawCartItems as $item) {
            if (isset($productsMap[$item['product_id']])) {
                $product = $productsMap[$item['product_id']];
                $cart[] = [
                    'product_id' => $product['product_id'],
                    'title' => $product['title'],
                    'description' => $product['description'],
                    'price' => $product['price'],
                    'image_path' => $product['image_path'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $product['price'] * $item['quantity']
                ];
            } else {
                error_log("getCartItems: Product ID " . $item['product_id'] . " found in cart_items/session but not in productsDetails.");
            }
        }
        error_log("getCartItems: Final cart array count: " . count($cart));
        return $cart;

    } catch (PDOException $e) {
        error_log("Cart: getCartItems - PDOException: " . $e->getMessage() . " (User ID: " . ($userId ?? 'Guest') . ")");
        return [];
    }
}
/**
 * Calculates the total number of items (not unique products) in the cart.
 * @param int|null $userId
 * @return int
 */
function getCartTotalQuantity($userId = null) {
    try {
        if ($userId) {
            $conn = getDBConnection();
            if (!$conn) {
                error_log("Cart: getCartTotalQuantity - Database connection failed for user $userId.");
                return 0;
            }
            $stmt = $conn->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } else {
            if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $totalQuantity = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $totalQuantity += $item['quantity'];
                }
                return $totalQuantity;
            }
            return 0;
        }
    } catch (PDOException $e) {
        error_log("Cart: getCartTotalQuantity - PDOException: " . $e->getMessage() . " (User ID: " . ($userId ?? 'Guest') . ")");
        return 0;
    }
}

/**
 * Transfers session cart items to database cart for a newly logged-in user.
 * @param int $userId
 */
function transferSessionCartToDb($userId) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return; // Nothing to transfer
    }

    $conn = getDBConnection();
    if (!$conn) {
        error_log("Cart Transfer: transferSessionCartToDb - Database connection failed.");
        return;
    }

    $conn->beginTransaction();
    try {
        foreach ($_SESSION['cart'] as $productId => $item) {
            $existingItemStmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND product_id = :product_id");
            $existingItemStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $existingItemStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $existingItemStmt->execute();
            $existingItem = $existingItemStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Update quantity if item already exists in DB cart
                $newQuantity = $existingItem['quantity'] + $item['quantity'];
                $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity WHERE id = :id");
                $updateStmt->bindParam(':quantity', $newQuantity, PDO::PARAM_INT);
                $updateStmt->bindParam(':id', $existingItem['id'], PDO::PARAM_INT);
                $updateStmt->execute();
            } else {
                // Insert new item into DB cart
                $insertStmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)");
                $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $insertStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                $insertStmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $insertStmt->execute();
            }
        }
        $conn->commit();
        unset($_SESSION['cart']); // Clear session cart after transfer
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Cart: transferSessionCartToDb - PDOException: Cart transfer failed: " . $e->getMessage() . " (User ID: $userId)");
    }
}
?>