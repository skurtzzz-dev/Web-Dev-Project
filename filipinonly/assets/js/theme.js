(() => {
  'use strict';
  const KEY = 'filipinonly.theme';
  const button = document.getElementById('themeToggle');

  function sync() {
    const dark = document.documentElement.dataset.theme === 'dark';
    if (!button) return;
    const action = dark ? 'Switch to light mode' : 'Switch to dark mode';
    button.setAttribute('aria-pressed', String(dark));
    button.setAttribute('aria-label', action);
    button.setAttribute('title', action);
  }

  sync();
  button?.addEventListener('click', () => {
    const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem(KEY, next); } catch (_) {}
    sync();
  });
})();
