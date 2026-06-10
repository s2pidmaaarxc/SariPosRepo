<?php
//  manager/users.php — User Management
//  Sari-Sari Store POS System

require_once '../config.php';
requireManager();

$db = getDB();

//  HANDLER — AJAX POST requests

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        //  ADD USER 
        case 'add_user':
            $username  = trim($_POST['username']  ?? '');
            $password  = $_POST['password']       ?? '';
            $password2 = $_POST['password2']      ?? '';
            $role      = $_POST['role']            ?? '';

            if (!$username || !$password || !$role) {
                jsonResponse(false, 'Must fill up all fields.');
            }
            if (!in_array($role, ['manager', 'cashier'])) {
                jsonResponse(false, 'Invalid role.');
            }
            if (strlen($password) < 6) {
                jsonResponse(false, 'Password must be at least 6 characters.');
            }
            if ($password !== $password2) {
                jsonResponse(false, 'Passwords do not match.');
            }

            // Check duplicate username
            $stmt = $db -> prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt -> execute([$username]);
            if ($stmt -> fetch()) {
                jsonResponse(false, "Username '{$username}' is already taken.");
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $db -> prepare("
                INSERT INTO users (username, password, role, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt -> execute([$username, $hashed, $role]);
            jsonResponse(true, 'User added successfully.');
            break;

        // EDIT USER
        case 'edit_user':
            $uid      = (int)($_POST['user_id']  ?? 0);
            $username = trim($_POST['username']  ?? '');
            $role     = $_POST['role']           ?? '';

            if (!$uid || !$username || !$role) {
                jsonResponse(false, 'Must fill up all fields.');
            }
            if (!in_array($role, ['manager', 'cashier'])) {
                jsonResponse(false, 'Invalid role.');
            }

            // Prevent editing own account role
            $current = getCurrentUser();
            if ($uid === (int)$current['user_id'] && $role !== 'manager') {
                jsonResponse(false, 'Role change request denied.');
            }

            // Check duplicate username excluding self
            $stmt = $db -> prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $stmt -> execute([$username, $uid]);
            if ($stmt -> fetch()) {
                jsonResponse(false, "Username '{$username}' is already taken.");
            }

            $stmt = $db -> prepare("UPDATE users SET username = ?, role = ? WHERE user_id = ?");
            $stmt -> execute([$username, $role, $uid]);
            jsonResponse(true, 'User updated successfully.');
            break;

        // RESET PASSWORD 
        case 'reset_password':
            $uid       = (int)($_POST['user_id']  ?? 0);
            $password  = $_POST['password']       ?? '';
            $password2 = $_POST['password2']      ?? '';

            if (!$uid || !$password) {
                jsonResponse(false, 'Must fill up all fields.');
            }
            if (strlen($password) < 6) {
                jsonResponse(false, 'Password must be at least 6 characters.');
            }
            if ($password !== $password2) {
                jsonResponse(false, 'Passwords do not match.');
            }

            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed, $uid]);
            jsonResponse(true, 'Password reset successfully.');
            break;

        // DELETE USER 
        case 'delete_user':
            $uid     = (int)($_POST['user_id'] ?? 0);
            $current = getCurrentUser();

            if (!$uid) jsonResponse(false, 'Invalid user ID.');

            // Prevent self-delete
            if ($uid === (int)$current['user_id']) {
                jsonResponse(false, 'Delete request denied.');
            }

            // Prevent delete if user has orders
            $stmt = $db -> prepare("SELECT COUNT(*) FROM orders WHERE cashier_id = ?");
            $stmt -> execute([$uid]);
            if ((int)$stmt -> fetchColumn() > 0) {
                jsonResponse(false, 'Cannot delete — user has existing transaction records.');
            }

            $stmt = $db -> prepare("DELETE FROM users WHERE user_id = ?");
            $stmt -> execute([$uid]);
            jsonResponse(true, 'User deleted successfully.');
            break;

        default:
            jsonResponse(false, 'Invalid action.');
    }
}

$user = getCurrentUser();

// Fetch all users with transaction count
$users = $db -> query("
    SELECT u.user_id, u.username, u.role, u.created_at,
           COUNT(o.order_id) AS total_orders,
           COALESCE(SUM(CASE WHEN o.status='completed' THEN o.total_amount END), 0) AS total_sales
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.cashier_id
    GROUP BY u.user_id, u.username, u.role, u.created_at
    ORDER BY u.role DESC, u.username ASC
") -> fetchAll();

$total_managers = count(array_filter($users, fn($u) => $u['role'] === 'manager'));
$total_cashiers = count(array_filter($users, fn($u) => $u['role'] === 'cashier'));

$db = null;

$flash = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — Manager</title>
    <link rel="stylesheet" href="/pos_system/assets/css/style.css">
    <link rel="stylesheet" href="/pos_system/assets/css/users.css">
</head>
<body>
<div class="layout">
    <?php include '../includes/sidebar_manager.php'; ?>

    <div class="main-content">
        <div class="topbar">
            <span class="topbar-title">Users</span>
            <div class="topbar-right">
                <span class="topbar-date" id="topbar-clock"></span>
                <button class="btn btn-primary btn-sm" data-modal-open="modal-add">+ Add User</button>
            </div>
        </div>

        <div class="page-content">
            <div class="page-header">
                <h1>User Management</h1>
                <p>Manage manager and cashier accounts</p>
            </div>

            <!-- Flash Messages -->
            <?php if ($flash === 'added'):    echo '<div class="alert alert-success" data-auto-dismiss="3000">✅ User added successfully.</div>';      endif; ?>
            <?php if ($flash === 'updated'):  echo '<div class="alert alert-success" data-auto-dismiss="3000">✅ User updated successfully.</div>';    endif; ?>
            <?php if ($flash === 'deleted'):  echo '<div class="alert alert-success" data-auto-dismiss="3000">🗑️ User deleted.</div>';                 endif; ?>
            <?php if ($flash === 'pw_reset'): echo '<div class="alert alert-success" data-auto-dismiss="3000">🔑 Password reset successfully.</div>'; endif; ?>
            <?php if ($flash === 'error'):    echo '<div class="alert alert-danger"  data-auto-dismiss="4000">❌ Something went wrong.</div>';          endif; ?>

            <!-- Stat Cards -->
            <div class="stats-grid" style="margin-bottom:20px">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-value"><?= count($users) ?></div>
                        <div class="stat-sub">active accounts</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f0e6ff; color:#7c3aed">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Managers</div>
                        <div class="stat-value"><?= $total_managers ?></div>
                        <div class="stat-sub">manager accounts</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Cashiers</div>
                        <div class="stat-value"><?= $total_cashiers ?></div>
                        <div class="stat-sub">cashier accounts</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="stat-label">Total Transactions</div>
                        <div class="stat-value"><?= array_sum(array_column($users, 'total_orders')) ?></div>
                        <div class="stat-sub">all users combined</div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">All Accounts</span>
                    <span class="text-muted" style="font-size:0.8rem"><?= count($users) ?> users</span>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th class="text-right">Orders</th>
                                <th class="text-right">Total Sales</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u):
                            $isMe = (int)$u['user_id'] === (int)$user['user_id'];
                        ?>
                        <tr>
                            <td>
                                <span class="avatar avatar-<?= $u['role'] ?>">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </span>
                                <span class="font-bold"><?= htmlspecialchars($u['username']) ?></span>
                                <?php if ($isMe): ?>
                                    <span class="you-badge">You</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $u['role'] ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="text-right text-mono"><?= number_format($u['total_orders']) ?></td>
                            <td class="text-right font-bold text-primary">
                                ₱<?= number_format($u['total_sales'], 2) ?>
                            </td>
                            <td class="text-muted">
                                <?= $u['created_at'] ? date('M j, Y', strtotime($u['created_at'])) : '—' ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-outline btn-sm"
                                        onclick="openEdit(
                                            <?= $u['user_id'] ?>,
                                            '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>',
                                            '<?= $u['role'] ?>'
                                        )">Edit</button>
                                    <button class="btn btn-outline btn-sm"
                                        onclick="openResetPw(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                        🔑 Reset PW
                                    </button>
                                    <?php if (!$isMe && $u['total_orders'] == 0): ?>
                                    <button class="btn btn-danger btn-sm"
                                        onclick="deleteUser(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                                        Delete
                                    </button>
                                    <?php elseif (!$isMe): ?>
                                    <button class="btn btn-outline btn-sm" disabled
                                        title="Cannot delete — has transaction records"
                                        style="opacity:0.4; cursor:not-allowed">
                                        Delete
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Add User</span>
            <button class="modal-close" data-modal-close="modal-add">✕</button>
        </div>
        <div class="modal-body">
            <div id="err-add" class="alert alert-danger" style="display:none"></div>
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" id="add-username" placeholder="e.g. juan_cashier">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-control" id="add-role">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="pw-wrap">
                    <input type="password" class="form-control" id="add-pw" placeholder="Min. 6 characters">
                    <button type="button" class="pw-eye" onclick="togglePw('add-pw', this)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Confirm Password</label>
                <div class="pw-wrap">
                    <input type="password" class="form-control" id="add-pw2" placeholder="Repeat password">
                    <button type="button" class="pw-eye" onclick="togglePw('add-pw2', this)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-modal-close="modal-add">Cancel</button>
            <button class="btn btn-primary" onclick="saveAdd()">Add User</button>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">✏️ Edit User</span>
            <button class="modal-close" data-modal-close="modal-edit">✕</button>
        </div>
        <div class="modal-body">
            <div id="err-edit" class="alert alert-danger" style="display:none"></div>
            <input type="hidden" id="edit-id">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" id="edit-username">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Role</label>
                <select class="form-control" id="edit-role">
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-modal-close="modal-edit">Cancel</button>
            <button class="btn btn-primary" onclick="saveEdit()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="modal-reset-pw">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">🔑 Reset Password</span>
            <button class="modal-close" data-modal-close="modal-reset-pw">✕</button>
        </div>
        <div class="modal-body">
            <div id="err-reset" class="alert alert-danger" style="display:none"></div>
            <div id="reset-for" class="alert alert-info" style="margin-bottom:16px"></div>
            <input type="hidden" id="reset-id">
            <div class="form-group">
                <label class="form-label">New Password</label>
                <div class="pw-wrap">
                    <input type="password" class="form-control" id="reset-pw" placeholder="Min. 6 characters">
                    <button type="button" class="pw-eye" onclick="togglePw('reset-pw', this)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Confirm New Password</label>
                <div class="pw-wrap">
                    <input type="password" class="form-control" id="reset-pw2" placeholder="Repeat new password">
                    <button type="button" class="pw-eye" onclick="togglePw('reset-pw2', this)">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" data-modal-close="modal-reset-pw">Cancel</button>
            <button class="btn btn-primary" onclick="saveResetPw()">Reset Password</button>
        </div>
    </div>
</div>

<script src="/pos_system/assets/js/main.js"></script>
<script src="/pos_system/assets/js/users.js"></script>
</body>
</html>
