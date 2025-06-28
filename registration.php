<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';
$conn = getDBConnection();

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Sanitize inputs
$first = htmlspecialchars(trim($_POST['first-name'] ?? ''));
$last = htmlspecialchars(trim($_POST['last-name'] ?? ''));
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
$username = htmlspecialchars(trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm-password'] ?? '';
$city = htmlspecialchars(trim($_POST['city'] ?? ''));
$state = htmlspecialchars(trim($_POST['state'] ?? ''));
$address = htmlspecialchars(trim($_POST['address'] ?? ''));
$zip = htmlspecialchars(trim($_POST['zip'] ?? ''));

// Validation
if (!$first || !$last || !$email || !$username || !$password || !$confirm) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

try {
    // Check for duplicates
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);

    if ($check->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email or Username already exists.']);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users 
        (first_name, last_name, email, phone, username, password, city, state, address, zip) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$first, $last, $email, $phone, $username, $hashed_password, $city, $state, $address, $zip]);

    $userId = $conn->lastInsertId();

    if (!createUserWallet($userId, $conn)) {
        throw new Exception("Failed to create user wallet.");
    }

    if (!addWelcomeReward($userId, $conn)) {
        throw new Exception("Failed to add welcome reward.");
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    
    echo json_encode(['status' => 'success', 'message' => 'Registration successful!', 'redirect' => '../profile.php']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Dummy function to simulate wallet creation
function createUserWallet($userId, $conn) {
    $stmt = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)");
    return $stmt->execute([$userId]);
}

// Dummy function to simulate adding welcome reward
function addWelcomeReward($userId, $conn) {
    $rewardAmount = 100; // example reward amount
    $stmt = $conn->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    return $stmt->execute([$rewardAmount, $userId]);
}
?>
