<?php
/**
 * db_connect.php
 * Centralized cURL REST engine for Cabin Putih Ordering System
 * Targets Oracle ORDS endpoints; falls back to seeded mock data.
 */

define('APEX_API_BASE', 'https://oracleapex.com/ords/cabin_putih/');
define('API_TIMEOUT', 5);

// ── cURL Engine ───────────────────────────────────────────────────────────────
function apex_request(string $endpoint, string $method = 'GET', array $payload = []): array {
    $url = APEX_API_BASE . $endpoint;
    $ch  = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => API_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
    ]);
    if (in_array(strtoupper($method), ['POST','PUT','PATCH']) && $payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error || $code < 200 || $code >= 300) return ['__error' => true, '__code' => $code];
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['__error' => true];
}

// ── Convenience wrappers ──────────────────────────────────────────────────────
function api_get(string $table): array    { return apex_request($table); }
function api_post(string $table, array $d): array { return apex_request($table, 'POST', $d); }
function api_put(string $table, string $id, array $d): array { return apex_request($table . '/' . $id, 'PUT', $d); }
function api_delete(string $table, string $id): array { return apex_request($table . '/' . $id, 'DELETE'); }
function api_new_order(array $payload): array { return apex_request('orders/new-order', 'POST', $payload); }

function is_api_error(array $r): bool { return !empty($r['__error']); }

// ── Session bootstrap ─────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Fallback seed datasets ────────────────────────────────────────────────────
function get_fallback_employees(): array {
    return [
        ['id'=>'EMP001','role'=>'Manager', 'name'=>'Siti Norbaizura binti Othman'],
        ['id'=>'EMP002','role'=>'Cashier', 'name'=>'Farah Nabilah binti Ahmad Shukri'],
        ['id'=>'EMP003','role'=>'Chef',    'name'=>'Khairul Anwar bin Azman'],
        ['id'=>'EMP004','role'=>'Waiter',  'name'=>'Thrishaa a/p Loganathan'],
        ['id'=>'EMP005','role'=>'Cashier', 'name'=>'Desmond Lim Wei Kiat'],
        ['id'=>'EMP006','role'=>'Runner',  'name'=>'Mohamad Shahrizal bin Hazali'],
        ['id'=>'EMP007','role'=>'Runner',  'name'=>'Megat Iskandar bin Zulkifli'],
        ['id'=>'EMP008','role'=>'Runner',  'name'=>'Muhammad Haziq bin Roslan'],
        ['id'=>'EMP009','role'=>'Waiter',  'name'=>'Aiman Hakim bin Norizan'],
        ['id'=>'EMP010','role'=>'Chef',    'name'=>'Muhammad Danish Zakwan bin Mansor'],
        ['id'=>'EMP011','role'=>'Chef',    'name'=>'Muhammad Luqman bin Mohd Hafiz'],
        ['id'=>'EMP012','role'=>'Waiter',  'name'=>'Syed Muzaffar bin Syed Ali'],
        ['id'=>'EMP013','role'=>'Cashier', 'name'=>'Anas bin Muhammad Fakhrul'],
        ['id'=>'EMP014','role'=>'Waiter',  'name'=>'Muhammad Fitri bin Kamaruddin'],
        ['id'=>'EMP015','role'=>'Runner',  'name'=>'Mohamad Nazmi bin Mohd Asri'],
        ['id'=>'EMP016','role'=>'Waiter',  'name'=>'Muhammad Zaim bin Khairuddin'],
        ['id'=>'EMP017','role'=>'Chef',    'name'=>'Muhammad Shahril bin Abdul Rashid'],
        ['id'=>'EMP018','role'=>'Runner',  'name'=>'Nik Muhammad Ariff bin Nik Nazmi'],
        ['id'=>'EMP019','role'=>'Waiter',  'name'=>'Muhammad Syamil bin Mohd Sukri'],
        ['id'=>'EMP020','role'=>'Runner',  'name'=>'Zulfadhli bin Zainal Abidin'],
    ];
}

function get_fallback_suppliers(): array {
    return [
        ['id'=>'SUP001','name'=>'Pasar Borong Selayang Veggies','phone'=>'012-3456789','delivery_date'=>'2026-05-01','address'=>'Selayang, Selangor','type'=>'Fresh Produce'],
        ['id'=>'SUP002','name'=>'Ayam Segar Valley Sdn Bhd',   'phone'=>'013-9876543','delivery_date'=>'2026-05-02','address'=>'Kuala Lumpur',       'type'=>'Meat & Seafood'],
        ['id'=>'SUP003','name'=>'Sri Frozen Food Supplier',     'phone'=>'017-4455667','delivery_date'=>'2026-05-02','address'=>'Shah Alam, Selangor','type'=>'Meat & Seafood'],
        ['id'=>'SUP004','name'=>'Mewah Dairy & Beverage Ltd',   'phone'=>'019-2233445','delivery_date'=>'2026-05-03','address'=>'Petaling Jaya, Selangor','type'=>'Beverages & Dairy'],
        ['id'=>'SUP005','name'=>'Ocean Harvest Seafood',        'phone'=>'011-5544332','delivery_date'=>'2026-05-04','address'=>'Klang, Selangor',    'type'=>'Meat & Seafood'],
        ['id'=>'SUP006','name'=>'Daging Nusantara Ent.',        'phone'=>'016-8899001','delivery_date'=>'2026-05-04','address'=>'Gombak, Selangor',   'type'=>'Meat & Seafood'],
        ['id'=>'SUP007','name'=>'Best Bake Flour Mills',        'phone'=>'014-2233114','delivery_date'=>'2026-05-05','address'=>'Rawang, Selangor',   'type'=>'Pantry & Grains'],
        ['id'=>'SUP008','name'=>'Rasa Sayang Spice Trade',      'phone'=>'017-9955443','delivery_date'=>'2026-05-05','address'=>'Cheras, Kuala Lumpur','type'=>'Pantry & Grains'],
        ['id'=>'SUP009','name'=>'Sauce & Condiments Masters',   'phone'=>'013-4455221','delivery_date'=>'2026-05-06','address'=>'Puchong, Selangor',  'type'=>'Pantry & Grains'],
        ['id'=>'SUP010','name'=>'Premium Rice Wholesalers',     'phone'=>'012-8844331','delivery_date'=>'2026-05-06','address'=>'Alor Setar, Kedah',  'type'=>'Pantry & Grains'],
        ['id'=>'SUP011','name'=>'Fresh Herbs Garden',           'phone'=>'018-1122334','delivery_date'=>'2026-05-07','address'=>'Cameron Highlands, Pahang','type'=>'Fresh Produce'],
        ['id'=>'SUP012','name'=>'Golden Oil Refinery',          'phone'=>'019-4455661','delivery_date'=>'2026-05-07','address'=>'Banting, Selangor',  'type'=>'Operations & Utilities'],
        ['id'=>'SUP013','name'=>'Sweet Nature Sugar Corp',      'phone'=>'015-6677889','delivery_date'=>'2026-05-08','address'=>'Perai, Penang',      'type'=>'Pantry & Grains'],
        ['id'=>'SUP014','name'=>'Eco Packaging Solutions',      'phone'=>'014-9988776','delivery_date'=>'2026-05-08','address'=>'Seremban, Negeri Sembilan','type'=>'Operations & Utilities'],
        ['id'=>'SUP015','name'=>'Pure Water Bottlers',          'phone'=>'013-2211449','delivery_date'=>'2026-05-09','address'=>'Subang Jaya, Selangor','type'=>'Beverages & Dairy'],
        ['id'=>'SUP016','name'=>'Malaysian Egg Farm',           'phone'=>'017-6655442','delivery_date'=>'2026-05-09','address'=>'Ijok, Selangor',     'type'=>'Fresh Produce'],
        ['id'=>'SUP017','name'=>'Global Cheese & Butter',       'phone'=>'012-7788993','delivery_date'=>'2026-05-10','address'=>'Ampang, Kuala Lumpur','type'=>'Beverages & Dairy'],
        ['id'=>'SUP018','name'=>'Noodle Craft Industries',      'phone'=>'016-3322115','delivery_date'=>'2026-05-10','address'=>'Kajang, Selangor',   'type'=>'Pantry & Grains'],
        ['id'=>'SUP019','name'=>'Zesty Citrus Orchards',        'phone'=>'011-8877665','delivery_date'=>'2026-05-11','address'=>'Muar, Johor',        'type'=>'Fresh Produce'],
        ['id'=>'SUP020','name'=>'Universal Gas Supply',         'phone'=>'018-9900112','delivery_date'=>'2026-05-11','address'=>'Batu Caves, Selangor','type'=>'Operations & Utilities'],
    ];
}

function get_fallback_items(): array {
    return [
        ['id'=>'ITM001','name'=>'Chicken Zinger Burger',              'price'=>5.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP002'],
        ['id'=>'ITM002','name'=>'Chicken Chop',                       'price'=>7.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP002'],
        ['id'=>'ITM003','name'=>'Hot Chicken Tenders',                'price'=>6.00, 'qty'=>0,  'status'=>'Available','supplier_id'=>'SUP002'],
        ['id'=>'ITM004','name'=>'French Fries',                       'price'=>4.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP003'],
        ['id'=>'ITM005','name'=>'Mushroom Soup & Garlic Bread',       'price'=>5.50, 'qty'=>0,  'status'=>'Available','supplier_id'=>'SUP007'],
        ['id'=>'ITM006','name'=>'Butterrice Buttermilk Chicken',      'price'=>6.00, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP010'],
        ['id'=>'ITM007','name'=>'Butterrice Pedas Terbakaq',          'price'=>6.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP010'],
        ['id'=>'ITM008','name'=>'Butterrice Keju Leleh',              'price'=>7.00, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP010'],
        ['id'=>'ITM009','name'=>'Spaghetti Carbonara',                'price'=>6.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP018'],
        ['id'=>'ITM010','name'=>'Spaghetti Bolonis',                  'price'=>5.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP018'],
        ['id'=>'ITM011','name'=>'Fry Maggi Chicken Original',         'price'=>5.50, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP018'],
        ['id'=>'ITM012','name'=>'Fry Maggi Chicken Keju Leleh',       'price'=>7.00, 'qty'=>100,'status'=>'Available','supplier_id'=>'SUP018'],
        ['id'=>'ITM013','name'=>'Combo A (Fries + 2pcs Chicken)',     'price'=>9.50, 'qty'=>50, 'status'=>'Available','supplier_id'=>'SUP014'],
        ['id'=>'ITM014','name'=>'Combo B (Zinger + Fries + 1pc Chicken)','price'=>10.00,'qty'=>50,'status'=>'Available','supplier_id'=>'SUP014'],
        ['id'=>'ITM015','name'=>'Combo C (Butterrice + 1pc Chicken + Fries)','price'=>11.50,'qty'=>50,'status'=>'Available','supplier_id'=>'SUP014'],
        ['id'=>'ITM016','name'=>'Cold White Cabin Kaffe',             'price'=>5.50, 'qty'=>150,'status'=>'Available','supplier_id'=>'SUP004'],
        ['id'=>'ITM017','name'=>'Cold Nutella Chocolate',             'price'=>4.50, 'qty'=>0,  'status'=>'Available','supplier_id'=>'SUP004'],
        ['id'=>'ITM018','name'=>'Cold Lipton Lemon Ted o',            'price'=>3.50, 'qty'=>200,'status'=>'Available','supplier_id'=>'SUP015'],
        ['id'=>'ITM019','name'=>'Cold Sky Juice',                     'price'=>0.50, 'qty'=>300,'status'=>'Available','supplier_id'=>'SUP015'],
        ['id'=>'ITM020','name'=>'Hot Kaffe o',                        'price'=>3.50, 'qty'=>150,'status'=>'Available','supplier_id'=>'SUP004'],
    ];
}

function get_fallback_customers(): array {
    return [
        ['id'=>'CUS001','first_name'=>'MUHAMMAD AMIR HAKIMI', 'last_name'=>'ABD RAHMAN',       'phone'=>'011-2345678'],
        ['id'=>'CUS002','first_name'=>'AMNI HANNAH',          'last_name'=>'ABDUL HALIM',       'phone'=>'016-7654321'],
        ['id'=>'CUS003','first_name'=>'MUHAMMAD IRFAN MUQRI', 'last_name'=>'ABDUL MANAF',       'phone'=>'012-4455882'],
        ['id'=>'CUS004','first_name'=>'AHMAD DANISH',         'last_name'=>'AHMAD YAZID',       'phone'=>'017-9988771'],
        ['id'=>'CUS005','first_name'=>'SHAHIRUL FIKRI',       'last_name'=>'AMAN SHAH',         'phone'=>'019-3344556'],
        ['id'=>'CUS006','first_name'=>'ANIS HUMAIRA',         'last_name'=>'AZUAR SHAHNAZ',     'phone'=>'014-5566778'],
        ['id'=>'CUS007','first_name'=>'MOHAMAD SYAHRIL HAIQAL','last_name'=>'HAMZAH',           'phone'=>'013-2211445'],
        ['id'=>'CUS008','first_name'=>'MUHAMMAD IQRAM ALIFF', 'last_name'=>'ISMAIL',            'phone'=>'018-7766554'],
        ['id'=>'CUS009','first_name'=>'AMAR HAFIZ',           'last_name'=>'KAMALUDIN',         'phone'=>'011-8899001'],
        ['id'=>'CUS010','first_name'=>'MUHAMMAD HARITH SAFWAN','last_name'=>'MD ILYAS',         'phone'=>'012-6677889'],
        ['id'=>'CUS011','first_name'=>'NURFATEN NABILAH',     'last_name'=>'MOHAMAD NAZRI',     'phone'=>'017-5544332'],
        ['id'=>'CUS012','first_name'=>'MUHAMMAD RAYHAN ARIFF','last_name'=>'MOHAMAD SHAHRUL',   'phone'=>'016-2233114'],
        ['id'=>'CUS013','first_name'=>'MUHAMMAD YAMIN NABHAN','last_name'=>'MOHAMAD YUSOFF',    'phone'=>'019-8877665'],
        ['id'=>'CUS014','first_name'=>'MUHAMMAD SYUKRI',      'last_name'=>'MOHAMMAD WEDDAN',   'phone'=>'013-4455221'],
        ['id'=>'CUS015','first_name'=>'IMRAN MUZAKKIR',       'last_name'=>'MOHD ISRAM',        'phone'=>'011-7788992'],
        ['id'=>'CUS016','first_name'=>'NOR AFIQAH NABILA',    'last_name'=>'MOHD NASIR',        'phone'=>'018-9955443'],
        ['id'=>'CUS017','first_name'=>'NUR INSYIRAH',         'last_name'=>'MOHD SHUKRI',       'phone'=>'014-3322119'],
        ['id'=>'CUS018','first_name'=>'MUHAMAD ZULHAIRI',     'last_name'=>'MOHD ZAWAWI',       'phone'=>'012-8844331'],
        ['id'=>'CUS019','first_name'=>'MOHAMAD ADIB FAWZAN',  'last_name'=>'MOHD ZUHAIRI',      'phone'=>'017-6655441'],
        ['id'=>'CUS020','first_name'=>'MUHAMMAD AQIL HUSAINY','last_name'=>'MUHAMMAD ZAIDI',    'phone'=>'019-5566332'],
    ];
}

function get_fallback_orders(): array {
    return [
        ['order_id'=>'ORD001','dining_type'=>'Dine-In',   'pay_id'=>'PAY001','pay_type'=>'DuitNow QR', 'amount'=>9.00,  'pos'=>'POS-01','date'=>'2026-05-02','emp_id'=>'EMP002','cust_id'=>'CUS001','status'=>'Completed','details'=>[['item_id'=>'ITM018','qty'=>1,'subtotal'=>3.50],['item_id'=>'ITM011','qty'=>1,'subtotal'=>5.50]]],
        ['order_id'=>'ORD002','dining_type'=>'Take-Away', 'pay_id'=>'PAY002','pay_type'=>'Cash',       'amount'=>11.50, 'pos'=>'POS-02','date'=>'2026-05-03','emp_id'=>'EMP002','cust_id'=>'CUS002','status'=>'Completed','details'=>[['item_id'=>'ITM015','qty'=>1,'subtotal'=>11.50]]],
        ['order_id'=>'ORD003','dining_type'=>'Dine-In',   'pay_id'=>'PAY003','pay_type'=>'Credit Card','amount'=>20.50, 'pos'=>'POS-01','date'=>'2026-05-07','emp_id'=>'EMP005','cust_id'=>'CUS003','status'=>'Completed','details'=>[['item_id'=>'ITM002','qty'=>2,'subtotal'=>15.00],['item_id'=>'ITM016','qty'=>1,'subtotal'=>5.50]]],
        ['order_id'=>'ORD004','dining_type'=>'Delivery',  'pay_id'=>'PAY004','pay_type'=>'DuitNow QR', 'amount'=>11.50, 'pos'=>'POS-03','date'=>'2026-05-09','emp_id'=>'EMP002','cust_id'=>'CUS004','status'=>'Completed','details'=>[['item_id'=>'ITM015','qty'=>1,'subtotal'=>11.50]]],
        ['order_id'=>'ORD005','dining_type'=>'Dine-In',   'pay_id'=>'PAY005','pay_type'=>'Cash',       'amount'=>14.50, 'pos'=>'POS-01','date'=>'2026-05-15','emp_id'=>'EMP005','cust_id'=>'CUS005','status'=>'Completed','details'=>[['item_id'=>'ITM014','qty'=>1,'subtotal'=>10.00],['item_id'=>'ITM004','qty'=>1,'subtotal'=>4.50]]],
        ['order_id'=>'ORD006','dining_type'=>'Take-Away', 'pay_id'=>'PAY006','pay_type'=>'E-Wallet',   'amount'=>6.50,  'pos'=>'POS-02','date'=>'2026-05-20','emp_id'=>'EMP002','cust_id'=>'CUS006','status'=>'Completed','details'=>[['item_id'=>'ITM009','qty'=>1,'subtotal'=>6.50]]],
        ['order_id'=>'ORD007','dining_type'=>'Dine-In',   'pay_id'=>'PAY007','pay_type'=>'DuitNow QR', 'amount'=>12.00, 'pos'=>'POS-02','date'=>'2026-05-24','emp_id'=>'EMP005','cust_id'=>'CUS007','status'=>'Completed','details'=>[['item_id'=>'ITM006','qty'=>2,'subtotal'=>12.00]]],
        ['order_id'=>'ORD008','dining_type'=>'Delivery',  'pay_id'=>'PAY008','pay_type'=>'Cash',       'amount'=>13.00, 'pos'=>'POS-03','date'=>'2026-05-28','emp_id'=>'EMP002','cust_id'=>'CUS008','status'=>'Completed','details'=>[['item_id'=>'ITM013','qty'=>1,'subtotal'=>9.50],['item_id'=>'ITM018','qty'=>1,'subtotal'=>3.50]]],
        ['order_id'=>'ORD009','dining_type'=>'Dine-In',   'pay_id'=>'PAY009','pay_type'=>'Credit Card','amount'=>23.00, 'pos'=>'POS-01','date'=>'2026-05-31','emp_id'=>'EMP005','cust_id'=>'CUS009','status'=>'Completed','details'=>[['item_id'=>'ITM015','qty'=>2,'subtotal'=>23.00]]],
        ['order_id'=>'ORD010','dining_type'=>'Take-Away', 'pay_id'=>'PAY010','pay_type'=>'E-Wallet',   'amount'=>9.50,  'pos'=>'POS-02','date'=>'2026-06-03','emp_id'=>'EMP002','cust_id'=>'CUS010','status'=>'Completed','details'=>[['item_id'=>'ITM013','qty'=>1,'subtotal'=>9.50]]],
        ['order_id'=>'ORD011','dining_type'=>'Dine-In',   'pay_id'=>'PAY011','pay_type'=>'Cash',       'amount'=>7.50,  'pos'=>'POS-01','date'=>'2026-06-05','emp_id'=>'EMP005','cust_id'=>'CUS011','status'=>'Completed','details'=>[['item_id'=>'ITM002','qty'=>1,'subtotal'=>7.50]]],
        ['order_id'=>'ORD012','dining_type'=>'Take-Away', 'pay_id'=>'PAY012','pay_type'=>'DuitNow QR', 'amount'=>11.50, 'pos'=>'POS-02','date'=>'2026-06-07','emp_id'=>'EMP002','cust_id'=>'CUS012','status'=>'Completed','details'=>[['item_id'=>'ITM015','qty'=>1,'subtotal'=>11.50]]],
        ['order_id'=>'ORD013','dining_type'=>'Delivery',  'pay_id'=>'PAY013','pay_type'=>'Credit Card','amount'=>19.50, 'pos'=>'POS-03','date'=>'2026-06-11','emp_id'=>'EMP002','cust_id'=>'CUS013','status'=>'Completed','details'=>[['item_id'=>'ITM008','qty'=>2,'subtotal'=>14.00],['item_id'=>'ITM001','qty'=>1,'subtotal'=>5.50]]],
        ['order_id'=>'ORD014','dining_type'=>'Dine-In',   'pay_id'=>'PAY014','pay_type'=>'Cash',       'amount'=>10.00, 'pos'=>'POS-01','date'=>'2026-06-13','emp_id'=>'EMP005','cust_id'=>'CUS014','status'=>'Completed','details'=>[['item_id'=>'ITM014','qty'=>1,'subtotal'=>10.00]]],
        ['order_id'=>'ORD015','dining_type'=>'Take-Away', 'pay_id'=>'PAY015','pay_type'=>'E-Wallet',   'amount'=>17.00, 'pos'=>'POS-02','date'=>'2026-06-17','emp_id'=>'EMP002','cust_id'=>'CUS015','status'=>'Completed','details'=>[['item_id'=>'ITM012','qty'=>1,'subtotal'=>7.00],['item_id'=>'ITM014','qty'=>1,'subtotal'=>10.00]]],
        ['order_id'=>'ORD016','dining_type'=>'Dine-In',   'pay_id'=>'PAY016','pay_type'=>'DuitNow QR', 'amount'=>5.50,  'pos'=>'POS-01','date'=>'2026-06-19','emp_id'=>'EMP005','cust_id'=>'CUS001','status'=>'Completed','details'=>[['item_id'=>'ITM011','qty'=>1,'subtotal'=>5.50]]],
        ['order_id'=>'ORD017','dining_type'=>'Delivery',  'pay_id'=>'PAY017','pay_type'=>'Cash',       'amount'=>15.50, 'pos'=>'POS-03','date'=>'2026-06-21','emp_id'=>'EMP002','cust_id'=>'CUS002','status'=>'Preparing','details'=>[['item_id'=>'ITM014','qty'=>1,'subtotal'=>10.00],['item_id'=>'ITM011','qty'=>1,'subtotal'=>5.50]]],
        ['order_id'=>'ORD018','dining_type'=>'Dine-In',   'pay_id'=>'PAY018','pay_type'=>'Credit Card','amount'=>21.50, 'pos'=>'POS-02','date'=>'2026-06-25','emp_id'=>'EMP005','cust_id'=>'CUS001','status'=>'Ready for Pickup','details'=>[['item_id'=>'ITM015','qty'=>1,'subtotal'=>11.50],['item_id'=>'ITM014','qty'=>1,'subtotal'=>10.00]]],
        ['order_id'=>'ORD019','dining_type'=>'Take-Away', 'pay_id'=>'PAY019','pay_type'=>'E-Wallet',   'amount'=>11.50, 'pos'=>'POS-01','date'=>'2026-06-27','emp_id'=>'EMP002','cust_id'=>'CUS002','status'=>'Received','details'=>[['item_id'=>'ITM015','qty'=>1,'subtotal'=>11.50]]],
        ['order_id'=>'ORD020','dining_type'=>'Dine-In',   'pay_id'=>'PAY020','pay_type'=>'DuitNow QR', 'amount'=>16.50, 'pos'=>'POS-03','date'=>'2026-06-28','emp_id'=>'EMP005','cust_id'=>'CUS003','status'=>'Received','details'=>[['item_id'=>'ITM012','qty'=>1,'subtotal'=>7.00],['item_id'=>'ITM013','qty'=>1,'subtotal'=>9.50]]],
    ];
}

// ── Live-data fetchers with fallback ──────────────────────────────────────────
function fetch_items(): array {
    $r = api_get('item');
    if (is_api_error($r) || empty($r['items'])) {
        if (!isset($_SESSION['items'])) $_SESSION['items'] = get_fallback_items();
        return $_SESSION['items'];
    }
    return $r['items'];
}

function fetch_customers(): array {
    $r = api_get('customer');
    if (is_api_error($r) || empty($r['items'])) {
        if (!isset($_SESSION['customers'])) $_SESSION['customers'] = get_fallback_customers();
        return $_SESSION['customers'];
    }
    return $r['items'];
}

function fetch_orders(): array {
    $r = api_get('orders');
    if (is_api_error($r) || empty($r['items'])) {
        if (!isset($_SESSION['orders'])) $_SESSION['orders'] = get_fallback_orders();
        return $_SESSION['orders'];
    }
    return $r['items'];
}

function fetch_suppliers(): array {
    $r = api_get('supplier');
    if (is_api_error($r) || empty($r['items'])) return get_fallback_suppliers();
    return $r['items'];
}

function fetch_employees(): array {
    $r = api_get('employee');
    if (is_api_error($r) || empty($r['items'])) return get_fallback_employees();
    return $r['items'];
}

// ── Item helpers ──────────────────────────────────────────────────────────────
function get_item_by_id(string $id): ?array {
    foreach (fetch_items() as $item) {
        if (($item['id'] ?? $item['ITEM_ID'] ?? '') === $id) return $item;
    }
    return null;
}

function get_item_name(string $id): string {
    $item = get_item_by_id($id);
    return $item ? ($item['name'] ?? $item['ITEM_NAME'] ?? $id) : $id;
}

function get_customer_name(string $id): string {
    foreach (fetch_customers() as $c) {
        if (($c['id'] ?? '') === $id) return trim($c['first_name'] . ' ' . $c['last_name']);
    }
    return $id;
}

// ── Session cart helpers ──────────────────────────────────────────────────────
function cart_add(string $item_id, int $qty = 1): void {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $item = get_item_by_id($item_id);
    if (!$item) return;
    $price = (float)($item['price'] ?? $item['PRICE'] ?? 0);
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$item_id] = [
            'item_id' => $item_id,
            'name'    => $item['name'] ?? $item['ITEM_NAME'] ?? $item_id,
            'price'   => $price,
            'qty'     => $qty,
        ];
    }
}

function cart_remove(string $item_id): void {
    unset($_SESSION['cart'][$item_id]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
}

function cart_total(): float {
    $total = 0;
    foreach ($_SESSION['cart'] ?? [] as $row) {
        $total += $row['price'] * $row['qty'];
    }
    return $total;
}

function cart_count(): int {
    return array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
}

// ── Order helpers ─────────────────────────────────────────────────────────────
function get_next_order_id(): string {
    $orders = $_SESSION['orders'] ?? get_fallback_orders();
    $max    = 0;
    foreach ($orders as $o) {
        $num = (int)substr($o['order_id'], 3);
        if ($num > $max) $max = $num;
    }
    return 'ORD' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

function get_next_pay_id(): string {
    $orders = $_SESSION['orders'] ?? get_fallback_orders();
    $max    = 0;
    foreach ($orders as $o) {
        $num = (int)substr($o['pay_id'], 3);
        if ($num > $max) $max = $num;
    }
    return 'PAY' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

function get_next_item_id(): string {
    $items = $_SESSION['items'] ?? get_fallback_items();
    $max   = 0;
    foreach ($items as $it) {
        $num = (int)substr($it['id'], 3);
        if ($num > $max) $max = $num;
    }
    return 'ITM' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

function get_next_customer_id(): string {
    $customers = $_SESSION['customers'] ?? get_fallback_customers();
    $max = 0;
    foreach ($customers as $c) {
        $num = (int)substr($c['id'], 3);
        if ($num > $max) $max = $num;
    }
    return 'CUS' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// ── Auth helpers ──────────────────────────────────────────────────────────────
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
function is_customer(): bool {
    return ($_SESSION['user_role'] ?? '') === 'customer';
}
function is_staff(): bool {
    return ($_SESSION['user_role'] ?? '') === 'staff';
}
function require_auth(string $redirect = 'login.php'): void {
    if (!is_logged_in()) { header("Location: $redirect"); exit; }
}
function require_staff(string $redirect = 'login.php'): void {
    if (!is_logged_in() || !is_staff()) { header("Location: $redirect"); exit; }
}
function require_customer(string $redirect = 'login.php'): void {
    if (!is_logged_in() || !is_customer()) { header("Location: $redirect"); exit; }
}
