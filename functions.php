<?php
// C:\xampp\htdocs\E-Jewelleries\functions.php

// IMPORTANT: Ensure session is started only once.
// This block ensures session_start() is called if a session hasn't already been started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// IMPORTANT: Include db.php here if getUsername() needs it and is called before profile.php includes it.
// This assumes db.php is in the same directory (root) as functions.php
require_once 'db.php';


// Function to check if a user is logged in
function isLoggedIn() {
    // Check if 'user_id' is set AND is not empty (e.g., 0 or null)
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to get the logged-in user's ID
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get the logged-in user's username
function getUsername() {
    // Prefer to get username from session first to avoid repeated DB queries
    if (isset($_SESSION['username'])) {
        return $_SESSION['username'];
    }

    // If not in session, try to fetch from DB using user_id
    $user_id = getUserId();
    if ($user_id) {
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
                error_log("Error fetching username in getUsername(): " . $e->getMessage());
            }
        }
    }
    return null; // Return null if username cannot be determined
}

// Function to redirect to login page if user is not logged in
function redirectToLoginIfNotLoggedIn() {
    if (!isLoggedIn()) {
        // Determine the correct path to login.html (or login.php)
        // Given your previous setup, profile.php is in 'pages/'
        // and login.html is also in 'pages/'.
        // So, from a script in 'pages/' (like profile.php), 'login.html' is correct.
        // If the script calling this function is in the root, it would be 'pages/login.html'.

        // To make it robust regardless of where the calling script is:
        // Option 1 (Recommended for scripts included by 'pages/profile.php'):
        header('Location: pages/login.html'); // Assuming login.html is in the same directory as profile.php (pages/)
        // If you process login via login.php:
        // header('Location: login.php'); // Assuming login.php is in the same directory (pages/)
        // Note: Your file structure image shows login.html AND login.php. Be consistent.
        // If login.php processes login and then redirects, and login.html is the form, then redirect to login.html.

        // Option 2 (More robust, absolute path from web root):
        // You'd need to define a base URL, e.g., in a config file:
        // define('BASE_URL', 'http://localhost/E-Jewelleries/');
        // header('Location: ' . BASE_URL . 'pages/login.html');
        // This requires BASE_URL to be defined and correctly configured.

        // Sticking with Option 1 for now, assuming profile.php in pages/ and login.html in pages/
        // If login.php is the actual login form, use that instead of login.html

        exit(); // CRITICAL: Stop script execution after redirect
    }
}

// You might also have other functions here, like:
/*
function sanitizeInput($data) {
    // Basic sanitization example
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
*/