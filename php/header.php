<?php
// header.php — top bar component
// Expects $page_title (string) and $breadcrumb (string, optional)
$page_title  = $page_title  ?? 'Dashboard';
$breadcrumb  = $breadcrumb  ?? '';
$user_name   = $_SESSION['user_name']    ?? 'Guest';
$user_role   = $_SESSION['user_role']    ?? '';
$cart_count  = cart_count();
?>
<header class="top-header">
    <div class="header-title">
        <?php if ($breadcrumb): ?>
            <span class="breadcrumb"><?= htmlspecialchars($breadcrumb) ?> /</span>
        <?php endif; ?>
        <?= htmlspecialchars($page_title) ?>
    </div>
    <div class="header-actions">
        <?php if ($user_role === 'customer' && $cart_count > 0): ?>
        <span class="header-badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <?= $cart_count ?> item<?= $cart_count > 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
        <span class="header-badge" style="background:var(--bg-elevated);border-color:var(--border);color:var(--text-secondary);">
            <?php if ($user_role === 'staff'): ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Staff
            <?php else: ?>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Customer
            <?php endif; ?>
        </span>
        <form method="post" action="login.php" style="margin:0;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:0.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </form>
    </div>
</header>
