<?php
// db.php

function getDBConnection() {
    $host = 'zgx02.h.filess.io';
    $dbname = 'ejewelleries_welcomekey';
    $username = 'ejewelleries_welcomekey';
    $password = 'f254bf078179433b71cc921505afeff83183ab09';
    $port = 3306; // <--- This is the crucial addition! Use the actual port from Filess.io

    // Filess.io typically shows the port when you view connection details.
    // In your DBeaver screenshot, it might be visible in the connection properties.
    // If you're unsure, check your Filess.io dashboard for the database connection details.
    // Common ports for cloud MySQL are 3306, or sometimes something like 25060, etc.

    try {
        // Modified PDO DSN to include the port
        $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}


// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
