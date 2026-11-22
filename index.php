<?php
session_start();
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
$menu = require __DIR__ . '/data/menu.php';
$categories = ['All', 'Almusal', 'Tanghalian', 'Hapunan', 'Panghimagas'];
$csrf = htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fffaf3">
    <meta name="description" content="FilipinOnly — Filipino favorites, delivered fresh to your door.">
    <title>FilipinOnly — Lutong Pinoy, Delivered</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>window.FILIPINONLY = { csrf: <?= json_encode($_SESSION['csrf']) ?>, menu: <?= json_encode($menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> };</script>
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<div class="announcement"><span class="announcement-dot"></span> A little taste of home, wherever you are. <strong>Free delivery over ₱499</strong></div>
<header class="site-header">
    <div class="header-inner wrap">
        <a class="brand" href="#home" aria-label="FilipinOnly home">
            <span class="brand-mark">F<span>+</span></span>
            <span class="brand-copy"><strong>filipin<span>only</span></strong><small>LUTONG PINOY, DELIVERED</small></span>
        </a>
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open navigation"><span></span><span></span></button>
        <nav class="main-nav" id="mainNav">
            <a class="nav-link active" href="#home">Home</a>
            <a class="nav-link" href="#menu">Our Menu</a>
            <a class="nav-link" href="#story">Our Story</a>
            <button class="nav-link nav-button" id="trackNavBtn">Track Order</button>
        </nav>
        <div class="header-actions">
            <button class="icon-button account-button" id="accountBtn" aria-label="Your account"><span class="user-icon">♙</span><span class="account-label" id="accountLabel">Sign in</span></button>
            <button class="bag-button" id="openCartBtn" aria-label="Open shopping bag"><span class="bag-icon">▱</span><span>My Bag</span><b id="cartCount">0</b></button>
        </div>
    </div>
</header>

<main>
    <section class="hero wrap" id="home">
        <div class="hero-copy">
            <div class="eyebrow"><span class="eyebrow-line"></span> MADE WITH SARAP, MADE FOR YOU</div>
            <h1>Cravings for home?<br><em>We've got you.</em></h1>
            <p class="hero-description">From your morning silog to a bowl of sinigang for dinner, your Filipino favorites are just a few clicks away.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#menu">Explore the menu <span>↗</span></a>
                <div class="hero-social-proof"><div class="avatar-stack"><i>J</i><i>M</i><i>A</i></div><span><strong>1,200+</strong><small> happy foodies</small></span></div>
            </div>
            <div class="hero-perks"><span><b>✓</b> Freshly prepared</span><span><b>✓</b> Filipino-owned</span><span><b>✓</b> Made with love</span></div>
        </div>
        <div class="hero-art">
            <div class="hero-photo-main"><img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&q=90" alt="A freshly prepared Filipino-style meal"><span class="photo-sticker">100%<small>Pinoy<br>proud</small></span></div>
            <div class="hero-photo-small"><img src="https://images.unsplash.com/photo-1569718212165-3a8278d5f624?auto=format&fit=crop&w=600&q=85" alt="A bowl of noodles"></div>
            <div class="hero-floating-note"><span class="note-icon">♡</span><span><strong>Lasang tahanan</strong><small>Every single bite.</small></span></div>
            <span class="hero-sparkle sparkle-one">✳</span><span class="hero-sparkle sparkle-two">✦</span>
        </div>
        <div class="hero-bottom-label"><span>01</span><span class="hero-bottom-rule"></span><span>YOUR FILIPINO FOOD DESTINATION</span></div>
    </section>

    <section class="category-strip">
        <div class="wrap category-inner">
            <div><span class="section-kicker">WHAT ARE YOU IN THE MOOD FOR?</span><h2>Hanap mo, <em>luto namin.</em></h2></div>
            <div class="category-tiles" id="categoryTiles">
                <button class="category-tile selected" data-category="All"><span class="category-emoji">🍽️</span><span>Everything</span></button>
                <button class="category-tile" data-category="Almusal"><span class="category-emoji">🍳</span><span>Almusal</span></button>
                <button class="category-tile" data-category="Tanghalian"><span class="category-emoji">🍲</span><span>Tanghalian</span></button>
                <button class="category-tile" data-category="Hapunan"><span class="category-emoji">🍗</span><span>Hapunan</span></button>
                <button class="category-tile" data-category="Panghimagas"><span class="category-emoji">🍮</span><span>Panghimagas</span></button>
            </div>
        </div>
    </section>

    <section class="menu-section wrap" id="menu">
        <div class="section-heading">
            <div><span class="section-kicker">THE GOOD STUFF</span><h2>Our <em>menu.</em></h2><p>Timeless Filipino favorites, prepared fresh and delivered with care.</p></div>
            <div class="menu-tools"><label class="search-box"><span>⌕</span><input type="search" id="menuSearch" placeholder="Search your cravings..."></label><select id="sortMenu" aria-label="Sort menu"><option value="featured">Featured</option><option value="low">Price: low to high</option><option value="high">Price: high to low</option></select></div>
        </div>
        <div class="menu-filter-row"><div class="filter-pills" id="filterPills"><?php foreach ($categories as $category): ?><button class="filter-pill <?= $category === 'All' ? 'active' : '' ?>" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category === 'All' ? 'All dishes' : $category) ?></button><?php endforeach; ?></div><span class="menu-count" id="menuCount">8 dishes</span></div>
        <div class="product-grid" id="productGrid">
            <?php foreach ($menu as $item): ?>
                <article class="product-card" data-id="<?= htmlspecialchars($item['id']) ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <div class="product-image"><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy"><span class="product-tag"><?= htmlspecialchars($item['tag']) ?></span><button class="favorite-btn" aria-label="Save <?= htmlspecialchars($item['name']) ?>">♡</button></div>
                    <div class="product-info"><div class="product-meta"><span><?= htmlspecialchars($item['category']) ?></span><span>★ <?= htmlspecialchars($item['rating']) ?></span></div><h3><?= htmlspecialchars($item['name']) ?></h3><p><?= htmlspecialchars($item['description']) ?></p><div class="allergen-line"><span class="allergen-icon">ⓘ</span> Contains: <?= htmlspecialchars(implode(', ', $item['allergens'])) ?></div><div class="product-bottom"><strong>₱<?= number_format($item['price'], 2) ?></strong><div><button class="customize-btn" data-customize="<?= htmlspecialchars($item['id']) ?>">Customize</button><button class="add-btn" data-add="<?= htmlspecialchars($item['id']) ?>" aria-label="Add <?= htmlspecialchars($item['name']) ?>">+</button></div></div></div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="no-results" id="noResults" hidden><span>🥢</span><h3>Walang nakita!</h3><p>Try another search or browse all our dishes.</p><button class="text-button" id="clearSearch">Show all dishes →</button></div>
    </section>

    <section class="promo-section wrap">
        <div class="promo-card"><div class="promo-copy"><span class="promo-label">A LITTLE EXTRA SARAP</span><h2>Good food.<br><em>Better together.</em></h2><p>Share the comfort of Filipino favorites with the people who feel like home.</p><a class="btn btn-light" href="#menu">Order for the family <span>↗</span></a></div><div class="promo-image"><img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1000&q=85" alt="A meal made for sharing"><div class="promo-stamp">SALO-<br>SALO<br><small>TIME!</small></div></div><div class="promo-decoration">✳</div></div>
    </section>

    <section class="story-section" id="story"><div class="wrap story-inner"><div class="story-image"><img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1000&q=85" alt="Food being shared at a table"><span class="story-image-note">KAIN NA TAYO!</span></div><div class="story-copy"><span class="section-kicker">A TABLE FULL OF STORIES</span><h2>More than food.<br><em>A taste of home.</em></h2><p>FilipinOnly was made for the moments when you miss lutong bahay, crave a regional favorite, or simply want a meal that feels familiar.</p><p>We bring Filipino dishes together in one easy place — so you can spend less time searching and more time enjoying.</p><div class="story-signature"><span class="signature-mark">F+</span><span><strong>FilipinOnly</strong><small>Para sa bawat Pinoy.</small></span></div></div></div></section>

    <section class="how-section wrap"><div class="section-heading centered"><div><span class="section-kicker">SARAP MADE SIMPLE</span><h2>From us to <em>your table.</em></h2></div></div><div class="steps-grid"><div class="step-card"><span class="step-number">01</span><div class="step-icon">⌕</div><h3>Choose your cravings</h3><p>Explore Filipino dishes by mealtime and find your new favorite.</p></div><div class="step-card"><span class="step-number">02</span><div class="step-icon">♨</div><h3>Make it your own</h3><p>See allergen details and customize ingredients, rice, and spice.</p></div><div class="step-card"><span class="step-number">03</span><div class="step-icon">⌂</div><h3>Enjoy at home</h3><p>Choose how to pay, follow your order, and enjoy every bite.</p></div></div></section>
</main>

<footer class="site-footer"><div class="wrap footer-main"><div class="footer-brand"><a class="brand brand-footer" href="#home"><span class="brand-mark">F<span>+</span></span><span class="brand-copy"><strong>filipin<span>only</span></strong><small>LUTONG PINOY, DELIVERED</small></span></a><p>Bringing the comfort of Filipino food a little closer, one meal at a time.</p></div><div class="footer-links"><strong>Explore</strong><a href="#menu">Our menu</a><a href="#story">Our story</a><button id="footerTrackBtn">Track an order</button></div><div class="footer-links"><strong>Need help?</strong><a href="mailto:hello@filipinonly.demo">Contact us</a><span>Mon–Sun · 9 AM–9 PM</span><span>Made for Filipino food lovers</span></div><div class="footer-note"><span>MADE WITH <b>♥</b> IN THE PHILIPPINES</span><p>Every dish tells a story.<br>What's yours?</p></div></div><div class="wrap footer-bottom"><span>© <?= date('Y') ?> FilipinOnly. Academic project prototype.</span><span>Privacy · Terms · All rights reserved</span></div></footer>

<!-- Cart drawer -->
<div class="overlay" id="cartOverlay"></div><aside class="drawer" id="cartDrawer" aria-label="Shopping bag" aria-hidden="true"><div class="drawer-header"><div><span class="section-kicker">YOUR ORDER</span><h2>Your <em>bag.</em></h2></div><button class="close-button" data-close="cart" aria-label="Close bag">×</button></div><div class="cart-content" id="cartContent"><div class="empty-cart"><span>🥢</span><h3>Your bag is waiting.</h3><p>Add a Filipino favorite to get started.</p><button class="btn btn-primary" data-close="cart">Explore menu</button></div></div><div class="cart-footer" id="cartFooter" hidden><div class="voucher-row"><input id="voucherInput" type="text" placeholder="Promo code"><button id="applyVoucherBtn">Apply</button></div><p class="voucher-hint" id="voucherHint">Try <strong>SARAP10</strong> for 10% off orders over ₱299.</p><div class="totals-row"><span>Subtotal</span><strong id="cartSubtotal">₱0.00</strong></div><div class="totals-row discount-row" id="discountRow" hidden><span>Voucher discount</span><strong id="cartDiscount">−₱0.00</strong></div><div class="totals-row"><span>Delivery</span><strong>Calculated at checkout</strong></div><div class="cart-total"><span>Estimated total</span><strong id="cartTotal">₱0.00</strong></div><button class="btn btn-primary checkout-btn" id="checkoutBtn">Continue to checkout <span>→</span></button><small class="secure-note">Secure checkout · Your details stay in this demo session</small></div></aside>

<!-- Checkout modal -->
<div class="modal-backdrop" id="checkoutModal"><section class="modal checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle"><div class="modal-header"><div><span class="section-kicker">ALMOST THERE</span><h2 id="checkoutTitle">Checkout <em>details.</em></h2></div><button class="close-button" data-close="checkout" aria-label="Close checkout">×</button></div><form id="checkoutForm"><div class="checkout-layout"><div class="checkout-fields"><div class="form-section-title"><span>01</span><div><strong>Delivery information</strong><small>Where should we bring your food?</small></div></div><div class="form-grid"><label class="field full">Full name<input name="name" id="checkoutName" required placeholder="e.g. Juan dela Cruz"></label><label class="field">Contact number<input name="phone" id="checkoutPhone" required placeholder="09XX XXX XXXX" inputmode="tel"></label><label class="field">Email address <small>(optional)</small><input name="email" id="checkoutEmail" type="email" placeholder="you@example.com"></label><label class="field full">Complete delivery address<textarea name="address" id="checkoutAddress" required rows="2" placeholder="House no., street, barangay, city"></textarea></label><label class="field full">Nearby landmark <small>(optional)</small><input name="landmark" id="checkoutLandmark" placeholder="e.g. Across the town plaza"></label></div><div class="delivery-choice"><label class="radio-card"><input type="radio" name="delivery" value="ASAP" checked><span><b>Deliver ASAP</b><small>Usually 30–45 minutes</small></span></label><label class="radio-card"><input type="radio" name="delivery" value="Scheduled"><span><b>Schedule delivery</b><small>Pick a date and time</small></span></label></div><label class="field schedule-field" id="scheduleField" hidden>Delivery date & time<input type="datetime-local" name="schedule" id="checkoutSchedule"></label><div class="form-section-title payment-title"><span>02</span><div><strong>Payment method</strong><small>Choose how you'd like to pay.</small></div></div><div class="payment-options"><label class="payment-option"><input type="radio" name="payment" value="GCash" checked><span class="payment-symbol gcash">G</span><span><b>GCash</b><small>E-wallet</small></span></label><label class="payment-option"><input type="radio" name="payment" value="Cash on Delivery"><span class="payment-symbol cash">₱</span><span><b>Cash on Delivery</b><small>Pay when it arrives</small></span></label><label class="payment-option"><input type="radio" name="payment" value="Credit/Debit Card"><span class="payment-symbol card-symbol">▣</span><span><b>Credit / Debit</b><small>Demo card selection</small></span></label></div></div><aside class="checkout-summary"><span class="section-kicker">ORDER SUMMARY</span><div id="checkoutSummaryItems"></div><div class="summary-divider"></div><div class="summary-line"><span>Subtotal</span><strong id="checkoutSubtotal">₱0.00</strong></div><div class="summary-line"><span>Discount</span><strong id="checkoutDiscount">₱0.00</strong></div><div class="summary-line"><span>Delivery fee</span><strong id="checkoutDelivery">₱39.00</strong></div><div class="summary-total"><span>Total</span><strong id="checkoutTotal">₱0.00</strong></div><p class="demo-payment-note">This is a classroom demo. No real payment will be collected.</p><button class="btn btn-primary place-order-btn" type="submit">Place my order <span>→</span></button><small class="terms-note">By placing your order, you agree to our demo checkout flow.</small></aside></div></form></section></div>

<!-- Customize modal -->
<div class="modal-backdrop" id="customizeModal"><section class="modal customize-modal" role="dialog" aria-modal="true" aria-labelledby="customizeTitle"><div class="modal-header"><div><span class="section-kicker">MAKE IT YOURS</span><h2 id="customizeTitle">Customize your <em>dish.</em></h2></div><button class="close-button" data-close="customize" aria-label="Close customization">×</button></div><div id="customizeBody"></div></section></div>

<!-- Account modal -->
<div class="modal-backdrop" id="accountModal"><section class="modal account-modal" role="dialog" aria-modal="true" aria-labelledby="accountTitle"><div class="modal-header"><div><span class="section-kicker">WELCOME TO THE TABLE</span><h2 id="accountTitle">Your <em>account.</em></h2></div><button class="close-button" data-close="account" aria-label="Close account">×</button></div><div id="accountContent"></div></section></div>

<!-- Tracking modal -->
<div class="modal-backdrop" id="trackingModal"><section class="modal tracking-modal" role="dialog" aria-modal="true" aria-labelledby="trackingTitle"><div class="modal-header"><div><span class="section-kicker">GOOD THINGS ARE ON THE WAY</span><h2 id="trackingTitle">Order <em>tracking.</em></h2></div><button class="close-button" data-close="tracking" aria-label="Close tracking">×</button></div><form class="track-search" id="trackForm"><label class="field">Enter your order number<input type="text" id="trackingCode" placeholder="e.g. FO-A1B2C3D4" required></label><button class="btn btn-primary" type="submit">Track order →</button></form><div id="trackingResult"><div class="tracking-empty"><span>🛵</span><p>Your order updates will appear here.</p></div></div><p class="tracking-disclaimer">Prototype note: tracking steps are sample interface states. Live rider GPS and email notifications are not connected.</p></section></div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<div class="mobile-bottom-bar"><button id="mobileMenuLink">Menu</button><button id="mobileCartLink">Bag <b id="mobileCartCount">0</b></button></div>
</body>
</html>
