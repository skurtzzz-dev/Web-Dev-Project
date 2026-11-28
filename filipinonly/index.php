<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$menu = require __DIR__ . '/data/menu.php';
$branches = require __DIR__ . '/data/branches.php';
$cities = array_column($branches, 'city');
sort($cities, SORT_NATURAL | SORT_FLAG_CASE);
$categories = ['All', 'Almusal', 'Tanghalian', 'Hapunan', 'Panghimagas'];
$heroPicks = [];
foreach (['sinigang', 'tapsilog', 'halohalo'] as $heroPickId) {
    foreach ($menu as $item) {
        if ($item['id'] === $heroPickId) { $heroPicks[] = $item; break; }
    }
}
$csrf = htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1f1c19">
    <meta name="description" content="FilipinOnly — Filipino favorites from 17 branches across Metro Manila.">
    <title>FilipinOnly — Filipino food, cooked today</title>
    <script>(function(){var t;try{t=localStorage.getItem('filipinonly.theme')}catch(e){}if(!t)t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t;})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=Fraunces:ital,wght@0,700;0,800;1,700;1,800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=20">
    <script>window.FILIPINONLY = { csrf: <?= json_encode($_SESSION['csrf']) ?>, menu: <?= json_encode($menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, branches: <?= json_encode($branches, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>, googleClientId: <?= json_encode(GOOGLE_CLIENT_ID) ?> };</script>
    <?php if (GOOGLE_CLIENT_ID !== ''): ?><script src="https://accounts.google.com/gsi/client" async defer></script><?php endif; ?>
    <script src="assets/js/sound.js?v=5" defer></script>
    <script src="assets/js/theme.js?v=5" defer></script>
    <script src="assets/js/app.js?v=20" defer></script>
</head>
<body>
<div class="announcement">Freshly Guaranteed! &middot; Open 9 AM–9 PM &middot; Free delivery over &#8369;499</div>

<header class="site-header">
    <div class="header-inner wrap">
        <a class="brand" href="#home" aria-label="FilipinOnly home">
            <span class="brand-mark">F</span>
            <span class="brand-copy">filipin<span>only</span></span>
        </a>
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open navigation" aria-expanded="false"><span></span><span></span></button>
        <nav class="main-nav" id="mainNav">
            <a class="nav-link active" href="#home">Home</a>
            <a class="nav-link" href="#menu">Menu</a>
            <a class="nav-link" href="#branches">Branches</a>
            <button class="nav-link nav-button" id="trackNavBtn">Track an order</button>
        </nav>
        <div class="header-actions">
            <button class="icon-button account-button" id="accountBtn"><span class="account-label" id="accountLabel">Sign in</span></button>
            <button class="bag-button" id="openCartBtn">Bag <b id="cartCount">0</b></button>
            <button class="icon-button theme-button" id="themeToggle" aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode"><svg class="theme-icon theme-icon-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg><svg class="theme-icon theme-icon-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg></button>
        </div>
    </div>
</header>

<main>
    <section class="hero wrap" id="home">
        <div class="hero-copy">
            <div class="hero-eyebrow"><span class="hero-dot"></span>Freshly Guaranteed!</div>
            <h1>Filipino food,<br><em>cooked today.</em></h1>
            <p class="hero-description">Silog at 7 AM. Sinigang when the rain comes. Halo-halo by 2 PM. Twenty dishes made in small batches and sent out while they're still warm.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#menu">See the menu <span>&rarr;</span></a>
                <a class="btn btn-text" href="#categories">Pick a mealtime <span>&darr;</span></a>
            </div>
            <div class="hero-stats">
                <div><strong>Open 9 AM–9 PM</strong><span>every day</span></div>
                <div><strong>30–45 min</strong><span>delivery</span></div>
                <div><strong>&#8369;499</strong><span>free delivery</span></div>
            </div>
        </div>
        <div class="hero-art" aria-label="Freshly guaranteed picks">
            <div class="hero-board">
                <div class="hero-board-head">
                    <div><span>Kitchen board</span><strong>Fresh today</strong></div>
                    <span class="hero-board-stamp">FRESH<br>DAILY</span>
                </div>
                <div class="hero-board-list">
                    <?php foreach ($heroPicks as $index => $item): ?>
                        <a class="hero-board-row" href="#menu">
                            <span class="hero-board-num"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="hero-board-copy"><strong><?= htmlspecialchars($item['name']) ?></strong><small><?= htmlspecialchars($item['serving']) ?> &middot; <?= htmlspecialchars($item['prep']) ?></small></span>
                            <span class="hero-board-price">&#8369;<?= number_format((float)$item['price']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="hero-board-footer"><span>Freshly guaranteed</span></div>
            </div>
            <div class="hero-board-note"><span>Freshly Guaranteed!</span><strong>small batches, served warm</strong><small>made fresh daily</small></div>
        </div>
    </section>

    <section class="category-strip" id="categories">
        <div class="wrap category-inner">
            <div class="category-intro">
                <span class="section-kicker">Browse the board</span>
                <h2>Choose a time of day.</h2>
                <p>A small menu for every kind of appetite.</p>
            </div>
            <div class="category-tiles" id="categoryTiles">
                <button class="category-tile selected" data-category="All"><span class="category-num">01</span>Everything<span class="category-arrow">&rarr;</span></button>
                <button class="category-tile" data-category="Almusal"><span class="category-num">02</span>Almusal<span class="category-arrow">&rarr;</span></button>
                <button class="category-tile" data-category="Tanghalian"><span class="category-num">03</span>Tanghalian<span class="category-arrow">&rarr;</span></button>
                <button class="category-tile" data-category="Hapunan"><span class="category-num">04</span>Hapunan<span class="category-arrow">&rarr;</span></button>
                <button class="category-tile" data-category="Panghimagas"><span class="category-num">05</span>Panghimagas<span class="category-arrow">&rarr;</span></button>
            </div>
        </div>
    </section>

    <section class="menu-section wrap" id="menu">
        <div class="section-heading">
            <div>
                <span class="section-kicker">The menu</span>
                <h2>What we're <em>cooking today.</em></h2>
                <p>Rice plates, soups, and something sweet. Every dish shows its serving size before you add it to the bag.</p>
            </div>
            <div class="menu-tools">
                <label class="search-box"><span>&nearr;</span><input type="search" id="menuSearch" placeholder="Search dishes or ingredients" aria-label="Search the menu"></label>
                <select id="sortMenu" aria-label="Sort menu">
                    <option value="featured">Featured</option>
                    <option value="low">Price: low to high</option>
                    <option value="high">Price: high to low</option>
                </select>
            </div>
        </div>
        <div class="menu-filter-row">
            <div class="filter-pills" id="filterPills"><?php foreach ($categories as $category): ?><button class="filter-pill <?= $category === 'All' ? 'active' : '' ?>" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category === 'All' ? 'All dishes' : $category) ?></button><?php endforeach; ?></div>
            <span class="menu-count" id="menuCount">20 dishes</span>
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($menu as $item): ?>
                <article class="product-card" data-id="<?= htmlspecialchars($item['id']) ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="product-image"><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"><span class="product-tag"><?= htmlspecialchars($item['tag']) ?></span></div>
                    <div class="product-info">
                        <div class="product-meta"><span><?= htmlspecialchars($item['category']) ?></span><span><?= htmlspecialchars($item['prep']) ?></span></div>
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p><?= htmlspecialchars($item['description']) ?></p>
                        <div class="allergen-line">Contains: <?= htmlspecialchars(implode(', ', $item['allergens'])) ?></div>
                        <div class="serving-line"><span>Serving size</span><strong><?= htmlspecialchars($item['serving'] ?? '1 serving') ?></strong></div>
                        <div class="product-bottom"><strong>&#8369;<?= number_format($item['price'], 2) ?></strong><div><button class="customize-btn" data-customize="<?= htmlspecialchars($item['id']) ?>">Customize</button><button class="add-btn" data-add="<?= htmlspecialchars($item['id']) ?>" aria-label="Add <?= htmlspecialchars($item['name']) ?>">Add</button></div></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="no-results" id="noResults" hidden><h3>Walang nakita.</h3><p>Try a different dish name or ingredient.</p><button class="text-button" id="clearSearch">Show all dishes &rarr;</button></div>
    </section>

    <section class="promo-section wrap">
        <div class="promo-card">
            <div class="promo-copy">
                <span class="promo-label">At checkout</span>
                <h2>For the whole table.</h2>
                <p>More dishes on the table, less on the bill. Use <strong>SARAP10</strong> for 10% off orders over &#8369;299.</p>
                <a class="btn btn-light" href="#menu">Back to the menu <span>&rarr;</span></a>
            </div>
            <div class="promo-image"><img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1000&q=85" alt="A table of food ready to be shared"></div>
        </div>
    </section>

    <section class="branches-section wrap" id="branches">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Metro Manila</span>
                <h2>17 neighborhoods, one kitchen.</h2>
                <p>One branch per city and municipality in NCR. Pick the one nearest you.</p>
            </div>
        </div>
        <div class="branch-tools">
            <label for="branchCityFilter">Filter by city</label>
            <select id="branchCityFilter">
                <option value="all">All cities</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
            <span id="branchCount"><?= count($branches) ?> branches</span>
        </div>
        <div class="branch-grid">
            <?php foreach ($branches as $branch): ?>
                <article class="branch-card" data-city="<?= htmlspecialchars($branch['city']) ?>">
                    <span class="branch-city"><?= htmlspecialchars($branch['city']) ?></span>
                    <strong class="branch-area"><?= htmlspecialchars($branch['area']) ?></strong>
                    <p class="branch-address"><?= htmlspecialchars($branch['address']) ?></p>
                    <span class="branch-hours">Open <?= htmlspecialchars($branch['hours']) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="how-section wrap">
        <div class="section-heading">
            <div>
                <span class="section-kicker">How it works</span>
                <h2>From our kitchen to your door.</h2>
            </div>
        </div>
        <div class="steps-grid">
            <article class="step-card"><span class="step-number">01</span><h3>Pick your dish and serving</h3><p>Choose from twenty dishes, check the serving size, and adjust rice, spice, and ingredients.</p></article>
            <article class="step-card"><span class="step-number">02</span><h3>We cook your order</h3><p>Nothing is pre-packed. The kitchen starts after checkout; give us 20–40 minutes depending on the dish.</p></article>
            <article class="step-card"><span class="step-number">03</span><h3>A rider picks it up</h3><p>Delivery is &#8369;39, or free when your order reaches &#8369;499. Track the status from your confirmation.</p></article>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="wrap footer-main">
        <div class="footer-brand">
            <a class="brand brand-footer" href="#home"><span class="brand-mark">F</span><span class="brand-copy">filipin<span>only</span></span></a>
            <p>A small food-ordering prototype built for a web development class.</p>
        </div>
        <div class="footer-links"><strong>Site</strong><a href="#menu">Menu</a><a href="#branches">Branches</a><button id="footerTrackBtn">Track an order</button></div>
        <div class="footer-links"><strong>Details</strong><a href="mailto:hello@filipinonly.demo">hello@filipinonly.demo</a><a href="admin.php">Staff dashboard</a><span>Mon–Sun, 9 AM–9 PM</span></div>
        <div class="footer-note"><strong>Demo only</strong><p>No real orders, payments, or deliveries are processed.</p></div>
    </div>
    <div class="wrap footer-bottom"><span>&copy; <?= date('Y') ?> FilipinOnly. Academic project.</span><span>Quezon City, Philippines</span></div>
</footer>

<div class="overlay" id="cartOverlay"></div>
<aside class="drawer" id="cartDrawer" aria-label="Shopping bag" aria-hidden="true">
    <div class="drawer-header"><div><span class="section-kicker">Your order</span><h2>Bag</h2></div><button class="close-button" data-close="cart" aria-label="Close bag">&times;</button></div>
    <div class="cart-content" id="cartContent"></div>
    <div class="cart-footer" id="cartFooter" hidden>
        <div class="voucher-row"><input id="voucherInput" type="text" placeholder="Promo code"><button id="applyVoucherBtn">Apply</button></div>
        <p class="voucher-hint" id="voucherHint">Try <strong>SARAP10</strong> for 10% off orders over &#8369;299.</p>
        <div class="totals-row"><span>Subtotal</span><strong id="cartSubtotal">&#8369;0.00</strong></div>
        <div class="totals-row discount-row" id="discountRow" hidden><span>Voucher discount</span><strong id="cartDiscount">&minus;&#8369;0.00</strong></div>
        <div class="totals-row"><span>Delivery</span><strong>&#8369;39.00, free over &#8369;499</strong></div>
        <div class="cart-total"><span>Estimated total</span><strong id="cartTotal">&#8369;0.00</strong></div>
        <button class="btn btn-primary checkout-btn" id="checkoutBtn">Continue to checkout <span>&rarr;</span></button>
        <small class="secure-note">Demo checkout. No payment is collected.</small>
    </div>
</aside>

<div class="modal-backdrop" id="checkoutModal">
    <section class="modal checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
        <div class="modal-header"><div><span class="section-kicker">Checkout</span><h2 id="checkoutTitle">Delivery details</h2></div><button class="close-button" data-close="checkout" aria-label="Close checkout">&times;</button></div>
        <form id="checkoutForm">
            <div class="checkout-layout">
                <div class="checkout-fields">
                    <div class="form-section-title"><span>01</span><div><strong>Delivery information</strong><small>Where should we bring your food?</small></div></div>
                    <div class="form-grid">
                        <label class="field full"><span class="field-label">Full name</span><input name="name" id="checkoutName" required autocomplete="name" placeholder="e.g. Juan dela Cruz"></label>
                        <label class="field"><span class="field-label">Contact number</span><input name="phone" id="checkoutPhone" required autocomplete="tel" inputmode="tel" placeholder="09XX XXX XXXX"></label>
                        <label class="field"><span class="field-label">Email address <small>(optional)</small></span><input name="email" id="checkoutEmail" type="email" autocomplete="email" placeholder="you@example.com"></label>
                        <label class="field full"><span class="field-label">House no., street, barangay</span><input name="address_line" id="checkoutAddressLine" required autocomplete="address-line1" placeholder="e.g. 12 Rizal St., Barangay San Jose"></label>
                        <label class="field"><span class="field-label">City / municipality</span><select name="city" id="checkoutCity" required autocomplete="address-level2"><option value="" disabled selected>Choose NCR city</option></select></label>
                        <label class="field"><span class="field-label">Province / region</span><select name="province" id="checkoutProvince" autocomplete="address-level1"><option value="Metro Manila" selected>Metro Manila (NCR)</option></select></label>
                        <label class="field"><span class="field-label">ZIP code</span><input name="zip" id="checkoutZip" required autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{4,5}" maxlength="5" placeholder="e.g. 1101"></label>
                        <label class="field"><span class="field-label">Nearby landmark <small>(optional)</small></span><input name="landmark" id="checkoutLandmark" placeholder="e.g. Across the town plaza"></label>
                    </div>
                    <div class="delivery-choice">
                        <label class="radio-card"><input type="radio" name="delivery" value="ASAP" checked><span><b>Deliver ASAP</b><small>Usually 30–45 minutes</small></span></label>
                        <label class="radio-card"><input type="radio" name="delivery" value="Scheduled"><span><b>Schedule delivery</b><small>Pick a date and time</small></span></label>
                    </div>
                    <label class="field schedule-field" id="scheduleField" hidden>Delivery date & time<input type="datetime-local" name="schedule" id="checkoutSchedule"></label>
                    <div class="form-section-title payment-title"><span>02</span><div><strong>Payment method</strong><small>Choose how you'd like to pay.</small></div></div>
                    <div class="payment-options">
                        <label class="payment-option"><input type="radio" name="payment" value="GCash" checked><span class="payment-symbol gcash">G</span><span><b>GCash</b><small>E-wallet</small></span></label>
                        <label class="payment-option"><input type="radio" name="payment" value="Maya"><span class="payment-symbol gcash">M</span><span><b>Maya</b><small>E-wallet</small></span></label>
                        <label class="payment-option"><input type="radio" name="payment" value="Cash on Delivery"><span class="payment-symbol cash">&#8369;</span><span><b>Cash on Delivery</b><small>Pay when it arrives</small></span></label>
                        <label class="payment-option"><input type="radio" name="payment" value="Credit/Debit Card"><span class="payment-symbol card-symbol">&#9632;</span><span><b>Credit / Debit</b><small>Demo card selection</small></span></label>
                    </div>
                </div>
                <aside class="checkout-summary">
                    <span class="section-kicker">Your order</span>
                    <div id="checkoutSummaryItems"></div>
                    <div class="summary-divider"></div>
                    <div class="summary-line"><span>Subtotal</span><strong id="checkoutSubtotal">&#8369;0.00</strong></div>
                    <div class="summary-line"><span>Discount</span><strong id="checkoutDiscount">&#8369;0.00</strong></div>
                    <div class="summary-line"><span>Delivery fee</span><strong id="checkoutDelivery">&#8369;39.00</strong></div>
                    <div class="summary-line"><span>Estimated delivery</span><strong id="checkoutEta">30–45 minutes</strong></div>
                    <div class="summary-total"><span>Total</span><strong id="checkoutTotal">&#8369;0.00</strong></div>
                    <p class="demo-payment-note">Classroom demo. No payment will be charged.</p>
                    <button class="btn btn-primary place-order-btn" type="submit">Place my order <span>&rarr;</span></button>
                    <small class="terms-note">By placing your order, you agree to this demo checkout flow.</small>
                </aside>
            </div>
        </form>
    </section>
</div>

<div class="modal-backdrop" id="customizeModal">
    <section class="modal customize-modal" role="dialog" aria-modal="true" aria-labelledby="customizeTitle">
        <div class="modal-header"><div><span class="section-kicker">Make it yours</span><h2 id="customizeTitle">Customize your dish</h2></div><button class="close-button" data-close="customize" aria-label="Close customization">&times;</button></div>
        <div id="customizeBody"></div>
    </section>
</div>

<div class="modal-backdrop" id="accountModal">
    <section class="modal account-modal" role="dialog" aria-modal="true" aria-labelledby="accountTitle">
        <div class="modal-header"><div><span class="section-kicker">Account</span><h2 id="accountTitle">Sign in</h2></div><button class="close-button" data-close="account" aria-label="Close account">&times;</button></div>
        <div id="accountContent"></div>
    </section>
</div>

<div class="modal-backdrop" id="historyModal">
    <section class="modal tracking-modal" role="dialog" aria-modal="true" aria-labelledby="historyTitle">
        <div class="modal-header"><div><span class="section-kicker">Account</span><h2 id="historyTitle">Order history</h2></div><button class="close-button" data-close="history" aria-label="Close order history">&times;</button></div>
        <div id="historyResult"><div class="tracking-empty"><p>Loading your orders…</p></div></div>
    </section>
</div>

<div class="modal-backdrop" id="trackingModal">
    <section class="modal tracking-modal" role="dialog" aria-modal="true" aria-labelledby="trackingTitle">
        <div class="modal-header"><div><span class="section-kicker">Order status</span><h2 id="trackingTitle">Track your order</h2></div><button class="close-button" data-close="tracking" aria-label="Close tracking">&times;</button></div>
        <form class="track-search" id="trackForm"><label class="field">Enter your order number<input type="text" id="trackingCode" placeholder="e.g. FO-A1B2C3D4" required></label><button class="btn btn-primary" type="submit">Check status <span>&rarr;</span></button></form>
        <div id="trackingResult"></div>
        <p class="tracking-disclaimer">Demo tracking: staff updates the status. Live rider GPS and real payment processing are not connected.</p>
    </section>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<div class="mobile-bottom-bar"><button id="mobileMenuLink">Menu</button><button id="mobileCartLink">Bag <b id="mobileCartCount">0</b></button></div>
</body>
</html>
