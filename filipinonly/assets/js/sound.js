(() => {
  'use strict';
  let context;

  try { localStorage.removeItem('filipinonly.sound'); } catch (_) {}

  function audio() {
    if (!context) {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return null;
      try { context = new AudioContext(); } catch (_) { return null; }
    }
    if (context.state === 'suspended') context.resume().catch(() => {});
    return context;
  }

  function tone(frequency, duration, wave = 'triangle', volume = 0.028) {
    const ac = audio();
    if (!ac) return;
    const now = ac.currentTime;
    const oscillator = ac.createOscillator();
    const gain = ac.createGain();
    oscillator.type = wave;
    oscillator.frequency.setValueAtTime(frequency, now);
    gain.gain.setValueAtTime(volume, now);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);
    oscillator.connect(gain);
    gain.connect(ac.destination);
    oscillator.start(now);
    oscillator.stop(now + duration + 0.01);
  }

  const sound = {
    click: () => tone(540, 0.045),
    add: () => tone(720, 0.075, 'sine', 0.034),
    remove: () => tone(320, 0.055, 'sine', 0.03),
    error: () => tone(220, 0.12, 'sawtooth', 0.017)
  };

  document.addEventListener('click', event => {
    const target = event.target.closest('button, a[href], input, select, .category-tile, .filter-pill, .custom-chip');
    if (!target || target.disabled) return;
    if (target.closest('[data-add], #addCustomizedBtn')) sound.add();
    else if (target.closest('[data-qty], [data-remove]')) sound.remove();
    else sound.click();
  });

  window.FilipinOnlySound = sound;
})();
