/**
 * Floating Add to Cart
 *
 * Product image + name + price + Add to Cart
 *
 * Supports:
 * - Regular products
 * - Document products
 * - Music products
 *
 * - Appears after scrolling past original Add to Cart
 * - Disappears when scrolling back
 * - Hides when .footer enters the viewport
 * - Reappears when scrolling above the footer
 * - Uses original Zen Cart Add to Cart button
 * - Keeps selected quantity and product attributes
 * - Updates price when product price changes
 * - Supports desktop and mobile
 * - Works with Back to Top button
 */

(function () {
    'use strict';

    function initFloatingCart() {

        // =====================================
        // SUPPORTED ZEN CART PRODUCT PAGES
        // =====================================

        if (!document.body) return;

        const supportedPages = [
            'productinfoBody',
            'documentproductinfoBody',
            'productmusicinfoBody'
        ];

        const page = new URLSearchParams(
            window.location.search
        ).get('main_page');

        const supportedProductPages = [
            'product_info',
            'document_product_info',
            'product_music_info'
        ];

        if (
            !supportedPages.includes(document.body.id) &&
            !supportedProductPages.includes(page)
        ) {
            return;
        }

        // Prevent duplicate initialization.
        if (document.getElementById('floatingCartBar')) {
            console.warn('Floating Cart: Bar already exists.');
            return;
        }

        // =====================================
        // ORIGINAL ZEN CART ELEMENTS
        // =====================================

        const form = document.getElementById('addToCartForm');

        const originalButton =
            document.getElementById('addToCartButton');

        const originalPrice =
            document.getElementById('productPrices');

        const originalName =
            document.getElementById('productName');

        const originalImage =
            document.getElementById('mainImage');

        // Your footer uses class="footer".
        const footer = document.querySelector(
            '.footer, #footerWrapper, #footer, footer'
        );

        // Do not display on products without
        // the normal Add to Cart form.
        if (!form || !originalButton || !originalPrice) {
            console.log(
                'Floating Cart: Missing Add to Cart elements.'
            );
            return;
        }

        // =====================================
        // CREATE FLOATING BAR
        // =====================================

        const bar = document.createElement('div');

        bar.id = 'floatingCartBar';
        bar.setAttribute('role', 'region');
        bar.setAttribute('aria-label', 'Product purchase bar');

        // PRODUCT IMAGE
        const imageBox = document.createElement('img');
        imageBox.className = 'floating-cart-image';

        if (originalImage) {

            const imageSource =
                originalImage.getAttribute('src') ||
                originalImage.currentSrc ||
                originalImage.src;

            if (imageSource) {
                imageBox.src = imageSource;
                imageBox.alt =
                    originalImage.alt || 'Product image';
                imageBox.loading = 'eager';
                imageBox.decoding = 'async';
            }
        }

        // PRODUCT INFORMATION CONTAINER
        const infoBox = document.createElement('div');
        infoBox.className = 'floating-cart-info';

        // PRODUCT NAME
        const nameBox = document.createElement('div');
        nameBox.className = 'floating-cart-name';

        if (originalName) {
            nameBox.textContent =
                originalName.textContent.trim();
        }

        // PRODUCT PRICE
        const priceBox = document.createElement('div');
        priceBox.className = 'floating-cart-price';

        function updatePrice() {
            priceBox.innerHTML = originalPrice.innerHTML;
        }

        updatePrice();

        // Keep floating price synchronized.
        const priceObserver = new MutationObserver(function () {
            updatePrice();
        });

        priceObserver.observe(originalPrice, {
            childList: true,
            subtree: true,
            characterData: true
        });

        // Combine name and price.
        infoBox.appendChild(nameBox);
        infoBox.appendChild(priceBox);

        // =====================================
        // FLOATING ADD TO CART BUTTON
        // =====================================

        const floatingButton =
            document.createElement('button');

        floatingButton.type = 'button';
        floatingButton.className = 'floating-cart-button';

        // Detect actual selectable product attributes,
        // not just hidden inputs.
        const attributes =
            document.getElementById('productAttributes');

        const hasOptions = !!(
            attributes &&
            attributes.querySelector(
                'select, input[type="radio"], input[type="checkbox"], input[type="text"], textarea'
            )
        );

        floatingButton.textContent =
            hasOptions ? 'Select options' : 'Add to Cart';

        if (hasOptions) {
            floatingButton.classList.add('has-options');
        }

        // Add image only if a valid source exists.
        if (imageBox.getAttribute('src')) {
            bar.appendChild(imageBox);
        }

        bar.appendChild(infoBox);
        bar.appendChild(floatingButton);

        // Add bar directly to body.
        document.body.appendChild(bar);

        // =====================================
        // ADD TO CART CLICK
        // =====================================

        floatingButton.addEventListener('click', function (event) {

            event.preventDefault();
            event.stopImmediatePropagation();

            // Products with options: never submit.
            // Scroll to original Zen Cart attributes.
            if (hasOptions) {

                attributes.scrollIntoView({
                    behavior: window.matchMedia(
                        '(prefers-reduced-motion: reduce)'
                    ).matches ? 'auto' : 'smooth',
                    block: 'start'
                });

                return;
            }

            // Products without options:
            // Preserve original Add to Cart action.
            if (originalButton.disabled) return;
            if (!form.reportValidity()) return;

            originalButton.click();
        });

        // =====================================
        // FOOTER VISIBILITY
        // =====================================

        function isFooterVisible() {

            if (!footer) {
                return false;
            }

            const footerRect =
                footer.getBoundingClientRect();

            return (
                footerRect.top < window.innerHeight &&
                footerRect.bottom > 0
            );
        }

        // =====================================
        // SHOW / HIDE FLOATING BAR
        // =====================================

        function updateVisibility() {

            if (!document.body.contains(originalButton)) {

                bar.classList.remove('is-visible');

                document.body.classList.remove(
                    'has-floating-cart'
                );

                return;
            }

            const rect =
                originalButton.getBoundingClientRect();

            // Preserve original music product behavior.
          // Show floating cart after scrolling 300 pixels.
        // =====================================
// FLOATING CART SCROLL SETTINGS
// MOBILE / TABLET / DESKTOP
// =====================================

// Scroll distance for each device
const mobileScroll = 500;
const tabletScroll = 600;
const desktopScroll = 700;

// Detect screen width
const screenWidth = window.innerWidth;

// Select scroll distance
let scrollDistance;

if (screenWidth <= 575) {
    // Mobile
    scrollDistance = mobileScroll;

} else if (screenWidth <= 991) {
    // Tablet
    scrollDistance = tabletScroll;

} else {
    // Desktop
    scrollDistance = desktopScroll;
}

// Show floating cart after scrolling
const passedButton = window.scrollY > scrollDistance;


            // Hide the floating bar when
            // the footer becomes visible.
            const footerVisible = isFooterVisible();

            const show =
                passedButton &&
                !originalButton.disabled &&
                !footerVisible;

            bar.classList.toggle('is-visible', show);

            // Used by Back to Top positioning.
            document.body.classList.toggle(
                'has-floating-cart',
                show
            );
        }

        // =====================================
        // OPTIMIZED SCROLL HANDLING
        // =====================================

        let scrollScheduled = false;

        function onScroll() {

            if (scrollScheduled) return;

            scrollScheduled = true;

            window.requestAnimationFrame(function () {

                updateVisibility();

                scrollScheduled = false;

            });
        }

        window.addEventListener('scroll', onScroll, {
            passive: true
        });

        window.addEventListener('resize', onScroll);

        // Check initial position.
        updateVisibility();

        console.log(
            'Floating Cart Ready',
            {
                page: document.body.id,
                image: !!imageBox.getAttribute('src'),
                name: nameBox.textContent,
                price: priceBox.textContent.trim(),
                footerDetected: !!footer
            }
        );
    }

    // =====================================
    // INITIALIZATION
    // =====================================

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            initFloatingCart
        );

    } else {

        initFloatingCart();

    }

})();
