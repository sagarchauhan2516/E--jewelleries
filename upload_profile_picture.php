<?php
// C:\xampp\htdocs\E-Jewelleries\upload_profile_picture.php

session_start();

require_once 'db.php';
require_once 'functions.php';

redirectToLoginIfNotLoggedIn();

$loggedInUserId = getUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $targetDir = "uploads/profile_pictures/"; // Make sure this directory exists and is writable!
    $fileName = basename($_FILES["profile_picture"]["name"]);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Generate a unique file name to prevent overwriting and security issues
    $uniqueFileName = uniqid() . '.' . $fileType;
    $targetFilePath = $targetDir . $uniqueFileName;

    $uploadOk = 1;

    // 1. Check if image file is an actual image
    $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
    if ($check !== false) {
        // file is an image
    } else {
        $_SESSION['upload_error'] = "File is not an image.";
        $uploadOk = 0;
    }

    // 2. Check file size (e.g., max 2MB)
    if ($_FILES["profile_picture"]["size"] > 2000000) { // 2MB in bytes
        $_SESSION['upload_error'] = "Sorry, your file is too large (max 2MB).";
        $uploadOk = 0;
    }

    // 3. Allow certain file formats
    $allowedTypes = array('jpg', 'png', 'jpeg', 'gif');
    if (!in_array($fileType, $allowedTypes)) {
        $_SESSION['upload_error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // 4. Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        // Error occurred, redirect back to profile page
        header("Location: profile.php");
        exit();
    } else {
        // If everything is ok, try to upload file
        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            // File uploaded successfully, now update database
            $conn = getDBConnection();
            if ($conn) {
                try {
                    $stmt = $conn->prepare("UPDATE users SET profile_picture_path = ? WHERE id = ?");
                    $stmt->execute([$uniqueFileName, $loggedInUserId]); // Store just the unique file name

                    $_SESSION['upload_success'] = "The file " . htmlspecialchars($fileName) . " has been uploaded.";
                } catch (PDOException $e) {
                    $_SESSION['upload_error'] = "Database update failed: " . $e->getMessage();
                    error_log("Profile picture DB update error: " . $e->getMessage());
                }
            } else {
                $_SESSION['upload_error'] = "Database connection failed for update.";
            }
        } else {
            $_SESSION['upload_error'] = "Sorry, there was an error uploading your file.";
        }
    }
} else {
    $_SESSION['upload_error'] = "No file uploaded or invalid request.";
}

// Redirect back to the profile page
header("Location: profile.php");
exit();
?>