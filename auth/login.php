<?php
//  auth/login.php — Login & Logout Handler
//  Sari-Sari Store POS System

require_once __DIR__ . '/../config.php';

// Post Request validation
$action = $_POST['action'] ?? '';

if ($action !== 'login') {
    http_response_code(400);
    jsonResponse(false, 'Invalid action.');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password']       ?? '';

// Basic validation
if (!$username || !$password) {
    jsonResponse(false, 'All fields are required.');
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT user_id, username, password, role
    FROM users
    WHERE username = ?
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch();

// Verify password
if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(false, 'Invalid username or password.');
}

// Set session variables
$_SESSION['user_id']      = $user['user_id'];
$_SESSION['username']     = $user['username'];
$_SESSION['user_role']    = $user['role'];
$_SESSION['logged_in_at'] = time();

// Determine redirect based on role
$redirect = $user['role'] === 'manager'
    ? BASE_URL . '/manager/dashboard.php'
    : BASE_URL . '/cashier/dashboard.php';

jsonResponse(true, 'Login successful!', [
    'redirect' => $redirect,
    'role'     => $user['role'],
    'username' => $user['username'],
]);
