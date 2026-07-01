<?php
require_once 'db_connect.php';

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Already logged in → redirect
if (is_logged_in()) {
    header('Location: ' . (is_staff() ? 'dashboard_staff.php' : 'dashboard_customer.php'));
    exit;
}

$error   = '';
$success = '';
$tab     = $_POST['active_tab'] ?? 'customer';

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Customer Login via Phone ──────────────────────────────────────────────
    if ($action === 'customer_login') {
        $tab      = 'customer';
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $customers = fetch_customers();
        $matched   = null;
        foreach ($customers as $c) {
            if (($c['phone'] ?? '') === $phone) { $matched = $c; break; }
        }
        if (!$matched) {
            $error = 'No account found with that phone number.';
        } elseif (($matched['password'] ?? 'password123') !== $password) {
            $error = 'Incorrect password. Try "password123" for demo accounts.';
        } else {
            $_SESSION['user_id']    = $matched['id'];
            $_SESSION['user_role']  = 'customer';
            $_SESSION['user_name']  = trim($matched['first_name'] . ' ' . $matched['last_name']);
            $_SESSION['user_phone'] = $matched['phone'];
            header('Location: dashboard_customer.php');
            exit;
        }
    }

    // ── Staff Login via Employee ID ───────────────────────────────────────────
    elseif ($action === 'staff_login') {
        $tab     = 'staff';
        $emp_id  = strtoupper(trim($_POST['emp_id'] ?? ''));
        $password = $_POST['staff_password'] ?? '';
        $employees = fetch_employees();
        $matched   = null;
        foreach ($employees as $e) {
            if (($e['id'] ?? '') === $emp_id) { $matched = $e; break; }
        }
        if (!$matched) {
            $error = 'Employee ID not found.';
        } elseif ($password !== 'staff123') {
            $error = 'Incorrect staff password. Use "staff123" for all staff accounts.';
        } else {
            $_SESSION['user_id']   = $matched['id'];
            $_SESSION['user_role'] = 'staff';
            $_SESSION['user_name'] = $matched['name'];
            $_SESSION['emp_role']  = $matched['role'];
            header('Location: dashboard_staff.php');
            exit;
        }
    }

    // ── Customer Registration ─────────────────────────────────────────────────
    elseif ($action === 'register') {
        $tab       = 'register';
        $first     = strtoupper(trim($_POST['reg_first'] ?? ''));
        $last      = strtoupper(trim($_POST['reg_last']  ?? ''));
        $phone     = trim($_POST['reg_phone']    ?? '');
        $password  = $_POST['reg_password']  ?? '';
        $password2 = $_POST['reg_password2'] ?? '';

        if (!$first || !$last || !$phone || !$password) {
            $error = 'All fields are required.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $customers = fetch_customers();
            foreach ($customers as $c) {
                if (($c['phone'] ?? '') === $phone) { $error = 'Phone number already registered.'; break; }
            }
            if (!$error) {
                $new_id = get_next_customer_id();
                $new_cust = ['id'=>$new_id,'first_name'=>$first,'last_name'=>$last,'phone'=>$phone,'password'=>$password];
                $r = api_post('customer', $new_cust);
                if (!isset($_SESSION['customers'])) $_SESSION['customers'] = $customers;
                $_SESSION['customers'][] = $new_cust;
                $_SESSION['user_id']    = $new_id;
                $_SESSION['user_role']  = 'customer';
                $_SESSION['user_name']  = $first . ' ' . $last;
                $_SESSION['user_phone'] = $phone;
                header('Location: dashboard_customer.php');
                exit;
            }
        }
    }

    // ── Forgot Password ───────────────────────────────────────────────────────
    elseif ($action === 'forgot_password') {
        $first   = strtoupper(trim($_POST['forgot_first'] ?? ''));
        $last    = strtoupper(trim($_POST['forgot_last']  ?? ''));
        $phone   = trim($_POST['forgot_phone'] ?? '');
        $customers = fetch_customers();
        $matched = null;
        foreach ($customers as &$c) {
            if (strtoupper($c['first_name']) === $first &&
                strtoupper($c['last_name'])  === $last  &&
                $c['phone']                 === $phone) {
                $matched = &$c;
                break;
            }
        }
        if (!$matched) {
            $error = 'No matching account found. Check your details.';
        } else {
            $matched['password'] = 'password123';
            api_put('customer', $matched['id'], $matched);
            if (isset($_SESSION['customers'])) {
                foreach ($_SESSION['customers'] as &$sc) {
                    if ($sc['id'] === $matched['id']) { $sc['password'] = 'password123'; break; }
                }
            }
            $success = 'Password reset successfully. Your new password is: password123';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cabin Putih — Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-shell">
<div class="login-card">

    <div class="login-logo">
        <div class="logo-mark">&#9749;</div>
        <h1>Cabin Putih</h1>
        <p>F&amp;B Ordering System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="login-tabs">
        <button class="login-tab <?= $tab === 'customer' ? 'active' : '' ?>" onclick="switchTab('customer', this)">Customer</button>
        <button class="login-tab <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register', this)">Register</button>
        <button class="login-tab <?= $tab === 'staff'    ? 'active' : '' ?>" onclick="switchTab('staff', this)">Staff</button>
    </div>

    <!-- Customer Login -->
    <div class="tab-pane <?= $tab === 'customer' ? 'active' : '' ?>" id="tab-customer">
        <form method="post">
            <input type="hidden" name="action" value="customer_login">
            <input type="hidden" name="active_tab" value="customer">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="e.g. 011-2345678" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <a class="forgot-link" onclick="openForgotModal()">Forgot Password?</a>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Sign In</button>
        </form>
        <div class="login-divider">Demo accounts</div>
        <p style="font-size:0.78rem;color:var(--text-muted);text-align:center;">Phone: <code style="color:var(--amber)">011-2345678</code> &bull; Password: <code style="color:var(--amber)">password123</code></p>
    </div>

    <!-- Register -->
    <div class="tab-pane <?= $tab === 'register' ? 'active' : '' ?>" id="tab-register">
        <form method="post">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="active_tab" value="register">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="reg_first" class="form-control" placeholder="FIRST NAME" value="<?= htmlspecialchars($_POST['reg_first'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="reg_last" class="form-control" placeholder="LAST NAME" value="<?= htmlspecialchars($_POST['reg_last'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="reg_phone" class="form-control" placeholder="e.g. 012-3456789" value="<?= htmlspecialchars($_POST['reg_phone'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="reg_password" class="form-control" placeholder="Min. 6 chars" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="reg_password2" class="form-control" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:4px;">Create Account</button>
        </form>
    </div>

    <!-- Staff Login -->
    <div class="tab-pane <?= $tab === 'staff' ? 'active' : '' ?>" id="tab-staff">
        <form method="post">
            <input type="hidden" name="action" value="staff_login">
            <input type="hidden" name="active_tab" value="staff">
            <div class="form-group">
                <label class="form-label">Employee ID</label>
                <input type="text" name="emp_id" class="form-control" placeholder="e.g. EMP001" value="<?= htmlspecialchars($_POST['emp_id'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Staff Password</label>
                <input type="password" name="staff_password" class="form-control" placeholder="Staff access password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:8px;">Staff Sign In</button>
        </form>
        <div class="login-divider">Demo credentials</div>
        <p style="font-size:0.78rem;color:var(--text-muted);text-align:center;">ID: <code style="color:var(--amber)">EMP001</code>&ndash;<code style="color:var(--amber)">EMP020</code> &bull; Password: <code style="color:var(--amber)">staff123</code></p>
    </div>

</div><!-- .login-card -->
</div><!-- .login-shell -->

<!-- Forgot Password Modal -->
<div class="modal-overlay" id="forgotModal">
    <div class="modal-box">
        <div class="modal-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--amber)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Password Recovery
        </div>
        <button class="modal-close" onclick="closeForgotModal()">&times;</button>
        <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:18px;">Enter your registered details to reset your password to <strong style="color:var(--amber)">password123</strong>.</p>
        <form method="post">
            <input type="hidden" name="action" value="forgot_password">
            <input type="hidden" name="active_tab" value="customer">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="forgot_first" class="form-control" placeholder="e.g. MUHAMMAD AMIR HAKIMI" required>
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="forgot_last" class="form-control" placeholder="e.g. ABD RAHMAN" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="forgot_phone" class="form-control" placeholder="e.g. 011-2345678" required>
            </div>
            <div style="display:flex;gap:10px;margin-top:4px;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeForgotModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.login-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('active');
    btn.classList.add('active');
}
function openForgotModal()  { document.getElementById('forgotModal').classList.add('open'); }
function closeForgotModal() { document.getElementById('forgotModal').classList.remove('open'); }
document.getElementById('forgotModal').addEventListener('click', function(e) {
    if (e.target === this) closeForgotModal();
});
<?php if ($success && strpos($success, 'reset') !== false): ?>
document.getElementById('forgotModal').classList.remove('open');
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
</body>
</html>
