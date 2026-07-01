<?php
require_once 'db_connect.php';
require_staff();

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // POS Cart
    if ($action === 'pos_add') {
        $item_id = trim($_POST['item_id'] ?? '');
        $qty     = max(1, (int)($_POST['qty'] ?? 1));
        cart_add($item_id, $qty);
        header('Location: dashboard_staff.php?view=pos');
        exit;
    }
    if ($action === 'pos_remove') {
        cart_remove(trim($_POST['item_id'] ?? ''));
        header('Location: dashboard_staff.php?view=pos');
        exit;
    }
    if ($action === 'pos_clear') {
        cart_clear();
        header('Location: dashboard_staff.php?view=pos');
        exit;
    }
    if ($action === 'pos_checkout') {
        $pay_type    = $_POST['pay_type']    ?? 'Cash';
        $dining_type = $_POST['dining_type'] ?? 'Dine-In';
        $cust_id_sel = $_POST['cust_id']     ?? 'CUS001';
        $pos_id      = $_POST['pos_id']      ?? 'POS-01';
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
                'pos'         => $pos_id,
                'date'        => date('Y-m-d'),
                'emp_id'      => $_SESSION['user_id'],
                'cust_id'     => $cust_id_sel,
                'status'      => 'Received',
                'details'     => $details,
            ];
            $r = api_new_order($new_order);
            if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
            $_SESSION['orders'][] = $new_order;
            cart_clear();
            header('Location: dashboard_staff.php?view=pos&msg=placed&oid=' . $order_id);
            exit;
        }
        header('Location: dashboard_staff.php?view=pos&msg=empty');
        exit;
    }

    // Menu CRUD
    if ($action === 'add_item') {
        $new_id = get_next_item_id();
        $new_item = [
            'id'          => $new_id,
            'name'        => trim($_POST['item_name'] ?? ''),
            'price'       => (float)($_POST['item_price'] ?? 0),
            'qty'         => (int)($_POST['item_qty'] ?? 0),
            'status'      => $_POST['item_status'] ?? 'Available',
            'supplier_id' => trim($_POST['item_supplier'] ?? ''),
        ];
        api_post('item', $new_item);
        if (!isset($_SESSION['items'])) $_SESSION['items'] = get_fallback_items();
        $_SESSION['items'][] = $new_item;
        header('Location: dashboard_staff.php?view=menu&msg=added');
        exit;
    }
    if ($action === 'edit_item') {
        $id = trim($_POST['item_id'] ?? '');
        if (!isset($_SESSION['items'])) $_SESSION['items'] = get_fallback_items();
        foreach ($_SESSION['items'] as &$it) {
            if ($it['id'] === $id) {
                $it['name']        = trim($_POST['item_name']     ?? $it['name']);
                $it['price']       = (float)($_POST['item_price'] ?? $it['price']);
                $it['qty']         = (int)($_POST['item_qty']     ?? $it['qty']);
                $it['status']      = $_POST['item_status']        ?? $it['status'];
                $it['supplier_id'] = trim($_POST['item_supplier'] ?? $it['supplier_id']);
                api_put('item', $id, $it);
                break;
            }
        }
        header('Location: dashboard_staff.php?view=menu&msg=updated');
        exit;
    }
    if ($action === 'delete_item') {
        $id = trim($_POST['item_id'] ?? '');
        api_delete('item', $id);
        if (!isset($_SESSION['items'])) $_SESSION['items'] = get_fallback_items();
        $_SESSION['items'] = array_values(array_filter($_SESSION['items'], fn($it) => $it['id'] !== $id));
        header('Location: dashboard_staff.php?view=menu&msg=deleted');
        exit;
    }

    // Order management
    if ($action === 'update_order_status') {
        $oid    = trim($_POST['order_id'] ?? '');
        $status = $_POST['new_status'] ?? '';
        $valid  = ['Received','Preparing','Ready for Pickup','Completed'];
        if (in_array($status, $valid)) {
            if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
            foreach ($_SESSION['orders'] as &$o) {
                if ($o['order_id'] === $oid) {
                    $o['status'] = $status;
                    api_put('orders', $oid, $o);
                    break;
                }
            }
        }
        header('Location: dashboard_staff.php?view=orders');
        exit;
    }
    if ($action === 'cancel_order_staff') {
        $oid = trim($_POST['order_id'] ?? '');
        api_delete('orders', $oid);
        if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
        $_SESSION['orders'] = array_values(array_filter($_SESSION['orders'], fn($o) => $o['order_id'] !== $oid));
        header('Location: dashboard_staff.php?view=orders&msg=cancelled');
        exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$items      = fetch_items();
$orders     = fetch_orders();
$customers  = fetch_customers();
$suppliers  = fetch_suppliers();
$employees  = fetch_employees();

$active_view = $_GET['view'] ?? 'pos';
$msg         = $_GET['msg']  ?? '';
$page_title  = 'Staff Dashboard';
$breadcrumb  = 'Cabin Putih';
$active_view_nav = $active_view;

// Build customer lookup map
$cust_map = [];
foreach ($customers as $c) $cust_map[$c['id']] = trim($c['first_name'] . ' ' . $c['last_name']);

// ── Analytics calculations ────────────────────────────────────────────────────
$total_revenue  = 0;
$total_orders   = count($orders);
$dining_counts  = [];
$pay_counts     = [];
foreach ($orders as $o) {
    $total_revenue += (float)($o['amount'] ?? 0);
    $dt = $o['dining_type'] ?? 'Other';
    $pt = $o['pay_type']    ?? 'Other';
    $dining_counts[$dt] = ($dining_counts[$dt] ?? 0) + 1;
    $pay_counts[$pt]    = ($pay_counts[$pt]    ?? 0) + 1;
}
arsort($dining_counts);
arsort($pay_counts);
$avg_order = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// OOS items
$oos_items = array_values(array_filter($items, fn($it) => (int)($it['qty'] ?? 0) === 0));

// Pexels image map
$img_map = [
    'ITM001'=>'https://images.pexels.com/photos/1251198/pexels-photo-1251198.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM002'=>'https://images.pexels.com/photos/2338407/pexels-photo-2338407.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM003'=>'https://images.pexels.com/photos/60616/fried-chicken-chicken-fried-crunchy-60616.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM004'=>'https://images.pexels.com/photos/1583884/pexels-photo-1583884.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM005'=>'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM006'=>'https://images.pexels.com/photos/723198/pexels-photo-723198.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM007'=>'https://images.pexels.com/photos/2474658/pexels-photo-2474658.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM008'=>'https://images.pexels.com/photos/3590401/pexels-photo-3590401.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM009'=>'https://images.pexels.com/photos/1437267/pexels-photo-1437267.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM010'=>'https://images.pexels.com/photos/1279330/pexels-photo-1279330.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM011'=>'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM012'=>'https://images.pexels.com/photos/4518843/pexels-photo-4518843.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM013'=>'https://images.pexels.com/photos/1640777/pexels-photo-1640777.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM014'=>'https://images.pexels.com/photos/1199957/pexels-photo-1199957.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM015'=>'https://images.pexels.com/photos/2097090/pexels-photo-2097090.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM016'=>'https://images.pexels.com/photos/302899/pexels-photo-302899.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM017'=>'https://images.pexels.com/photos/3026804/pexels-photo-3026804.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM018'=>'https://images.pexels.com/photos/792613/pexels-photo-792613.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM019'=>'https://images.pexels.com/photos/416528/pexels-photo-416528.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
    'ITM020'=>'https://images.pexels.com/photos/312418/pexels-photo-312418.jpeg?auto=compress&cs=tinysrgb&w=120&h=120&dpr=1',
];

$status_steps = ['Received','Preparing','Ready for Pickup','Completed'];
$status_colors = ['Received'=>'badge-warning','Preparing'=>'badge-info','Ready for Pickup'=>'badge-amber','Completed'=>'badge-success'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cabin Putih — Staff Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">

<?php include 'sidebar.php'; ?>

<div class="main-content" id="mainContent">
<?php include 'header.php'; ?>

<main class="page-body">

<?php
$alerts = ['placed'=>'<div class="alert alert-success" style="margin-bottom:16px;">Order '.(htmlspecialchars($_GET['oid'] ?? '')).' placed successfully!</div>',
           'empty'=>'<div class="alert alert-danger" style="margin-bottom:16px;">Cart is empty.</div>',
           'added'=>'<div class="alert alert-success" style="margin-bottom:16px;">Item added to menu.</div>',
           'updated'=>'<div class="alert alert-success" style="margin-bottom:16px;">Item updated.</div>',
           'deleted'=>'<div class="alert alert-danger" style="margin-bottom:16px;">Item deleted.</div>',
           'cancelled'=>'<div class="alert alert-info" style="margin-bottom:16px;">Order cancelled.</div>'];
if ($msg && isset($alerts[$msg])) echo $alerts[$msg];
?>

<!-- ════════════════════════════════════════════════════════
     VIEW: POS REGISTER
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'pos' ? 'active' : '' ?>" id="view-pos">
    <div class="page-header">
        <div><h2>POS Cashier Register</h2><div class="subtitle">Process customer orders at the counter</div></div>
    </div>
    <div class="pos-layout">
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
                        <span class="tag <?= $oos ? '' : 'tag-amber' ?>"><?= $oos ? 'Out of Stock' : 'In Stock' ?></span>
                    </div>
                    <?php if (!$oos): ?>
                    <form method="post" style="margin-top:8px;">
                        <input type="hidden" name="action" value="pos_add">
                        <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                        <div class="qty-control" style="margin-bottom:6px;">
                            <button type="button" class="qty-btn" onclick="changeQty(this,-1)">&#8722;</button>
                            <input type="number" name="qty" class="qty-input" value="1" min="1" max="<?= $qty ?>">
                            <button type="button" class="qty-btn" onclick="changeQty(this,1)">&#43;</button>
                        </div>
                        <button type="submit" class="btn-add-cart">Add to Order</button>
                    </form>
                    <?php else: ?>
                    <button class="btn-add-cart" disabled style="margin-top:8px;opacity:0.35;">Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

        <!-- POS Cart -->
        <div>
            <div class="cart-panel">
                <div class="cart-title">Order Cart <span class="cart-badge"><?= cart_count() ?></span></div>
                <?php $cart = $_SESSION['cart'] ?? []; ?>
                <?php if (empty($cart)): ?>
                <div class="cart-empty">No items added.<br>Select from the menu.</div>
                <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart as $row): ?>
                    <div class="cart-row">
                        <span class="cart-item-name"><?= htmlspecialchars($row['name']) ?></span>
                        <span class="cart-item-qty">x<?= $row['qty'] ?></span>
                        <span class="cart-item-price">RM <?= number_format($row['price'] * $row['qty'], 2) ?></span>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="pos_remove">
                            <input type="hidden" name="item_id" value="<?= htmlspecialchars($row['item_id']) ?>">
                            <button type="submit" class="cart-remove">&times;</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <hr class="cart-divider">
                <div class="cart-total-row" style="margin-bottom:12px;">
                    <span class="cart-total-label">Total</span>
                    <span class="cart-total-value">RM <?= number_format(cart_total(), 2) ?></span>
                </div>
                <div style="display:flex;gap:8px;margin-bottom:8px;">
                    <form method="post" style="flex:1;">
                        <input type="hidden" name="action" value="pos_clear">
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;">Clear</button>
                    </form>
                    <button class="btn btn-primary btn-sm" style="flex:2;" onclick="document.getElementById('posCheckoutModal').classList.add('open')">Checkout</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW: MENU CRUD
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'menu' ? 'active' : '' ?>" id="view-menu">
    <div class="page-header">
        <div><h2>Menu Item Administration</h2><div class="subtitle">Add, edit, or remove menu items</div></div>
        <button class="btn btn-primary" onclick="document.getElementById('addItemModal').classList.add('open')">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Item
        </button>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Supplier</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item):
                $id      = $item['id'] ?? '';
                $name    = $item['name'] ?? '';
                $price   = (float)($item['price'] ?? 0);
                $qty     = (int)($item['qty'] ?? 0);
                $status  = $item['status'] ?? 'Available';
                $sup_id  = $item['supplier_id'] ?? '';
                $img     = $img_map[$id] ?? '';
                $oos     = $qty === 0;
            ?>
            <tr>
                <td class="td-mono"><?= htmlspecialchars($id) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" style="width:36px;height:36px;border-radius:6px;object-fit:cover;border:1px solid var(--border);" loading="lazy" alt="">
                        <?php endif; ?>
                        <span><?= htmlspecialchars($name) ?></span>
                    </div>
                </td>
                <td style="color:var(--amber);font-weight:600;">RM <?= number_format($price, 2) ?></td>
                <td>
                    <?php if ($oos): ?>
                    <span class="badge badge-danger">0 — OOS</span>
                    <?php else: ?>
                    <span class="badge badge-success"><?= $qty ?></span>
                    <?php endif; ?>
                </td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($status) ?></span></td>
                <td class="td-mono" style="font-size:0.75rem;"><?= htmlspecialchars($sup_id) ?></td>
                <td>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= htmlspecialchars(json_encode(['id'=>$id,'name'=>$name,'price'=>$price,'qty'=>$qty,'status'=>$status,'supplier_id'=>$sup_id]), ENT_QUOTES) ?>)'>Edit</button>
                        <form method="post" onsubmit="return confirm('Delete this item?')" style="display:inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="item_id" value="<?= htmlspecialchars($id) ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW: ORDER HUB
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'orders' ? 'active' : '' ?>" id="view-orders">
    <div class="page-header">
        <div><h2>Order Management Hub</h2><div class="subtitle">Track and manage all transactions</div></div>
    </div>

    <div class="search-bar-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="search-input" id="orderSearch" placeholder="Search by Order ID (ORD001), Customer ID (CUS001), or Name..." oninput="filterOrders(this.value)">
    </div>

    <div class="table-wrapper">
        <table id="ordersTable">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>POS</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ordersBody">
            <?php foreach (array_reverse($orders) as $ord):
                $oid      = $ord['order_id']    ?? '';
                $cid      = $ord['cust_id']      ?? '';
                $cname    = $cust_map[$cid]      ?? $cid;
                $status   = $ord['status']       ?? 'Received';
                $bclass   = $status_colors[$status] ?? 'badge-neutral';
                $item_list = [];
                foreach ($ord['details'] ?? [] as $d) {
                    $item_list[] = get_item_name($d['item_id']) . ' x' . $d['qty'];
                }
            ?>
            <tr data-search="<?= strtolower(htmlspecialchars($oid . ' ' . $cid . ' ' . $cname)) ?>">
                <td class="td-mono"><?= htmlspecialchars($oid) ?></td>
                <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($ord['date'] ?? '') ?></td>
                <td>
                    <div style="font-size:0.83rem;font-weight:500;"><?= htmlspecialchars(mb_strimwidth($cname, 0, 22, '…')) ?></div>
                    <div class="td-mono" style="font-size:0.72rem;"><?= htmlspecialchars($cid) ?></div>
                </td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($ord['dining_type'] ?? '') ?></span></td>
                <td style="font-size:0.78rem;color:var(--text-muted);max-width:160px;"><?= htmlspecialchars(implode(', ', $item_list)) ?></td>
                <td style="color:var(--amber);font-weight:700;">RM <?= number_format($ord['amount'] ?? 0, 2) ?></td>
                <td style="font-size:0.8rem;"><?= htmlspecialchars($ord['pay_type'] ?? '') ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($ord['pos'] ?? '') ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="action" value="update_order_status">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($oid) ?>">
                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                            <?php foreach ($status_steps as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Cancel and remove this order?')" style="display:inline;">
                        <input type="hidden" name="action" value="cancel_order_staff">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($oid) ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="noOrderResults" style="display:none;text-align:center;padding:30px;color:var(--text-muted);">No orders match your search.</div>
</div>

<!-- ════════════════════════════════════════════════════════
     VIEW: MANAGER ANALYTICS
══════════════════════════════════════════════════════════ -->
<div class="view-section <?= $active_view === 'analytics' ? 'active' : '' ?>" id="view-analytics">
    <div class="page-header">
        <div><h2>Manager Analytics</h2><div class="subtitle">Sales metrics, inventory status, and supplier directory</div></div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card amber">
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value">RM <?= number_format($total_revenue, 2) ?></div>
            <div class="kpi-sub">All recorded transactions</div>
            <div class="kpi-icon">&#128181;</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-label">Total Orders</div>
            <div class="kpi-value"><?= $total_orders ?></div>
            <div class="kpi-sub">Across all channels</div>
            <div class="kpi-icon">&#128203;</div>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-label">Average Order Value</div>
            <div class="kpi-value">RM <?= number_format($avg_order, 2) ?></div>
            <div class="kpi-sub">Per transaction</div>
            <div class="kpi-icon">&#128200;</div>
        </div>
        <div class="kpi-card red">
            <div class="kpi-label">Out-of-Stock Items</div>
            <div class="kpi-value"><?= count($oos_items) ?></div>
            <div class="kpi-sub">Require restocking</div>
            <div class="kpi-icon">&#9888;</div>
        </div>
        <div class="kpi-card wood">
            <div class="kpi-label">Menu Items</div>
            <div class="kpi-value"><?= count($items) ?></div>
            <div class="kpi-sub">Active listings</div>
            <div class="kpi-icon">&#127860;</div>
        </div>
    </div>

    <!-- Dining & Payment Breakdown -->
    <div class="form-row" style="margin-bottom:20px;">
        <div class="card" style="margin-bottom:0;">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Orders by Dining Type
            </div>
            <?php foreach ($dining_counts as $type => $count): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span style="font-size:0.85rem;min-width:110px;color:var(--text-secondary);"><?= htmlspecialchars($type) ?></span>
                <div style="flex:1;background:var(--bg-elevated);border-radius:4px;height:10px;overflow:hidden;">
                    <div style="height:100%;background:var(--amber);border-radius:4px;width:<?= round($count / $total_orders * 100) ?>%;transition:width 0.5s;"></div>
                </div>
                <span style="font-size:0.82rem;color:var(--amber);font-weight:600;min-width:30px;text-align:right;"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card" style="margin-bottom:0;">
            <div class="card-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Orders by Payment Type
            </div>
            <?php foreach ($pay_counts as $type => $count): ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span style="font-size:0.85rem;min-width:120px;color:var(--text-secondary);"><?= htmlspecialchars($type) ?></span>
                <div style="flex:1;background:var(--bg-elevated);border-radius:4px;height:10px;overflow:hidden;">
                    <div style="height:100%;background:var(--wood-light);border-radius:4px;width:<?= round($count / $total_orders * 100) ?>%;transition:width 0.5s;"></div>
                </div>
                <span style="font-size:0.82rem;color:var(--wood-light);font-weight:600;min-width:30px;text-align:right;"><?= $count ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Inventory Matrix -->
    <div class="card">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Inventory Tracking Matrix
        </div>
        <div class="inventory-grid">
            <?php foreach ($items as $item):
                $qty  = (int)($item['qty'] ?? 0);
                $crit = $qty === 0;
                $low  = $qty > 0 && $qty <= 20;
                $qclass = $crit ? 'zero' : ($low ? 'low' : 'ok');
            ?>
            <div class="inventory-item <?= $crit ? 'critical' : '' ?>">
                <div class="inventory-item-name"><?= htmlspecialchars(mb_strimwidth($item['name'] ?? '', 0, 28, '…')) ?></div>
                <div style="font-size:0.7rem;color:var(--text-muted);margin-bottom:4px;"><?= htmlspecialchars($item['id'] ?? '') ?></div>
                <div class="inventory-item-qty <?= $qclass ?>"><?= $qty === 0 ? 'OUT' : $qty ?></div>
                <?php if ($crit): ?>
                <div style="font-size:0.7rem;color:var(--danger);margin-top:2px;">&#9888; Restock needed</div>
                <?php elseif ($low): ?>
                <div style="font-size:0.7rem;color:var(--warning);margin-top:2px;">&#9651; Low stock</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Supplier Manifest -->
    <div class="card">
        <div class="card-title">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Supplier Manifest
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Supplier Name</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Next Delivery</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td class="td-mono"><?= htmlspecialchars($s['id'] ?? '') ?></td>
                    <td style="font-weight:500;"><?= htmlspecialchars($s['name'] ?? '') ?></td>
                    <td><span class="tag tag-amber"><?= htmlspecialchars($s['type'] ?? '') ?></span></td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($s['phone'] ?? '') ?></td>
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= htmlspecialchars($s['address'] ?? '') ?></td>
                    <td style="font-size:0.82rem;color:var(--amber);"><?= htmlspecialchars($s['delivery_date'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div><!-- view-analytics -->

</main>
<?php include 'footer.php'; ?>
</div><!-- .main-content -->
</div><!-- .app-shell -->

<!-- ── POS Checkout Modal ───────────────────────────────────────────────────── -->
<div class="modal-overlay" id="posCheckoutModal">
    <div class="modal-box">
        <div class="modal-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            POS Checkout
        </div>
        <button class="modal-close" onclick="document.getElementById('posCheckoutModal').classList.remove('open')">&times;</button>

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
            <input type="hidden" name="action" value="pos_checkout">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Dining Type</label>
                    <select name="dining_type" class="form-control">
                        <option>Dine-In</option><option>Take-Away</option><option>Delivery</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">POS Terminal</label>
                    <select name="pos_id" class="form-control">
                        <option>POS-01</option><option>POS-02</option><option>POS-03</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="cust_id" class="form-control">
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['id'] . ' — ' . $c['first_name'] . ' ' . $c['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Payment Method</label>
                <select name="pay_type" class="form-control">
                    <option>Cash</option><option>DuitNow QR</option><option>Credit Card</option><option>E-Wallet</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('posCheckoutModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:2;">Process Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Add Item Modal ──────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="addItemModal">
    <div class="modal-box">
        <div class="modal-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Menu Item
        </div>
        <button class="modal-close" onclick="document.getElementById('addItemModal').classList.remove('open')">&times;</button>
        <form method="post">
            <input type="hidden" name="action" value="add_item">
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" class="form-control" placeholder="e.g. Grilled Salmon" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price (RM)</label>
                    <input type="number" step="0.01" min="0" name="item_price" class="form-control" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" min="0" name="item_qty" class="form-control" placeholder="100" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="item_status" class="form-control">
                        <option>Available</option><option>Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier ID</label>
                    <select name="item_supplier" class="form-control">
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['id'] . ' — ' . mb_strimwidth($s['name'], 0, 22, '…')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('addItemModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Add Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Edit Item Modal ─────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="editItemModal">
    <div class="modal-box">
        <div class="modal-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Menu Item
        </div>
        <button class="modal-close" onclick="document.getElementById('editItemModal').classList.remove('open')">&times;</button>
        <form method="post" id="editItemForm">
            <input type="hidden" name="action" value="edit_item">
            <input type="hidden" name="item_id" id="edit_id">
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="item_name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Price (RM)</label>
                    <input type="number" step="0.01" min="0" name="item_price" id="edit_price" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Qty</label>
                    <input type="number" min="0" name="item_qty" id="edit_qty" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="item_status" id="edit_status" class="form-control">
                        <option>Available</option><option>Unavailable</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier ID</label>
                    <select name="item_supplier" id="edit_supplier" class="form-control">
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['id'] . ' — ' . mb_strimwidth($s['name'], 0, 22, '…')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('editItemModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Qty control
function changeQty(btn, delta) {
    const inp = btn.closest('.qty-control').querySelector('input[type=number]');
    const min = parseInt(inp.min)||1, max = parseInt(inp.max)||999;
    inp.value = Math.min(max, Math.max(min, parseInt(inp.value)+delta));
}

// Edit modal
function openEditModal(item) {
    document.getElementById('edit_id').value       = item.id;
    document.getElementById('edit_name').value     = item.name;
    document.getElementById('edit_price').value    = item.price;
    document.getElementById('edit_qty').value      = item.qty;
    document.getElementById('edit_status').value   = item.status;
    const sel = document.getElementById('edit_supplier');
    for (let o of sel.options) { if (o.value === item.supplier_id) { o.selected = true; break; } }
    document.getElementById('editItemModal').classList.add('open');
}

// Modal backdrop close
['posCheckoutModal','addItemModal','editItemModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Order search
function filterOrders(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#ordersBody tr').forEach(tr => {
        const match = !q || tr.dataset.search.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('noOrderResults').style.display = visible === 0 ? 'block' : 'none';
}

<?php if ($msg === 'added'): ?>
setTimeout(() => { document.getElementById('addItemModal').classList.remove('open'); showToast('Item added successfully!', 'success'); }, 100);
<?php elseif ($msg === 'updated'): ?>
setTimeout(() => { document.getElementById('editItemModal').classList.remove('open'); showToast('Item updated!', 'success'); }, 100);
<?php elseif ($msg === 'deleted'): ?>
setTimeout(() => showToast('Item deleted.', 'error'), 100);
<?php elseif ($msg === 'placed'): ?>
setTimeout(() => showToast('Order <?= addslashes($_GET['oid'] ?? '') ?> processed!', 'success'), 100);
<?php elseif ($msg === 'cancelled'): ?>
setTimeout(() => showToast('Order cancelled.', 'info'), 100);
<?php endif; ?>
</script>
</body>
</html>
