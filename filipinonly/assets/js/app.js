(() => {
  'use strict';
  const API = 'api.php';
  const menu = window.FILIPINONLY?.menu || [];
  const branches = window.FILIPINONLY?.branches || [];
  let csrf = window.FILIPINONLY?.csrf || '';
  const googleClientId = window.FILIPINONLY?.googleClientId || '';
  let currentCategory = 'All';
  let currentProduct = null;
  let state = { cart: { items: [], subtotal: 0, count: 0 }, discount: 0, total: 0, user: null };
  let emailCheck = { email: '', available: true, checked: false, checking: false };
  let emailCheckTimer;
  let historyOrders = [];
  let toastTimer;

  const $ = (selector, root = document) => (root || document).querySelector(selector);
  const $$ = (selector, root = document) => [...(root || document).querySelectorAll(selector)];
  const peso = value => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
  const formatEta = value => {
    if (!value) return '30–45 minutes';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
  };
  const readImageAsDataUrl = file => new Promise((resolve, reject) => {
    if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type)) { reject(new Error('Choose a JPG, PNG, WEBP, or GIF image.')); return; }
    if (file.size > 1048576) { reject(new Error('Please choose an image under 1 MB.')); return; }
    const reader = new FileReader();
    reader.onload = () => resolve(String(reader.result || ''));
    reader.onerror = () => reject(new Error('Could not read that image.'));
    reader.readAsDataURL(file);
  });

  const composeAddress = parts => [parts.address_line, parts.city, parts.province, parts.zip]
    .map(value => String(value || '').trim()).filter(Boolean).join(', ');

  const parseAddress = value => {
    const text = String(value || '').trim();
    if (!text) return { address_line: '', city: '', province: '', zip: '' };
    const pieces = text.split(',').map(piece => piece.trim()).filter(Boolean);
    const zipIndex = pieces.findIndex(piece => /^\d{4,5}$/.test(piece));
    const zip = zipIndex >= 0 ? pieces.splice(zipIndex, 1)[0] : '';
    const fallbackZip = text.match(/\b\d{4,5}\b/);
    return {
      address_line: pieces[0] || text,
      city: pieces[1] || '',
      province: pieces[2] || '',
      zip: zip || (fallbackZip ? fallbackZip[0] : ''),
    };
  };

  const addressOf = user => {
    const fallback = parseAddress(user?.address);
    return {
      address_line: String(user?.address_line ?? fallback.address_line),
      city: String(user?.city ?? fallback.city),
      province: String(user?.province ?? fallback.province),
      zip: String(user?.zip ?? fallback.zip),
      landmark: String(user?.landmark ?? ''),
    };
  };

  const branchForOrder = order => {
    const wanted = String(order?.branch_city || order?.city || '').trim().toLowerCase();
    const match = branches.find(branch => String(branch?.city || '').trim().toLowerCase() === wanted);
    const fallback = branches.find(branch => String(branch?.city || '').trim().toLowerCase() === 'quezon city');
    const branch = match || fallback || { area: 'Cubao', address: '18 General Roxas Ave, Cubao, Quezon City' };
    const area = String(order?.branch_area ?? branch.area ?? '').trim();
    const address = String(order?.branch_address ?? branch.address ?? '').trim();
    return [area ? `${area} branch` : '', address].filter(Boolean).join(' · ');
  };

  const ncrCities = ['Caloocan', 'Las Piñas', 'Makati', 'Malabon', 'Marikina', 'Manila', 'Muntinlupa', 'Navotas', 'Parañaque', 'Pasay', 'Pasig', 'Pateros', 'Quezon City', 'San Juan', 'Taguig', 'Valenzuela'];

  function cityOptions(selected = '') {
    const value = String(selected || '').trim();
    const cities = [...ncrCities];
    if (value && !cities.some(city => city.toLowerCase() === value.toLowerCase())) cities.unshift(value);
    const placeholder = '<option value="" disabled selected>Choose NCR city</option>';
    return placeholder + cities.map(city => `<option value="${escapeHtml(city)}"${city.toLowerCase() === value.toLowerCase() ? ' selected' : ''}>${escapeHtml(city)}</option>`).join('');
  }

  function provinceOptions() {
    return '<option value="Metro Manila" selected>Metro Manila (NCR)</option>';
  }

  function setAuthFeedback(message, type = 'success', box = null) {
    const target = box || $('#authFeedback');
    if (!target) return;
    target.textContent = message;
    target.dataset.state = type;
  }

  function passwordScore(value) {
    const text = String(value || '');
    if (text.length < 6) return 0;
    let score = 1;
    if (text.length >= 10) score++;
    if (/[A-Z]/.test(text) && /[a-z]/.test(text)) score++;
    if (/\d/.test(text)) score++;
    if (/[^A-Za-z0-9]/.test(text)) score++;
    return Math.min(4, score);
  }

  async function checkEmailAvailability(email) {
    const normalized = String(email || '').trim().toLowerCase();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalized)) return;
    if (emailCheck.email === normalized && emailCheck.checked) return;
    emailCheck = { email: normalized, available: true, checked: false, checking: true };
    validateAuthForm($('#authForm'));
    try {
      const result = await request('email_check', { email: normalized });
      if (emailCheck.email !== normalized) return;
      emailCheck = { email: normalized, available: !!result.available, checked: true, checking: false };
    } catch (error) {
      if (emailCheck.email !== normalized) return;
      emailCheck = { email: normalized, available: true, checked: true, checking: false };
    }
    validateAuthForm($('#authForm'));
  }

  function validateAuthForm(form, showReady = false) {
    if (!form) return false;
    const register = !!form.querySelector('[name="name"]');
    const values = Object.fromEntries(new FormData(form).entries());
    const email = String(values.email || '').trim();
    const password = String(values.password || '');
    const phone = String(values.phone || '').trim();
    const name = String(values.name || '').trim();
    const confirm = String(values.confirm || '');
    const emailFormatOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && email.length <= 254;
    const nameOk = !register || (/^[\p{L}][\p{L}\p{M}'’.\-\s]{1,59}$/u.test(name) && [...name].length >= 2);
    const digits = phone.replace(/\D/g, '');
    const phoneOk = !register || (digits.length >= 7 && digits.length <= 13);
    const passwordOk = register ? (password.length >= 6 && /[A-Za-z]/.test(password) && /\d/.test(password)) : password.length >= 6;
    const emailOk = emailFormatOk && (!register || (emailCheck.checked && emailCheck.email === email.toLowerCase() && emailCheck.available));
    const checks = {
      name: nameOk,
      phone: phoneOk,
      email: emailOk,
      password: passwordOk,
      confirm: !register || (confirm.length > 0 && confirm === password),
    };
    const messages = {
      name: 'Use at least 2 letters. Spaces, hyphens, dots, and apostrophes are allowed.',
      phone: 'Use 7–13 digits.',
      email: !emailFormatOk ? 'Enter a valid email address.' : emailCheck.checking ? 'Checking email…' : 'Email already registered. Sign in instead.',
      password: register ? 'Use 6+ characters with a letter and a number.' : 'Use at least 6 characters.',
      confirm: 'Passwords do not match.',
    };
    let valid = true;
    Object.entries(checks).forEach(([key, ok]) => {
      const value = String(values[key] ?? '');
      if (!ok && (key === 'name' || key === 'phone') && !register) return;
      const field = form.querySelector(`[data-auth-field="${key}"]`);
      const feedback = form.querySelector(`[data-feedback="${key}"]`);
      const input = form.querySelector(`[name="${key}"]`);
      const invalid = !ok && (value.trim().length > 0 || (key === 'confirm' && password.length > 0));
      const good = ok && value.trim().length > 0;
      if (field) { field.classList.toggle('invalid', invalid); field.classList.toggle('valid', good); }
      if (feedback) feedback.textContent = invalid ? messages[key] : (good && showReady ? 'Looks good.' : '');
      if (input) input.setAttribute('aria-invalid', String(invalid));
      if (!ok) valid = false;
    });
    const strength = form.querySelector('#passwordStrength');
    if (strength && register) {
      const score = passwordScore(password);
      const labels = ['Use at least 6 characters.', 'Weak password', 'Fair password', 'Good password', 'Strong password'];
      strength.hidden = false;
      strength.dataset.level = String(score);
      const bar = strength.querySelector('span');
      const label = strength.querySelector('small');
      if (bar) bar.style.width = `${Math.max(10, score * 25)}%`;
      if (label) label.textContent = password ? labels[score] : labels[0];
      const rules = {
        length: password.length >= 6,
        case: /[A-Z]/.test(password) && /[a-z]/.test(password),
        number: /\d/.test(password),
        symbol: /[^A-Za-z0-9]/.test(password),
      };
      Object.entries(rules).forEach(([key, ok]) => {
        const item = strength.querySelector(`[data-check="${key}"]`);
        if (item) item.dataset.met = String(ok);
      });
    }
    const box = form.querySelector('#authFeedback');
    const hasInput = Object.values(values).some(value => String(value || '').trim() !== '');
    const checkingEmail = register && emailFormatOk && emailCheck.checking;
    if (!valid && hasInput) setAuthFeedback(checkingEmail ? 'Checking email availability…' : 'Check the highlighted fields.', checkingEmail ? '' : 'error', box);
    else if (valid && showReady) setAuthFeedback(register ? 'Ready to create your account.' : 'Ready to sign in.', 'success', box);
    else if (!showReady) setAuthFeedback('', '', box);
    return valid;
  }

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
    if (type === 'error') window.FilipinOnlySound?.error();
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
    $('#accountLabel').textContent = state.user ? 'Profile' : 'Sign in';
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

  function closeMobileNav() {
    $('#mainNav').classList.remove('open');
    $('#mobileMenuToggle').setAttribute('aria-expanded', 'false');
  }

  function renderProducts() {
    const query = $('#menuSearch').value.trim().toLowerCase();
    const sort = $('#sortMenu').value;
    let filtered = menu.filter(item => (currentCategory === 'All' || item.category === currentCategory) &&
      `${item.name} ${item.description} ${item.category} ${item.serving || ''} ${item.allergens.join(' ')}`.toLowerCase().includes(query));
    if (sort === 'low') filtered.sort((a, b) => a.price - b.price);
    if (sort === 'high') filtered.sort((a, b) => b.price - a.price);
    $('#menuCount').textContent = `${filtered.length} ${filtered.length === 1 ? 'dish' : 'dishes'}`;
    $('#noResults').hidden = filtered.length > 0;
    $('#productGrid').hidden = filtered.length === 0;
    $('#productGrid').innerHTML = filtered.map(item => `
      <article class="product-card" data-id="${escapeHtml(item.id)}" data-dish-category="${escapeHtml(item.category)}">
        <div class="product-image"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" loading="lazy"><span class="product-tag">${escapeHtml(item.tag)}</span></div>
        <div class="product-info"><div class="product-meta"><span>${escapeHtml(item.category)}</span><span>${escapeHtml(item.prep)}</span></div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.description)}</p><div class="allergen-line">Contains: ${escapeHtml(item.allergens.join(', '))}</div><div class="serving-line"><span>Serving size</span><strong>${escapeHtml(item.serving || '1 serving')}</strong></div><div class="product-bottom"><strong>${peso(item.price)}</strong><div><button class="customize-btn" data-customize="${escapeHtml(item.id)}">Customize</button><button class="add-btn" data-add="${escapeHtml(item.id)}" aria-label="Add ${escapeHtml(item.name)}">Add</button></div></div></div>
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
      showToast(`${item.name} (${item.serving || '1 serving'}) added to your bag.`);
      if (custom) closeOverlay('customize');
    } catch (error) { showToast(error.message, 'error'); }
  }

  function renderCart() {
    const items = state.cart?.items || [];
    const content = $('#cartContent');
    const footer = $('#cartFooter');
    if (!items.length) {
      content.innerHTML = `<div class="empty-cart"><h3>Nothing in the bag yet.</h3><p>Add a dish from the menu to get started.</p><button class="btn btn-primary" data-close="cart">Browse the menu</button></div>`;
      footer.hidden = true;
      return;
    }
    footer.hidden = false;
    content.innerHTML = items.map(item => {
      const extras = [`Serving: ${item.serving || '1 serving'}`, item.custom?.rice && item.custom.rice !== 'No rice' ? item.custom.rice : '', item.custom?.spice && item.custom.spice !== 'Regular' ? `${item.custom.spice} spice` : '', ...(item.custom?.removed || []).map(value => `No ${value}`)].filter(Boolean).join(' · ');
      return `<div class="cart-item"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}"><div><h4>${escapeHtml(item.name)}</h4><small>${escapeHtml(extras)}</small><div class="cart-item-actions"><button class="qty-button" data-qty="${escapeHtml(item.id)}" data-change="-1" aria-label="Decrease servings">−</button><span class="qty-number">${item.qty}<small>servings</small></span><button class="qty-button" data-qty="${escapeHtml(item.id)}" data-change="1" aria-label="Increase servings">+</button><button class="remove-item" data-remove="${escapeHtml(item.id)}">Remove</button></div></div><strong class="cart-item-price">${peso(item.line_total)}</strong></div>`;
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
      <div class="custom-product-head"><img src="${escapeHtml(item.image)}" alt=""><div><h3>${escapeHtml(item.name)}</h3><p>${escapeHtml(item.description)}</p><p class="custom-serving">Serving: ${escapeHtml(item.serving || '1 serving')}</p><p class="custom-allergens">Allergens: ${escapeHtml(item.allergens.join(', '))}</p></div></div>
      <div class="custom-group"><h4>Ingredient preferences <small>(choose any)</small></h4><div class="custom-checks">${item.customs.map(value => `<button type="button" class="custom-chip" data-remove-ingredient="${escapeHtml(value)}">${escapeHtml(value)}</button>`).join('')}</div></div>
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

  function updateCheckoutEta() {
    const mode = $('input[name="delivery"]:checked')?.value || 'ASAP';
    const schedule = $('#checkoutSchedule').value;
    $('#checkoutEta').textContent = mode === 'Scheduled' ? (schedule ? formatEta(schedule) : 'Choose a date and time') : '30–45 minutes';
  }

  function openCheckout() {
    if (!state.cart.items?.length) { showToast('Your bag is empty.', 'error'); return; }
    closeOverlay('cart');
    const user = state.user || {};
    const address = addressOf(user);
    $('#checkoutName').value = user.name || '';
    $('#checkoutPhone').value = user.phone || '';
    $('#checkoutEmail').value = user.email || '';
    $('#checkoutAddressLine').value = address.address_line;
    $('#checkoutCity').innerHTML = cityOptions(address.city);
    $('#checkoutProvince').innerHTML = provinceOptions();
    $('#checkoutZip').value = address.zip;
    $('#checkoutLandmark').value = address.landmark;
    $('#checkoutSummaryItems').innerHTML = state.cart.items.map(item => `<div class="checkout-summary-item"><span>${item.qty} × ${escapeHtml(item.name)} <small>(${escapeHtml(item.serving || '1 serving')})</small></span><strong>${peso(item.line_total)}</strong></div>`).join('');
    const discount = state.discount || 0;
    const delivery = state.cart.subtotal >= 499 ? 0 : 39;
    $('#checkoutSubtotal').textContent = peso(state.cart.subtotal);
    $('#checkoutDiscount').textContent = `−${peso(discount)}`;
    $('#checkoutDelivery').textContent = delivery === 0 ? 'FREE' : peso(delivery);
    $('#checkoutTotal').textContent = peso(state.cart.subtotal - discount + delivery);
    $('#checkoutSchedule').min = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    updateCheckoutEta();
    openOverlay('checkout');
  }

  async function placeOrder(event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());
    payload.address = composeAddress(payload);
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

  async function openOrderHistory() {
    $('#historyResult').innerHTML = '<div class="tracking-empty"><p>Loading your orders…</p></div>';
    openOverlay('history');
    try {
      const result = await request('order_history');
      historyOrders = Array.isArray(result.orders) ? result.orders : [];
      renderOrderHistory(historyOrders);
    } catch (error) {
      historyOrders = [];
      $('#historyResult').innerHTML = `<div class="tracking-empty"><p>${escapeHtml(error.message)}</p></div>`;
    }
  }

  function renderOrderHistory(orders) {
    if (!orders.length) {
      $('#historyResult').innerHTML = '<div class="history-summary"><span>Your orders</span><strong>0 orders</strong></div><div class="tracking-empty"><p>No orders yet. Your placed orders will appear here.</p></div>';
      return;
    }
    const formatDate = value => {
      const date = new Date(String(value || ''));
      return Number.isNaN(date.getTime()) ? String(value || '—') : date.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    };
    const orderTime = value => {
      const time = Date.parse(String(value || ''));
      return Number.isNaN(time) ? 0 : time;
    };
    const sortedOrders = [...orders].sort((a, b) => orderTime(b.created_at) - orderTime(a.created_at));
    const countLabel = `${sortedOrders.length} order${sortedOrders.length === 1 ? '' : 's'}`;
    $('#historyResult').innerHTML = `<div class="history-summary"><span>Your orders</span><strong>${countLabel}</strong></div><div class="history-list">${sortedOrders.map((order, index) => {
      const id = String(order.id || '');
      const status = String(order.status || 'Order received');
      const state = status === 'Delivered' ? 'delivered' : status === 'Cancelled' ? 'cancelled' : 'progress';
      const stateLabel = state === 'delivered' ? 'DELIVERED' : state === 'cancelled' ? 'CANCELLED' : 'IN PROGRESS';
      const received = order.delivered_at || (status === 'Delivered' ? order.updated_at : '');
      const items = Array.isArray(order.items) && order.items.length
        ? order.items.map(item => `${Number(item.qty || 1)} × ${String(item.name || '')}${item.serving ? ` (${String(item.serving)})` : ''}`).join(', ')
        : '—';
      return `<article class="history-row" data-state="${state}"><div class="history-card-head"><span class="history-number">${String(index + 1).padStart(2, '0')}</span><div class="history-top"><div><span class="history-id">Order Number: ${escapeHtml(id)}</span><strong>${escapeHtml(order.name || '')}</strong></div><div class="history-status"><span class="status-pill" data-state="${state}">${stateLabel}</span><small>${escapeHtml(status)}</small></div></div></div><dl class="history-details">
<div><dt>Price</dt><dd>${peso(order.total)}</dd></div><div><dt>Date ordered</dt><dd>${escapeHtml(formatDate(order.created_at))}</dd></div><div><dt>Date received</dt><dd>${received ? escapeHtml(formatDate(received)) : '—'}</dd></div><div class="wide"><dt>Branch location</dt><dd>${escapeHtml(branchForOrder(order))}</dd></div><div class="wide"><dt>Ordered items</dt><dd>${escapeHtml(items)}</dd></div>
</dl><div class="history-actions"><button class="btn btn-outline" data-history-track="${escapeHtml(id)}">Track</button><a class="btn btn-outline" href="receipt.php?id=${encodeURIComponent(id)}" target="_blank" rel="noopener">Receipt</a></div></article>`;
    }).join('')}</div><p class="tracking-disclaimer">Showing your most recent orders placed with this account.</p>`;
  }

  function renderTracking(order) {
    const status = String(order.status || 'Order received');
    const state = status === 'Delivered' ? 'delivered' : status === 'Cancelled' ? 'cancelled' : 'progress';
    const stateLabel = state === 'delivered' ? 'DELIVERED' : state === 'cancelled' ? 'CANCELLED' : 'IN PROGRESS';
    const steps = ['Order received', 'Preparing', 'On the way', 'Delivered'];
    const active = Math.max(0, steps.indexOf(status));
    const itemList = Array.isArray(order.items) ? order.items : [];
    const receiptHref = `receipt.php?id=${encodeURIComponent(order.id)}`;
    const deliveryLine = state === 'cancelled'
      ? '<p class="tracking-eta">This order was cancelled. It will not be delivered.</p>'
      : `<p class="tracking-eta">Estimated delivery: ${escapeHtml(formatEta(order.eta || order.schedule))} · Rider contact: ${escapeHtml(order.phone || '—')}</p>`;
    const progress = state === 'cancelled'
      ? '<div class="tracking-cancelled">CANCELLED · No delivery is scheduled for this order.</div>'
      : `<div class="tracking-steps">${steps.map((step, index) => `<div class="tracking-step ${index <= active ? 'done' : ''}"><i></i><span>${step}</span></div>`).join('')}</div>`;
    $('#trackingResult').innerHTML = `
      <div class="tracking-card"><div class="tracking-card-head"><strong>${escapeHtml(order.id)}</strong><span class="status-pill" data-state="${state}">${stateLabel}</span></div>
      <p class="tracking-address">Delivering to ${escapeHtml(order.name)} · ${escapeHtml(order.address)}</p>
      ${deliveryLine}
      ${progress}
      <div class="tracking-order-items">${itemList.map(item => `${Number(item.qty || 1)} × ${escapeHtml(item.name)} <small>(${escapeHtml(item.serving || '1 serving')})</small>`).join('<br>')}<div class="tracking-total"><span>Order total</span><strong>${peso(order.total)}</strong></div></div>
      <a class="btn btn-outline" href="${receiptHref}" target="_blank" rel="noopener">View / Print receipt ↗</a>
      <p class="tracking-refresh-note">Refresh tracking to see the latest staff update.</p></div>`;
  }

  async function trackOrder(event) {
    event.preventDefault();
    try {
      const data = await request('track_order', { id: $('#trackingCode').value });
      renderTracking(data.order);
    } catch (error) {
      $('#trackingResult').innerHTML = `<div class="tracking-empty"><p>${escapeHtml(error.message)}</p></div>`;
    }
  }

  function openAccount(mode = 'view') {
    if (typeof mode !== 'string') mode = 'view';
    const user = state.user;
    if (!user) {
      $('#accountTitle').textContent = 'Sign in';
      renderAuthForm('login');
      openOverlay('account');
      return;
    }
    const initial = escapeHtml((user.name || '?').trim().charAt(0).toUpperCase() || '?');
    const avatar = user.avatar ? `<img src="${escapeHtml(user.avatar)}" alt="Profile picture">` : `<span>${initial}</span>`;
    const address = addressOf(user);
    if (mode === 'edit') {
      $('#accountTitle').textContent = 'Edit profile';
      $('#accountContent').innerHTML = `<div class="profile-edit-head"><p>Update your details to make checkout faster.</p><button type="button" class="text-button" id="cancelProfileBtn">Cancel</button></div><form id="profileForm" class="auth-form"><div class="avatar-editor"><div class="avatar-preview" id="avatarPreview">${avatar}</div><div><label class="field">Profile picture<input type="file" name="avatar_file" accept="image/png,image/jpeg,image/webp,image/gif"></label><small>PNG, JPG, WEBP, or GIF.</small><button type="button" class="text-button" id="clearAvatarBtn" data-cleared="0">Remove photo</button></div></div><div class="auth-row"><label class="field">Full name<input name="name" required value="${escapeHtml(user.name)}"></label><label class="field">Contact number<input name="phone" required value="${escapeHtml(user.phone || '')}" placeholder="09XX XXX XXXX"></label></div><label class="field">Email address<input name="email" type="email" required value="${escapeHtml(user.email)}"></label><div class="auth-row"><label class="field">House no., street, barangay<input name="address_line" required value="${escapeHtml(address.address_line)}" placeholder="12 Rizal St., Barangay San Jose"></label><label class="field">ZIP code<input name="zip" required inputmode="numeric" pattern="[0-9]{4,5}" maxlength="5" value="${escapeHtml(address.zip)}" placeholder="1101"></label></div><div class="auth-row"><label class="field">City / municipality<select name="city" required>${cityOptions(address.city)}</select></label><label class="field">Province / region<select name="province">${provinceOptions()}</select></label>
</div><label class="field">Nearby landmark<input name="landmark" value="${escapeHtml(address.landmark)}" placeholder="Across the town plaza"></label><button class="btn btn-primary" type="submit">Save changes</button></form>`;
    } else {
      const since = user.created_at ? new Date(user.created_at).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }) : 'Active account';
      $('#accountTitle').textContent = 'Your profile';
      $('#accountContent').innerHTML = `<div class="profile-view"><div class="profile-hero"><div class="avatar-preview large">${avatar}</div><div class="profile-identity"><span class="profile-kicker">FilipinOnly account</span><h3>${escapeHtml(user.name)}</h3><p>Member since ${escapeHtml(since)}</p></div><span class="profile-badge">Active</span></div><dl class="profile-details"><div><dt>Email address</dt><dd>${escapeHtml(user.email)}</dd></div><div><dt>Contact number</dt><dd>${escapeHtml(user.phone || 'Not set')}</dd></div><div class="wide"><dt>Delivery address</dt><dd>${escapeHtml(composeAddress(address) || 'Not set yet')}${address.landmark ? ` · ${escapeHtml(address.landmark)}` : ''}</dd></div>
</dl><div class="profile-actions"><button class="btn btn-primary" id="editProfileBtn">Edit profile</button><button class="btn btn-outline" id="historyBtn">Order history</button><button class="btn btn-outline" id="viewOrdersBtn">Track latest order</button><button class="btn btn-outline" id="logoutBtn">Sign out</button></div>
</div>`;
    }
    openOverlay('account');
  }

  function renderAuthForm(mode) {
    clearTimeout(emailCheckTimer);
    emailCheck = { email: '', available: true, checked: false, checking: false };
    const register = mode === 'register';
    const nameField = register ? '<label class="field" data-auth-field="name">Full name<input name="name" required autocomplete="name" placeholder="Juan Dela Cruz" data-auth-input><small class="field-feedback" data-feedback="name"></small></label>' : '';
    const phoneField = register ? '<label class="field" data-auth-field="phone">Contact number<input name="phone" required inputmode="tel" autocomplete="tel" placeholder="09XX XXX XXXX" data-auth-input><small class="field-feedback" data-feedback="phone"></small></label>' : '';
    const identityRow = register ? `<div class="auth-row">${nameField}${phoneField}</div>` : '';
    const confirmField = register ? '<label class="field" data-auth-field="confirm">Confirm password<input name="confirm" type="password" required autocomplete="new-password" placeholder="Repeat your password" data-auth-input><small class="field-feedback" data-feedback="confirm"></small></label>' : '';
    const strength = register ? '<div class="password-strength" id="passwordStrength" hidden><span></span><small></small><ul class="password-checks"><li data-check="length">6+ characters</li><li data-check="case">Upper and lowercase</li><li data-check="number">Number</li><li data-check="symbol">Symbol</li></ul></div>' : '';
    const intro = register ? 'Create an account to save your details for faster checkout.' : 'Sign in to continue your order.';
    $('#accountContent').innerHTML = `<div class="auth-tabs"><button type="button" class="auth-tab ${!register ? 'active' : ''}" data-auth-tab="login">Sign in</button><button type="button" class="auth-tab ${register ? 'active' : ''}" data-auth-tab="register">Create account</button></div><p class="auth-intro">${intro}</p><form class="auth-form" id="authForm">${identityRow}<label class="field" data-auth-field="email">Email address<input name="email" type="email" required autocomplete="email" placeholder="you@example.com" data-auth-input><small class="field-feedback" data-feedback="email"></small></label><label class="field" data-auth-field="password">Password<span class="password-wrap"><input name="password" type="password" minlength="6" required autocomplete="${register ? 'new-password' : 'current-password'}" placeholder="At least 6 characters" data-auth-input><button type="button" class="password-toggle" data-password-toggle>Show</button></span><small class="field-feedback" data-feedback="password"></small></label>${strength}${confirmField}<div class="auth-feedback" id="authFeedback" role="status" aria-live="polite"></div><button class="btn btn-primary" type="submit">${register ? 'Create my account' : 'Sign in'} <span>→</span></button></form><button type="button" class="btn btn-outline gmail-button" id="gmailSignInBtn">Continue with Gmail</button><p class="auth-switch-note">${register ? 'Already have an account? <button class="text-button" data-auth-tab="login">Sign in</button>' : 'New to FilipinOnly? <button class="text-button" data-auth-tab="register">Create an account</button>'}</p><p class="auth-footnote">Accounts use hashed passwords. Gmail sign-in works after adding a Google Client ID in config.php.</p>`;
    validateAuthForm($('#authForm'));
  }

  async function submitAuth(event) {
    event.preventDefault();
    const form = event.target;
    const isRegister = !!form.querySelector('[name="name"]');
    if (isRegister) {
      clearTimeout(emailCheckTimer);
      await checkEmailAvailability(form.elements.email.value);
    }
    if (!validateAuthForm(form, true)) return;
    const payload = Object.fromEntries(new FormData(form).entries());
    delete payload.confirm;
    const mode = isRegister ? 'register' : 'login';
    const button = form.querySelector('button[type="submit"]');
    const originalLabel = button?.innerHTML || '';
    if (button) { button.disabled = true; button.textContent = mode === 'register' ? 'Creating account…' : 'Signing in…'; }
    try {
      const result = await request(mode, payload);
      await refreshState();
      openAccount();
      showToast(result.message);
    } catch (error) {
      setAuthFeedback(error.message, 'error');
      showToast(error.message, 'error');
    } finally {
      if (button?.isConnected) { button.disabled = false; button.innerHTML = originalLabel; }
    }
  }

  async function startGmailSignIn() {
    if (!googleClientId) { showToast('Add GOOGLE_CLIENT_ID in config.php to enable Gmail sign-in.', 'error'); return; }
    if (!window.google?.accounts?.oauth2) { showToast('Google sign-in is still loading. Try again.', 'error'); return; }
    try {
      const client = google.accounts.oauth2.initTokenClient({
        client_id: googleClientId,
        scope: 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        callback: async response => {
          if (!response?.access_token) { showToast('Gmail sign-in was cancelled.', 'error'); return; }
          try {
            const result = await request('gmail_auth', { access_token: response.access_token });
            await refreshState();
            openAccount();
            showToast(result.message);
          } catch (error) { showToast(error.message, 'error'); }
        }
      });
      client.requestAccessToken();
    } catch (error) { showToast(error.message, 'error'); }
  }

  async function saveProfile(event) {
    event.preventDefault();
    const form = event.target;
    const payload = Object.fromEntries(new FormData(form).entries());
    delete payload.avatar_file;
    payload.address = composeAddress(payload);
    try {
      const file = form.elements.avatar_file.files[0];
      if (file) payload.avatar = await readImageAsDataUrl(file);
      else if ($('#clearAvatarBtn', form)?.dataset.cleared === '1') payload.avatar = '';
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

  async function openTracking() {
    const latestId = state.latest_order?.id || '';
    $('#trackingResult').innerHTML = latestId
      ? '<div class="tracking-empty"><p>Loading your latest order…</p></div>'
      : '<div class="tracking-empty"><p>Enter the order number from your confirmation to see its status.</p></div>';
    $('#trackingCode').value = latestId;
    if (latestId) {
      try {
        const data = await request('track_order', { id: latestId });
        renderTracking(data.order);
      } catch (error) {
        renderTracking(state.latest_order);
      }
    }
    openOverlay('tracking');
  }

  // Delegated events keep controls working when product or cart markup is re-rendered.
  document.addEventListener('click', async event => {
    const add = event.target.closest('[data-add]');
    const customize = event.target.closest('[data-customize]');
    const close = event.target.closest('[data-close]');
    const qty = event.target.closest('[data-qty]');
    const remove = event.target.closest('[data-remove]');
    const category = event.target.closest('button[data-category]');
    const authTab = event.target.closest('[data-auth-tab]');
    const gmail = event.target.closest('#gmailSignInBtn');
    const passwordToggle = event.target.closest('[data-password-toggle]');
    const clearAvatar = event.target.closest('#clearAvatarBtn');
    const editProfile = event.target.closest('#editProfileBtn');
    const cancelProfile = event.target.closest('#cancelProfileBtn');
    const historyBtn = event.target.closest('#historyBtn');
    const historyTrack = event.target.closest('[data-history-track]');
    const ingredient = event.target.closest('[data-remove-ingredient]');
    const spice = event.target.closest('[data-spice]');
    const addCustomized = event.target.closest('#addCustomizedBtn');
    if (add) addToCart(add.dataset.add);
    if (customize) openCustomize(customize.dataset.customize);
    if (close) closeOverlay(close.dataset.close);
    if (qty) changeQuantity(qty.dataset.qty, Number(qty.dataset.change));
    if (remove) { try { await request('remove_from_cart', { id: remove.dataset.remove }); await refreshState(); } catch (error) { showToast(error.message, 'error'); } }
    if (category) setCategory(category.dataset.category);
    if (authTab) renderAuthForm(authTab.dataset.authTab);
    if (passwordToggle) {
      const passwordInput = $('#authForm [name="password"]');
      if (passwordInput) {
        const show = passwordInput.type === 'password';
        passwordInput.type = show ? 'text' : 'password';
        passwordToggle.textContent = show ? 'Hide' : 'Show';
      }
    }
    if (editProfile) openAccount('edit');
    if (cancelProfile) openAccount('view');
    if (historyBtn) openOrderHistory();
    if (historyTrack) {
      const order = historyOrders.find(item => String(item.id) === historyTrack.dataset.historyTrack);
      closeOverlay('history');
      if (order) openTrackingWithOrder(order);
      else { $('#trackingCode').value = historyTrack.dataset.historyTrack; openTracking(); }
    }
    if (gmail) startGmailSignIn();
    if (clearAvatar) {
      clearAvatar.dataset.cleared = '1';
      clearAvatar.textContent = 'Photo will be removed';
      $('#avatarPreview').innerHTML = '<span>?</span>';
    }
    if (ingredient) ingredient.classList.toggle('selected');
    if (spice) { $$('#spiceChoices .custom-chip').forEach(button => button.classList.toggle('selected', button === spice)); }
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
  $('#accountBtn').addEventListener('click', () => openAccount());
  $('#applyVoucherBtn').addEventListener('click', applyVoucher);
  $('#menuSearch').addEventListener('input', renderProducts);
  $('#sortMenu').addEventListener('change', renderProducts);
  $('#clearSearch').addEventListener('click', () => { $('#menuSearch').value = ''; setCategory('All'); });
  $('#mobileMenuToggle').addEventListener('click', () => {
    const open = $('#mainNav').classList.toggle('open');
    $('#mobileMenuToggle').setAttribute('aria-expanded', String(open));
  });
  $('#mainNav').addEventListener('click', event => { if (event.target.closest('a')) closeMobileNav(); });
  $('#mobileMenuLink').addEventListener('click', () => { closeMobileNav(); $('#menu').scrollIntoView({ behavior: 'smooth' }); });
  $('#mobileCartLink').addEventListener('click', () => openOverlay('cart'));
  $('#accountContent').addEventListener('submit', event => {
    if (event.target.id === 'authForm') submitAuth(event);
    if (event.target.id === 'profileForm') saveProfile(event);
  });
  $('#accountContent').addEventListener('input', event => {
    if (!event.target.matches('#authForm [data-auth-input]')) return;
    const form = $('#authForm');
    if (event.target.name === 'email' && form.querySelector('[name="name"]')) {
      const value = event.target.value;
      clearTimeout(emailCheckTimer);
      emailCheck = { email: value.trim().toLowerCase(), available: true, checked: false, checking: false };
      validateAuthForm(form);
      emailCheckTimer = setTimeout(() => checkEmailAvailability(value), 350);
      return;
    }
    validateAuthForm(form);
  });
  $('#accountContent').addEventListener('focusout', event => {
    if (event.target.matches('#authForm [data-auth-input]')) validateAuthForm($('#authForm'), true);
  });
  $('#accountContent').addEventListener('change', event => {
    if (event.target.name === 'avatar_file') {
      const button = $('#clearAvatarBtn');
      if (button) { button.dataset.cleared = '0'; button.textContent = 'Remove photo'; }
      const file = event.target.files?.[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = () => {
          const preview = $('#avatarPreview');
          if (preview) preview.innerHTML = `<img src="${reader.result}" alt="Profile picture">`;
        };
        reader.readAsDataURL(file);
      }
    }
  });
  $('#accountContent').addEventListener('click', event => {
    if (event.target.id === 'logoutBtn') logout();
    if (event.target.id === 'viewOrdersBtn') { closeOverlay('account'); openTracking(); }
  });
  $$('input[name="delivery"]').forEach(input => input.addEventListener('change', () => { $('#scheduleField').hidden = input.value !== 'Scheduled' || !input.checked; updateCheckoutEta(); }));
  $('#checkoutSchedule').addEventListener('input', updateCheckoutEta);
  $$('.modal-backdrop').forEach(backdrop => backdrop.addEventListener('click', event => { if (event.target === backdrop) closeOverlay(backdrop.id.replace('Modal', '')); }));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') { ['cart','checkout','customize','account','history','tracking'].forEach(closeOverlay);
 closeMobileNav(); } });

  function filterBranches() {
    const city = $('#branchCityFilter')?.value || 'all';
    const cards = $$('.branch-card');
    let shown = 0;
    cards.forEach(card => {
      const match = city === 'all' || card.dataset.city === city;
      card.hidden = !match;
      if (match) shown++;
    });
    const count = $('#branchCount');
    if (count) count.textContent = city === 'all' ? `${cards.length} branches` : `${shown} ${shown === 1 ? 'branch' : 'branches'} in ${city}`;
  }
  $('#branchCityFilter')?.addEventListener('change', filterBranches);
  filterBranches();

  refreshState().catch(error => showToast(error.message, 'error'));
  renderProducts();
})();
