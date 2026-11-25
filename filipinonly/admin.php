<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if (hash_equals(ADMIN_PASSWORD, (string)$_POST['admin_password'])) {
        session_regenerate_id(true);
        $_SESSION['filipinonly_admin'] = true;
        header('Location: admin.php'); exit;
    }
    $error = 'Incorrect dashboard password.';
}
if (isset($_GET['logout'])) { unset($_SESSION['filipinonly_admin']); header('Location: admin.php'); exit; }
$csrf = htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8');
$isAdmin = !empty($_SESSION['filipinonly_admin']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Staff Dashboard | FilipinOnly</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/admin.css"></head><body class="admin-body">
<header class="admin-top"><a class="brand" href="index.php"><span class="brand-mark">F<span>+</span></span><span class="brand-copy"><strong>filipin<span>only</span></strong><small>STAFF DASHBOARD</small></span></a><a class="admin-back" href="index.php">← Back to storefront</a></header>
<main class="admin-wrap">
<?php if (!$isAdmin): ?>
<section class="admin-login panel"><span class="section-kicker">AUTHORIZED STAFF ONLY</span><h1>Welcome <em>back.</em></h1><p>Sign in to manage incoming orders and update customer tracking.</p><?php if ($error): ?><div class="admin-error"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post"><label class="field">Staff password<input type="password" name="admin_password" required autofocus placeholder="Enter dashboard password"></label><button class="btn btn-primary" type="submit">Open dashboard →</button></form><small>Demo default password is documented in README.md. Change it in config.php before your presentation.</small></section>
<?php else: ?>
<div class="admin-heading"><div><span class="section-kicker">ORDER OPERATIONS</span><h1>Order <em>dashboard.</em></h1><p>Review orders and keep customers updated.</p></div><div class="admin-heading-actions"><span class="mode-chip">Storage: <?= htmlspecialchars(ORDER_STORAGE_MODE) ?></span><a class="admin-back" href="admin.php?logout=1">Sign out</a></div></div>
<div class="admin-toolbar"><div><strong id="ordersCount">Orders</strong><span id="lastUpdated">Loading orders…</span></div><button class="btn btn-outline" id="refreshOrders">↻ Refresh</button></div>
<div id="adminMessage" class="admin-message" hidden></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Update</th><th>Receipt</th></tr></thead><tbody id="ordersBody"><tr><td colspan="8">Loading orders…</td></tr></tbody></table></div>
<div class="admin-hint"><strong>Demo note:</strong> GCash and card selections are simulated. Mark an order Delivered only after your team confirms the handoff during your demonstration.</div>
<?php endif; ?>
</main>
<?php if ($isAdmin): ?><script>window.FILIPINONLY_ADMIN={csrf:<?= json_encode($_SESSION['csrf']) ?>};</script><script src="assets/js/admin.js" defer></script><?php endif; ?>
</body></html>
