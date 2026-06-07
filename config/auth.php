<?php 
//  config/auth.php
//  Handles: DB connection + all auth functions

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pos_clean');

// Database connection
function getDB(){
    static $conn = null;
    if ($conn === null){
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn -> connect_error){
            http_response_code(500);
            die(json_encode(['success' => false, 'message' =>
                'Database connection failded']));
        }
        $conn -> set_charset('utf8mb4');
    }
    return $conn;
}

// Auth Check functions
function isLoggedIn() : bool {
    return isset($_SESSION['employee_id'], $_SESSION['role']);
}   // Will just return true if user is logged in else null

function getRole() : ? string {
    return $_SESSION['role'] ?? null;
}   // Will returns the logged-in user's role, or not if not logged-in else null

function getUserId() : ? int {
    return $_SESSION['full_name'] ?? null;
}   // Will return a user's id if logged-in but if not the returns null

function getUserName(): ?string {
    return $_SESSION['full_name'] ?? null;
}   // Will return a user's name if logged-in but if not the returns null

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /sari-sari-pos/auth/login.php?error=Please+log+in+first.');
        exit;
    }
}  // Redirects to login page if not logged-in, will call this on any page that is designed to be protected

function requireRole(string|array $roles): void {
    requireLogin();
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array(getRole(), $allowed, true)) {
        // Redirect to their own dashboard instead of a blank error
        $redirect = getRole() === 'cashier'
            ? '/sari-sari-pos/cashier/pos.php'
            : '/sari-sari-pos/manager/dashboard.php';
        header("Location: $redirect?error=Access+denied.");
        exit;
    }
}   // Redirects to login (or dashboard with error) if user doesn't have the required role. 
    // @param string|array $roles  A role string or array of allowed roles.
    

function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}   // Sends a JSON response and stops execution. Used to all AJAX endpoints.

function validateFields(array $fields): ? string {
    foreach ($fields as $field) {
        if (empty($_POST[$field])) {
            return "Field '$field' is required.";
        }
    }
    return null;
}   // Validates that required POST fields are present and not empty else returns an error message

function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}   // Sanitizes a string input from POST or GET.

function requireAjax(): void {
    $isAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        isset($_SERVER['CONTENT_TYPE']) &&
        str_contains($_SERVER['CONTENT_TYPE'], 'application/json')
    );

    if (!$isAjax || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Direct access not allowed.']));
    }
}   // Only allows AJAX POST requests. Rejects direct browser access to ajax.

?>
