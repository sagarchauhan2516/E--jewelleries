<?php
// C:\xampp\htdocs\E-Jewelleries\logout.php

session_start(); // Start the session to access session variables

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page or home page after logout
header("Location: pages/login.html"); // Or header("Location: index.php"); if you want to go to the home page
exit();
?>