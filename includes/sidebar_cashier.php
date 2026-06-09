<?php
//  includes/sidebar_cashier.php
//  I-include sa lahat ng cashier pages

$user = getCurrentUser();
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="store-name"> Sari-Sari Store</div>
        <div class="store-sub">POS System</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Menu</div>

        <a href="/pos_system/cashier/dashboard.php"
           class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <a href="/pos_system/cashier/pos.php"
           class="nav-item <?= $current === 'pos.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            Point of Sale
        </a>

        <a href="/pos_system/cashier/transactions.php"
           class="nav-item <?= $current === 'transactions.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            Transactions
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                <div class="user-role">Cashier</div>
            </div>
        </div>
        <a href="/pos_system/auth/logout.php" class="btn-logout">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</aside>
