-- ============================================================
--  SARI-SARI STORE POS SYSTEM — DIRTY DATASET (EXAM DEMO)
--  Gamitin ito sa MySQL Workbench para sa Data Quality Demo
-- ============================================================

DROP DATABASE IF EXISTS sarisari_pos_dirty;
CREATE DATABASE sarisari_pos_dirty;
USE sarisari_pos_dirty;

-- ============================================================
-- TABLE 1: categories
-- ============================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50)
);

-- ============================================================
-- TABLE 2: users (Manager / Cashier — RBAC)
-- ============================================================
CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50),
    password    VARCHAR(255),
    role        VARCHAR(20),
    created_at  DATETIME
);

-- ============================================================
-- TABLE 3: products
-- ============================================================
CREATE TABLE products (
    product_id   INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100),
    category_id  INT,
    price        DECIMAL(10,2),
    stock        INT,
    created_at   DATETIME,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- ============================================================
-- TABLE 4: orders
-- ============================================================
CREATE TABLE orders (
    order_id      INT AUTO_INCREMENT PRIMARY KEY,
    cashier_id    INT,
    order_date    DATETIME,
    total_amount  DECIMAL(10,2),
    status        VARCHAR(20),
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

-- ============================================================
-- TABLE 5: order_items
-- ============================================================
CREATE TABLE order_items (
    item_id      INT AUTO_INCREMENT PRIMARY KEY,
    order_id     INT,
    product_id   INT,
    quantity     INT,
    unit_price   DECIMAL(10,2),
    subtotal     DECIMAL(10,2),
    FOREIGN KEY (order_id)   REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- ============================================================
-- TABLE 6: receipts
-- ============================================================
CREATE TABLE receipts (
    receipt_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id       INT,
    receipt_number VARCHAR(20),
    issued_at      DATETIME,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);


-- ============================================================
-- INSERT: categories
-- (DIRTY: inconsistent casing, duplicates)
-- ============================================================
INSERT INTO categories (category_name) VALUES
('Noodles'),        -- 1
('noodles'),        -- 2  DUPLICATE (different casing)
('Drinks'),         -- 3
('DRINKS'),         -- 4  DUPLICATE (all caps)
('Snacks'),         -- 5
('snacks'),         -- 6  DUPLICATE
('Canned Goods'),   -- 7
('canned goods'),   -- 8  DUPLICATE
('Condiments'),     -- 9
('condiments'),     -- 10 DUPLICATE
('Personal Care'),  -- 11
('personal care'),  -- 12 DUPLICATE
('Beverages'),      -- 13
NULL;               -- 14 NULL category name


-- ============================================================
-- INSERT: users
-- (DIRTY: NULL passwords, wrong roles, duplicate usernames)
-- ============================================================
INSERT INTO users (username, password, role, created_at) VALUES
('admin',        '$2y$10$abc123hashedpass',  'manager',  '2024-01-01 08:00:00'),  -- 1
('juan_cashier', '$2y$10$def456hashedpass',  'cashier',  '2024-01-02 09:00:00'),  -- 2
('maria_staff',  '$2y$10$ghi789hashedpass',  'cashier',  '2024-01-03 10:00:00'),  -- 3
('pedro_mgr',    '$2y$10$jkl012hashedpass',  'manager',  '2024-01-04 11:00:00'),  -- 4
('juan_cashier', '$2y$10$mno345hashedpass',  'cashier',  '2024-01-05 12:00:00'),  -- 5  DUPLICATE username
('ana_staff',    NULL,                        'cashier',  '2024-01-06 08:30:00'),  -- 6  NULL password
('jose_cashier', '$2y$10$pqr678hashedpass',  'CASHIER',  '2024-01-07 09:30:00'),  -- 7  wrong role casing
('liza_staff',   '$2y$10$stu901hashedpass',  'Staff',    '2024-01-08 10:30:00'),  -- 8  invalid role
('mark_cashier', '$2y$10$vwx234hashedpass',  'cashier',  NULL),                   -- 9  NULL created_at
('nina_mgr',     '$2y$10$yza567hashedpass',  'manager',  '2024-01-10 11:30:00'); -- 10


-- ============================================================
-- INSERT: products
-- (DIRTY: NULLs, negative price, duplicates, inconsistent names)
-- ============================================================
INSERT INTO products (name, category_id, price, stock, created_at) VALUES
-- Noodles
('Lucky Me Pancit Canton',     1,  15.00,  100, '2024-01-01 08:00:00'),
('lucky me pancit canton',     1,  15.00,   80, '2024-01-01 08:00:00'),  -- DUPLICATE (lowercase)
('Lucky Me Chicken',           1,  14.00,   90, '2024-01-01 08:00:00'),
('Lucky Me Chicken Noodles',   1,  14.00,   90, '2024-01-01 08:00:00'),  -- NEAR DUPLICATE
('Nissin Cup Noodles',         1,  20.00,   60, '2024-01-01 08:00:00'),
('NISSIN CUP NOODLES',         1,  20.00,   60, '2024-01-01 08:00:00'),  -- DUPLICATE (all caps)

-- Drinks
('Coke 1.5L',                  3,  75.00,   40, '2024-01-01 08:00:00'),
('coke 1.5l',                  3,  75.00,   40, '2024-01-01 08:00:00'),  -- DUPLICATE
('Coke Mismo',                 3,  15.00,   80, '2024-01-01 08:00:00'),
('Royal 1.5L',                 3,  65.00,   35, '2024-01-01 08:00:00'),
('Sprite 1.5L',                3,  70.00,   30, '2024-01-01 08:00:00'),
('Sprite 1.5L',                3,  70.00,   30, '2024-01-01 08:00:00'),  -- EXACT DUPLICATE
('Mineral Water 500ml',        3,  12.00,  120, '2024-01-01 08:00:00'),
('mineral water 500ml',        3,  12.00,  120, '2024-01-01 08:00:00'),  -- DUPLICATE

-- Snacks
('Chippy BBQ',                 5,  12.00,   75, '2024-01-01 08:00:00'),
('chippy bbq',                 5,  12.00,   75, '2024-01-01 08:00:00'),  -- DUPLICATE
('Piattos Cheese',             5,  15.00,   60, '2024-01-01 08:00:00'),
('Nova Country Cheddar',       5,  13.00,   55, '2024-01-01 08:00:00'),
('Boy Bawang Garlic',          5,  10.00,   70, '2024-01-01 08:00:00'),
('Clover Chips',               5,  -8.00,   50, '2024-01-01 08:00:00'),  -- NEGATIVE PRICE (invalid)

-- Canned Goods
('Argentina Corned Beef 150g', 7,  55.00,   45, '2024-01-01 08:00:00'),
('Ligo Sardines in Tomato',    7,  28.00,   80, '2024-01-01 08:00:00'),
('San Marino Corned Tuna',     7,  35.00,   60, '2024-01-01 08:00:00'),
('CDO Liver Spread',           7,  NULL,    40, '2024-01-01 08:00:00'),  -- NULL price
('Mega Sardines Hot',          7,  26.00,   NULL,'2024-01-01 08:00:00'), -- NULL stock

-- Condiments
('Datu Puti Suka',             9,  20.00,   50, '2024-01-01 08:00:00'),
('Silver Swan Toyo',           9,  22.00,   45, '2024-01-01 08:00:00'),
('UFC Banana Catsup',          9,  35.00,   40, '2024-01-01 08:00:00'),
('Knorr Seasoning',            9,  NULL,    35, '2024-01-01 08:00:00'),  -- NULL price
('AJI-NO-MOTO 11g',            9,   5.00,  200, '2024-01-01 08:00:00'),

-- Personal Care
('Safeguard Bar Soap',        11,  42.00,   60, '2024-01-01 08:00:00'),
('Palmolive Shampoo Sachet',  11,   8.00,  150, '2024-01-01 08:00:00'),
('Colgate Toothpaste 50ml',   11,  NULL,    80, '2024-01-01 08:00:00'),  -- NULL price
('Rejoice Conditioner Sachet',11,   7.00,  130, '2024-01-01 08:00:00'),
('Head & Shoulders Sachet',   11,   9.00,  120, '2024-01-01 08:00:00'),

-- Beverages
('Milo 22g Sachet',           13,  10.00,  200, '2024-01-01 08:00:00'),
('Nescafe 3in1',              13,   8.00,  180, '2024-01-01 08:00:00'),
('Kopiko Brown Coffee',       13,   8.00,  160, '2024-01-01 08:00:00'),
('Tang Orange 25g',           13,   6.00,  140, '2024-01-01 08:00:00'),
('Nestea Iced Tea 25g',       13,   6.00,  NULL,'2024-01-01 08:00:00'); -- NULL stock


-- ============================================================
-- INSERT: orders
-- (DIRTY: NULL total, invalid status values)
-- ============================================================
INSERT INTO orders (cashier_id, order_date, total_amount, status) VALUES
(2, '2024-01-10 08:15:00',  NULL,   'completed'),   -- NULL total
(2, '2024-01-10 09:22:00',  145.00, 'Completed'),   -- inconsistent casing
(3, '2024-01-10 10:05:00',  78.00,  'COMPLETED'),   -- all caps
(2, '2024-01-11 08:30:00',  220.00, 'completed'),
(3, '2024-01-11 09:45:00',  95.00,  'completed'),
(7, '2024-01-11 10:55:00',  310.00, 'completed'),
(2, '2024-01-12 08:20:00',  55.00,  'cancelled'),
(3, '2024-01-12 09:10:00',  180.00, 'Cancelled'),   -- inconsistent casing
(7, '2024-01-12 10:30:00',  NULL,   'pending'),     -- NULL total
(2, '2024-01-13 08:00:00',  420.00, 'completed'),
(3, '2024-01-13 09:15:00',  135.00, 'completed'),
(7, '2024-01-13 10:45:00',  260.00, 'COMPLETED'),
(2, '2024-01-14 08:10:00',  90.00,  'completed'),
(3, '2024-01-14 09:30:00',  175.00, 'completed'),
(7, '2024-01-14 10:50:00',  340.00, 'completed'),
(2, '2024-01-15 08:25:00',  115.00, 'completed'),
(3, '2024-01-15 09:40:00',  195.00, 'completed'),
(7, '2024-01-15 11:00:00',  280.00, 'completed'),
(2, '2024-01-16 08:05:00',  60.00,  'cancelled'),
(3, '2024-01-16 09:20:00',  320.00, 'completed'),
(7, '2024-01-17 08:15:00',  145.00, 'completed'),
(2, '2024-01-17 09:30:00',  235.00, 'completed'),
(3, '2024-01-17 10:45:00',  180.00, 'completed'),
(7, '2024-01-18 08:00:00',  95.00,  'completed'),
(2, '2024-01-18 09:15:00',  410.00, 'completed'),
(3, '2024-01-19 08:30:00',  155.00, 'completed'),
(7, '2024-01-19 09:45:00',  270.00, 'completed'),
(2, '2024-01-20 08:10:00',  85.00,  'completed'),
(3, '2024-01-20 09:25:00',  NULL,   'completed'),   -- NULL total
(7, '2024-01-20 10:40:00',  305.00, 'completed'),
(2, '2024-01-21 08:20:00',  190.00, 'completed'),
(3, '2024-01-21 09:35:00',  125.00, 'completed'),
(7, '2024-01-22 08:05:00',  345.00, 'completed'),
(2, '2024-01-22 09:20:00',  70.00,  'cancelled'),
(3, '2024-01-22 10:35:00',  215.00, 'completed'),
(7, '2024-01-23 08:15:00',  160.00, 'completed'),
(2, '2024-01-23 09:30:00',  290.00, 'completed'),
(3, '2024-01-24 08:00:00',  105.00, 'completed'),
(7, '2024-01-24 09:15:00',  380.00, 'completed'),
(2, '2024-01-24 10:30:00',  140.00, 'completed');


-- ============================================================
-- INSERT: order_items (60+ records to hit 100+ total)
-- ============================================================
INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES
(1,  1,  2,  15.00,  30.00),
(1,  9,  1,  15.00,  15.00),
(1,  26, 1,  20.00,  20.00),
(2,  7,  1,  75.00,  75.00),
(2,  18, 2,  15.00,  30.00),
(2,  37, 4,  10.00,  40.00),
(3,  22, 2,  28.00,  56.00),
(3,  34, 1,  42.00,  42.00), -- subtotal wrong intentionally
(4,  11, 3,  70.00, 210.00),
(4,  5,  1,  20.00,  20.00),
(5,  38, 5,   8.00,  40.00),
(5,  17, 3,  15.00,  45.00),
(6,  10, 2,  65.00, 130.00),
(6,  21, 3,  55.00, 165.00),
(7,  26, 1,  20.00,  20.00),
(7,  31, 1,  42.00,  42.00), -- cancelled order
(8,  28, 2,  35.00,  70.00),
(8,  29, 2,  35.00,  70.00),
(9,  13, 2,  12.00,  24.00),
(9,  40, 3,   6.00,  18.00),
(10, 7,  3,  75.00, 225.00),
(10, 38, 5,   8.00,  40.00),
(10, 39, 5,   8.00,  40.00),
(10, 37, 2,  10.00,  20.00),
(11, 22, 2,  28.00,  56.00),
(11, 23, 1,  35.00,  35.00),
(11, 35, 5,   8.00,  40.00),
(12, 10, 2,  65.00, 130.00),
(12, 11, 1,  70.00,  70.00),
(12, 41, 3,   8.00,  24.00),
(13, 1,  2,  15.00,  30.00),
(13, 27, 1,  20.00,  20.00),
(13, 38, 5,   8.00,  40.00),
(14, 7,  1,  75.00,  75.00),
(14, 22, 2,  28.00,  56.00),
(14, 31, 1,  42.00,  42.00),
(15, 10, 2,  65.00, 130.00),
(15, 21, 3,  55.00, 165.00),
(16, 3,  3,  14.00,  42.00),
(16, 35, 5,   8.00,  40.00),
(16, 41, 4,   8.00,  32.00),
(17, 22, 2,  28.00,  56.00),
(17, 23, 1,  35.00,  35.00),
(17, 28, 2,  22.00,  44.00),
(17, 37, 6,  10.00,  60.00),
(18, 7,  2,  75.00, 150.00),
(18, 38, 5,   8.00,  40.00),
(18, 39, 5,   8.00,  40.00),
(19, 26, 1,  20.00,  20.00),
(19, 31, 1,  42.00,  42.00), -- cancelled
(20, 10, 3,  65.00, 195.00),
(20, 18, 3,  15.00,  45.00),
(20, 41, 5,   8.00,  40.00),
(21, 3,  2,  14.00,  28.00),
(21, 22, 2,  28.00,  56.00),
(21, 35, 5,   8.00,  40.00),
(22, 7,  2,  75.00, 150.00),
(22, 18, 3,  15.00,  45.00),
(23, 22, 2,  28.00,  56.00),
(23, 23, 2,  35.00,  70.00),
(23, 37, 6,  10.00,  60.00),
(24, 1,  2,  15.00,  30.00),
(24, 27, 1,  20.00,  20.00),
(24, 38, 5,   8.00,  45.00), -- wrong subtotal (should be 40)
(25, 10, 3,  65.00, 195.00),
(25, 21, 3,  55.00, 165.00),
(26, 3,  3,  14.00,  42.00),
(26, 35, 5,   8.00,  40.00),
(26, 41, 4,   8.00,  32.00),
(26, 38, 5,   8.00,  40.00),
(27, 10, 2,  65.00, 130.00),
(27, 11, 1,  70.00,  70.00),
(27, 37, 5,  10.00,  50.00),
(28, 1,  2,  15.00,  30.00),
(28, 27, 1,  20.00,  20.00),
(28, 38, 5,   8.00,  40.00),
(29, 7,  1,  75.00,  75.00), -- NULL total order
(30, 10, 2,  65.00, 130.00),
(30, 22, 2,  28.00,  56.00),
(30, 38, 5,   8.00,  40.00),
(30, 18, 5,  15.00,  75.00),
(31, 3,  3,  14.00,  42.00),
(31, 35, 5,   8.00,  40.00),
(31, 41, 5,   8.00,  40.00),
(31, 37, 5,  10.00,  50.00),
(32, 7,  1,  75.00,  75.00),
(32, 18, 2,  15.00,  30.00),
(32, 38, 5,   8.00,  20.00), -- wrong subtotal
(33, 10, 2,  65.00, 130.00),
(33, 21, 3,  55.00, 165.00),
(33, 37, 5,  10.00,  50.00),
(34, 26, 1,  20.00,  20.00),
(34, 31, 1,  42.00,  42.00), -- cancelled
(35, 22, 2,  28.00,  56.00),
(35, 23, 2,  35.00,  70.00),
(35, 37, 5,  10.00,  50.00),
(35, 41, 5,   8.00,  40.00),
(36, 3,  3,  14.00,  42.00),
(36, 38, 5,   8.00,  40.00),
(36, 35, 5,   8.00,  40.00),
(36, 27, 2,  20.00,  38.00), -- wrong subtotal
(37, 10, 2,  65.00, 130.00),
(37, 21, 2,  55.00, 110.00),
(37, 37, 5,  10.00,  50.00),
(38, 1,  2,  15.00,  30.00),
(38, 35, 5,   8.00,  40.00),
(38, 38, 5,   8.00,  40.00), -- should be 40 not listed right
(39, 10, 3,  65.00, 195.00),
(39, 21, 3,  55.00, 165.00),
(40, 3,  3,  14.00,  42.00),
(40, 35, 5,   8.00,  40.00),
(40, 41, 5,   8.00,  40.00);


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
-- DATA QUALITY IDENTIFICATION QUERIES
-- Gamitin ito sa presentation para ipakita ang mga problema
-- ============================================================

-- 1. Identify NULL values in products
SELECT product_id, name, price, stock
FROM products
WHERE price IS NULL OR stock IS NULL;

-- 2. Identify duplicate product names
SELECT name, COUNT(*) AS count
FROM products
GROUP BY name
HAVING COUNT(*) > 1;

-- 3. Identify duplicate categories
SELECT category_name, COUNT(*) AS count
FROM categories
GROUP BY category_name
HAVING COUNT(*) > 1;

-- 4. Identify invalid/negative prices
SELECT product_id, name, price
FROM products
WHERE price < 0;

-- 5. Identify NULL order totals
SELECT order_id, order_date, total_amount
FROM orders
WHERE total_amount IS NULL;

-- 6. Identify inconsistent order status
SELECT DISTINCT status FROM orders;

-- 7. Identify NULL passwords in users
SELECT user_id, username, role
FROM users
WHERE password IS NULL;

-- 8. Identify duplicate usernames
SELECT username, COUNT(*) AS count
FROM users
GROUP BY username
HAVING COUNT(*) > 1;

-- 9. Identify invalid roles
SELECT user_id, username, role
FROM users
WHERE role NOT IN ('manager', 'cashier');

-- 10. Identify wrong subtotals in order_items
SELECT item_id, order_id, product_id, quantity, unit_price, subtotal,
       (quantity * unit_price) AS correct_subtotal
FROM order_items
WHERE subtotal != (quantity * unit_price);
