<?php
// db.php

function getDBConnection() {
    $host = 'localhost';
    $dbname = 'e-jewelleries';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
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