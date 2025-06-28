<?php
// C:\xampp\htdocs\E-Jewelleries\functions.php

// IMPORTANT: Ensure session is started only once.
// This block ensures session_start() is called if a session hasn't already been started.
// This is a good safeguard. The primary session_start() should still be at the very top of your main entry scripts (like profile.php).
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANT: Include db.php here as getUsername() and other functions might need it.
// This assumes db.php is in the same directory (root: C:\xampp\htdocs\E-Jewelleries) as functions.php
require_once __DIR__ . '/db.php'; // Use __DIR__ for robust path resolution


// Function to check if a user is logged in
function isLoggedIn(): bool {
    // Check if 'user_id' is set AND is not empty (e.g., 0 or null)
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to get the logged-in user's ID
function getUserId(): ?int { // Return type hint for clarity
    return $_SESSION['user_id'] ?? null;
}

// Function to get the logged-in user's username
function getUsername(): ?string { // Return type hint for clarity
    // Prefer to get username from session first to avoid repeated DB queries
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }

    // If not in session, try to fetch from DB using user_id
    $user_id = getUserId();
    if ($user_id !== null) { // Use strict comparison
        $conn = getDBConnection(); // Assuming getDBConnection() is defined in db.php
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?"); // Adjust 'username' if your column name is different
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    // Store in session for future requests within the same session
                    $_SESSION['username'] = $user['username'];
                    return $user['username'];
                }
            } catch (PDOException $e) {
                // Log the error for debugging purposes (e.g., to your server's error log)
                error_log("Error fetching username in getUsername(): " . $e->getMessage());
                // In a production environment, you might not want to expose this error to the user
            }
        } else {
            // Log if DB connection failed, though getDBConnection should handle this
            error_log("Database connection failed in getUsername().");
        }
    }
    return null; // Return null if username cannot be determined
}

// Function to redirect to login page if user is not logged in
function redirectToLoginIfNotLoggedIn(): void { // Return type hint for clarity
    if (!isLoggedIn()) {
        // Determine the correct path for redirection.
        // If profile.php is in /pages/ and login.php (or login.html) is also in /pages/,
        // then the redirect path should be relative to the current URL.
        // Example: If current URL is http://localhost/E-Jewelleries/pages/profile.php
        // and target is http://localhost/E-Jewelleries/pages/login.php
        // then 'login.php' is the correct relative path.

        // It's generally best practice to redirect to the PHP login script
        // if it handles the form submission, even if it might show an HTML form.
        // Assuming login.php is the target for login actions.
        header('Location: pages/login.html'); // Corrected path (relative to the calling script in the 'pages' directory)

        // If you specifically want to redirect to login.html (a static form page):
        // header('Location: login.html');

        exit(); // CRITICAL: Stop script execution after redirect
    }
}

// Example of a basic input sanitization function
function sanitizeInput(string $data): string {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); // Ensure proper HTML escaping and character set
    return $data;
}

// You can add more general utility functions here as needed
// e.g., for form validation, message handling, etc.

?>