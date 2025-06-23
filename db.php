<?php
// db.php

function getDBConnection() {
    $host = 'zgx02.h.filess.io';
    $dbname = 'ejewelleries_welcomekey';
    $username = 'ejewelleries_welcomekey';
    $password = 'f254bf078179433b71cc921505afeff83183ab09';

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
