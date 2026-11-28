<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/lib/order_store.php';
$id = strtoupper(trim((string)($_GET['id'] ?? '')));
$order = null; $error = '';
try { $order = find_order($id); if (!$order) $error = 'Order not found. Check the order number and try again.'; }
catch (Throwable $e) { $error = $e->getMessage(); }
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function peso_receipt(float|int $value): string { return '₱' . number_format((float)$value, 2); }
function order_eta(array $order): string {
    $eta = trim((string)($order['eta'] ?? ''));
    return $eta !== '' ? $eta : '30–45 minutes';
}
function receipt_item_details(array $item): string {
    $details = [];
    if (!empty($item['serving'])) $details[] = 'Serving: ' . (string)$item['serving'];
    if (!empty($item['custom']['rice']) && $item['custom']['rice'] !== 'No rice') $details[] = (string)$item['custom']['rice'];
    if (!empty($item['custom']['spice']) && $item['custom']['spice'] !== 'Regular') $details[] = (string)$item['custom']['spice'] . ' spice';
    foreach (($item['custom']['removed'] ?? []) as $removed) {
        if (is_string($removed) && trim($removed) !== '') $details[] = 'No ' . $removed;
    }
    return implode(' · ', $details);
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Receipt <?= h($id) ?> | FilipinOnly</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;700;800;900&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet"><style>
:root{--ink:#1f1c19;--muted:#6d675e;--line:#ddd6c9;--red:#c2412d;--paper:#f6f3ec}
*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.5 'Archivo',system-ui,sans-serif}.receipt{width:min(720px,calc(100% - 28px));margin:32px auto;background:#fffdf8;padding:38px 42px;border:1px solid var(--line)}
.brand{font:900 24px/1 'Archivo',sans-serif;letter-spacing:-1px}.brand span{color:var(--red)}.tag{font:500 10px/1 'IBM Plex Mono',monospace;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-top:8px}
.paid{font:600 11px/1 'IBM Plex Mono',monospace;letter-spacing:1px;color:var(--red);margin-top:26px;border-top:3px solid var(--ink);padding-top:20px}.heading{font:900 30px/1 'Archivo',sans-serif;letter-spacing:-1px;margin:12px 0 28px}
.meta{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:20px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.meta small{display:block;font:500 10px/1 'IBM Plex Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:5px}.meta strong{font-size:14px}
.items{width:100%;border-collapse:collapse;margin:26px 0}.items th{text-align:left;font:500 10px/1 'IBM Plex Mono',monospace;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);padding:10px 0;border-bottom:1px solid var(--line)}.items td{padding:14px 0;border-bottom:1px solid var(--line-soft)}.items th:last-child,.items td:last-child{text-align:right;font-family:'IBM Plex Mono',monospace}.items th:nth-child(2),.items td:nth-child(2){text-align:center;font-family:'IBM Plex Mono',monospace}.items small{color:var(--muted)}
.totals{width:min(300px,100%);margin-left:auto}.line{display:flex;justify-content:space-between;padding:7px 0;color:var(--muted);font-size:13px}.line strong{font-family:'IBM Plex Mono',monospace;font-weight:500;color:var(--ink)}.total{border-top:2px solid var(--ink);margin-top:8px;padding-top:14px;font-size:17px;font-weight:800;color:var(--ink)}
.footer{border-top:1px solid var(--line);padding-top:22px;margin-top:32px;text-align:center;color:var(--muted);font-size:12px}.footer p{margin:8px 0}.footer small{font-size:11px}
.actions{display:flex;justify-content:center;gap:10px;margin:22px auto 40px}.actions button,.actions a{border:1px solid var(--ink);background:var(--ink);color:#fff;padding:11px 16px;font-size:13px;font-weight:700;text-decoration:none;cursor:pointer;transition:transform .12s ease,background-color .15s,border-color .15s,color .15s}.actions a{background:transparent;color:var(--ink)}.actions button:hover{background:var(--red);border-color:var(--red)}.actions a:hover{background:var(--paper)}.actions button:active,.actions a:active{transform:scale(.97)}.error a:hover{text-decoration:underline}:focus-visible{outline:2px solid var(--red);outline-offset:2px}
.error{margin:80px auto;text-align:center;max-width:500px;padding:26px;background:#fffdf8;border:1px solid var(--line)}@media print{body{background:#fff}.receipt{margin:0 auto;border:0;width:100%;padding:12mm}.actions{display:none}}
</style><script src="assets/js/sound.js" defer></script></head><body>
<?php if ($error): ?><section class="error"><h2>Receipt unavailable</h2><p><?= h($error) ?></p><p><a href="index.php">Return to FilipinOnly</a></p></section><?php else: ?>
<main class="receipt"><div class="brand">filipin<span>only</span></div><div class="tag"><?= h((string)(branch_for_order($order)['city'] ?? 'Quezon City')) ?> &middot; Demo receipt</div><div class="paid">Order confirmation</div><h1 class="heading">Receipt</h1>
<div class="meta"><div><small>Receipt number</small><strong><?= h((string)($order['receipt_number'] ?? '')) ?></strong></div><div><small>Order number</small><strong><?= h((string)$order['id']) ?></strong></div><div><small>Branch location</small><strong><?= h(branch_label(branch_for_order($order))) ?></strong></div><div><small>Customer</small><strong><?= h((string)$order['name']) ?></strong></div><div><small>Date</small><strong><?= h((string)($order['created_at'] ?? '')) ?></strong></div><div><small>Rider contact</small><strong><?= h((string)$order['phone']) ?></strong></div><div><small>Payment method</small><strong><?= h((string)($order['payment'] ?? '')) ?></strong></div><div><small>Estimated delivery</small><strong><?= h(order_eta($order)) ?></strong></div><div><small>Order status</small><strong><?= h((string)($order['status'] ?? 'Order received')) ?></strong></div><div style="grid-column:1/-1"><small>Delivery address</small><strong><?= h((string)($order['address'] ?? '')) ?><?= !empty($order['landmark']) ? ' · ' . h((string)$order['landmark']) : '' ?></strong></div></div>
<table class="items"><thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead><tbody><?php foreach (($order['items'] ?? []) as $item): ?><tr><td><?= h((string)($item['name'] ?? 'Food item')) ?><br><small><?= h(receipt_item_details($item)) ?></small></td><td><?= (int)($item['qty'] ?? 1) ?></td><td><?= peso_receipt((float)($item['line_total'] ?? 0)) ?></td></tr><?php endforeach; ?></tbody></table>
<div class="totals"><div class="line"><span>Subtotal</span><strong><?= peso_receipt((float)($order['subtotal'] ?? 0)) ?></strong></div><div class="line"><span>Discount</span><strong>&minus;<?= peso_receipt((float)($order['discount'] ?? 0)) ?></strong></div><div class="line"><span>Delivery fee</span><strong><?= peso_receipt((float)($order['delivery_fee'] ?? 0)) ?></strong></div><div class="line total"><span>Order total</span><strong><?= peso_receipt((float)($order['total'] ?? 0)) ?></strong></div></div>
<div class="footer"><strong>Salamat.</strong><p>Payment status: <?= h((string)($order['payment_status'] ?? 'Pending')) ?></p><small>This is an academic demo receipt. No real payment was processed.</small></div></main><div class="actions"><button onclick="window.print()">Print / Save as PDF</button><a href="index.php">Back to website</a></div>
<?php endif; ?></body></html>
