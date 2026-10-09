/**
 * @file
 * Lekki skrypt obsługi banera plików cookies (RODO / e-Privacy).
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'architect_cookie_consent';

  function initCookieBanner() {
    const banner = document.getElementById('architect-cookie-banner');
    const acceptBtn = document.getElementById('cookie-btn-accept');

    if (!banner || !acceptBtn) {
      return;
    }

    try {
      if (localStorage.getItem(STORAGE_KEY) === 'accepted') {
        return;
      }
    } catch (e) {
      // localStorage może być zablokowany w trybie incognito
    }

    banner.style.display = 'block';

    acceptBtn.addEventListener('click', function () {
      try {
        localStorage.setItem(STORAGE_KEY, 'accepted');
      } catch (e) {}

      banner.style.transition = 'opacity 0.25s, transform 0.25s';
      banner.style.opacity = '0';
      banner.style.transform = 'translateY(100%)';

      setTimeout(function () {
        banner.style.display = 'none';
      }, 300);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookieBanner);
  } else {
    initCookieBanner();
  }
})();
