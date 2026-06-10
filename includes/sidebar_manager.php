<?php
//  includes/sidebar_manager.php
//  I-include sa lahat ng manager pages

$user    = getCurrentUser();
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="store-name"> Sari-Sari Store</div>
        <div class="store-sub">Manager Panel</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>

        <a href="/pos_system/manager/dashboard.php"
           class="nav-item <?= $current === 'dashboard.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <a href="/pos_system/manager/reports.php"
           class="nav-item <?= $current === 'reports.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6"  y1="20" x2="6"  y2="14"/>
            </svg>
            Reports
        </a>

        <div class="nav-section-label" style="margin-top:8px">Management</div>

        <a href="/pos_system/manager/products.php"
           class="nav-item <?= $current === 'products.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            Products
        </a>

        <a href="/pos_system/manager/users.php"
           class="nav-item <?= $current === 'users.php' ? 'active' : '' ?>">
            <svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Users
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars($user['username']) ?></div>
                <div class="user-role">Manager</div>
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
