<?php
//  manager/products.php — Product Management
//  Sari-Sari Store POS System

require_once '../config.php';
requireManager();

$db = getDB();


//  HANDLER

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        //ADD PRODUCT
        case 'add_product':
            $name   = trim($_POST['name']         ?? '');
            $cat_id = (int)($_POST['category_id'] ?? 0);
            $price  = (float)($_POST['price']     ?? 0);
            $stock  = (int)($_POST['stock']       ?? 0);

            if (!$name || !$cat_id || $price < 0 || $stock < 0) {
                jsonResponse(false, 'Invalid input. Please check all fields.');
            }

            // Check duplicate name
            $stmt = $db -> prepare("SELECT product_id FROM products WHERE name = ?");
            $stmt -> execute([$name]);
            if ($stmt -> fetch()) {
                jsonResponse(false, "Product '{$name}' already exists.");
            }

            $stmt = $db -> prepare("
                INSERT INTO products (name, category_id, price, stock, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt -> execute([$name, $cat_id, $price, $stock]);
            jsonResponse(true, 'Product added successfully.');
            break;

        //  EDIT PRODUCT
        case 'edit_product':
            $pid    = (int)($_POST['product_id']  ?? 0);
            $name   = trim($_POST['name']         ?? '');
            $cat_id = (int)($_POST['category_id'] ?? 0);
            $price  = (float)($_POST['price']     ?? 0);
            $stock  = (int)($_POST['stock']       ?? 0);

            if (!$pid || !$name || !$cat_id || $price < 0 || $stock < 0) {
                jsonResponse(false, 'Invalid input. Please check all fields.');
            }

            // Check duplicate name excluding self
            $stmt = $db -> prepare("SELECT product_id FROM products WHERE name = ? AND product_id != ?");
            $stmt -> execute([$name, $pid]);
            if ($stmt -> fetch()) {
                jsonResponse(false, "Another product named '{$name}' already exists.");
            }

            $stmt = $db -> prepare("
                UPDATE products
                SET name = ?, category_id = ?, price = ?, stock = ?
                WHERE product_id = ?
            ");
            $stmt -> execute([$name, $cat_id, $price, $stock, $pid]);
            jsonResponse(true, 'Product updated successfully.');
            break;

        // DELETE PRODUCT
        case 'delete_product':
            $pid = (int)($_POST['product_id'] ?? 0);
            if (!$pid) jsonResponse(false, 'Invalid product ID.');

            // Protect — cannot delete product that has a existing order records
            $stmt = $db -> prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmt -> execute([$pid]);
            if ((int)$stmt->fetchColumn() > 0) {
                jsonResponse(false, 'Cannot delete — product has existing order records.');
            }

            $stmt = $db -> prepare("DELETE FROM products WHERE product_id = ?");
            $stmt -> execute([$pid]);
            jsonResponse(true, 'Product deleted successfully.');
            break;

        // ADD CATEGORY 
        case 'add_category':
            $name = trim($_POST['name'] ?? '');
            if (!$name) jsonResponse(false, 'Category name is required.');

            $stmt = $db -> prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $stmt -> execute([$name]);
            if ($stmt -> fetch()) {
                jsonResponse(false, "Category '{$name}' already exists.");
            }

            $stmt = $db -> prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt -> execute([$name]);
            jsonResponse(true, 'Category added successfully.');
            break;

        default:
            jsonResponse(false, 'Invalid action.');
    }
}

$user = getCurrentUser();

// Fetch categories
$categories = $db -> query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Fetch products with filters
$search     = trim($_GET['search']   ?? '');
$filter_cat = $_GET['category']      ?? 'all';
$where      = ['1=1'];
$params     = [];

if ($search) {
    $where[]  = "p.name LIKE ?";
    $params[] = "%$search%";
}
if ($filter_cat !== 'all') {
    $where[]  = "p.category_id = ?";
    $params[] = (int)$filter_cat;
}

$stmt = $db -> prepare("
    SELECT p.*, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.category_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.category_name, p.name
");
$stmt -> execute($params);
$products = $stmt -> fetchAll();

$db = null;

$flash = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Manager</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/products.css">
</head>
<body>
<div class="layout">
    <?php include '../includes/sidebar_manager.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">Products</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
                <button class="btn btn-primary btn-sm" data-modal-open="modal-add">+ Add Product</button>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>Product Management</h1>
                <p>Manage your store inventory and categories</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash === 'added'):    echo '<div class="alert alert-success" data-auto-dismiss="3000">✅ Product added successfully.</div>';    endif; ?>
            <?php if ($flash === 'updated'):  echo '<div class="alert alert-success" data-auto-dismiss="3000">✅ Product updated successfully.</div>';  endif; ?>
            <?php if ($flash === 'deleted'):  echo '<div class="alert alert-success" data-auto-dismiss="3000">🗑️ Product deleted.</div>';               endif; ?>
            <?php if ($flash === 'cat_added'):echo '<div class="alert alert-success" data-auto-dismiss="3000">✅ Category added successfully.</div>';  endif; ?>
            <?php if ($flash === 'error'):    echo '<div class="alert alert-danger"  data-auto-dismiss="4000">❌ Something went wrong.</div>';          endif; ?>

            <!-- Stat Cards -->
            <div class="stats-grid" style="margin-bottom:20px">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Products</div>
                        <div class="stat-value"><?= count($products) ?></div>
                        <div class="stat-sub"><?= count($categories) ?> categories</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">In Stock</div>
                        <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock'] >= 10)) ?></div>
                        <div class="stat-sub">sufficient stock</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Low Stock</div>
                        <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] < 10)) ?></div>
                        <div class="stat-sub">below 10 units</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Out of Stock</div>
                        <div class="stat-value"><?= count(array_filter($products, fn($p) => $p['stock'] == 0)) ?></div>
                        <div class="stat-sub">needs restocking</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card" style="margin-bottom:16px; padding:14px 20px">
                <form method="GET" class="filter-bar">
                    <input type="search" name="search" class="form-control"
                           placeholder="Search products..."
                           value="<?= htmlspecialchars($search) ?>">
                    <select name="category" class="form-control">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"
                            <?= $filter_cat == $cat['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="/pos_system/manager/products.php" class="btn btn-outline btn-sm">Reset</a>
                    <button type="button" class="btn btn-outline btn-sm"
                        data-modal-open="modal-add"
                        onclick="switchTab('tab-category')">
                        + Add Category
                    </button>
                </form>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Products</span>
                    <span class="text-muted" style="font-size:0.8rem"><?= count($products) ?> items</span>
                </div>
                <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📦</div>
                    <p>Walang products na nahanap.</p>
                </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $i => $p):
                            $sc = $p['stock'] == 0 ? 'stock-critical' : ($p['stock'] < 10 ? 'stock-low' : 'stock-ok');
                            $sb = $p['stock'] == 0
                                ? '<span class="badge badge-danger">Out of Stock</span>'
                                : ($p['stock'] < 10
                                    ? '<span class="badge badge-warning">Low Stock</span>'
                                    : '<span class="badge badge-success">In Stock</span>');
                        ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td class="font-bold"><?= htmlspecialchars($p['name']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($p['category_name']) ?></span></td>
                            <td class="text-right text-mono font-bold">₱<?= number_format($p['price'], 2) ?></td>
                            <td class="text-right <?= $sc ?>"><?= $p['stock'] ?></td>
                            <td><?= $sb ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-outline btn-sm" onclick="openEdit(
                                        <?= $p['product_id'] ?>,
                                        '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>',
                                        <?= $p['category_id'] ?>,
                                        <?= $p['price'] ?>,
                                        <?= $p['stock'] ?>
                                    )">Edit</button>
                                    <button class="btn btn-danger btn-sm"
                                        onclick="deleteProduct(<?= $p['product_id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>')">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Add New</span>
            <button class="modal-close" data-modal-close="modal-add">✕</button>
        </div>
        <div class="modal-body">
            <div class="tab-switcher">
                <button class="tab-switch-btn active" data-tab="tab-product" onclick="switchTab('tab-product')">Product</button>
                <button class="tab-switch-btn" data-tab="tab-category" onclick="switchTab('tab-category')">Category</button>
            </div>
            <div class="tab-pane active" id="tab-product">
                <div id="err-add-product" class="alert alert-danger" style="display:none"></div>
                <div class="form-group">
                    <label class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="add-name" placeholder="e.g. Lucky Me Pancit Canton">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select class="form-control" id="add-cat">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                    <div class="form-group mb-0">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="add-price" placeholder="0.00" step="0.01" min="0">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" id="add-stock" placeholder="0" min="0">
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="tab-category">
                <div id="err-add-cat" class="alert alert-danger" style="display:none"></div>
                <div class="form-group">
                    <label class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="add-cat-name" placeholder="e.g. Frozen Foods">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-modal-close="modal-add">Cancel</button>
            <button class="btn btn-primary" onclick="saveAdd()">Save</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">✏️ Edit Product</span>
            <button class="modal-close" data-modal-close="modal-edit">✕</button>
        </div>
        <div class="modal-body">
            <div id="err-edit" class="alert alert-danger" style="display:none"></div>
            <input type="hidden" id="edit-id">
            <div class="form-group">
                <label class="form-label">Product Name</label>
                <input type="text" class="form-control" id="edit-name">
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select class="form-control" id="edit-cat">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px">
                <div class="form-group mb-0">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" class="form-control" id="edit-price" step="0.01" min="0">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Stock</label>
                    <input type="number" class="form-control" id="edit-stock" min="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-modal-close="modal-edit">Cancel</button>
            <button class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/products.js"></script>
</body>
</html>
