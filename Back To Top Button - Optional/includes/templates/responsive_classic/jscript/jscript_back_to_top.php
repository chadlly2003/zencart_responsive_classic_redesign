<?php
/**
 * Back to Top
 * Automatically loaded by Zen Cart's template jscript loader.
 */
?>
<script>
(function () {
  'use strict';

  function init() {
    if (document.getElementById('rcr-back-to-top')) return;

    // Create accessible navigation landmark
    var landmark = document.createElement('nav');
    landmark.id = 'rcr-back-to-top-landmark';
    landmark.setAttribute('aria-label', 'Back to top navigation');

    // Create Back to Top button
    var button = document.createElement('button');
    button.id = 'rcr-back-to-top';
    button.type = 'button';
    button.setAttribute('aria-label', 'Back to top');
    button.setAttribute('title', 'Back to top');
    button.textContent = '\u2191';

    landmark.appendChild(button);
    document.body.appendChild(landmark);

    var cart = document.getElementById('floatingCartBar');

    // Automatically position above floating cart
    function positionBackToTop() {
      var bottom = 20;

      if (cart && cart.classList.contains('is-visible')) {
        var rect = cart.getBoundingClientRect();

        if (rect.height > 0) {
          bottom = Math.max(
            20,
            window.innerHeight - rect.top + 10
          );
        }
      }

      landmark.style.position = 'fixed';
      landmark.style.bottom = bottom + 'px';
      landmark.style.right = '20px';
      landmark.style.zIndex = '10001';

      // Button is positioned by the landmark
      button.style.position = 'relative';
      button.style.bottom = 'auto';
      button.style.right = 'auto';
    }

    // Show button after scrolling
    function update() {
      button.classList.toggle(
        'rcr-visible',
        window.scrollY > 300
      );

      positionBackToTop();
    }

    window.addEventListener('scroll', update, {
      passive: true
    });

    window.addEventListener('resize', update);

    // Watch floating cart visibility changes
    if (cart) {
      var observer = new MutationObserver(update);

      observer.observe(cart, {
        attributes: true,
        attributeFilter: ['class', 'style']
      });

      // Adjust when responsive cart height changes
      if ('ResizeObserver' in window) {
        var resizeObserver = new ResizeObserver(
          positionBackToTop
        );
        resizeObserver.observe(cart);
      }
    }

    update();

    // Scroll to top
    button.addEventListener('click', function () {
      window.scrollTo({
        top: 0,
        behavior: window.matchMedia(
          '(prefers-reduced-motion: reduce)'
        ).matches ? 'auto' : 'smooth'
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
