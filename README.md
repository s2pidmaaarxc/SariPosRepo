# Sari-Sari Store POS System
### DCIT 55A - Advanced Database Management System
**Final Examination | 2nd Semester A.Y. 2025–2026**
Cavite State University — CvSU CCAT Campus, Department of Computer Studies

---
## Project Overview

A **Sales / Point-of-Sale (POS) System** designed for a sari-sari store, built to demonstrate real-world database management including database design, data quality identification, data cleaning, and SQL-based analytics.

---
```
sari-sari-pos/
│
├── database/
|   └── pos_clean.sql          # Clean database (production-ready)
│
├── config/
│   ├── db.php                 # Database connection **later
│   └── auth.php               # requireLogin(), requireRole(), isLoggedIn()
│
├── auth/
│   ├── login.php              # Login page
│   ├── register.php           # Register new employee **later
│   └── logout.php             # Session destroy **later
│ I'll continue later

```
---
## Tech Stack

- **Backend:** PHP 8+
- **Database:** MySQL 8+
- **Frontend:** HTML5, Vanilla JavaScript, CSS3
- **Server:** XAMPP

---

## Database Design (6 Tables)

| Table | Description | Key Relationships |
|---|---|---|
| `categories` | Product categories | Parent of `products` |
| `products` | Store inventory (100 items) | FK → `categories` |
| `employees` | Staff accounts with roles | FK → `sales`, `inventory_logs` |
| `sales` | Transaction headers | FK → `employees` |
| `sale_items` | Line items per transaction | FK → `sales`, `products` |
| `inventory_logs` | Stock change history | FK → `products`, `employees` |

**Relationships:**
- `categories` → `products` (1-to-many)
- `employees` → `sales` (1-to-many)
- `sales` → `sale_items` (1-to-many)
- `products` → `sale_items` (1-to-many)
- `products` → `inventory_logs` (1-to-many)
- `employees` → `inventory_logs` (1-to-many)

---
## RBAC (Role-Based Access Control)

| Feature | Manager | Cashier |
|---|:---:|:---:|
| Sales Dashboard & Charts | ✅ | ❌ |
| Add / Update Products | ✅ | ❌ |
| Add / Promote / Delete Employees | ✅ | ❌ |
| POS / Checkout | ❌*change or nah? | ✅ |
| View Own Transactions | ❌ | ✅ |
| View Inventory | ✅ | ✅ (read-only) |

---
## Installation

1. Clone or copy this project to `htdocs/sari-sari-pos/`
2. Open **phpMyAdmin** → Import `database/pos_clean.sql` *(for the working system)*
3. Open browser → `http://localhost/sari-sari-pos/auth/login.php`

**Default Login Credentials:**
| Role | Username | Password |
|---|---|---|
| Manager | `manager1` | `password123` |
| Cashier | `cashier1` | `password123` |

---
## License

This project is created for academic purposes only — DCIT 55A Final Examination, Cavite State University.
