(() => {
  'use strict';
  const API = 'api.php';
  const menu = window.FILIPINONLY?.menu || [];
  let csrf = window.FILIPINONLY?.csrf || '';
  let currentCategory = 'All';
  let currentProduct = null;
  let state = { cart: { items: [], subtotal: 0, count: 0 }, discount: 0, total: 0, user: null };
  let toastTimer;

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const peso = value => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));

  async function request(action, payload = {}) {
    const response = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ action, ...payload })
    });
    const data = await response.json().catch(() => ({ ok: false, message: 'The server returned an unreadable response.' }));
    if (data.csrf) csrf = data.csrf;
    if (!response.ok || !data.ok) throw new Error(data.message || 'Something went wrong.');
    return data;
  }

  async function refreshState() {
    const response = await fetch(`${API}?action=state`, { cache: 'no-store' });
    const data = await response.json();
    if (!data.ok) throw new Error('Could not load your session. Refresh the page.');
    state = data;
    csrf = data.csrf || csrf;
    updateHeader();
    renderCart();
    return data;
  }

  function showToast(message, type = 'success') {
    const toast = $('#toast');
    toast.textContent = message;
    toast.classList.toggle('error', type === 'error');
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
  }

  function updateHeader() {
    const count = state.cart?.count || 0;
    $('#cartCount').textContent = count;
    $('#mobileCartCount').textContent = count;
    $('#accountLabel').textContent = state.user ? (state.user.name.split(' ')[0] || 'Account') : 'Sign in';
  }

  function openOverlay(type) {
    closeMobileNav();
    if (type === 'cart') {
      $('#cartOverlay').classList.add('open');
      $('#cartDrawer').classList.add('open');
      $('#cartDrawer').setAttribute('aria-hidden', 'false');
    } else {
      $(`#${type}Modal`).classList.add('open');
    }
    document.body.classList.add('no-scroll');
  }

  function closeOverlay(type) {
    if (type === 'cart') {
      $('#cartOverlay').classList.remove('open');
      $('#cartDrawer').classList.remove('open');
      $('#cartDrawer').setAttribute('aria-hidden', 'true');
    } else {
      $(`#${type}Modal`).classList.remove('open');
    }
    if (!$('.modal-backdrop.open') && !$('#cartDrawer').classList.contains('open')) document.body.classList.remove('no-scroll');
  }

  function closeMobileNav() { $('#mainNav').classList.remove('open'); }

  function renderProducts() {
    const query = $('#menuSearch').value.trim().toLowerCase();
    const sort = $('#sortMenu').value;
    let filtered = menu.filter(item => (currentCategory === 'All' || item.category === currentCategory) &&
      `${item.name} ${item.description} ${item.category} ${item.allergens.join(' ')}`.toLowerCase().includes(query));
    if (sort === 'low') filtered.sort((a, b) => a.price - b.price);
    if (sort === 'high') filtered.sort((a, b) => b.price - a.price);
    $('#menuCount').textContent = `${filtered.length} ${filtered.length === 1 ? 'dish' : 'dishes'}`;
    $('#noResults').hidden = filtered.length > 0;
    $('#productGrid').hidden = filtered.length === 0;
    $('#productGrid').innerHTML = filtered.map(item => `
      <article class="product-card" data-id="${escapeHtml(item.id)}" data-category="${escapeHtml(item.category)}">
        <div class="product-image"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" loading="lazy"><span class="product-tag">${escapeHtml(item.tag)}</span><button class="favorite-btn" aria-label="Save ${escapeHtml(item.name)}">♡</button></div>
        <div class="product-info"><div class="product-meta"><span>${escapeHtml(item.category)}</span><span>★ ${escapeHtml(item.rating)}</span></div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.description)}</p><div class="allergen-line"><span class="allergen-icon">ⓘ</span> Contains: ${escapeHtml(item.allergens.join(', '))}</div><div class="product-bottom"><strong>${peso(item.price)}</strong><div><button class="customize-btn" data-customize="${escapeHtml(item.id)}">Customize</button><button class="add-btn" data-add="${escapeHtml(item.id)}" aria-label="Add ${escapeHtml(item.name)}">+</button></div></div></div>
      </article>`).join('');
  }

  function setCategory(category) {
    currentCategory = category;
    $$('.filter-pill').forEach(button => button.classList.toggle('active', button.dataset.category === category));
    $$('.category-tile').forEach(button => button.classList.toggle('selected', button.dataset.category === category));
    renderProducts();
  }

  async function addToCart(id, custom = null) {
    const item = menu.find(product => product.id === id);
    if (!item) return;
    try {
      await request('add_to_cart', { id, qty: 1, custom: custom || { removed: [], rice: item.rice ? 'Garlic rice' : 'No rice', spice: 'Regular' } });
      await refreshState();
      showToast(`${item.name} added to your bag.`);
      if (custom) closeOverlay('customize');
    } catch (error) { showToast(error.message, 'error'); }
  }

  function renderCart() {
    const items = state.cart?.items || [];
    const content = $('#cartContent');
    const footer = $('#cartFooter');
    if (!items.length) {
      content.innerHTML = `<div class="empty-cart"><span>🥢</span><h3>Your bag is waiting.</h3><p>Add a Filipino favorite to get started.</p><button class="btn btn-primary" data-close="cart">Explore menu</button></div>`;
      footer.hidden = true;
      return;
    }
    footer.hidden = false;
    content.innerHTML = items.map(item => {
      const extras = [item.custom?.rice && item.custom.rice !== 'No rice' ? item.custom.rice : '', item.custom?.spice && item.custom.spice !== 'Regular' ? `${item.custom.spice} spice` : '', ...(item.custom?.removed || []).map(value => `No ${value}`)].filter(Boolean).join(' · ');
      return `<div class="cart-item"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}"><div><h4>${escapeHtml(item.name)}</h4><small>${escapeHtml(extras || 'Prepared just the way you like it')}</small><div class="cart-item-actions"><button class="qty-button" data-qty="${escapeHtml(item.id)}" data-change="-1" aria-label="Decrease quantity">−</button><span class="qty-number">${item.qty}</span><button class="qty-button" data-qty="${escapeHtml(item.id)}" data-change="1" aria-label="Increase quantity">+</button><button class="remove-item" data-remove="${escapeHtml(item.id)}">Remove</button></div></div><strong class="cart-item-price">${peso(item.line_total)}</strong></div>`;
    }).join('');
    $('#cartSubtotal').textContent = peso(state.cart.subtotal);
    $('#cartDiscount').textContent = `−${peso(state.discount)}`;
    $('#discountRow').hidden = !(state.discount > 0);
    $('#cartTotal').textContent = peso(state.cart.subtotal - (state.discount || 0));
    $('#voucherInput').value = state.voucher || '';
    $('#voucherHint').innerHTML = state.voucher ? '<strong>SARAP10 applied.</strong> Discount is reflected above.' : 'Try <strong>SARAP10</strong> for 10% off orders over ₱299.';
  }

  async function changeQuantity(id, amount) {
    const current = state.cart.items.find(item => item.id === id);
    if (!current) return;
    try { await request('set_quantity', { id, qty: current.qty + amount }); await refreshState(); }
    catch (error) { showToast(error.message, 'error'); }
  }

  function openCustomize(id) {
    const item = menu.find(product => product.id === id);
    if (!item) return;
    currentProduct = item;
    $('#customizeBody').innerHTML = `
      <div class="custom-product-head"><img src="${escapeHtml(item.image)}" alt=""><div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.description)}</p><p style="margin-top:6px;color:#a25d3a">Allergens: ${escapeHtml(item.allergens.join(', '))}</p></div></div>
      <div class="custom-group"><h4>Ingredient preferences <span style="font-weight:400;color:#999">(choose any)</span></h4><div class="custom-checks">${item.customs.map(value => `<button type="button" class="custom-chip" data-remove-ingredient="${escapeHtml(value)}">${escapeHtml(value)}</button>`).join('')}</div></div>
      ${item.rice ? `<div class="custom-group"><h4>Choose your rice</h4><select class="custom-select" id="customRice"><option>Garlic rice</option><option>White rice</option><option>Brown rice</option><option>No rice</option></select></div>` : ''}
      ${item.spice ? `<div class="custom-group"><h4>Flavor level</h4><div class="custom-checks" id="spiceChoices"><button type="button" class="custom-chip" data-spice="Mild">Mild</button><button type="button" class="custom-chip selected" data-spice="Regular">Regular</button><button type="button" class="custom-chip" data-spice="Spicy">Spicy</button></div></div>` : ''}
      <div class="custom-add-row"><strong>${peso(item.price)}</strong><button type="button" class="btn btn-primary" id="addCustomizedBtn">Add to bag <span>+</span></button></div>`;
    openOverlay('customize');
  }

  async function applyVoucher() {
    try {
      const result = await request('apply_voucher', { code: $('#voucherInput').value });
      await refreshState();
      showToast(result.message);
    } catch (error) { showToast(error.message, 'error'); }
  }

  function openCheckout() {
    if (!state.cart.items?.length) { showToast('Your bag is empty.', 'error'); return; }
    closeOverlay('cart');
    const user = state.user || {};
    $('#checkoutName').value = user.name || '';
    $('#checkoutPhone').value = user.phone || '';
    $('#checkoutEmail').value = user.email || '';
    $('#checkoutAddress').value = user.address || '';
    $('#checkoutSummaryItems').innerHTML = state.cart.items.map(item => `<div class="checkout-summary-item"><span>${item.qty} × ${escapeHtml(item.name)}</span><strong>${peso(item.line_total)}</strong></div>`).join('');
    const discount = state.discount || 0;
    const delivery = state.cart.subtotal >= 499 ? 0 : 39;
    $('#checkoutSubtotal').textContent = peso(state.cart.subtotal);
    $('#checkoutDiscount').textContent = `−${peso(discount)}`;
    $('#checkoutDelivery').textContent = delivery === 0 ? 'FREE' : peso(delivery);
    $('#checkoutTotal').textContent = peso(state.cart.subtotal - discount + delivery);
    $('#checkoutSchedule').min = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    openOverlay('checkout');
  }

  async function placeOrder(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());
    try {
      const result = await request('checkout', payload);
      await refreshState();
      closeOverlay('checkout');
      showToast('Your order has been placed. Salamat!');
      openTrackingWithOrder(result.order);
    } catch (error) { showToast(error.message, 'error'); }
  }

  function openTrackingWithOrder(order) {
    $('#trackingCode').value = order.id;
    renderTracking(order);
    openOverlay('tracking');
  }

  function renderTracking(order) {
    const steps = ['Order received', 'Preparing', 'On the way', 'Delivered'];
    const active = Math.max(0, steps.indexOf(order.status || 'Order received'));
    const itemList = Array.isArray(order.items) ? order.items : [];
    const receiptHref = `receipt.php?id=${encodeURIComponent(order.id)}`;
    $('#trackingResult').innerHTML = `
      <div class="tracking-card"><div class="tracking-card-head"><strong>${escapeHtml(order.id)}</strong><span class="status-pill">${escapeHtml(order.status || 'Order received')}</span></div>
      <p class="tracking-address">Delivering to ${escapeHtml(order.name)} · ${escapeHtml(order.address)}</p>
      <div class="tracking-steps">${steps.map((step, index) => `<div class="tracking-step ${index <= active ? 'done' : ''}"><i></i><span>${step}</span></div>`).join('')}</div>
      <div class="tracking-order-items">${itemList.map(item => `${Number(item.qty || 1)} × ${escapeHtml(item.name)}`).join('<br>')}<div class="tracking-total"><span>Order total</span><strong>${peso(order.total)}</strong></div></div>
      <a class="btn btn-outline" href="${receiptHref}" target="_blank" rel="noopener">View / Print receipt ↗</a>
      <p class="tracking-refresh-note">Refresh tracking to see the latest staff update.</p></div>`;
  }

  async function trackOrder(event) {
    event.preventDefault();
    try {
      const data = await request('track_order', { id: $('#trackingCode').value });
      renderTracking(data.order);
    } catch (error) {
      $('#trackingResult').innerHTML = `<div class="tracking-empty"><span>🔎</span><p>${escapeHtml(error.message)}</p></div>`;
    }
  }

  function openAccount() {
    const user = state.user;
    if (user) {
      $('#accountTitle').innerHTML = 'Your <em>profile.</em>';
      $('#accountContent').innerHTML = `<div class="profile-panel"><h3>Hello, ${escapeHtml(user.name)}!</h3><p>Your details help make checkout quicker.</p><form id="profileForm" class="auth-form"><label class="field">Full name<input name="name" required value="${escapeHtml(user.name)}"></label><label class="field">Email address<input value="${escapeHtml(user.email)}" disabled></label><label class="field">Contact number<input name="phone" value="${escapeHtml(user.phone || '')}" placeholder="09XX XXX XXXX"></label><label class="field">Delivery address<textarea name="address" rows="2" placeholder="Your usual delivery address">${escapeHtml(user.address || '')}</textarea></label><button class="btn btn-primary" type="submit">Save profile</button></form><div class="profile-actions"><button class="btn btn-outline" id="viewOrdersBtn">Track latest order</button><button class="btn btn-outline" id="logoutBtn">Sign out</button></div></div>`;
    } else {
      $('#accountTitle').innerHTML = 'Your <em>account.</em>';
      renderAuthForm('login');
    }
    openOverlay('account');
  }

  function renderAuthForm(mode) {
    const register = mode === 'register';
    $('#accountContent').innerHTML = `<div class="auth-tabs"><button type="button" class="auth-tab ${!register ? 'active' : ''}" data-auth-tab="login">Sign in</button><button type="button" class="auth-tab ${register ? 'active' : ''}" data-auth-tab="register">Create account</button></div><form class="auth-form" id="authForm">${register ? '<label class="field">Full name<input name="name" required placeholder="Your full name"></label><label class="field">Contact number<input name="phone" placeholder="09XX XXX XXXX"></label>' : ''}<label class="field">Email address<input name="email" type="email" required placeholder="you@example.com"></label><label class="field">Password<input name="password" type="password" minlength="6" required placeholder="At least 6 characters"></label><button class="btn btn-primary" type="submit">${register ? 'Create my account' : 'Sign in'} <span>→</span></button></form><p class="auth-switch-note">${register ? 'Already have an account? <button class="text-button" data-auth-tab="login">Sign in</button>' : 'New to FilipinOnly? <button class="text-button" data-auth-tab="register">Create an account</button>'}</p><p class="auth-footnote">Demo mode: account details are stored only in this browser session. Gmail sign-in and database storage are not connected.</p>`;
  }

  async function submitAuth(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());
    const mode = event.currentTarget.querySelector('[name="name"]') ? 'register' : 'login';
    try {
      const result = await request(mode, payload);
      await refreshState();
      openAccount();
      showToast(result.message);
    } catch (error) { showToast(error.message, 'error'); }
  }

  async function saveProfile(event) {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
    try {
      const result = await request('update_profile', payload);
      await refreshState();
      openAccount();
      showToast(result.message);
    } catch (error) { showToast(error.message, 'error'); }
  }

  async function logout() {
    try {
      const result = await request('logout');
      await refreshState();
      closeOverlay('account');
      showToast(result.message);
    } catch (error) { showToast(error.message, 'error'); }
  }

  function openTracking() {
    $('#trackingResult').innerHTML = '<div class="tracking-empty"><span>🛵</span><p>Enter your order number to see its current demo status.</p></div>';
    $('#trackingCode').value = state.latest_order?.id || '';
    if (state.latest_order) renderTracking(state.latest_order);
    openOverlay('tracking');
  }

  // Delegated events keep controls working when product or cart markup is re-rendered.
  document.addEventListener('click', async event => {
    const add = event.target.closest('[data-add]');
    const customize = event.target.closest('[data-customize]');
    const close = event.target.closest('[data-close]');
    const qty = event.target.closest('[data-qty]');
    const remove = event.target.closest('[data-remove]');
    const category = event.target.closest('[data-category]');
    const authTab = event.target.closest('[data-auth-tab]');
    const ingredient = event.target.closest('[data-remove-ingredient]');
    const spice = event.target.closest('[data-spice]');
    const favorite = event.target.closest('.favorite-btn');
    const addCustomized = event.target.closest('#addCustomizedBtn');
    if (add) addToCart(add.dataset.add);
    if (customize) openCustomize(customize.dataset.customize);
    if (close) closeOverlay(close.dataset.close);
    if (qty) changeQuantity(qty.dataset.qty, Number(qty.dataset.change));
    if (remove) { try { await request('remove_from_cart', { id: remove.dataset.remove }); await refreshState(); } catch (error) { showToast(error.message, 'error'); } }
    if (category) setCategory(category.dataset.category);
    if (authTab) renderAuthForm(authTab.dataset.authTab);
    if (ingredient) ingredient.classList.toggle('selected');
    if (spice) { $$('#spiceChoices .custom-chip').forEach(button => button.classList.toggle('selected', button === spice)); }
    if (favorite) { favorite.classList.toggle('saved'); favorite.textContent = favorite.classList.contains('saved') ? '♥' : '♡'; }
    if (addCustomized && currentProduct) {
      const removed = $$('.custom-chip.selected[data-remove-ingredient]').map(button => button.dataset.removeIngredient);
      const rice = $('#customRice')?.value || 'No rice';
      const spiceLevel = $('[data-spice].selected')?.dataset.spice || 'Regular';
      addToCart(currentProduct.id, { removed, rice, spice: spiceLevel });
    }
  });

  $('#openCartBtn').addEventListener('click', () => openOverlay('cart'));
  $('#cartOverlay').addEventListener('click', () => closeOverlay('cart'));
  $('#checkoutBtn').addEventListener('click', openCheckout);
  $('#checkoutForm').addEventListener('submit', placeOrder);
  $('#trackForm').addEventListener('submit', trackOrder);
  $('#trackNavBtn').addEventListener('click', openTracking);
  $('#footerTrackBtn').addEventListener('click', openTracking);
  $('#accountBtn').addEventListener('click', openAccount);
  $('#applyVoucherBtn').addEventListener('click', applyVoucher);
  $('#menuSearch').addEventListener('input', renderProducts);
  $('#sortMenu').addEventListener('change', renderProducts);
  $('#clearSearch').addEventListener('click', () => { $('#menuSearch').value = ''; setCategory('All'); });
  $('#mobileMenuToggle').addEventListener('click', () => $('#mainNav').classList.toggle('open'));
  $('#mainNav').addEventListener('click', event => { if (event.target.closest('a')) closeMobileNav(); });
  $('#mobileMenuLink').addEventListener('click', () => { closeMobileNav(); $('#menu').scrollIntoView({ behavior: 'smooth' }); });
  $('#mobileCartLink').addEventListener('click', () => openOverlay('cart'));
  $('#accountContent').addEventListener('submit', event => {
    if (event.target.id === 'authForm') submitAuth(event);
    if (event.target.id === 'profileForm') saveProfile(event);
  });
  $('#accountContent').addEventListener('click', event => {
    if (event.target.id === 'logoutBtn') logout();
    if (event.target.id === 'viewOrdersBtn') { closeOverlay('account'); openTracking(); }
  });
  $$('input[name="delivery"]').forEach(input => input.addEventListener('change', () => { $('#scheduleField').hidden = input.value !== 'Scheduled' || !input.checked; }));
  $$('.modal-backdrop').forEach(backdrop => backdrop.addEventListener('click', event => { if (event.target === backdrop) closeOverlay(backdrop.id.replace('Modal', '')); }));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') { ['cart','checkout','customize','account','tracking'].forEach(closeOverlay); closeMobileNav(); } });

  refreshState().catch(error => showToast(error.message, 'error'));
  renderProducts();
})();
