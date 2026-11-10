 
<?php
/**
 * RCR Mini Cart + Product Attribute Validation
 *
 * Normal Zen Cart page reload, no AJAX.
 *
 * Features:
 * - Regular, document and music products
 * - Homepage and category listings
 * - Product image fallback
 * - Clickable product images and names
 * - Selected attributes in UL / LI format
 * - Cart session attribute fallback
 * - Hide duplicate Add to Cart success message
 * - Preserve other warnings and errors
 * - Validate radio and dropdown attributes
 * - Scroll to missing attribute
 * - Support standard Add to Cart form submissions
 * - Close popup, Escape key and mobile swipe
 */

$rcrmcCart = $_SESSION['cart'] ?? null;
$rcrmcCount = $rcrmcCart
    ? (int)$rcrmcCart->count_contents()
    : 0;

$rcrmcPrevious =
    $_SESSION['rcrmc_mini_cart_previous_count'] ?? null;

$rcrmcShow = $rcrmcPrevious !== null
    && $rcrmcCount > (int)$rcrmcPrevious
    && in_array(
        $current_page_base ?? '',
        [
            'product_info',
            'document_product_info',
            'product_music_info',
            'index',
            'products_all',
            'products_new',
            'featured_products',
            'specials'
        ],
        true
    );

$_SESSION['rcrmc_mini_cart_previous_count'] = $rcrmcCount;

$rcrmcItems = [];
$rcrmcDefaultImage = DIR_WS_IMAGES . 'no_picture.gif';

/*
 * ========================================
 * COLLECT CART PRODUCTS
 * ========================================
 */

if ($rcrmcShow && $rcrmcCart) {

    foreach ($rcrmcCart->get_products() as $product) {

        $rcrmcCartId = (string)($product['id'] ?? '');
        $rcrmcProductId = (int)$rcrmcCartId;

        $rcrmcImage = !empty($product['image'])
            ? ltrim((string)$product['image'], '/')
            : '';

        if (
            $rcrmcImage === '' ||
            !is_file(
                DIR_FS_CATALOG .
                DIR_WS_IMAGES .
                $rcrmcImage
            )
        ) {
            $rcrmcImage = 'no_picture.gif';
        }

        /*
         * SELECTED PRODUCT ATTRIBUTES
         */

        $rcrmcAttributes = [];

        $rcrmcSelectedAttributes =
            $product['attributes'] ?? [];

        if (
            empty($rcrmcSelectedAttributes) ||
            !is_array($rcrmcSelectedAttributes)
        ) {
            $rcrmcSelectedAttributes =
                $rcrmcCart->contents[$rcrmcCartId]['attributes']
                ?? [];
        }

        if (
            !empty($rcrmcSelectedAttributes) &&
            is_array($rcrmcSelectedAttributes)
        ) {

            foreach (
                $rcrmcSelectedAttributes as $option => $value
            ) {

                $sql = "SELECT
                            popt.products_options_name,
                            poval.products_options_values_name
                        FROM " . TABLE_PRODUCTS_OPTIONS . " popt,
                             " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval,
                             " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                        WHERE pa.products_id = :productsID
                          AND pa.options_id = :optionsID
                          AND pa.options_id = popt.products_options_id
                          AND pa.options_values_id = :optionsValuesID
                          AND pa.options_values_id = poval.products_options_values_id
                          AND popt.language_id = :languageID
                          AND poval.language_id = :languageID";

                $sql = $db->bindVars(
                    $sql,
                    ':productsID',
                    $rcrmcProductId,
                    'integer'
                );

                $sql = $db->bindVars(
                    $sql,
                    ':optionsID',
                    $option,
                    'integer'
                );

                $sql = $db->bindVars(
                    $sql,
                    ':optionsValuesID',
                    $value,
                    'integer'
                );

                $sql = $db->bindVars(
                    $sql,
                    ':languageID',
                    $_SESSION['languages_id'],
                    'integer'
                );

                $result = $db->Execute($sql);

                if ($result->EOF) {
                    continue;
                }

                if (
                    defined('PRODUCTS_OPTIONS_VALUES_TEXT_ID') &&
                    $value == PRODUCTS_OPTIONS_VALUES_TEXT_ID
                ) {
                    $attributeValue = (string)(
                        $product['attributes_values'][$option] ?? ''
                    );
                } else {
                    $attributeValue = (string)(
                        $result->fields[
                            'products_options_values_name'
                        ] ?? ''
                    );
                }

                $rcrmcAttributes[] = [
                    'name' => (string)(
                        $result->fields[
                            'products_options_name'
                        ] ?? ''
                    ),
                    'value' => $attributeValue
                ];
            }
        }

        /*
         * PRODUCT LINK
         */

        $rcrmcProductPage =
            zen_get_info_page($rcrmcProductId);

        $rcrmcProductUrl = zen_href_link(
            $rcrmcProductPage,
            'products_id=' . $rcrmcProductId
        );

        /*
         * MINI CART PRODUCT DATA
         */

        $rcrmcItems[] = [
            'name' => (string)($product['name'] ?? ''),
            'url' => $rcrmcProductUrl,
            'attributes' => $rcrmcAttributes,
            'quantity' => (int)($product['quantity'] ?? 0),
            'price' => $currencies->format(
                (float)(
                    $product['final_price']
                    ?? $product['price']
                    ?? 0
                ) * (int)($product['quantity'] ?? 0)
            ),
            'image' => DIR_WS_IMAGES . $rcrmcImage
        ];
    }
}

/*
 * ========================================
 * MINI CART PAYLOAD
 * ========================================
 */

$rcrmcPayload = [
    'show' => $rcrmcShow && count($rcrmcItems) > 0,
    'items' => $rcrmcItems,
    'subtotal' => $rcrmcCart
        ? $currencies->format($rcrmcCart->show_total())
        : '',
    'checkout' => zen_href_link(
        FILENAME_CHECKOUT_SHIPPING,
        '',
        'SSL'
    ),
    'defaultImage' => $rcrmcDefaultImage
];
?>

<script>
(function () {
    'use strict';

    const data = <?php
        echo json_encode(
            $rcrmcPayload,
            JSON_HEX_TAG |
            JSON_HEX_AMP |
            JSON_HEX_APOS |
            JSON_HEX_QUOT
        );
    ?>;

    /*
     * ========================================
     * ATTRIBUTE VALIDATION
     * ========================================
     *
     * This version treats enabled radio groups
     * and dropdown choices as required.
     *
     * It does not validate optional attributes
     * separately or replace server validation.
     */

    function clearAttributeErrors() {
        document.querySelectorAll(
            '.rcr-attribute-error'
        ).forEach(function (error) {
            error.remove();
        });
    }

    function showAttributeError(block) {

        clearAttributeErrors();

        const label = block.querySelector('.optionName');

        const name = label
            ? label.textContent.trim().replace(/:$/, '')
            : 'an option';

        const error = document.createElement('div');

        error.className = 'rcr-attribute-error';
        error.setAttribute('role', 'alert');

        error.style.cssText =
            'color:#d93025;' +
            'font-weight:bold;' +
            'margin:10px 0;';

        error.textContent =
            'Please select ' + name +
            ' before adding this product to your cart.';

        block.prepend(error);

        block.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

    }

    function validateCartAttributes() {

        const attributes =
            document.getElementById('productAttributes');

        if (!attributes) return true;

        clearAttributeErrors();

        const blocks = attributes.querySelectorAll(
            '.wrapperAttribsOptions'
        );

        for (const block of blocks) {

            const radios = Array.from(
                block.querySelectorAll(
                    'input[type="radio"]:not(:disabled)'
                )
            );

            if (
                radios.length > 0 &&
                !radios.some(radio => radio.checked)
            ) {
                showAttributeError(block);
                return false;
            }

            const selects = block.querySelectorAll(
                'select:not(:disabled)'
            );

            for (const select of selects) {

                const selected =
                    select.selectedOptions[0];

                if (
                    !select.value ||
                    select.value === '0' ||
                    (selected && selected.disabled)
                ) {
                    showAttributeError(block);
                    return false;
                }
            }
        }

        return true;
    }

    /*
     * VALIDATE NORMAL FORM SUBMISSION
     */

    document.addEventListener(
        'submit',
        function (event) {

            if (
                !event.target.matches('#addToCartForm')
            ) {
                return;
            }

            if (!validateCartAttributes()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        },
        true
    );

    /*
     * VALIDATE ADD TO CART BUTTON CLICKS
     */

    document.addEventListener(
        'click',
        function (event) {

            const button = event.target.closest(
                '#addToCartButton, ' +
                '#cartAdd input[type="image"], ' +
                '#cartAdd button[type="submit"]'
            );

            if (!button) return;

            if (!validateCartAttributes()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        },
        true
    );

    /*
     * REMOVE ERRORS AFTER ATTRIBUTE CHANGE
     */

    document.addEventListener(
        'change',
        function (event) {

            if (
                event.target.closest('#productAttributes')
            ) {
                clearAttributeErrors();
            }
        }
    );

    /*
     * ========================================
     * RCR CART SCROLL RESTORATION
     * ========================================
     * Normal Add to Cart: restore after load (working method).
     * Buy Now links: restore 500 ms after load.
     * No AJAX. One shared storage key.
     */
    const rcrScrollKey = 'rcrmc-add-to-cart-scroll';

    function saveCartPosition(kind) {
        try {
            sessionStorage.setItem(rcrScrollKey, JSON.stringify({
                position: window.scrollY,
                pathname: location.pathname,
                mainPage: new URLSearchParams(location.search).get('main_page') || 'index',
                kind: kind,
                time: Date.now()
            }));
        } catch (error) {
            // Never block cart actions.
        }
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (event.defaultPrevented ||
            !form.matches('form[name="cart_quantity"]') ||
            !form.querySelector('[name="products_id"]')) return;
        saveCartPosition('form');
    }, false);

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 ||
            event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ||
            link.target === '_blank' || link.hasAttribute('download')) return;
        let url;
        try { url = new URL(link.href, location.href); }
        catch (error) { return; }
        if (url.origin !== location.origin ||
            url.searchParams.get('action') !== 'buy_now') return;
        saveCartPosition('buy_now');
    }, true);

    let savedScroll = null;
    try {
        const stored = sessionStorage.getItem(rcrScrollKey);
        if (stored) {
            savedScroll = JSON.parse(stored);
            sessionStorage.removeItem(rcrScrollKey);
        }
    } catch (error) {
        savedScroll = null;
    }

    const currentMainPage = new URLSearchParams(location.search)
        .get('main_page') || 'index';
    const shouldRestore = savedScroll &&
        savedScroll.pathname === location.pathname &&
        savedScroll.mainPage === currentMainPage &&
        Number.isFinite(savedScroll.position) &&
        Number.isFinite(savedScroll.time) &&
        Date.now() - savedScroll.time < 30000;

    if (shouldRestore) {
        // Keep the browser from competing with our restoration.
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const savedY = Math.max(0, savedScroll.position);
        let finished = false;

        function restorePosition() {
            const oldBehavior = document.documentElement.style.scrollBehavior;
            document.documentElement.style.scrollBehavior = 'auto';
            const maxY = Math.max(
                0,
                document.documentElement.scrollHeight - window.innerHeight
            );
            const targetY = Math.min(savedY, maxY);
            if (Math.abs(window.scrollY - targetY) > 2) {
                window.scrollTo(0, targetY);
            }
            document.documentElement.style.scrollBehavior = oldBehavior;
        }

        function finishRestoration() {
            if (finished) return;
            finished = true;
            restorePosition();
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'auto';
            }
        }

        // Restore as soon as the document is ready, rather than showing
        // a long scroll animation or hiding the entire page.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', restorePosition, {
                once: true
            });
        } else {
            restorePosition();
        }

        // After images and the page layout finish, correct only if needed.
        // Keep the proven 500ms Buy Now correction as a fallback.
        function afterLoad() {
            restorePosition();
            if (savedScroll.kind === 'buy_now') {
                window.setTimeout(finishRestoration, 500);
            } else {
                requestAnimationFrame(finishRestoration);
            }
        }

        if (document.readyState === 'complete') {
            afterLoad();
        } else {
            window.addEventListener('load', afterLoad, { once: true });
        }

        // Safety: return control to the browser even if load is delayed.
        window.setTimeout(finishRestoration, 3000);
    }

    /*
     * ========================================
     * MINI CART DISPLAY
     * ========================================
     */
    if (!data.show) return;

    /*
     * HIDE DUPLICATE SUCCESS MESSAGE
     */

    function hideDuplicateCartSuccess() {

        document.querySelectorAll(
            '.messageStackSuccess'
        ).forEach(function (message) {

            const messageText =
                message.textContent || '';

            if (
                /Successfully added\s+Product\s+to\s+the\s+cart/i
                    .test(messageText)
            ) {
                message.style.display = 'none';
            }
        });
    }

    function init() {

        if (
            document.getElementById('rcrmc-mini-cart')
        ) {
            return;
        }

        hideDuplicateCartSuccess();

        /*
         * MINI CART PANEL
         */

        const panel = document.createElement('section');

        panel.id = 'rcrmc-mini-cart';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'false');
        panel.setAttribute(
            'aria-labelledby',
            'rcrmc-mini-cart-title'
        );

        /*
         * HEADER
         */

        const header = document.createElement('div');
        header.className = 'rcrmc-mini-cart-header';

        const title = document.createElement('strong');
        title.id = 'rcrmc-mini-cart-title';
        title.replaceChildren();

const checkmark = document.createElement('span');
checkmark.className = 'cart-checkmark';
checkmark.textContent = '✓';

title.appendChild(checkmark);
title.appendChild(document.createTextNode(' Added to your cart'));

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'rcrmc-mini-cart-close';
        close.setAttribute(
            'aria-label',
            'Close mini cart'
        );
        close.textContent = '×';

        header.append(title, close);

        /*
         * CART CONTENTS
         */

        const contents = document.createElement('div');
        contents.className = 'rcrmc-mini-cart-items';

        data.items.forEach(function (item) {

            const row = document.createElement('div');
            row.className = 'rcrmc-mini-cart-item';

            /*
             * PRODUCT IMAGE
             */

            const img = document.createElement('img');

            img.src = item.image || data.defaultImage;
            img.alt = item.name;
            img.loading = 'lazy';

            img.addEventListener(
                'error',
                function () {

                    if (!img.dataset.rcrmcFallback) {
                        img.dataset.rcrmcFallback = '1';
                        img.src = data.defaultImage;
                    }
                }
            );

            /*
             * CLICKABLE PRODUCT IMAGE
             */

            const imageLink = document.createElement('a');

            imageLink.href = item.url;
            imageLink.setAttribute(
                'aria-label',
                'View product: ' + item.name
            );

            imageLink.appendChild(img);
            row.appendChild(imageLink);

            /*
             * PRODUCT INFORMATION
             */

            const info = document.createElement('div');

            const name = document.createElement('a');
            name.href = item.url;
            name.textContent = item.name;

            info.appendChild(name);

            /*
             * SELECTED PRODUCT ATTRIBUTES
             * UL / LI FORMAT
             */

            if (
                Array.isArray(item.attributes) &&
                item.attributes.length > 0
            ) {

                const attributes =
                    document.createElement('div');

                attributes.className = 'cartAttribsList';

                const list = document.createElement('ul');

                item.attributes.forEach(function (attribute) {

                    const li = document.createElement('li');

                    li.textContent =
                        attribute.name +
                        '\u00a0-\u00a0' +
                        attribute.value;

                    list.appendChild(li);
                });

                attributes.appendChild(list);
                info.appendChild(attributes);
            }

            /*
             * PRODUCT QUANTITY AND PRICE
             */

            const detail = document.createElement('small');

            detail.textContent =
                'Qty: ' +
                item.quantity +
                ' · ' +
                item.price;

            info.appendChild(detail);
            row.appendChild(info);

            contents.appendChild(row);
        });

        /*
         * SUBTOTAL
         */

        const subtotal = document.createElement('div');

        subtotal.className = 'rcrmc-mini-cart-subtotal';

        subtotal.textContent =
            'Cart subtotal: ' + data.subtotal;

        /*
         * ACTION BUTTONS
         */

        const actions = document.createElement('div');
        actions.className = 'rcrmc-mini-cart-actions';

        const continueButton =
            document.createElement('button');

        continueButton.type = 'button';
        continueButton.textContent = 'Continue Shopping';

        const checkout = document.createElement('a');

        checkout.href = data.checkout;
        checkout.textContent = 'Checkout';

        actions.append(continueButton, checkout);

        /*
         * BUILD POPUP
         */

        panel.append(
            header,
            contents,
            subtotal,
            actions
        );

        document.body.appendChild(panel);

        /*
         * CLOSE POPUP
         */

        const dismiss = function () {
            panel.remove();
        };

        close.addEventListener('click', dismiss);

        continueButton.addEventListener(
            'click',
            dismiss
        );

        /*
         * ESCAPE KEY
         */

        document.addEventListener(
            'keydown',
            function onKey(event) {

                if (event.key === 'Escape') {

                    dismiss();

                    document.removeEventListener(
                        'keydown',
                        onKey
                    );
                }
            }
        );

        /*
         * MOBILE SWIPE TO DISMISS
         */

        let startX = null;

        panel.addEventListener(
            'touchstart',
            function (event) {

                startX = event.touches[0].clientX;

            },
            { passive: true }
        );

        panel.addEventListener(
            'touchend',
            function (event) {

                if (
                    startX !== null &&
                    Math.abs(
                        event.changedTouches[0].clientX -
                        startX
                    ) > 75
                ) {
                    dismiss();
                }

                startX = null;

            },
            { passive: true }
        );

        close.focus({
            preventScroll: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            init
        );
    } else {
        init();
    }

})();
</script>
