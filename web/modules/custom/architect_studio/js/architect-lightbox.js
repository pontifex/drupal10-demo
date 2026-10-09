/**
 * @file
 * Obsługa powiększania obrazów wizualizacji i rzutów w oknie modalnym (Lightbox).
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.architectLightbox = {
    attach: function (context) {
      const dialog = document.getElementById('architect-lightbox-dialog');
      if (!dialog) {
        return;
      }

      const activeImg = document.getElementById('lightbox-active-img');
      const caption = document.getElementById('lightbox-caption');
      const counter = document.getElementById('lightbox-counter');
      const closeBtn = document.getElementById('lightbox-close');
      const prevBtn = document.getElementById('lightbox-prev');
      const nextBtn = document.getElementById('lightbox-next');
      const navContainer = document.getElementById('lightbox-nav');

      const zoomables = Array.from(document.querySelectorAll('.zoomable-image-wrapper'));
      if (zoomables.length === 0) {
        return;
      }

      let currentIndex = 0;

      function showImage(index) {
        if (index < 0) {
          index = zoomables.length - 1;
        } else if (index >= zoomables.length) {
          index = 0;
        }
        currentIndex = index;

        const target = zoomables[currentIndex];
        const src = target.getAttribute('data-full-src');
        const title = target.getAttribute('data-title') || '';

        if (activeImg) {
          activeImg.src = src;
          activeImg.alt = title;
        }
        if (caption) {
          caption.textContent = title;
        }
        if (counter) {
          counter.textContent = (currentIndex + 1) + ' / ' + zoomables.length;
        }
        if (navContainer) {
          navContainer.style.display = zoomables.length > 1 ? 'flex' : 'none';
        }
      }

      function openLightbox(index) {
        showImage(index);
        if (typeof dialog.showModal === 'function') {
          dialog.showModal();
        } else {
          dialog.setAttribute('open', '');
        }
        document.body.classList.add('lightbox-open');
      }

      function closeLightbox() {
        if (typeof dialog.close === 'function') {
          dialog.close();
        } else {
          dialog.removeAttribute('open');
        }
        document.body.classList.remove('lightbox-open');
      }

      zoomables.forEach(function (wrapper, idx) {
        if (wrapper.dataset.lightboxBound) {
          return;
        }
        wrapper.dataset.lightboxBound = 'true';

        wrapper.addEventListener('click', function (e) {
          e.preventDefault();
          openLightbox(idx);
        });

        wrapper.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openLightbox(idx);
          }
        });
      });

      if (closeBtn && !closeBtn.dataset.lightboxBound) {
        closeBtn.dataset.lightboxBound = 'true';
        closeBtn.addEventListener('click', function () {
          closeLightbox();
        });
      }

      if (prevBtn && !prevBtn.dataset.lightboxBound) {
        prevBtn.dataset.lightboxBound = 'true';
        prevBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          showImage(currentIndex - 1);
        });
      }

      if (nextBtn && !nextBtn.dataset.lightboxBound) {
        nextBtn.dataset.lightboxBound = 'true';
        nextBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          showImage(currentIndex + 1);
        });
      }

      if (!dialog.dataset.lightboxBound) {
        dialog.dataset.lightboxBound = 'true';
        dialog.addEventListener('click', function (e) {
          if (e.target === dialog) {
            closeLightbox();
          }
        });

        dialog.addEventListener('close', function () {
          document.body.classList.remove('lightbox-open');
        });

        window.addEventListener('keydown', function (e) {
          if (!dialog.open) {
            return;
          }
          if (e.key === 'ArrowLeft') {
            showImage(currentIndex - 1);
          } else if (e.key === 'ArrowRight') {
            showImage(currentIndex + 1);
          }
        });
      }
    }
  };
})(Drupal);
