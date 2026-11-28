<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    $submitted = (string)$_POST['admin_password'];
    $authenticated = false;
    try {
        $adminRow = db_row('SELECT password_hash FROM admin_users WHERE username = ? LIMIT 1', ['admin']);
        if ($adminRow && password_verify($submitted, (string)$adminRow['password_hash'])) $authenticated = true;
        if (!$adminRow) $authenticated = hash_equals(ADMIN_PASSWORD, $submitted);
    } catch (Throwable $ignored) {
        $authenticated = hash_equals(ADMIN_PASSWORD, $submitted);
    }
    if ($authenticated) {
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
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><script>(function(){var t;try{t=localStorage.getItem('filipinonly.theme')}catch(e){}if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t;})();</script><title>Staff Dashboard | FilipinOnly</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><link rel="stylesheet" href="assets/css/admin.css"><script src="assets/js/theme.js" defer></script></head><body class="admin-body">
<header class="admin-top"><a class="brand" href="index.php"><span class="brand-mark">F</span><span class="brand-copy">filipin<span>only</span></span></a><div class="admin-top-actions"><a class="admin-back" href="index.php">&larr; Back to storefront</a><button class="icon-button theme-button" id="themeToggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode"><svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg><svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button></div></header>
<main class="admin-wrap">
<?php if (!$isAdmin): ?>
<section class="admin-login panel"><span class="section-kicker">Staff access</span><h1>Sign in</h1><p>Manage incoming orders and update customer tracking.</p><?php if ($error): ?><div class="admin-error"><?= htmlspecialchars($error) ?></div><?php endif; ?><form method="post"><label class="field">Staff password<input type="password" name="admin_password" required autofocus placeholder="Enter dashboard password"></label><button class="btn btn-primary" type="submit">Open dashboard →</button></form><small>Demo default password is documented in README.md. Change it in config.php before your presentation.</small></section>
<?php else: ?>
<div class="admin-heading"><div><span class="section-kicker">Orders</span><h1>Order dashboard</h1><p>Review orders and keep customers updated.</p></div><div class="admin-heading-actions"><span class="mode-chip">Storage: <?= htmlspecialchars(ORDER_STORAGE_MODE) ?></span><a class="admin-back" href="admin.php?logout=1">Sign out</a></div></div>
<div class="admin-toolbar"><div><strong id="ordersCount">Orders</strong><span id="lastUpdated">Loading orders…</span></div><button class="btn btn-outline" id="refreshOrders">Refresh</button></div>
<div id="adminMessage" class="admin-message" hidden></div>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Update</th><th>Receipt</th></tr></thead><tbody id="ordersBody"><tr><td colspan="8">Loading orders…</td></tr></tbody></table></div>
<div class="admin-hint"><strong>Demo note:</strong> GCash and card selections are simulated. Mark an order Delivered only after your team confirms the handoff during your demonstration.</div>
<?php endif; ?>
</main>
<?php if ($isAdmin): ?><script>window.FILIPINONLY_ADMIN={csrf:<?= json_encode($_SESSION['csrf']) ?>};</script><script src="assets/js/sound.js" defer></script><script src="assets/js/admin.js?v=6" defer></script><?php endif; ?>
</body></html>
