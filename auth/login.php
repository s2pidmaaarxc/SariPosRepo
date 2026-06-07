<?php
//  auth/login.php
//  Handles: login form display + login POST

require_once __DIR__ . '/../config/auth.php';

// Redirect if already logged-in
if (isLoggedIn()) {
    $role = getRole() === 'manager' ?
    '/sari-sari-pos/manager/dashboard.php'
    : '/sari-sari-pos/cashier/pos.php';
    header("Location: $role");
    exit;
}

// Login post
$err = '';
$msg = clean($_GET['msg'] ?? '');
$inputErr = clean($_GET['err'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db -> prepare(
            "SELECT employee_id, full_name, password_hash, role, status
             FROM employees WHERE username = ? LIMIT 1"
        );

        $stmt -> bind_param('s', $username);
        $stmt -> execute();
        $result = $stmt -> get_result();
        $user = $result -> fetch_assoc();
        $stmt -> close();

        if (!$user) {
            $err = 'Invalid username / password.';
        } else if ($user['status'] !== 'active'){
            $err = 'Your account is inactive. Please, contact the manager.';
        } else if (!password_verify($password, $user['password_hash'])) {
            $err = 'Invalid username or password.';
        } else {

            // Login success
            session_regenerate_id(true);
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['full_name']   = $user['full_name'];
            $_SESSION['role']        = $user['role'];

            $role = $user['role'] === 'manager' ?
            '/sari-sari-pos/manager/dashboard.php'
            : '/sari-sari-pos/cashier/pos.php';
            header("Location: $role");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sari-Sari POS</title>
    <link rel="stylesheet" href="/sari-sari-pos/assets/css/style.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-logo">
        <span class="logo-icon">🏪</span>
        <h1>Sari-Sari POS</h1>
        <p>Point of Sale System</p>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($inputErr): ?>
        <div class="alert alert-warning"><?= $inputErr ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="alert alert-danger"><?= $err ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm" novalidate>
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                class="form-control"
                value="<?= clean($_POST['username'] ?? '') ?>"
                placeholder="Enter your username"
                autocomplete="username"
                required
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-eye">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
                <button type="button" class="eye-btn" id="togglePass" title="Show/hide password">👁</button>
                
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
            Sign In
        </button>
    </form>

    <p class="auth-footer">
        New employee? <a href="/sari-sari-pos/auth/register.php">Register here</a>
    </p>
</div>

<script src="js/login.js"></script>
</body>
</html>