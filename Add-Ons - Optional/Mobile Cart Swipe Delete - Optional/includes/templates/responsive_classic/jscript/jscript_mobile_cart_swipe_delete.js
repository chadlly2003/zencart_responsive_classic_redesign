
(function () {
  'use strict';

  function init() {
    if (document.body.id !== 'shoppingcartBody') return;

    const table = document.getElementById('cartContentsDisplay');
    if (!table || table.dataset.ddkSwipeFinal === '1') return;

    const mobile = window.matchMedia('(max-width: 575px)');

    // Clean up DOM injected by earlier test builds, if still present.
    table.querySelectorAll('.swipe-delete-action,.ddk-swipe-delete,.cart-swipe-delete-cell').forEach(el => el.remove());
    document.querySelectorAll('#ddkCartSwipeWrapper > .ddk-cart-delete-action').forEach(el => el.remove());

    let wrapper = table.parentElement;

    if (!wrapper || wrapper.id !== 'ddkCartSwipeWrapper') {
      wrapper = document.createElement('div');
      wrapper.id = 'ddkCartSwipeWrapper';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    }

    table.dataset.ddkSwipeFinal = '1';

    const entries = [];

    const rows = Array.from(table.querySelectorAll('tr')).filter(row =>
      row.querySelector('.cartRemoveItemDisplay a[href*="action=remove_product"]')
    );

    function position(entry) {
      const w = wrapper.getBoundingClientRect();
      const r = entry.row.getBoundingClientRect();

      entry.action.style.top = (r.top - w.top + wrapper.scrollTop) + 'px';
      entry.action.style.height = r.height + 'px';
    }

    function close(entry) {
      entry.row.classList.remove('ddk-swipe-open');
      entry.action.hidden = true;
    }

    function open(entry) {
      entries.forEach(other => {
        if (other !== entry) close(other);
      });

      position(entry);
      entry.action.hidden = false;
      entry.row.classList.add('ddk-swipe-open');
    }

    rows.forEach(row => {
      const link = row.querySelector('.cartRemoveItemDisplay a[href*="action=remove_product"]');
      const cells = Array.from(row.children).filter(el => el.tagName === 'TD');

      if (!link || !cells.length) return;

      row.classList.remove('swipe-open');
      row.classList.add('ddk-swipe-row');

      const action = document.createElement('button');
      action.type = 'button';
      action.className = 'ddk-cart-delete-action';

      // Visible button text
      action.textContent = 'Delete';

      // Accessibility: accessible name includes visible text
      action.setAttribute('aria-label', 'Delete this product from cart');

      action.hidden = true;
      wrapper.appendChild(action);

      const entry = { row, action };
      entries.push(entry);

      action.addEventListener('click', () => {
        window.location.assign(link.href);
      });

      let startX = 0;
      let startY = 0;
      let tracking = false;

      function start(x, y) {
        if (!mobile.matches) return;

        startX = x;
        startY = y;
        tracking = true;
      }

      function end(x, y) {
        if (!tracking || !mobile.matches) return;

        tracking = false;

        const dx = x - startX;
        const dy = y - startY;

        if (Math.abs(dx) < 35 || Math.abs(dx) <= Math.abs(dy) * 1.15) return;

        if (dx < 0) {
          open(entry);
        } else {
          close(entry);
        }
      }

      row.addEventListener('touchstart', e => {
        if (
          e.touches.length === 1 &&
          !e.target.closest('input,select,button')
        ) {
          start(
            e.touches[0].clientX,
            e.touches[0].clientY
          );
        }
      }, { passive: true });

      row.addEventListener('touchend', e => {
        if (e.changedTouches.length) {
          end(
            e.changedTouches[0].clientX,
            e.changedTouches[0].clientY
          );
        }
      }, { passive: true });

      row.addEventListener('touchcancel', () => {
        tracking = false;
      }, { passive: true });

      // Mouse dragging in desktop browser's mobile emulator.
      row.addEventListener('mousedown', e => {
        if (
          e.button === 0 &&
          !e.target.closest('a,input,select,button')
        ) {
          start(e.clientX, e.clientY);
        }
      });

      window.addEventListener('mouseup', e => {
        end(e.clientX, e.clientY);
      });
    });

    function refresh() {
      entries.forEach(entry => {
        if (!mobile.matches) {
          close(entry);
        } else if (entry.row.classList.contains('ddk-swipe-open')) {
          position(entry);
        }
      });
    }

    window.addEventListener('resize', refresh, { passive: true });
    window.addEventListener('scroll', refresh, { passive: true });

    console.info('Cart swipe initialized:', entries.length, 'product rows');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
