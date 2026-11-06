
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

    // Place button inside ARIA landmark
    landmark.appendChild(button);
    document.body.appendChild(landmark);

    // Show button after scrolling
    function update() {
      button.classList.toggle(
        'rcr-visible',
        window.scrollY > 300
      );
    }

    window.addEventListener('scroll', update, {
      passive: true
    });

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
