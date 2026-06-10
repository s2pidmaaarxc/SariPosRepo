# 🏪 SariPOS — Sari-Sari Store Point of Sale System

> **DCIT 55A — Advanced Database Management System**
> Final Examination | 2nd Semester A.Y. 2025–2026
> Cavite State University — CvSU CCAT Campus, Rosario, Cavite
> Department of Computer Studies

---

## 📋 Project Overview

**SariPOS** is a database-driven Point of Sale (POS) system designed for sari-sari store operations. It demonstrates proper database design, data preparation, and SQL-based analysis as required by the final examination of DCIT 55A — Advanced Database Management System.

The system supports two user roles — **Manager** and **Cashier** — with real-time inventory tracking, automatic receipt generation, and sales analytics.

---

## 🎯 Objectives

- Design and develop a fully functional database-driven system based on a real-world scenario
- Apply proper database design with normalized tables and relationships
- Demonstrate data quality assessment, data cleaning, and SQL-based data analysis
- Generate meaningful reports and insights from a structured dataset

---

## ✅ Expected Outcomes

- A fully functional database system with properly designed tables and relationships
- A dataset containing at least 100 records with identified and resolved data quality issues
- Cleaned and transformed data using SQL operations
- Executed SQL queries for filtering, joining, and data analysis
- Generated meaningful reports and insights from the dataset
- A system demonstration showing understanding of database structure and SQL operations
- Application of real-world database management and data handling practices

---

## 🗄️ Database Design

### Entity-Relationship Overview

The system uses **6 related tables** with proper Primary Key (PK) and Foreign Key (FK) relationships:

| Table | Description |
|---|---|
| `users` | Stores manager and cashier accounts |
| `categories` | Product category classifications |
| `products` | Store inventory with pricing and stock |
| `orders` | Transaction records per cashier |
| `order_items` | Line items per order (many-to-many bridge) |
| `receipts` | Generated receipt records linked to orders |

### Relationships

- `users` → `orders` (1-to-many): One cashier can process many orders
- `categories` → `products` (1-to-many): One category contains many products
- `orders` → `order_items` (1-to-many): One order has many line items
- `products` → `order_items` (1-to-many): One product appears in many order items
- `orders` → `receipts` (1-to-1): One order generates one receipt

### Key SQL Schema Highlights

```sql
-- Products table with FK constraint
CREATE TABLE products (
    product_id  INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    category_id INT           NOT NULL,
    price       DECIMAL(10,2) NOT NULL CHECK (price >= 0),
    stock       INT           NOT NULL DEFAULT 0,
    created_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Order items with computed subtotal column
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
```

---

## 📊 Dataset

- **35 products** across 7 categories (Noodles, Drinks, Snacks, Canned Goods, Condiments, Personal Care, Beverages)
- **6 user accounts** (2 managers, 4 cashiers)
- **7 product categories**
- Dataset is seeded automatically on first run via `config.php`
- All records are aligned to the sari-sari store sales scenario

> The dataset was designed to reflect real sari-sari store inventory with authentic Philippine brands and realistic pricing.

---

## 🧹 Data Quality

### Identified Data Issues

Before cleaning, the following types of data quality issues were assessed and documented:

| Issue Type | Example | Resolution |
|---|---|---|
| Missing values (NULL) | Orders with no receipt | Ensured receipt is auto-generated on checkout |
| Duplicate records | Duplicate product names | UNIQUE constraint on `products.name` |
| Inconsistent formats | Inconsistent date handling | `SET time_zone = '+08:00'` enforced server-wide |
| Invalid entries | Negative price or stock | `CHECK (price >= 0)` and `CHECK (quantity > 0)` constraints |
| Data inconsistencies | Orphaned order items | Foreign key constraints enforce referential integrity |

### Data Cleaning Queries (Sample)

```sql
-- Remove duplicate products by name
DELETE p1 FROM products p1
INNER JOIN products p2
WHERE p1.product_id > p2.product_id AND p1.name = p2.name;

-- Handle NULL total_amounts in orders
UPDATE orders SET total_amount = 0.00 WHERE total_amount IS NULL;

-- Standardize status values
UPDATE orders SET status = LOWER(TRIM(status));

-- Identify orders missing receipts (NULL check)
SELECT o.order_id FROM orders o
LEFT JOIN receipts r ON o.order_id = r.order_id
WHERE r.receipt_id IS NULL AND o.status = 'completed';
```

---

## 🔍 SQL Requirements

### 1. Data Filtering Queries (5+)

```sql
-- Filter products by category
SELECT p.name, p.price, p.stock
FROM products p
JOIN categories c ON p.category_id = c.category_id
WHERE c.category_name = 'Snacks';

-- Filter low stock products
SELECT name, stock FROM products WHERE stock < 10 ORDER BY stock ASC;

-- Filter completed orders today
SELECT * FROM orders
WHERE DATE(order_date) = CURDATE() AND status = 'completed';

-- Search transactions by receipt number
SELECT o.*, r.receipt_number
FROM orders o
LEFT JOIN receipts r ON o.order_id = r.order_id
WHERE r.receipt_number LIKE 'RCP-2025-%';

-- Filter orders within a date range
SELECT order_id, total_amount, status
FROM orders
WHERE order_date BETWEEN '2025-01-01' AND '2025-12-31';
```

### 2. Data Analysis / Report Queries (5+)

```sql
-- Daily revenue report
SELECT DATE(order_date) AS sale_date,
       COUNT(order_id)  AS total_orders,
       SUM(total_amount) AS revenue
FROM orders
WHERE status = 'completed'
GROUP BY DATE(order_date)
ORDER BY sale_date DESC;

-- Best-selling products by quantity
SELECT p.name, SUM(oi.quantity) AS total_qty_sold
FROM order_items oi
JOIN products p ON oi.product_id = p.product_id
JOIN orders o   ON oi.order_id   = o.order_id
WHERE o.status = 'completed'
GROUP BY p.product_id
ORDER BY total_qty_sold DESC
LIMIT 10;

-- Revenue per category
SELECT c.category_name, SUM(oi.subtotal) AS total_revenue
FROM order_items oi
JOIN products p   ON oi.product_id = p.product_id
JOIN categories c ON p.category_id = c.category_id
JOIN orders o     ON oi.order_id   = o.order_id
WHERE o.status = 'completed'
GROUP BY c.category_id
ORDER BY total_revenue DESC;

-- Cashier performance summary
SELECT u.username,
       COUNT(o.order_id)  AS total_orders,
       SUM(o.total_amount) AS total_sales,
       AVG(o.total_amount) AS avg_order_value
FROM orders o
JOIN users u ON o.cashier_id = u.user_id
WHERE o.status = 'completed'
GROUP BY u.user_id
ORDER BY total_sales DESC;

-- Monthly sales trend
SELECT YEAR(order_date) AS yr, MONTH(order_date) AS mo,
       SUM(total_amount) AS monthly_revenue
FROM orders
WHERE status = 'completed'
GROUP BY YEAR(order_date), MONTH(order_date)
ORDER BY yr, mo;
```

### 3. JOIN Operations (2+)

```sql
-- INNER JOIN: Orders with cashier name and receipt number
SELECT o.order_id, u.username AS cashier,
       r.receipt_number, o.total_amount, o.status
FROM orders o
INNER JOIN users    u ON o.cashier_id = u.user_id
INNER JOIN receipts r ON o.order_id   = r.order_id
ORDER BY o.order_date DESC;

-- LEFT JOIN: All products including those never sold
SELECT p.name, p.stock,
       COALESCE(SUM(oi.quantity), 0) AS total_sold
FROM products p
LEFT JOIN order_items oi ON p.product_id = oi.product_id
LEFT JOIN orders      o  ON oi.order_id  = o.order_id
                         AND o.status    = 'completed'
GROUP BY p.product_id
ORDER BY total_sold DESC;
```

---

## 🖥️ System Features

### Manager Panel
- **Dashboard** — Store-wide sales overview, cashier performance charts, low stock alerts
- **Products** — Add, edit, delete products and categories; inventory status monitoring
- **Users** — Add, edit, delete cashier/manager accounts; reset passwords
- **Reports** — Date-filtered analytics: daily sales, best sellers, cashier performance, category revenue

### Cashier Panel
- **Dashboard** — Personal sales summary, hourly breakdown, top products sold
- **Point of Sale** — Product search, category filtering, cart management, checkout workflow with payment confirmation
- **Transactions** — Full transaction history with receipt viewing and order cancellation

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 8.x |
| Database | MySQL 8.x |
| Charts | Chart.js 4.4.1 |
| Fonts | Plus Jakarta Sans, DM Mono |
| Server | Apache (XAMPP / WAMP) |

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP / WAMP / LAMP with PHP 8+ and MySQL 8+
- Web browser (Chrome, Firefox, Edge)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/saripos.git
   ```

2. **Move to your server's web root**
   ```bash
   # XAMPP (Windows)
   mv saripos C:/xampp/htdocs/pos_system

   # XAMPP (Linux/Mac)
   mv saripos /opt/lampp/htdocs/pos_system
   ```

3. **Configure database credentials** in `config.php`
   ```php
   define('DB_HOST',  'localhost');
   define('DB_USER',  'root');       // your MySQL username
   define('DB_PASS',  '');           // your MySQL password
   define('BASE_URL', 'http://localhost/pos_system');
   ```

4. **Start Apache and MySQL**, then open:
   ```
   http://localhost/pos_system/
   ```

5. **The database auto-initializes** on first visit — tables are created and seeded automatically. No manual SQL import needed.

### Default Login Credentials

| Username | Password | Role |
|---|---|---|
| `Admin` | `123456` | Manager |
| `Pedro_mgr` | `123456` | Manager |
| `Juan` | `123456` | Cashier |
| `Maria` | `123456` | Cashier |
| `Jose` | `123456` | Cashier |
| `Ana` | `123456` | Cashier |

---

## 📁 Project Structure

```
pos_system/
├── index.html                  # Login page
├── config.php                  # DB config, session helpers, auto-init
├── assets/
│   ├── css/
│   │   ├── style.css           # Global styles
│   │   ├── login.css
│   │   ├── pos.css
│   │   ├── mDashboard.css
│   │   ├── cDashboard.css
│   │   ├── products.css
│   │   ├── users.css
│   │   ├── transactions.css
│   │   ├── report.css
│   │   └── receipt.css
│   └── js/
│       ├── main.js             # Global JS (modals, toast, clock)
│       ├── login.js
│       ├── pos.js
│       ├── mDashboard.js
│       ├── cDashboard.js
│       ├── products.js
│       ├── users.js
│       ├── transactions.js
│       └── reports.js
├── auth/
│   ├── login.php
│   └── logout.php
├── includes/
│   ├── sidebar_manager.php
│   └── sidebar_cashier.php
├── manager/
│   ├── dashboard.php
│   ├── products.php
│   ├── users.php
│   └── reports.php
└── cashier/
    ├── dashboard.php
    ├── pos.php
    ├── transactions.php
    └── receipt.php
```

---

## 📈 Key Insights from Data Analysis

1. **Top-selling category** is Noodles and Beverages — fast-moving, low-margin items
2. **Peak transaction hours** can be tracked via the hourly sales chart on the cashier dashboard
3. **Cashier performance** varies significantly — reports allow managers to identify high and low performers
4. **Low stock alerts** are triggered at below 10 units to prevent stockouts
5. **Cancellation rate** tracking helps identify checkout friction or cashier errors

---

## 👥 Group Composition

This project was developed by a group of 6–7 members as required by the examination.

| Member | Role/Contribution |
|---|---|
| *(Member 1)* | *(e.g., Database Design, ERD)* |
| *(Member 2)* | *(e.g., Backend PHP — POS Module)* |
| *(Member 3)* | *(e.g., Frontend — Dashboard & Reports)* |
| *(Member 4)* | *(e.g., SQL Queries & Data Analysis)* |
| *(Member 5)* | *(e.g., Data Cleaning & Quality Report)* |
| *(Member 6)* | *(e.g., User Management & Authentication)* |
| *(Member 7)* | *(e.g., Documentation & Presentation)* |

> ⚠️ All members are equally responsible for the project. Any member may be asked to explain any part of the system during the demonstration.

---

## 🎬 System Demonstration

The demonstration covers:

1. **System Overview** — Purpose, real-world use case (sari-sari store POS)
2. **Database Explanation** — Tables, relationships, PK/FK walkthrough
3. **Data Quality Explanation** — Problems found, why they matter, how resolved
4. **SQL Demonstration** — Live SELECT, JOIN, filtering, and analytical queries
5. **Insights** — What the data reveals about store performance

---

## 📝 Examination Rubric Summary

| Criteria | Points |
|---|---|
| Database Design & Structure | 20 |
| Dataset Completeness (100+ records) | 10 |
| Data Quality Identification | 10 |
| Data Cleaning | 15 |
| Data Filtering (min. 5 queries) | 10 |
| Data Analysis / Reports (min. 5) | 15 |
| JOIN Operations (min. 2) | 10 |
| System Demonstration | 10 |
| Group Collaboration | 10 |
| **TOTAL** | **100** |

---

## 📄 License

This project was developed as an academic requirement for **DCIT 55A — Advanced Database Management System** at Cavite State University CvSU CCAT Campus. For educational purposes only.

---

*SariPOS © 2025 — CvSU CCAT Campus, Department of Computer Studies*
