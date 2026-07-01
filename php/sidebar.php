<?php
// sidebar.php — collapsible navigation component
// Expects $active_view (string) — matches nav item keys
$active_view = $active_view ?? '';
$role        = $_SESSION['user_role'] ?? '';
$user_name   = $_SESSION['user_name'] ?? 'User';
$user_id     = $_SESSION['user_id']   ?? '';

$initials = '';
$parts    = explode(' ', $user_name);
foreach (array_slice($parts, 0, 2) as $p) $initials .= strtoupper($p[0] ?? '');
?>
<nav class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">CP</div>
        <div class="brand-text">
            <strong>Cabin Putih</strong>
            <span>Ordering System</span>
        </div>
    </div>

    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div class="sidebar-nav">
        <?php if ($role === 'customer'): ?>
        <div class="nav-section-label">Menu</div>

        <button class="nav-item <?= $active_view === 'order' ? 'active' : '' ?>" onclick="showView('order')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
            <span class="nav-label">Order Food</span>
        </button>

        <button class="nav-item <?= $active_view === 'tracker' ? 'active' : '' ?>" onclick="showView('tracker')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span>
            <span class="nav-label">Order Tracker</span>
        </button>

        <div class="nav-section-label">Account</div>

        <button class="nav-item <?= $active_view === 'profile' ? 'active' : '' ?>" onclick="showView('profile')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <span class="nav-label">My Profile</span>
        </button>

        <?php elseif ($role === 'staff'): ?>
        <div class="nav-section-label">Operations</div>

        <button class="nav-item <?= $active_view === 'pos' ? 'active' : '' ?>" onclick="showView('pos')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span>
            <span class="nav-label">POS Register</span>
        </button>

        <button class="nav-item <?= $active_view === 'menu' ? 'active' : '' ?>" onclick="showView('menu')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></span>
            <span class="nav-label">Menu CRUD</span>
        </button>

        <button class="nav-item <?= $active_view === 'orders' ? 'active' : '' ?>" onclick="showView('orders')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></span>
            <span class="nav-label">Order Hub</span>
        </button>

        <div class="nav-section-label">Analytics</div>

        <button class="nav-item <?= $active_view === 'analytics' ? 'active' : '' ?>" onclick="showView('analytics')">
            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
            <span class="nav-label">Manager Analytics</span>
        </button>

        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars(mb_strimwidth($user_name, 0, 22, '…')) ?></div>
                <div class="role"><?= htmlspecialchars($user_id) ?></div>
            </div>
        </div>
    </div>
</nav>

<script>
(function() {
    const sidebar  = document.getElementById('appSidebar');
    const toggle   = document.getElementById('sidebarToggle');
    const main     = document.querySelector('.main-content');
    const collapsed = localStorage.getItem('sidebarCollapsed') === '1';

    if (collapsed) { sidebar.classList.add('collapsed'); if(main) main.classList.add('expanded'); }

    toggle.addEventListener('click', () => {
        const isNowCollapsed = sidebar.classList.toggle('collapsed');
        if(main) main.classList.toggle('expanded', isNowCollapsed);
        localStorage.setItem('sidebarCollapsed', isNowCollapsed ? '1' : '0');
    });
})();

function showView(id) {
    document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
    const el = document.getElementById('view-' + id);
    if (el) el.classList.add('active');
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    event.currentTarget.classList.add('active');
}
</script>
