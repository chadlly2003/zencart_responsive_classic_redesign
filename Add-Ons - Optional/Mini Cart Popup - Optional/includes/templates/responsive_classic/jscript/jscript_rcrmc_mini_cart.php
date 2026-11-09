<?php
/*
 * ========================================
 * SMOOTH SCROLL RESTORATION
 * ========================================
 *
 * Normal Zen Cart page reload, no AJAX.
 *
 * Supports:
 * - All Products
 * - Category listings
 * - New Products
 * - Featured Products
 * - Specials
 * - Product information pages
 *
 * Preserves the scroll position when
 * adding a product to the cart.
 */
const rcrScrollKey = 'rcrmc-add-to-cart-scroll';
/*
 * Save position before Add to Cart.
 */
document.addEventListener('submit', function (event) {

    const form = event.target;

    if (
        !form.matches('form[name="cart_quantity"]') ||
        !form.querySelector('[name="products_id"]')
    ) {
        return;
    }

    try {
        sessionStorage.setItem(
            rcrScrollKey,
            JSON.stringify({
                position: window.scrollY,
                path: window.location.pathname,
                page: new URLSearchParams(
                    window.location.search
                ).get('main_page') || 'index',
                time: Date.now()
            })
        );
    } catch (error) {
        // Continue normal Zen Cart submission.
    }

}, true);

/*
 * Restore position after page reload.
 */
let savedScroll = null;

try {
    const stored = sessionStorage.getItem(rcrScrollKey);

    if (stored !== null) {
        savedScroll = JSON.parse(stored);
        sessionStorage.removeItem(rcrScrollKey);
    }
} catch (error) {
    savedScroll = null;
}

/*
 * Only restore a recent Add to Cart position
 * on the same page.
 */
const currentPage = new URLSearchParams(
    window.location.search
).get('main_page') || 'index';

const shouldRestore = (
    savedScroll &&
    savedScroll.path === window.location.pathname &&
    savedScroll.page === currentPage &&
    Number.isFinite(savedScroll.position) &&
    Date.now() - savedScroll.time < 30000
);

if (shouldRestore) {

    /*
     * Prevent the browser from fighting
     * our custom scroll restoration.
     */
    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    /*
     * Hide the page while restoring.
     * This avoids the visible top-to-bottom jump.
     */
    document.documentElement.style.visibility = 'hidden';

    let restored = false;

    const restoreScroll = function () {

        if (restored) return;

        restored = true;

        const targetPosition = Math.min(
            savedScroll.position,
            Math.max(
                0,
                document.documentElement.scrollHeight -
                window.innerHeight
            )
        );

        /*
         * Restore immediately, without animation.
         * Smooth scrolling here would visibly
         * travel from the top of the page.
         */
        const previousScrollBehavior =
            document.documentElement.style.scrollBehavior;

        document.documentElement.style.scrollBehavior =
            'auto';

        window.scrollTo(0, targetPosition);

        requestAnimationFrame(function () {

            window.scrollTo(0, targetPosition);

            document.documentElement.style.visibility = '';

            document.documentElement.style.scrollBehavior =
                previousScrollBehavior;

        });

    };

    /*
     * Wait for images and layout to settle.
     */
    if (document.readyState === 'complete') {

        restoreScroll();

    } else {

        window.addEventListener(
            'load',
            restoreScroll,
            { once: true }
        );

    }

    /*
     * Safety fallback:
     * Never leave the page hidden indefinitely.
     */
    window.setTimeout(function () {

        if (!restored) {
            restoreScroll();
        }

        document.documentElement.style.visibility = '';

    }, 2500);
}

/*
 * ========================================
 * MINI CART DISPLAY
 * ========================================
 */

if (!data.show) return;

</script>
