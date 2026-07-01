<?php
require_once 'db_connect.php';
require_customer();

// ── Cart AJAX/POST actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $item_id = trim($_POST['item_id'] ?? '');
        $qty     = max(1, (int)($_POST['qty'] ?? 1));
        cart_add($item_id, $qty);
        header('Location: dashboard_customer.php?view=order&msg=added');
        exit;
    }

    if ($action === 'remove_from_cart') {
        cart_remove(trim($_POST['item_id'] ?? ''));
        header('Location: dashboard_customer.php?view=order');
        exit;
    }

    if ($action === 'clear_cart') {
        cart_clear();
        header('Location: dashboard_customer.php?view=order');
        exit;
    }

    if ($action === 'checkout') {
        $pay_type    = $_POST['pay_type']    ?? 'Cash';
        $dining_type = $_POST['dining_type'] ?? 'Dine-In';
        $cart        = $_SESSION['cart'] ?? [];
        if ($cart) {
            $order_id = get_next_order_id();
            $pay_id   = get_next_pay_id();
            $details  = [];
            $total    = 0;
            foreach ($cart as $row) {
                $sub       = round($row['price'] * $row['qty'], 2);
                $total    += $sub;
                $details[] = ['item_id' => $row['item_id'], 'qty' => $row['qty'], 'subtotal' => $sub];
            }
            $new_order = [
                'order_id'    => $order_id,
                'dining_type' => $dining_type,
                'pay_id'      => $pay_id,
                'pay_type'    => $pay_type,
                'amount'      => round($total, 2),
                'pos'         => 'POS-SELF',
                'date'        => date('Y-m-d'),
                'emp_id'      => 'EMP002',
                'cust_id'     => $_SESSION['user_id'],
                'status'      => 'Received',
                'details'     => $details,
            ];
            $r = api_new_order($new_order);
            if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
            $_SESSION['orders'][] = $new_order;
            cart_clear();
            header('Location: dashboard_customer.php?view=tracker&msg=placed&oid=' . $order_id);
            exit;
        }
        header('Location: dashboard_customer.php?view=order&msg=empty');
        exit;
    }

    if ($action === 'cancel_order') {
        $oid = trim($_POST['order_id'] ?? '');
        if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
        foreach ($_SESSION['orders'] as $k => $o) {
            if ($o['order_id'] === $oid && $o['cust_id'] === $_SESSION['user_id'] && ($o['status'] ?? '') === 'Received') {
                api_delete('orders', $oid);
                unset($_SESSION['orders'][$k]);
                $_SESSION['orders'] = array_values($_SESSION['orders']);
                break;
            }
        }
        header('Location: dashboard_customer.php?view=tracker&msg=cancelled');
        exit;
    }

    if ($action === 'update_profile') {
        $first = strtoupper(trim($_POST['p_first'] ?? ''));
        $last  = strtoupper(trim($_POST['p_last']  ?? ''));
        $phone = trim($_POST['p_phone'] ?? '');
        $pass  = $_POST['p_password'] ?? '';
        if (!isset($_SESSION['customers'])) $_SESSION['customers'] = get_fallback_customers();
        foreach ($_SESSION['customers'] as &$c) {
            if ($c['id'] === $_SESSION['user_id']) {
                $c['first_name'] = $first ?: $c['first_name'];
                $c['last_name']  = $last  ?: $c['last_name'];
                $c['phone']      = $phone ?: $c['phone'];
                if ($pass) $c['password'] = $pass;
                api_put('customer', $c['id'], $c);
                $_SESSION['user_name']  = trim($c['first_name'] . ' ' . $c['last_name']);
                $_SESSION['user_phone'] = $c['phone'];
                break;
            }
        }
        header('Location: dashboard_customer.php?view=profile&msg=updated');
        exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$items     = fetch_items();
$all_orders = fetch_orders();
$cust_id   = $_SESSION['user_id'];
$my_orders = array_values(array_filter($all_orders, fn($o) => ($o['cust_id'] ?? '') === $cust_id));

$my_profile = null;
foreach (fetch_customers() as $c) {
    if ($c['id'] === $cust_id) { $my_profile = $c; break; }
}

$active_view = $_GET['view'] ?? 'order';
$msg         = $_GET['msg']  ?? '';

$page_title = 'Customer Dashboard';
$breadcrumb = 'Cabin Putih';
$active_view_nav = $active_view;

// Status pipeline labels
$status_steps = ['Received', 'Preparing', 'Ready for Pickup', 'Completed'];

// Pexels food image map by item ID (deterministic, no download)
$img_map = [
    'ITM001' => 'https://images.pexels.com/photos/1251198/pexels-photo-1251198.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM002' => 'https://images.pexels.com/photos/2338407/pexels-photo-2338407.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM003' => 'https://images.pexels.com/photos/60616/fried-chicken-chicken-fried-crunchy-60616.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM004' => 'https://images.pexels.com/photos/1583884/pexels-photo-1583884.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM005' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM006' => 'https://images.pexels.com/photos/723198/pexels-photo-723198.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM007' => 'https://images.pexels.com/photos/2474658/pexels-photo-2474658.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM008' => 'https://images.pexels.com/photos/3590401/pexels-photo-3590401.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM009' => 'https://images.pexels.com/photos/1437267/pexels-photo-1437267.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM010' => 'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM011' => 'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM012' => 'https://images.pexels.com/photos/4518843/pexels-photo-4518843.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM013' => 'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM014' => 'https://images.pexels.com/photos/1199957/pexels-photo-1199957.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM015' => 'https://images.pexels.com/photos/2097090/pexels-photo-2097090.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM016' => 'https://images.pexels.com/photos/302899/pexels-photo-302899.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM017' => 'https://images.pexels.com/photos/3026804/pexels-photo-3026804.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM018' => 'https://images.pexels.com/photos/792613/pexels-photo-792613.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM019' => 'https://images.pexels.com/photos/416528/pexels-photo-416528.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM020' => 'https://images.pexels.com/photos/312418/pexels-photo-312418.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cabin Putih — My Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
<?php include 'header.php'; ?>

<main class="page-body">

<?php if ($msg === 'added'): ?>
<div class="alert alert-success" style="margin-bottom:16px;">Item added to cart!</div>
<?php elseif ($msg === 'empty'): ?>
<div class="alert alert-danger" style="margin-bottom:16px;">Your cart is empty.</div>
<?php elseif ($msg === 'placed'): ?>
<div class="alert alert-success" style="margin-bottom:16px;">Order <?= htmlspecialchars($_GET['oid'] ?? '') ?> placed successfully!</div>
<?php elseif ($msg === 'cancelled'): ?>
<div class="alert alert-info" style="margin-bottom:16px;">Order cancelled.</div>
<?php elseif ($msg === 'updated'): ?>
<div class="alert alert-success" style="margin-bottom:16px;">Profile updated.</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════
     VIEW: ORDER FOOD
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'order' ? 'active' : '' ?>" id="view-order">
    <div class="page-header">
        <div>
            <h2>Order Food</h2>
            <div class="subtitle">Browse our menu and build your order</div>
        </div>
    </div>

    <div class="two-col-layout">
        <!-- Menu Grid -->
        <div>
            <div class="menu-grid">
            <?php foreach ($items as $item):
                $id    = $item['id'] ?? '';
                $name  = $item['name'] ?? '';
                $price = (float)($item['price'] ?? 0);
                $qty   = (int)($item['qty'] ?? 0);
                $oos   = $qty === 0;
                $img   = $img_map[$id] ?? '';
            ?>
            <div class="menu-card <?= $oos ? 'out-of-stock' : '' ?>">
                <div class="menu-img-box">
                    <?php if ($img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="menu-img-placeholder">&#127860;</div>
                    <?php endif; ?>
                    <?php if ($oos): ?>
                    <div class="oos-overlay"><span class="oos-label">Currently<br>Unavailable</span></div>
                    <?php endif; ?>
                </div>
                <div class="menu-card-body">
                    <div class="menu-card-name"><?= htmlspecialchars($name) ?></div>
                    <div class="menu-card-price">RM <?= number_format($price, 2) ?></div>
                    <div class="menu-card-meta">
                        <span class="tag <?= $oos ? 'tag' : 'tag-amber' ?>"><?= $oos ? 'Out of Stock' : 'Available' ?></span>
                        <span class="stock-label" style="font-size:0.7rem;color:var(--text-muted);"><?= $oos ? '' : 'Qty: '.$qty ?></span>
                    </div>
                    <?php if (!$oos): ?>
                    <form method="post" style="margin-top:8px;">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                        <div class="qty-control" style="margin-bottom:6px;">
                            <button type="button" class="qty-btn" onclick="changeQty(this,-1)">&#8722;</button>
                            <input type="number" name="qty" class="qty-input" value="1" min="1" max="<?= $qty ?>">
                            <button type="button" class="qty-btn" onclick="changeQty(this,1)">&#43;</button>
                        </div>
                        <button type="submit" class="btn-add-cart">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            Add to Cart
                        </button>
                    </form>
                    <?php else: ?>
                    <button class="btn-add-cart" disabled style="margin-top:8px;opacity:0.35;">Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart Panel -->
        <div>
            <div class="cart-panel">
                <div class="cart-title">
                    My Cart
                    <span class="cart-badge"><?= cart_count() ?></span>
                </div>

                <?php $cart = $_SESSION['cart'] ?? []; ?>
                <?php if (empty($cart)): ?>
                <div class="cart-empty">Your cart is empty.<br>Add items from the menu.</div>
                <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart as $row): ?>
                    <div class="cart-row">
                        <span class="cart-item-name"><?= htmlspecialchars($row['name']) ?></span>
                        <span class="cart-item-qty">x<?= $row['qty'] ?></span>
                        <span class="cart-item-price">RM <?= number_format($row['price'] * $row['qty'], 2) ?></span>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="remove_from_cart">
                            <input type="hidden" name="item_id" value="<?= htmlspecialchars($row['item_id']) ?>">
                            <button type="submit" class="cart-remove" title="Remove">&times;</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr class="cart-divider">
                <div class="cart-total-row" style="margin-bottom:14px;">
                    <span class="cart-total-label">Total</span>
                    <span class="cart-total-value">RM <?= number_format(cart_total(), 2) ?></span>
                </div>

                <form method="post" style="display:inline;margin-right:6px;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="btn btn-secondary btn-sm">Clear Cart</button>
                </form>
                <button class="btn btn-primary btn-sm" onclick="openCheckoutModal()">Proceed to Checkout</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW: ORDER TRACKER
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'tracker' ? 'active' : '' ?>" id="view-tracker">
    <div class="page-header">
        <div>
            <h2>My Order Tracker</h2>
            <div class="subtitle">Live status of your orders</div>
        </div>
    </div>

    <?php if (empty($my_orders)): ?>
    <div class="card" style="text-align:center;padding:40px;">
        <p style="color:var(--text-muted);">No orders found. Place your first order!</p>
        <button class="btn btn-primary mt-16" onclick="showView('order')">Order Now</button>
    </div>
    <?php else: ?>
    <?php foreach (array_reverse($my_orders) as $ord):
        $status     = $ord['status'] ?? 'Received';
        $step_index = array_search($status, $status_steps);
        if ($step_index === false) $step_index = 0;
        $can_cancel = $status === 'Received';
    ?>
    <div class="order-track-card">
        <div>
            <div class="order-track-id"><?= htmlspecialchars($ord['order_id']) ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);"><?= htmlspecialchars($ord['date'] ?? '') ?> &bull; <?= htmlspecialchars($ord['dining_type'] ?? '') ?></div>
        </div>
        <div class="order-track-info">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                <?php foreach ($status_steps as $si => $step): ?>
                <?php if ($si > 0): ?><div class="progress-line <?= $si <= $step_index ? 'done' : '' ?>"></div><?php endif; ?>
                <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                    <div class="progress-dot <?= $si < $step_index ? 'done' : ($si === $step_index ? 'active' : '') ?>"></div>
                    <span style="font-size:0.6rem;color:var(--text-muted);white-space:nowrap;"><?= htmlspecialchars($step) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="order-track-items">
                <?php
                $item_names = [];
                foreach ($ord['details'] ?? [] as $d) {
                    $item_names[] = get_item_name($d['item_id']) . ' x' . $d['qty'];
                }
                echo htmlspecialchars(implode(', ', $item_names));
                ?>
            </div>
        </div>
        <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:1rem;font-weight:700;color:var(--amber);">RM <?= number_format($ord['amount'] ?? 0, 2) ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:6px;"><?= htmlspecialchars($ord['pay_type'] ?? '') ?></div>
            <?php if ($can_cancel): ?>
            <form method="post" onsubmit="return confirm('Cancel this order?')">
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($ord['order_id']) ?>">
                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
            </form>
            <?php else: ?>
            <span class="badge <?= $status === 'Completed' ? 'badge-success' : ($status === 'Ready for Pickup' ? 'badge-info' : 'badge-warning') ?>"><?= htmlspecialchars($status) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW: PROFILE
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'profile' ? 'active' : '' ?>" id="view-profile">
    <div class="page-header">
        <div>
            <h2>My Profile</h2>
            <div class="subtitle">Update your personal details</div>
        </div>
    </div>
    <div class="card" style="max-width:500px;">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Account Information
        </div>
        <?php if ($my_profile): ?>
        <form method="post">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="p_first" class="form-control" value="<?= htmlspecialchars($my_profile['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="p_last" class="form-control" value="<?= htmlspecialchars($my_profile['last_name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="p_phone" class="form-control" value="<?= htmlspecialchars($my_profile['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">New Password <span style="color:var(--text-muted);font-weight:400;">(leave blank to keep current)</span></label>
                <input type="password" name="p_password" class="form-control" placeholder="Enter new password">
            </div>
            <div style="display:flex;gap:10px;margin-top:6px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
        <hr class="divider">
        <div style="font-size:0.78rem;color:var(--text-muted);">
            Customer ID: <span style="color:var(--amber);font-family:monospace;"><?= htmlspecialchars($cust_id) ?></span>
        </div>
        <?php else: ?>
        <p style="color:var(--text-muted);">Profile not found.</p>
        <?php endif; ?>
    </div>
</div>

</main>
<?php include 'footer.php'; ?>
</div><!-- .main-content -->
</div><!-- .app-shell -->

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal-box">
        <div class="modal-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Checkout
        </div>
        <button class="modal-close" onclick="document.getElementById('checkoutModal').classList.remove('open')">&times;</button>

        <!-- Cart Summary -->
        <div class="checkout-summary">
            <?php foreach ($_SESSION['cart'] ?? [] as $row): ?>
            <div class="checkout-line">
                <span><?= htmlspecialchars($row['name']) ?> &times;<?= $row['qty'] ?></span>
                <span>RM <?= number_format($row['price'] * $row['qty'], 2) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="checkout-line total">
                <span>Total</span>
                <span>RM <?= number_format(cart_total(), 2) ?></span>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="checkout">
            <div class="form-group">
                <label class="form-label">Dining Type</label>
                <select name="dining_type" class="form-control">
                    <option>Dine-In</option>
                    <option>Take-Away</option>
                    <option>Delivery</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select name="pay_type" class="form-control">
                    <option>Cash</option>
                    <option>DuitNow QR</option>
                    <option>Credit Card</option>
                    <option>E-Wallet</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('checkoutModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Place Order</button>
            </div>
        </form>
    </div>
</div>

<script>
function changeQty(btn, delta) {
    const inp = btn.closest('.qty-control').querySelector('input[type=number]');
    const min = parseInt(inp.min) || 1;
    const max = parseInt(inp.max) || 999;
    inp.value = Math.min(max, Math.max(min, parseInt(inp.value) + delta));
}
function openCheckoutModal() {
    <?php if (empty($_SESSION['cart'])): ?>
    showToast('Your cart is empty!', 'error');
    <?php else: ?>
    document.getElementById('checkoutModal').classList.add('open');
    <?php endif; ?>
}
document.getElementById('checkoutModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});
<?php if ($msg === 'added'): ?>
setTimeout(() => showToast('Item added to cart!', 'success'), 200);
<?php endif; ?>
</script>
</body>
</html>
