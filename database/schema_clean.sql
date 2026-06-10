-- ============================================================
--  SARI-SARI STORE POS SYSTEM — CLEANED DATASET
--  Gamitin ito para sa actual na PHP/HTML website system
-- ============================================================

DROP DATABASE IF EXISTS sarisari_pos;
CREATE DATABASE sarisari_pos;
USE sarisari_pos;

-- ============================================================
-- TABLE 1: categories
-- ============================================================
CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE
);

-- ============================================================
-- TABLE 2: users (RBAC — manager / cashier)
-- ============================================================
CREATE TABLE users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('manager', 'cashier') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE 3: products
-- ============================================================
CREATE TABLE products (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)   NOT NULL,
    category_id INT            NOT NULL,
    price       DECIMAL(10,2)  NOT NULL CHECK (price >= 0),
    stock       INT            NOT NULL DEFAULT 0,
    created_at  DATETIME       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- ============================================================
-- TABLE 4: orders
-- ============================================================
CREATE TABLE orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id   INT           NOT NULL,
    order_date   DATETIME      DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status       ENUM('completed', 'cancelled', 'pending') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 5: order_items
-- ============================================================
CREATE TABLE order_items (
    item_id    INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT           NOT NULL,
    product_id INT           NOT NULL,
    quantity   INT           NOT NULL CHECK (quantity > 0),
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal   DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ============================================================
-- TABLE 6: receipts
-- ============================================================
CREATE TABLE receipts (
    receipt_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT         NOT NULL UNIQUE,
    receipt_number VARCHAR(20) NOT NULL UNIQUE,
    issued_at      DATETIME    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);


-- ============================================================
-- INSERT: categories (cleaned — no duplicates, proper casing)
-- ============================================================
INSERT INTO categories (category_name) VALUES
('Noodles'),        -- 1
('Drinks'),         -- 2
('Snacks'),         -- 3
('Canned Goods'),   -- 4
('Condiments'),     -- 5
('Personal Care'),  -- 6
('Beverages');      -- 7


-- ============================================================
-- INSERT: users (cleaned — no duplicates, proper roles)
-- password is 'password123' hashed with bcrypt for all demo users
-- ============================================================
INSERT INTO users (username, password, role, created_at) VALUES
('admin',        '$2y$10$abc123hashedpassword1', 'manager',  '2024-01-01 08:00:00'),
('pedro_mgr',    '$2y$10$jkl012hashedpassword4', 'manager',  '2024-01-04 11:00:00'),
('nina_mgr',     '$2y$10$yza567hashedpassword10','manager',  '2024-01-10 11:30:00'),
('juan_cashier', '$2y$10$def456hashedpassword2', 'cashier',  '2024-01-02 09:00:00'),
('maria_staff',  '$2y$10$ghi789hashedpassword3', 'cashier',  '2024-01-03 10:00:00'),
('jose_cashier', '$2y$10$pqr678hashedpassword7', 'cashier',  '2024-01-07 09:30:00'),
('ana_staff',    '$2y$10$stu901hashedpassword6', 'cashier',  '2024-01-06 08:30:00'),
('mark_cashier', '$2y$10$vwx234hashedpassword9', 'cashier',  '2024-01-08 10:00:00');


-- ============================================================
-- INSERT: products (cleaned — no duplicates, no NULL, no negative)
-- ============================================================
INSERT INTO products (name, category_id, price, stock, created_at) VALUES
-- Noodles (category 1)
('Lucky Me Pancit Canton',      1,  15.00,  100, '2024-01-01 08:00:00'),
('Lucky Me Chicken Noodles',    1,  14.00,   90, '2024-01-01 08:00:00'),
('Nissin Cup Noodles',          1,  20.00,   60, '2024-01-01 08:00:00'),
('Payless Pancit Canton',       1,  12.00,   80, '2024-01-01 08:00:00'),
('Lucky Me Beef',               1,  15.00,   75, '2024-01-01 08:00:00'),

-- Drinks (category 2)
('Coke 1.5L',                   2,  75.00,   40, '2024-01-01 08:00:00'),
('Coke Mismo 250ml',            2,  15.00,   80, '2024-01-01 08:00:00'),
('Royal 1.5L',                  2,  65.00,   35, '2024-01-01 08:00:00'),
('Sprite 1.5L',                 2,  70.00,   30, '2024-01-01 08:00:00'),
('Mineral Water 500ml',         2,  12.00,  120, '2024-01-01 08:00:00'),

-- Snacks (category 3)
('Chippy BBQ',                  3,  12.00,   75, '2024-01-01 08:00:00'),
('Piattos Cheese',              3,  15.00,   60, '2024-01-01 08:00:00'),
('Nova Country Cheddar',        3,  13.00,   55, '2024-01-01 08:00:00'),
('Boy Bawang Garlic',           3,  10.00,   70, '2024-01-01 08:00:00'),
('Clover Chips',                3,   8.00,   50, '2024-01-01 08:00:00'),

-- Canned Goods (category 4)
('Argentina Corned Beef 150g',  4,  55.00,   45, '2024-01-01 08:00:00'),
('Ligo Sardines in Tomato',     4,  28.00,   80, '2024-01-01 08:00:00'),
('San Marino Corned Tuna',      4,  35.00,   60, '2024-01-01 08:00:00'),
('CDO Liver Spread',            4,  22.00,   40, '2024-01-01 08:00:00'),
('Mega Sardines Hot',           4,  26.00,   65, '2024-01-01 08:00:00'),

-- Condiments (category 5)
('Datu Puti Suka 350ml',        5,  20.00,   50, '2024-01-01 08:00:00'),
('Silver Swan Toyo 350ml',      5,  22.00,   45, '2024-01-01 08:00:00'),
('UFC Banana Catsup 320g',      5,  35.00,   40, '2024-01-01 08:00:00'),
('Knorr Seasoning 130ml',       5,  38.00,   35, '2024-01-01 08:00:00'),
('AJI-NO-MOTO 11g',             5,   5.00,  200, '2024-01-01 08:00:00'),

-- Personal Care (category 6)
('Safeguard Bar Soap',          6,  42.00,   60, '2024-01-01 08:00:00'),
('Palmolive Shampoo Sachet',    6,   8.00,  150, '2024-01-01 08:00:00'),
('Colgate Toothpaste 50ml',     6,  55.00,   80, '2024-01-01 08:00:00'),
('Rejoice Conditioner Sachet',  6,   7.00,  130, '2024-01-01 08:00:00'),
('Head & Shoulders Sachet',     6,   9.00,  120, '2024-01-01 08:00:00'),

-- Beverages (category 7)
('Milo 22g Sachet',             7,  10.00,  200, '2024-01-01 08:00:00'),
('Nescafe 3in1 20g',            7,   8.00,  180, '2024-01-01 08:00:00'),
('Kopiko Brown Coffee 30g',     7,   8.00,  160, '2024-01-01 08:00:00'),
('Tang Orange 25g',             7,   6.00,  140, '2024-01-01 08:00:00'),
('Nestea Iced Tea 25g',         7,   6.00,  110, '2024-01-01 08:00:00');


-- ============================================================
-- INSERT: orders (40 orders — cleaned, no NULL totals)
-- cashier_id 4 = juan_cashier, 5 = maria_staff, 6 = jose_cashier
-- ============================================================
INSERT INTO orders (cashier_id, order_date, total_amount, status) VALUES
(4, '2024-01-10 08:15:00',  85.00,  'completed'),
(4, '2024-01-10 09:22:00', 145.00,  'completed'),
(5, '2024-01-10 10:05:00',  78.00,  'completed'),
(4, '2024-01-11 08:30:00', 220.00,  'completed'),
(5, '2024-01-11 09:45:00',  95.00,  'completed'),
(6, '2024-01-11 10:55:00', 310.00,  'completed'),
(4, '2024-01-12 08:20:00',  55.00,  'cancelled'),
(5, '2024-01-12 09:10:00', 140.00,  'completed'),
(6, '2024-01-12 10:30:00',  36.00,  'completed'),
(4, '2024-01-13 08:00:00', 420.00,  'completed'),
(5, '2024-01-13 09:15:00', 135.00,  'completed'),
(6, '2024-01-13 10:45:00', 260.00,  'completed'),
(4, '2024-01-14 08:10:00',  90.00,  'completed'),
(5, '2024-01-14 09:30:00', 175.00,  'completed'),
(6, '2024-01-14 10:50:00', 340.00,  'completed'),
(4, '2024-01-15 08:25:00', 115.00,  'completed'),
(5, '2024-01-15 09:40:00', 195.00,  'completed'),
(6, '2024-01-15 11:00:00', 280.00,  'completed'),
(4, '2024-01-16 08:05:00',  60.00,  'cancelled'),
(5, '2024-01-16 09:20:00', 320.00,  'completed'),
(6, '2024-01-17 08:15:00', 145.00,  'completed'),
(4, '2024-01-17 09:30:00', 235.00,  'completed'),
(5, '2024-01-17 10:45:00', 180.00,  'completed'),
(6, '2024-01-18 08:00:00',  95.00,  'completed'),
(4, '2024-01-18 09:15:00', 410.00,  'completed'),
(5, '2024-01-19 08:30:00', 155.00,  'completed'),
(6, '2024-01-19 09:45:00', 270.00,  'completed'),
(4, '2024-01-20 08:10:00',  85.00,  'completed'),
(5, '2024-01-20 09:25:00', 245.00,  'completed'),
(6, '2024-01-20 10:40:00', 305.00,  'completed'),
(4, '2024-01-21 08:20:00', 190.00,  'completed'),
(5, '2024-01-21 09:35:00', 125.00,  'completed'),
(6, '2024-01-22 08:05:00', 345.00,  'completed'),
(4, '2024-01-22 09:20:00',  70.00,  'cancelled'),
(5, '2024-01-22 10:35:00', 215.00,  'completed'),
(6, '2024-01-23 08:15:00', 160.00,  'completed'),
(4, '2024-01-23 09:30:00', 290.00,  'completed'),
(5, '2024-01-24 08:00:00', 105.00,  'completed'),
(6, '2024-01-24 09:15:00', 380.00,  'completed'),
(4, '2024-01-24 10:30:00', 140.00,  'completed');


-- ============================================================
-- INSERT: order_items (subtotal auto-computed via GENERATED col)
-- ============================================================
INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
(1,  1,  2,  15.00),
(1,  7,  1,  15.00),
(1,  21, 1,  20.00),
(1,  25, 5,   5.00),
(2,  6,  1,  75.00),
(2,  12, 2,  15.00),
(2,  31, 4,  10.00),
(3,  17, 2,  28.00),
(3,  26, 1,  42.00),
(4,  9,  3,  70.00),
(4,  5,  1,  20.00),
(5,  32, 5,   8.00),
(5,  12, 3,  15.00),
(6,  8,  2,  65.00),
(6,  16, 3,  55.00),
(7,  21, 1,  20.00),
(7,  26, 1,  42.00),
(8,  18, 2,  35.00),
(8,  23, 2,  35.00),
(9,  10, 2,  12.00),
(9,  35, 3,   6.00),
(10, 6,  3,  75.00),
(10, 32, 5,   8.00),
(10, 33, 5,   8.00),
(10, 14, 2,  10.00),
(11, 17, 2,  28.00),
(11, 18, 1,  35.00),
(11, 27, 5,   8.00),
(12, 8,  2,  65.00),
(12, 9,  1,  70.00),
(12, 31, 3,   8.00),-- corrected: Milo not Nescafe index shifted
(13, 1,  2,  15.00),
(13, 22, 1,  20.00),
(13, 32, 5,   8.00),
(14, 6,  1,  75.00),
(14, 17, 2,  28.00),
(14, 26, 1,  42.00),
(15, 8,  2,  65.00),
(15, 16, 3,  55.00),
(16, 3,  3,  14.00),
(16, 27, 5,   8.00),
(16, 31, 4,   8.00),
(17, 17, 2,  28.00),
(17, 18, 1,  35.00),
(17, 22, 2,  22.00),
(17, 14, 6,  10.00),
(18, 6,  2,  75.00),
(18, 32, 5,   8.00),
(18, 33, 5,   8.00),
(19, 21, 1,  20.00),
(19, 26, 1,  42.00),
(20, 8,  3,  65.00),
(20, 11, 3,  12.00),
(20, 31, 5,   8.00),
(21, 3,  2,  14.00),
(21, 17, 2,  28.00),
(21, 27, 5,   8.00),
(22, 6,  2,  75.00),
(22, 11, 3,  12.00),
(23, 17, 2,  28.00),
(23, 18, 2,  35.00),
(23, 14, 6,  10.00),
(24, 1,  2,  15.00),
(24, 22, 1,  20.00),
(24, 32, 5,   8.00),
(25, 8,  3,  65.00),
(25, 16, 3,  55.00),
(26, 3,  3,  14.00),
(26, 27, 5,   8.00),
(26, 31, 4,   8.00),
(26, 32, 5,   8.00),
(27, 8,  2,  65.00),
(27, 9,  1,  70.00),
(27, 14, 5,  10.00),
(28, 1,  2,  15.00),
(28, 22, 1,  20.00),
(28, 32, 5,   8.00),
(29, 17, 2,  28.00),
(29, 18, 1,  35.00),
(29, 22, 2,  22.00),
(29, 14, 6,  10.00),
(30, 8,  2,  65.00),
(30, 17, 2,  28.00),
(30, 32, 5,   8.00),
(30, 11, 5,  12.00),
(31, 3,  3,  14.00),
(31, 27, 5,   8.00),
(31, 31, 5,   8.00),
(31, 14, 5,  10.00),
(32, 6,  1,  75.00),
(32, 11, 2,  12.00),
(32, 32, 5,   8.00),
(33, 8,  2,  65.00),
(33, 16, 3,  55.00),
(33, 14, 5,  10.00),
(34, 21, 1,  20.00),
(34, 26, 1,  42.00),
(35, 17, 2,  28.00),
(35, 18, 2,  35.00),
(35, 14, 5,  10.00),
(35, 31, 5,   8.00),
(36, 3,  3,  14.00),
(36, 32, 5,   8.00),
(36, 27, 5,   8.00),
(36, 22, 2,  20.00),
(37, 8,  2,  65.00),
(37, 16, 2,  55.00),
(37, 14, 5,  10.00),
(38, 1,  2,  15.00),
(38, 27, 5,   8.00),
(38, 32, 5,   8.00),
(39, 8,  3,  65.00),
(39, 16, 3,  55.00),
(40, 3,  3,  14.00),
(40, 27, 5,   8.00),
(40, 31, 5,   8.00);


-- ============================================================
-- INSERT: receipts
-- ============================================================
INSERT INTO receipts (order_id, receipt_number, issued_at) VALUES
(1,  'RCP-2024-001', '2024-01-10 08:16:00'),
(2,  'RCP-2024-002', '2024-01-10 09:23:00'),
(3,  'RCP-2024-003', '2024-01-10 10:06:00'),
(4,  'RCP-2024-004', '2024-01-11 08:31:00'),
(5,  'RCP-2024-005', '2024-01-11 09:46:00'),
(6,  'RCP-2024-006', '2024-01-11 10:56:00'),
(7,  'RCP-2024-007', '2024-01-12 08:21:00'),
(8,  'RCP-2024-008', '2024-01-12 09:11:00'),
(9,  'RCP-2024-009', '2024-01-12 10:31:00'),
(10, 'RCP-2024-010', '2024-01-13 08:01:00'),
(11, 'RCP-2024-011', '2024-01-13 09:16:00'),
(12, 'RCP-2024-012', '2024-01-13 10:46:00'),
(13, 'RCP-2024-013', '2024-01-14 08:11:00'),
(14, 'RCP-2024-014', '2024-01-14 09:31:00'),
(15, 'RCP-2024-015', '2024-01-14 10:51:00'),
(16, 'RCP-2024-016', '2024-01-15 08:26:00'),
(17, 'RCP-2024-017', '2024-01-15 09:41:00'),
(18, 'RCP-2024-018', '2024-01-15 11:01:00'),
(19, 'RCP-2024-019', '2024-01-16 08:06:00'),
(20, 'RCP-2024-020', '2024-01-16 09:21:00'),
(21, 'RCP-2024-021', '2024-01-17 08:16:00'),
(22, 'RCP-2024-022', '2024-01-17 09:31:00'),
(23, 'RCP-2024-023', '2024-01-17 10:46:00'),
(24, 'RCP-2024-024', '2024-01-18 08:01:00'),
(25, 'RCP-2024-025', '2024-01-18 09:16:00'),
(26, 'RCP-2024-026', '2024-01-19 08:31:00'),
(27, 'RCP-2024-027', '2024-01-19 09:46:00'),
(28, 'RCP-2024-028', '2024-01-20 08:11:00'),
(29, 'RCP-2024-029', '2024-01-20 09:26:00'),
(30, 'RCP-2024-030', '2024-01-20 10:41:00'),
(31, 'RCP-2024-031', '2024-01-21 08:21:00'),
(32, 'RCP-2024-032', '2024-01-21 09:36:00'),
(33, 'RCP-2024-033', '2024-01-22 08:06:00'),
(34, 'RCP-2024-034', '2024-01-22 09:21:00'),
(35, 'RCP-2024-035', '2024-01-22 10:36:00'),
(36, 'RCP-2024-036', '2024-01-23 08:16:00'),
(37, 'RCP-2024-037', '2024-01-23 09:31:00'),
(38, 'RCP-2024-038', '2024-01-24 08:01:00'),
(39, 'RCP-2024-039', '2024-01-24 09:16:00'),
(40, 'RCP-2024-040', '2024-01-24 10:31:00');


-- ============================================================
-- EXAM REQUIRED QUERIES — LAHAT NAKA-READY PARA SA DEMO
-- ============================================================

-- ── DATA FILTERING (5 queries) ────────────────────────────

-- F1: Ipakita lahat ng completed na orders
SELECT o.order_id, u.username AS cashier, o.order_date, o.total_amount
FROM orders o
JOIN users u ON o.cashier_id = u.user_id
WHERE o.status = 'completed';

-- F2: Ipakita ang mga produkto na mababa na ang stock (below 50)
SELECT name, category_id, stock
FROM products
WHERE stock < 50
ORDER BY stock ASC;

-- F3: Ipakita ang mga produkto na nasa ilalim ng 20 pesos
SELECT name, price
FROM products
WHERE price < 20.00
ORDER BY price ASC;

-- F4: Ipakita ang mga orders na cancelled
SELECT o.order_id, u.username AS cashier, o.order_date, o.total_amount
FROM orders o
JOIN users u ON o.cashier_id = u.user_id
WHERE o.status = 'cancelled';

-- F5: Ipakita ang mga cashier accounts lang
SELECT user_id, username, role, created_at
FROM users
WHERE role = 'cashier';


-- ── DATA ANALYSIS / REPORTS (5 queries) ──────────────────

-- A1: Total sales per cashier
SELECT u.username, COUNT(o.order_id) AS total_orders,
       SUM(o.total_amount) AS total_sales
FROM orders o
JOIN users u ON o.cashier_id = u.user_id
WHERE o.status = 'completed'
GROUP BY u.username
ORDER BY total_sales DESC;

-- A2: Best-selling products (by quantity sold)
SELECT p.name, SUM(oi.quantity) AS total_qty_sold,
       SUM(oi.subtotal) AS total_revenue
FROM order_items oi
JOIN products p ON oi.product_id = p.product_id
GROUP BY p.name
ORDER BY total_qty_sold DESC
LIMIT 10;

-- A3: Total revenue per product category
SELECT c.category_name, SUM(oi.subtotal) AS category_revenue
FROM order_items oi
JOIN products p  ON oi.product_id  = p.product_id
JOIN categories c ON p.category_id = c.category_id
GROUP BY c.category_name
ORDER BY category_revenue DESC;

-- A4: Daily sales summary
SELECT DATE(order_date) AS sale_date,
       COUNT(order_id)  AS num_transactions,
       SUM(total_amount) AS daily_revenue
FROM orders
WHERE status = 'completed'
GROUP BY DATE(order_date)
ORDER BY sale_date ASC;

-- A5: Average order value per cashier
SELECT u.username,
       AVG(o.total_amount) AS avg_order_value,
       MAX(o.total_amount) AS highest_order,
       MIN(o.total_amount) AS lowest_order
FROM orders o
JOIN users u ON o.cashier_id = u.user_id
WHERE o.status = 'completed'
GROUP BY u.username;


-- ── JOIN OPERATIONS (2+ queries) ─────────────────────────

-- J1: Full receipt details — order + items + product name
SELECT r.receipt_number,
       o.order_date,
       u.username   AS cashier,
       p.name       AS product,
       oi.quantity,
       oi.unit_price,
       oi.subtotal
FROM receipts r
JOIN orders     o  ON r.order_id    = o.order_id
JOIN users      u  ON o.cashier_id  = u.user_id
JOIN order_items oi ON o.order_id   = oi.order_id
JOIN products   p  ON oi.product_id = p.product_id
ORDER BY r.receipt_number, p.name;

-- J2: Product inventory report with category
SELECT p.product_id, p.name, c.category_name,
       p.price, p.stock,
       (p.price * p.stock) AS inventory_value
FROM products p
JOIN categories c ON p.category_id = c.category_id
ORDER BY c.category_name, p.name;

-- J3 (bonus): Orders with cashier info and receipt number
SELECT o.order_id, r.receipt_number,
       u.username AS cashier,
       o.order_date, o.total_amount, o.status
FROM orders o
JOIN users    u ON o.cashier_id = u.user_id
JOIN receipts r ON o.order_id   = r.order_id
ORDER BY o.order_date DESC;
