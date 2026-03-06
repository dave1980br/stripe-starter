<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/Core/Db.php';
require_once __DIR__ . '/../vendor/autoload.php';

$configLocal = __DIR__ . '/../src/Config/config.local.php';
$configExample = __DIR__ . '/../src/Config/config.example.php';

if (file_exists($configLocal)) {
    $config = require $configLocal;
} else {
    $config = require $configExample;
}

function stripe_init(array $config): void {
    $sk = (string)($config['stripe']['secret_key'] ?? '');
    if ($sk === '') {
        throw new RuntimeException("Stripe secret key not configured.");
    }
    \Stripe\Stripe::setApiKey($sk);
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['_csrf'];
}

function csrf_verify(?string $token): void {
    $sess = (string)($_SESSION['_csrf'] ?? '');
    if ($sess === '' || $token === null || !hash_equals($sess, $token)) {
        http_response_code(400);
        echo "Bad CSRF token";
        exit;
    }
}

function auth_check(): bool {
    return !empty($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);
}

function auth_require(): void {
    if (!auth_check()) {
        global $basePath;

        // Remember where the user was trying to go (includes basePath)
        $req = (string)($_SERVER['REQUEST_URI'] ?? '');
        if ($req !== '') {
            $_SESSION['after_login'] = $req;
        } else {
            $_SESSION['after_login'] = $basePath . '/admin/orders';
        }

        header('Location: ' . $basePath . '/login');
        exit;
    }
}

function auth_login(array $user): void {
    // minimal session payload
    $_SESSION['auth_user'] = [
        'user_id' => (int)$user['user_id'],
        'email' => (string)$user['email'],
        'display_name' => (string)$user['display_name'],
        'role' => (string)$user['role'],
    ];
}

function auth_logout(): void {
    unset($_SESSION['auth_user']);
}

// Simple view renderer (keep it minimal for now)
function render(string $view, array $data = []): void {
    global $basePath;
    $data['basePath'] = $basePath;

	$cart = $_SESSION['cart'] ?? [];
	$cartCount = 0;
	if (!empty($cart)) {
		foreach ($cart as $qty) { $cartCount += (int)$qty; }
	}
	$data['cartCount'] = $cartCount;
    
	extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../src/Views/' . $view . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(500);
        echo "View not found: " . htmlspecialchars($view);
        return;
    }
    require __DIR__ . '/../src/Views/layout/header.php';
    require $viewFile;
    require __DIR__ . '/../src/Views/layout/footer.php';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$fullPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
if ($basePath === '/') { $basePath = ''; }

$path = $fullPath;
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
    if ($path === '') { $path = '/'; }
}

if ($path === '/') {
    header('Location: ' . $basePath . '/shop');
    exit;
}

if ($method === 'GET' && $path === '/shop') {
    $pdo = Db::pdo();
    $stmt = $pdo->query("SELECT product_id, sku, name, description, unit_price_cents FROM products WHERE is_active = 1 ORDER BY product_id ASC");
    $products = $stmt->fetchAll();
    render('shop/index', ['products' => $products]);
    exit;
}

if ($method === 'POST' && $path === '/cart/add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($productId > 0) {
        $qty = max(1, min(99, $qty));
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
    }
    header('Location: ' . $basePath . '/shop');
    exit;
}

if ($method === 'POST' && $path === '/cart/remove') {
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0 && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
    header('Location: ' . $basePath . '/cart');
    exit;
}

if ($method === 'GET' && $path === '/cart') {
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    $subtotal = 0;

    if (!empty($cart)) {
        $pdo = Db::pdo();
        $ids = array_keys($cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT product_id, sku, name, unit_price_cents FROM products WHERE product_id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        // index by product_id
        $byId = [];
        foreach ($rows as $r) { $byId[(int)$r['product_id']] = $r; }

        foreach ($cart as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if (!isset($byId[$pid])) { continue; }
            $unit = (int)$byId[$pid]['unit_price_cents'];
            $line = $unit * $qty;
            $subtotal += $line;
            $items[] = [
                'product_id' => $pid,
                'sku' => $byId[$pid]['sku'],
                'name' => $byId[$pid]['name'],
                'unit_price_cents' => $unit,
                'qty' => $qty,
                'line_total_cents' => $line,
            ];
        }
    }

    render('cart/index', [
        'items' => $items,
        'subtotal_cents' => $subtotal,
    ]);
    exit;
}

// ---- Checkout + Order + Ledger (no Stripe yet) ----

function calc_totals(int $subtotalCents): array {
    $shippingCents = ($subtotalCents > 0 && $subtotalCents < 3500) ? 599 : 0; // $5.99 under $35
    $taxRate = 0.087; // 8.7% demo tax
    $taxCents = (int)round($subtotalCents * $taxRate);
    $totalCents = $subtotalCents + $taxCents + $shippingCents;
    return [$taxCents, $shippingCents, $totalCents];
}

if ($method === 'GET' && $path === '/checkout') {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        header('Location: ' . $basePath . '/cart');
        exit;
    }

    $pdo = Db::pdo();
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT product_id, sku, name, unit_price_cents FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $r) { $byId[(int)$r['product_id']] = $r; }

    $items = [];
    $subtotal = 0;

    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if (!isset($byId[$pid])) { continue; }
        $unit = (int)$byId[$pid]['unit_price_cents'];
        $line = $unit * $qty;
        $subtotal += $line;
        $items[] = [
            'product_id' => $pid,
            'sku' => $byId[$pid]['sku'],
            'name' => $byId[$pid]['name'],
            'unit_price_cents' => $unit,
            'qty' => $qty,
            'line_total_cents' => $line,
        ];
    }

    [$taxCents, $shippingCents, $totalCents] = calc_totals($subtotal);

    render('checkout/index', [
        'items' => $items,
        'subtotal_cents' => $subtotal,
        'tax_cents' => $taxCents,
        'shipping_cents' => $shippingCents,
        'total_cents' => $totalCents,
    ]);
    exit;
}

if ($method === 'POST' && $path === '/checkout/create') {
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) {
        header('Location: ' . $basePath . '/cart');
        exit;
    }

    $pdo = Db::pdo();
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT product_id, sku, name, unit_price_cents FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $r) { $byId[(int)$r['product_id']] = $r; }

    $items = [];
    $subtotal = 0;

    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if (!isset($byId[$pid])) { continue; }
        $unit = (int)$byId[$pid]['unit_price_cents'];
        $line = $unit * $qty;
        $subtotal += $line;

        $items[] = [
            'product_id' => $pid,
            'sku' => $byId[$pid]['sku'],
            'name' => $byId[$pid]['name'],
            'quantity' => $qty,
            'unit_price_cents' => $unit,
            'line_total_cents' => $line,
        ];
    }

    if (empty($items)) {
        header('Location: ' . $basePath . '/cart');
        exit;
    }

    [$taxCents, $shippingCents, $totalCents] = calc_totals($subtotal);

    $orderPublicId = bin2hex(random_bytes(8)); // 16 hex chars

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_public_id, status, currency, subtotal_cents, tax_cents, shipping_cents, total_cents)
            VALUES (:ref, :status, 'usd', :subtotal, :tax, :shipping, :total)
        ");
        $stmt->execute([
            ':ref' => $orderPublicId,
            ':status' => 'pending_payment',
            ':subtotal' => $subtotal,
            ':tax' => $taxCents,
            ':shipping' => $shippingCents,
            ':total' => $totalCents,
        ]);

        $orderId = (int)$pdo->lastInsertId();

        $stmtLedger = $pdo->prepare("
            INSERT INTO ledger_entries (order_id, event_type, event_source, message, amount_delta_cents, data_json)
            VALUES (:order_id, :event_type, :event_source, :message, :amount_delta_cents, :data_json)
        ");

        $stmtLedger->execute([
            ':order_id' => $orderId,
            ':event_type' => 'order_created',
            ':event_source' => 'app',
            ':message' => 'Order created from checkout.',
            ':amount_delta_cents' => null,
            ':data_json' => null,
        ]);

        $stmtLedger->execute([
            ':order_id' => $orderId,
            ':event_type' => 'totals_calculated',
            ':event_source' => 'app',
            ':message' => 'Totals calculated server-side.',
            ':amount_delta_cents' => $totalCents,
            ':data_json' => json_encode([
                'subtotal_cents' => $subtotal,
                'tax_cents' => $taxCents,
                'shipping_cents' => $shippingCents,
                'total_cents' => $totalCents,
            ], JSON_UNESCAPED_SLASHES),
        ]);

        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, sku, name, quantity, unit_price_cents, line_total_cents)
            VALUES (:order_id, :product_id, :sku, :name, :qty, :unit, :line)
        ");

        foreach ($items as $it) {
            $stmtItem->execute([
                ':order_id' => $orderId,
                ':product_id' => $it['product_id'],
                ':sku' => $it['sku'],
                ':name' => $it['name'],
                ':qty' => $it['quantity'],
                ':unit' => $it['unit_price_cents'],
                ':line' => $it['line_total_cents'],
            ]);
        }

        // Create Stripe PaymentIntent BEFORE commit so failures rollback cleanly
        stripe_init($config);

        $pi = \Stripe\PaymentIntent::create([
            'amount' => $totalCents,
            'currency' => 'usd',
            'payment_method_types' => ['card', 'link', 'cashapp', 'us_bank_account'],
            'metadata' => [
                'order_public_id' => $orderPublicId,
                'demo' => 'blueoak_stripe_ledger',
            ],
        ]);

        $piId = (string)($pi->id ?? '');
        if ($piId === '') {
            throw new RuntimeException("Stripe PaymentIntent created without an id.");
        }

        // Store PI id on the order
        $stmt = $pdo->prepare("UPDATE orders SET stripe_payment_intent_id = ?, updated_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE order_id = ?");
        $stmt->execute([$piId, $orderId]);

        // Ledger: PI created
        $stmtLedger->execute([
            ':order_id' => $orderId,
            ':event_type' => 'payment_intent_created',
            ':event_source' => 'app',
            ':message' => 'Stripe PaymentIntent created.',
            ':amount_delta_cents' => $totalCents,
            ':data_json' => json_encode([
                'stripe_payment_intent_id' => $piId,
            ], JSON_UNESCAPED_SLASHES),
        ]);

        $pdo->commit();
		
        unset($_SESSION['cart']);

        header('Location: ' . $basePath . '/checkout/pay?ref=' . urlencode($orderPublicId));
        exit;

    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "Checkout failed: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

if ($method === 'GET' && $path === '/login') {
    if (auth_check()) {
        header('Location: ' . $basePath . '/admin/orders');
        exit;
    }
    render('auth/login', [
        'csrf' => csrf_token(),
        'error' => null,
        'email' => '',
    ]);
    exit;
}

if ($method === 'POST' && $path === '/login') {
    csrf_verify($_POST['_csrf'] ?? null);

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $pdo = Db::pdo();
    $stmt = $pdo->prepare("SELECT user_id, email, display_name, role, password_hash
                           FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        render('auth/login', [
            'csrf' => csrf_token(),
            'error' => 'Invalid email or password.',
            'email' => $email,
        ]);
        exit;
    }

	auth_login($user);

    $dest = (string)($_SESSION['after_login'] ?? '');
    unset($_SESSION['after_login']);

    if ($dest !== '') {
        header('Location: ' . $dest);
    } else {
        header('Location: ' . $basePath . '/admin/orders');
    }
    exit;
}

if ($method === 'POST' && $path === '/logout') {
    csrf_verify($_POST['_csrf'] ?? null);
    auth_logout();
    header('Location: ' . $basePath . '/login');
    exit;
}

// ---- Admin Portal ----

if ($method === 'GET' && $path === '/admin') {
    if (auth_check()) {
        header('Location: ' . $basePath . '/admin/orders');
    } else {
        // remember they wanted /admin, then send to login
        $_SESSION['after_login'] = $basePath . '/admin/orders';
        header('Location: ' . $basePath . '/login');
    }
    exit;
}

// ---- Admin Orders ----
if ($method === 'GET' && $path === '/admin/orders') {
	auth_require();
    $pdo = Db::pdo();
    $stmt = $pdo->query("SELECT order_public_id, status, total_cents, created_at_utc FROM orders ORDER BY order_id DESC LIMIT 50");
    $orders = $stmt->fetchAll();
    render('admin_orders/index', ['orders' => $orders]);
    exit;
}

if ($method === 'GET' && $path === '/admin/orders/view') {
	auth_require();
    $ref = (string)($_GET['ref'] ?? '');
    if ($ref === '') {
        header('Location: ' . $basePath . '/admin/orders');
        exit;
    }

    $pdo = Db::pdo();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_public_id = ? LIMIT 1");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo "Order not found";
        exit;
    }

    $stmt = $pdo->prepare("SELECT name, quantity, unit_price_cents, line_total_cents FROM order_items WHERE order_id = ? ORDER BY order_item_id ASC");
    $stmt->execute([(int)$order['order_id']]);
    $items = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT created_at_utc, event_type, event_source, message, amount_delta_cents FROM ledger_entries WHERE order_id = ? ORDER BY ledger_entry_id ASC");
    $stmt->execute([(int)$order['order_id']]);
    $ledger = $stmt->fetchAll();

    render('admin_orders/view', [
        'order' => $order,
        'items' => $items,
        'ledger' => $ledger,
    ]);
    exit;
}

// ---------------- Stripe Pay Page (Payment Element) ----------------

if ($method === 'GET' && $path === '/checkout/pay') {
    $ref = (string)($_GET['ref'] ?? '');
    if ($ref === '') {
        header('Location: ' . $basePath . '/shop');
        exit;
    }

    $pdo = Db::pdo();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_public_id = ? LIMIT 1");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo "Order not found";
        exit;
    }

    if (empty($order['stripe_payment_intent_id'])) {
        http_response_code(400);
        echo "Order missing Stripe PaymentIntent.";
        exit;
    }

    $publishableKey = (string)($config['stripe']['publishable_key'] ?? '');
    if ($publishableKey === '') {
        http_response_code(500);
        echo "Stripe publishable key not configured.";
        exit;
    }

    stripe_init($config);
    $pi = \Stripe\PaymentIntent::retrieve($order['stripe_payment_intent_id']);
    $clientSecret = (string)($pi->client_secret ?? '');

    if ($clientSecret === '') {
        http_response_code(500);
        echo "Unable to retrieve PaymentIntent client_secret.";
        exit;
    }

    // Return URL must be absolute for Stripe redirect flows
    $returnUrl = 'https://www.blueoaksoftware.com' . $basePath . '/checkout/complete?ref=' . urlencode($ref);

    render('checkout/pay', [
        'order' => $order,
        'publishableKey' => $publishableKey,
        'clientSecret' => $clientSecret,
        'returnUrl' => $returnUrl,
    ]);
    exit;
}

if ($method === 'GET' && $path === '/checkout/complete') {
    $ref = (string)($_GET['ref'] ?? '');
    if ($ref === '') {
        header('Location: ' . $basePath . '/shop');
        exit;
    }

    $pdo = Db::pdo();
    $stmt = $pdo->prepare("SELECT order_public_id, status, total_cents FROM orders WHERE order_public_id = ? LIMIT 1");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo "Order not found";
        exit;
    }

	// Webhook is source of truth; this page just confirms redirect succeeded.
	render('checkout/complete', [
		'order' => $order,
	]);
	exit;
}

// ---------------- Stripe Webhook ----------------

if ($method === 'POST' && $path === '/webhooks/stripe') {
    $payload = file_get_contents('php://input') ?: '';
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    $whSecret = (string)($config['stripe']['webhook_secret'] ?? '');
    if ($whSecret === '') {
        http_response_code(500);
        echo "Webhook secret not configured.";
        exit;
    }

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $whSecret);
    } catch (Throwable $e) {
        http_response_code(400);
        echo "Invalid webhook: " . $e->getMessage();
        exit;
    }

    $pdo = Db::pdo();
    $eventId = (string)($event->id ?? '');
    $eventType = (string)($event->type ?? '');
    $livemode = !empty($event->livemode) ? 1 : 0;

    if ($eventId === '' || $eventType === '') {
        http_response_code(400);
        echo "Malformed webhook event.";
        exit;
    }

    // Idempotency: store event_id unique
    try {
        $stmt = $pdo->prepare("
            INSERT INTO stripe_webhook_events (stripe_event_id, event_type, livemode, payload_json)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$eventId, $eventType, $livemode, $payload]);
    } catch (Throwable $e) {
        // Likely duplicate -> already processed
        http_response_code(200);
        echo "OK";
        exit;
    }

    try {
        $stmtLedger = $pdo->prepare("
            INSERT INTO ledger_entries (order_id, event_type, event_source, message, amount_delta_cents, data_json)
            VALUES (:order_id, :event_type, :event_source, :message, :amount_delta_cents, :data_json)
        ");

        $order = null;
        $orderId = null;

        // payment_intent.* events
        if (str_starts_with($eventType, 'payment_intent.')) {
            $pi = $event->data->object;
            $piId = (string)($pi->id ?? '');

            if ($piId !== '') {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE stripe_payment_intent_id = ? LIMIT 1");
                $stmt->execute([$piId]);
                $order = $stmt->fetch();
                if ($order) { $orderId = (int)$order['order_id']; }
            }
        }

        // charge.refunded includes payment_intent
        if ($order === null && $eventType === 'charge.refunded') {
            $ch = $event->data->object;
            $piId = (string)($ch->payment_intent ?? '');
            if ($piId !== '') {
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE stripe_payment_intent_id = ? LIMIT 1");
                $stmt->execute([$piId]);
                $order = $stmt->fetch();
                if ($order) { $orderId = (int)$order['order_id']; }
            }
        }

        if ($order === null || $orderId === null) {
            $pdo->prepare("UPDATE stripe_webhook_events SET processed_at_utc = strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE stripe_event_id = ?")
                ->execute([$eventId]);
            http_response_code(200);
            echo "OK";
            exit;
        }

        // Ledger: webhook received
        $stmtLedger->execute([
            ':order_id' => $orderId,
            ':event_type' => 'webhook_received',
            ':event_source' => 'stripe_webhook',
            ':message' => $eventType . ' received.',
            ':amount_delta_cents' => null,
            ':data_json' => null,
        ]);

        if ($eventType === 'payment_intent.succeeded') {
            $pdo->prepare("UPDATE orders SET status='paid', paid_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now'), updated_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE order_id=?")
                ->execute([$orderId]);

            $stmtLedger->execute([
                ':order_id' => $orderId,
                ':event_type' => 'order_paid',
                ':event_source' => 'stripe_webhook',
                ':message' => 'Payment succeeded.',
                ':amount_delta_cents' => (int)$order['total_cents'],
                ':data_json' => null,
            ]);
        }

        if ($eventType === 'payment_intent.payment_failed') {
            $pdo->prepare("UPDATE orders SET status='failed', updated_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE order_id=?")
                ->execute([$orderId]);

            $stmtLedger->execute([
                ':order_id' => $orderId,
                ':event_type' => 'order_failed',
                ':event_source' => 'stripe_webhook',
                ':message' => 'Payment failed.',
                ':amount_delta_cents' => null,
                ':data_json' => null,
            ]);
        }

        if ($eventType === 'charge.refunded') {
            $pdo->prepare("UPDATE orders SET status='refunded', updated_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE order_id=?")
                ->execute([$orderId]);

            $stmtLedger->execute([
                ':order_id' => $orderId,
                ':event_type' => 'order_refunded',
                ':event_source' => 'stripe_webhook',
                ':message' => 'Charge refunded.',
                ':amount_delta_cents' => null,
                ':data_json' => null,
            ]);
        }

        $pdo->prepare("UPDATE stripe_webhook_events SET processed_at_utc = strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE stripe_event_id = ?")
            ->execute([$eventId]);

        http_response_code(200);
        echo "OK";
        exit;

    } catch (Throwable $e) {
        $pdo->prepare("UPDATE stripe_webhook_events SET processing_error = ?, processed_at_utc = strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE stripe_event_id = ?")
            ->execute([$e->getMessage(), $eventId]);

        // Return 200 so Stripe doesn't retry forever in the demo
        http_response_code(200);
        echo "OK";
        exit;
    }
}

// ---------------- Admin Refund ----------------

if ($method === 'POST' && $path === '/admin/orders/refund') {
	auth_require();
	csrf_verify($_POST['_csrf'] ?? null);
	
    $ref = (string)($_POST['ref'] ?? '');
    if ($ref === '') {
        header('Location: ' . $basePath . '/admin/orders');
        exit;
    }

    $pdo = Db::pdo();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_public_id = ? LIMIT 1");
    $stmt->execute([$ref]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo "Order not found";
        exit;
    }

    if ($order['status'] !== 'paid') {
        http_response_code(400);
        echo "Only paid orders can be refunded.";
        exit;
    }

    if (empty($order['stripe_payment_intent_id'])) {
        http_response_code(400);
        echo "Order missing Stripe PaymentIntent.";
        exit;
    }

    stripe_init($config);

    try {
        \Stripe\Refund::create([
            'payment_intent' => $order['stripe_payment_intent_id'],
        ]);

        $pdo->prepare("UPDATE orders SET status='refunding', updated_at_utc=strftime('%Y-%m-%dT%H:%M:%SZ','now') WHERE order_id=?")
            ->execute([(int)$order['order_id']]);

        $stmtLedger = $pdo->prepare("
            INSERT INTO ledger_entries (order_id, event_type, event_source, message, amount_delta_cents, data_json)
            VALUES (:order_id, :event_type, :event_source, :message, :amount_delta_cents, :data_json)
        ");
        $stmtLedger->execute([
            ':order_id' => (int)$order['order_id'],
            ':event_type' => 'refund_requested',
            ':event_source' => 'admin',
            ':message' => 'Refund requested from admin.',
            ':amount_delta_cents' => null,
            ':data_json' => null,
        ]);

        header('Location: ' . $basePath . '/admin/orders/view?ref=' . urlencode($ref));
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        echo "Refund failed: " . htmlspecialchars($e->getMessage());
        exit;
    }
}

http_response_code(404);
echo "Not Found";