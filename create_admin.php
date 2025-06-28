<?php
// create_admin.php
require 'db.php'; // Ensure this path is correct

$username = 'shivani';
$password = 'admin123'; // <--- CHANGE THIS TO A REAL, STRONG PASSWORD!

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("INSERT INTO admin_users (username, password, role) VALUES (:username, :password, 'admin')");
    $stmt->execute([
        ':username' => $username,
        ':password' => $hashed_password
    ]);
    echo "Admin user '$username' created successfully!";
} catch (PDOException $e) {
    echo "Error creating admin user: " . $e->getMessage();
}
?>