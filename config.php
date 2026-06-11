<?php
// config.php — Database, Session & App Helpers
// Sari-Sari Store POS System

// App Constants 
date_default_timezone_set('Asia/Manila');
define('DB_HOST',   'localhost');
define('DB_USER',   'root');       // Change this to your MySQL username
define('DB_PASS',   '');           // Change this to your MySQL password
define('DB_NAME',   'sarisari_pos');
define('APP_NAME',  'SariPOS');
define('BASE_URL',  'http://localhost/pos_system'); // Change this to your project path

// Session Config
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

// Database Connection with Self-Initialization
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Connect to MySQL WITHOUT specifying a database name
            // This ensures connection succeeds even if the database doesn't exist yet
            $root = new PDO(
                "mysql:host=" . DB_HOST . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
 
            // Auto-create database if it doesn't exist
            $root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $root = null; // Close root connection

            // Connect specifically to your newly targeted database
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
 
            // Sync timezones
            $pdo->exec("SET time_zone = '+08:00'");

            // Step 4: Run tables and seeding logic automatically
            _initDatabase($pdo);
 
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]));
        }
    }
    return $pdo;
}

// Helper function to dynamically provision tables and seeds
function _initDatabase(PDO $pdo) {
    // Check if tables are already built. If yes, skip initialization.
    $tables = $pdo -> query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (count($tables) > 0) return;
 
    // CREATE TABLES
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS categories (
            category_id   INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(50) NOT NULL UNIQUE
        )
    ");
 
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id    INT AUTO_INCREMENT PRIMARY KEY,
            username   VARCHAR(50)  NOT NULL UNIQUE,
            password   VARCHAR(255) NOT NULL,
            role       ENUM('manager','cashier') NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
 
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS products (
            product_id  INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(100)  NOT NULL,
            category_id INT           NOT NULL,
            price       DECIMAL(10,2) NOT NULL CHECK (price >= 0),
            stock       INT           NOT NULL DEFAULT 0,
            created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(category_id)
        )
    ");
 
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS orders (
            order_id     INT AUTO_INCREMENT PRIMARY KEY,
            cashier_id   INT           NOT NULL,
            order_date   DATETIME      DEFAULT CURRENT_TIMESTAMP,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status       ENUM('completed','cancelled','pending') NOT NULL DEFAULT 'pending',
            FOREIGN KEY (cashier_id) REFERENCES users(user_id)
        )
    ");
 
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS order_items (
            item_id    INT AUTO_INCREMENT PRIMARY KEY,
            order_id   INT           NOT NULL,
            product_id INT           NOT NULL,
            quantity   INT           NOT NULL CHECK (quantity > 0),
            unit_price DECIMAL(10,2) NOT NULL,
            subtotal   DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
            FOREIGN KEY (order_id)   REFERENCES orders(order_id),
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        )
    ");
 
    $pdo -> exec("
        CREATE TABLE IF NOT EXISTS receipts (
            receipt_id     INT AUTO_INCREMENT PRIMARY KEY,
            order_id       INT         NOT NULL UNIQUE,
            receipt_number VARCHAR(20) NOT NULL UNIQUE,
            issued_at      DATETIME    DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id)
        )
    ");
 
    // SEED INITIAL MOCK DATA
    $pdo -> exec("
        INSERT INTO categories (category_name) VALUES
        ('Noodles'), ('Drinks'), ('Snacks'), ('Canned Goods'), ('Condiments'), ('Personal Care'), ('Beverages')
    ");
 
    $hash = password_hash('123456', PASSWORD_BCRYPT);
    $users = [
               ['Admin',     'manager'],
               ['Pedro', 'manager'],
               ['Juan',      'cashier'],
               ['Maria',     'cashier'],
               ['Jose',      'cashier'],
               ['Ana',       'cashier'],
             ];

    $stmt = $pdo -> prepare("
    INSERT INTO users (username, password, role) VALUES (?, ?, ?)
");

    foreach ($users as $u) {
    $stmt -> execute([$u[0], $hash, $u[1]]);
}
 
    $pdo -> exec("
        INSERT INTO products (name, category_id, price, stock) VALUES
        ('Lucky Me Pancit Canton',     1, 15.00, 100),
        ('Lucky Me Chicken Noodles',   1, 14.00,  90),
        ('Nissin Cup Noodles',         1, 20.00,  60),
        ('Payless Pancit Canton',      1, 12.00,  80),
        ('Lucky Me Beef',              1, 15.00,  75),
        ('Coke 1.5L',                  2, 75.00,  40),
        ('Coke Mismo 250ml',           2, 15.00,  80),
        ('Royal 1.5L',                 2, 65.00,  35),
        ('Sprite 1.5L',                2, 70.00,  30),
        ('Mineral Water 500ml',        2, 12.00, 120),
        ('Chippy BBQ',                 3, 12.00,  75),
        ('Piattos Cheese',             3, 15.00,  60),
        ('Nova Country Cheddar',       3, 13.00,  55),
        ('Boy Bawang Garlic',          3, 10.00,  70),
        ('Clover Chips',               3,  8.00,  50),
        ('Argentina Corned Beef 150g', 4, 55.00,  45),
        ('Ligo Sardines in Tomato',    4, 28.00,  80),
        ('San Marino Corned Tuna',     4, 35.00,  60),
        ('CDO Liver Spread',           4, 22.00,  40),
        ('Mega Sardines Hot',          4, 26.00,  65),
        ('Datu Puti Suka 350ml',       5, 20.00,  50),
        ('Silver Swan Toyo 350ml',     5, 22.00,  45),
        ('UFC Banana Catsup 320g',     5, 35.00,  40),
        ('Knorr Seasoning 130ml',      5, 38.00,  35),
        ('AJI-NO-MOTO 11g',            5,  5.00, 200),
        ('Safeguard Bar Soap',         6, 42.00,  60),
        ('Palmolive Shampoo Sachet',   6,  8.00, 150),
        ('Colgate Toothpaste 50ml',    6, 55.00,  80),
        ('Rejoice Conditioner Sachet', 6,  7.00, 130),
        ('Head & Shoulders Sachet',    6,  9.00, 120),
        ('Milo 22g Sachet',            7, 10.00, 200),
        ('Nescafe 3in1 20g',           7,  8.00, 180),
        ('Kopiko Brown Coffee 30g',    7,  8.00, 160),
        ('Tang Orange 25g',            7,  6.00, 140),
        ('Nestea Iced Tea 25g',        7,  6.00, 110)
    ");
}

// Auth Helpers
function isLoggedIn() { return !empty($_SESSION['user_id']); }

function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjax()) {
            http_response_code(401);
            die(json_encode(['success' => false, 'message' => 'Not authenticated.']));
        }
        header('Location: ' . BASE_URL . '/index.html');
        exit();
    }
}

function requireRole(string $role) {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        if (isAjax()) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Access denied.']));
        }
        redirectToDashboard();
    }
}

function requireManager() { requireRole('manager'); }
function requireCashier() { requireLogin(); }
function isManager() { return ($_SESSION['user_role'] ?? '') === 'manager'; }
function isCashier() { return ($_SESSION['user_role'] ?? '') === 'cashier'; }

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['user_role'],
    ];
}

function redirectToDashboard() {
    if (isManager()) {
        header('Location: ' . BASE_URL . '/manager/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/cashier/dashboard.php');
    }
    exit();
}

// Utility Helpers
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(bool $success, string $message = '', array $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit();
}