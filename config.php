<?php
// ============================================================
//  config.php — Database, Session & App Helpers
//  Sari-Sari Store POS System
//  Pattern: KeepNote-style (merged config + auth helpers)
// ============================================================

// ── App Constants ─────────────────────────────────────────
date_default_timezone_set('Asia/Manila');
define('DB_HOST',   'localhost');
define('DB_USER',   'root');       // palitan ng inyong MySQL username
define('DB_PASS',   '');           // palitan ng inyong MySQL password
define('DB_NAME',   'sarisari_pos');
define('APP_NAME',  'SariPOS');
define('BASE_URL',  'http://localhost/pos_system'); // palitan ng inyong path

// ── Session Config (tulad ng KeepNote) ────────────────────
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Connection (PDO — mas secure kaysa mysqli) ───
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
             $pdo -> exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed.'
            ]));
        }
    }
    return $pdo;
}

// ── Auth Helpers ──────────────────────────────────────────

// Check kung logged in
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

// Require login — redirect or JSON error kung hindi
function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjax()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'message' => 'Not authenticated.']));
        }
        header('Location: ' . BASE_URL . ' /index.html');
        exit();
    }
}

// Require specific role — 'manager' o 'cashier'
function requireRole(string $role) {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        if (isAjax()) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Access denied.']));
        }
        // I-redirect sa sariling dashboard
        redirectToDashboard();
    }
}

// Shorthand role checks
function requireManager() { requireRole('manager'); }

// Cashier pages — pareho pwede ang manager at cashier
function requireCashier() { requireLogin(); }

function isManager() {
    return ($_SESSION['user_role'] ?? '') === 'manager';
}

function isCashier() {
    return ($_SESSION['user_role'] ?? '') === 'cashier';
}

// Get current user info mula sa session
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['user_role'],
    ];
}

// Redirect papunta sa tamang dashboard base sa role
function redirectToDashboard() {
    if (isManager()) {
        header('Location: ' . BASE_URL . '/manager/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/cashier/dashboard.php');
    }
    exit();
}

// ── Utility Helpers ───────────────────────────────────────

// Check kung AJAX request (ginagamit para sa JSON vs redirect response)
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// JSON response helper — susunod sa KeepNote pattern
function jsonResponse(bool $success, string $message = '', array $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit();
}
