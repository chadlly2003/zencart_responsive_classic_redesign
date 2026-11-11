
<?php
/**
 * RCR Mini Cart + Product Attribute Validation
 *
 * AJAX updates using Zen Cart's native cart action.
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
 * - Scroll to missing attributes
 * - Support normal Add to Cart submissions
 * - AJAX quantity plus/minus buttons
 * - AJAX trash-can product removal
 * - Updated product prices and cart subtotal
 * - 15-second automatic close
 * - Escape key and mobile swipe to dismiss
 */


/* Product quantity settings, as configured in Zen Cart admin. */
function rcrmcQuantityRules(int $productId): array
{
    global $db;
    $sql = "SELECT products_quantity_order_min, products_quantity_order_max,
                   products_quantity_order_units, products_quantity_mixed
            FROM " . TABLE_PRODUCTS . " WHERE products_id = :pid LIMIT 1";
    $sql = $db->bindVars($sql, ':pid', $productId, 'integer');
    $result = $db->Execute($sql);
    if ($result->EOF) {
        return ['min' => 1, 'max' => 0, 'units' => 1, 'mixed' => false];
    }
    return [
        'min' => max(1, (float)($result->fields['products_quantity_order_min'] ?? 1)),
        'max' => max(0, (float)($result->fields['products_quantity_order_max'] ?? 0)),
        'units' => max(1, (float)($result->fields['products_quantity_order_units'] ?? 1)),
        'mixed' => (int)($result->fields['products_quantity_mixed'] ?? 0) === 1
    ];
}

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
         * Zen Cart catalog pricing markup (specials/sales where available).
         * The cart line total below remains authoritative for attributes/qty.
         */
        $rcrmcCatalogPriceHtml = function_exists('zen_get_products_display_price')
            ? (string)zen_get_products_display_price($rcrmcProductId)
            : '';

        $rcrmcItems[] = [
            'name' => (string)($product['name'] ?? ''),
            'url' => $rcrmcProductUrl,
            'attributes' => $rcrmcAttributes,
            'cartId' => $rcrmcCartId,
            'quantity' => (int)($product['quantity'] ?? 0),
            'quantityRules' => rcrmcQuantityRules($rcrmcProductId),
            'catalogPriceHtml' => $rcrmcCatalogPriceHtml,
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
    'cartUpdate' => zen_href_link(
        FILENAME_SHOPPING_CART,
        'action=update_product',
        'SSL'
    ),
    'addUrl' => rtrim(DIR_WS_CATALOG, '/') . '/rcrmc_cart_add.php',
    'quantityUrl' =>
        rtrim(DIR_WS_CATALOG, '/') .
        '/rcrmc_cart_quantity.php',
    'stateUrl' =>
        rtrim(DIR_WS_CATALOG, '/') .
        '/rcrmc_cart_state.php',
    'securityToken' =>
        (string)($_SESSION['securityToken'] ?? ''),
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

        const container = document.getElementById('productAttributes');
        const form = document.getElementById('addToCartForm');

        if (!container || !form) return true;

        clearAttributeErrors();

        /*
         * Group radio buttons by their actual Zen Cart field name.
         * This also handles radio inputs outside wrapperAttribsOptions.
         */
        const radioGroups = new Map();

        container.querySelectorAll(
            'input[type="radio"][name^="id["]:not(:disabled)'
        ).forEach(function (radio) {
            if (!form.contains(radio)) return;
            if (!radioGroups.has(radio.name)) {
                radioGroups.set(radio.name, []);
            }
            radioGroups.get(radio.name).push(radio);
        });

        for (const radios of radioGroups.values()) {
            if (!radios.some(function (radio) { return radio.checked; })) {
                showAttributeError(
                    radios[0].closest('.wrapperAttribsOptions') || container
                );
                return false;
            }
        }

        /*
         * A dropdown with no value, value 0, or a disabled
         * placeholder is not a valid attribute selection.
         */
        const selects = container.querySelectorAll(
            'select[name^="id["]:not(:disabled)'
        );

        for (const select of selects) {
            if (!form.contains(select)) continue;

            const option = select.selectedOptions[0];
            if (
                !select.value ||
                select.value === '0' ||
                !option ||
                option.disabled
            ) {
                showAttributeError(
                    select.closest('.wrapperAttribsOptions') || container
                );
                return false;
            }
        }

        /*
         * Do not require checkboxes by default: Zen Cart checkbox
         * attributes can be optional. Native server-side required
         * attribute rules remain authoritative.
         */
        return true;
    }

    /*
     * VALIDATE NORMAL ADD TO CART FORM
     */

    document.addEventListener(
        'submit',
        function (event) {

            if (!event.target.matches('#addToCartForm')) {
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
     * VALIDATE ADD TO CART BUTTONS
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
     * REMOVE ATTRIBUTE ERRORS AFTER CHANGE
     */

    document.addEventListener(
        'change',
        function (event) {

            if (event.target.closest('#productAttributes')) {
                clearAttributeErrors();
            }
        }
    );

    /*
     * ========================================
     * RCR CART SCROLL RESTORATION
     * ========================================
     */

    const rcrScrollKey = 'rcrmc-add-to-cart-scroll';

    function saveCartPosition(kind) {

        try {
            sessionStorage.setItem(
                rcrScrollKey,
                JSON.stringify({
                    position: window.scrollY,
                    pathname: location.pathname,
                    originalUrl: location.href,
                    mainPage:
                        new URLSearchParams(location.search)
                            .get('main_page') || 'index',
                    kind: kind,
                    time: Date.now()
                })
            );
        } catch (error) {
            // Do not block cart actions.
        }
    }

    document.addEventListener(
        'submit',
        function (event) {

            const form = event.target;

            if (
                event.defaultPrevented ||
                !form.matches('form[name="cart_quantity"]') ||
                !form.querySelector('[name="products_id"]')
            ) {
                return;
            }

            saveCartPosition('form');
        },
        false
    );

    document.addEventListener(
        'click',
        function (event) {

            const link = event.target.closest('a[href]');

            if (
                !link ||
                event.defaultPrevented ||
                event.button !== 0 ||
                event.ctrlKey ||
                event.metaKey ||
                event.shiftKey ||
                event.altKey ||
                link.target === '_blank' ||
                link.hasAttribute('download')
            ) {
                return;
            }

            let url;

            try {
                url = new URL(link.href, location.href);
            } catch (error) {
                return;
            }

            if (
                url.origin !== location.origin ||
                url.searchParams.get('action') !== 'buy_now'
            ) {
                return;
            }

            saveCartPosition('buy_now');
        },
        true
    );

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

    /*
     * RCR MINI CART - REAL PAGE RETURN
     *
     * Zen Cart's buy_now 302 can render different homepage content
     * at index.php?main_page=index&products_id=N. Changing the
     * address bar alone does not correct that rendered HTML.
     *
     * Save the popup data, make one REAL navigation to the exact
     * originating URL, then restore the popup on that page.
     */
    const rcrReturnKey = 'rcrmc-real-page-return-v1';

    function normalizedPagePath(path) {
        return path.replace(/\/index\.php$/i, '/');
    }

    function safeReadReturn() {
        try {
            const raw = sessionStorage.getItem(rcrReturnKey);
            if (!raw) return null;
            sessionStorage.removeItem(rcrReturnKey);
            const stored = JSON.parse(raw);
            if (!stored || !stored.url || !stored.payload ||
                !Number.isFinite(stored.time) ||
                Date.now() - stored.time > 30000) return null;
            const target = new URL(stored.url);
            if (target.origin !== location.origin ||
                target.href !== location.href) return null;
            return stored;
        } catch (error) {
            return null;
        }
    }

    // The real homepage request won't show a new cart-count increase,
    // so carry the already-confirmed Mini Cart payload across once.
    const completedReturn = safeReadReturn();
    if (completedReturn && !data.show) {
        data.show = true;
        data.items = completedReturn.payload.items;
        data.subtotal = completedReturn.payload.subtotal;
    }

    if (
        data.show &&
        savedScroll &&
        savedScroll.kind === 'buy_now' &&
        typeof savedScroll.originalUrl === 'string' &&
        Number.isFinite(savedScroll.time) &&
        Date.now() - savedScroll.time < 30000
    ) {
        try {
            const original = new URL(savedScroll.originalUrl);
            const current = new URL(location.href);
            const originalPage = original.searchParams.get('main_page') || 'index';
            const currentPage = current.searchParams.get('main_page') || 'index';
            const validReturn =
                original.origin === current.origin &&
                normalizedPagePath(original.pathname) === normalizedPagePath(current.pathname) &&
                originalPage === currentPage &&
                !current.searchParams.has('action') &&
                original.href !== current.href;

            if (validReturn && !completedReturn) {
                sessionStorage.setItem(rcrReturnKey, JSON.stringify({
                    url: original.href,
                    time: Date.now(),
                    payload: {
                        items: data.items,
                        subtotal: data.subtotal
                    }
                }));
                // Preserve scroll data for the final, correctly rendered page.
                sessionStorage.setItem(rcrScrollKey, JSON.stringify(savedScroll));
                window.location.replace(original.href);
                return;
            }
        } catch (error) {
            // Never interfere with normal cart actions.
        }
    }

    const currentMainPage =
        new URLSearchParams(location.search)
            .get('main_page') || 'index';

    const shouldRestore =
        savedScroll &&
        normalizedPagePath(savedScroll.pathname) === normalizedPagePath(location.pathname) &&
        savedScroll.mainPage === currentMainPage &&
        Number.isFinite(savedScroll.position) &&
        Number.isFinite(savedScroll.time) &&
        Date.now() - savedScroll.time < 30000;

    if (shouldRestore) {

        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const savedY = Math.max(0, savedScroll.position);
        let finished = false;

        function restorePosition() {

            const oldBehavior =
                document.documentElement.style.scrollBehavior;

            document.documentElement.style.scrollBehavior = 'auto';

            const maxY = Math.max(
                0,
                document.documentElement.scrollHeight -
                    window.innerHeight
            );

            const targetY = Math.min(savedY, maxY);

            if (Math.abs(window.scrollY - targetY) > 2) {
                window.scrollTo(0, targetY);
            }

            document.documentElement.style.scrollBehavior =
                oldBehavior;
        }

        function finishRestoration() {

            if (finished) return;

            finished = true;
            restorePosition();

            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'auto';
            }
        }

        if (document.readyState === 'loading') {

            document.addEventListener(
                'DOMContentLoaded',
                restorePosition,
                { once: true }
            );

        } else {
            restorePosition();
        }

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
            window.addEventListener(
                'load',
                afterLoad,
                { once: true }
            );
        }

        window.setTimeout(finishRestoration, 3000);
    }

    /*
     * ========================================
     * MINI CART DISPLAY
     * ========================================
     */

    // AJAX add can open the Mini Cart even when this page initially has no popup.


    function hideDuplicateCartSuccess() {

        document.querySelectorAll(
            '.messageStackSuccess'
        ).forEach(function (message) {

            const messageText = message.textContent || '';

            if (
                /Successfully added\s+Product\s+to\s+the\s+cart/i
                    .test(messageText)
            ) {
                message.style.display = 'none';
            }
        });
    }

    /*
     * ========================================
     * INITIALIZE MINI CART
     * ========================================
     */

    let activeMiniCartCleanup = null;

    function init() {

        if (activeMiniCartCleanup) {
            activeMiniCartCleanup();
            activeMiniCartCleanup = null;
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

        const checkmark = document.createElement('span');
        checkmark.className = 'cart-checkmark';
        checkmark.textContent = '✓';

        title.appendChild(checkmark);
        title.appendChild(
            document.createTextNode(' Added to your cart')
        );

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

        /*
         * SUBTOTAL
         */

        const subtotal = document.createElement('div');
        subtotal.className = 'rcrmc-mini-cart-subtotal';

        subtotal.textContent =
            'Cart subtotal: ' + data.subtotal;

        /*
         * STATUS MESSAGES
         */

        const status = document.createElement('div');

        status.className = 'rcrmc-mini-cart-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');

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
            status,
            actions
        );

        document.body.appendChild(panel);

        /*
         * ========================================
         * AUTO CLOSE AFTER 15 SECONDS OF INACTIVITY
         * ========================================
         * Pauses while hovered or touched.
         * Resumes after the user stops interacting.
         */

        const autoCloseDelay = 15000;
        let autoCloseTimer = null;
        let dismissed = false;
        let mouseInside = false;
        let touching = false;

        function dismiss() {

            if (panel.dataset.updating === '1' || mouseInside || touching) {
                return;
            }

            dismissed = true;
            window.clearTimeout(autoCloseTimer);
            panel.remove();
            document.removeEventListener('keydown', onKey);
        }

        function pauseAutoClose() {
            window.clearTimeout(autoCloseTimer);
            autoCloseTimer = null;
        }

        function restartAutoClose() {
            pauseAutoClose();

            if (dismissed || mouseInside || touching ||
                panel.dataset.updating === '1') {
                return;
            }

            autoCloseTimer = window.setTimeout(dismiss, autoCloseDelay);
        }

        // Desktop: don't close while the mouse is over the cart.
        panel.addEventListener('mouseenter', function () {
            mouseInside = true;
            pauseAutoClose();
        });

        panel.addEventListener('mouseleave', function () {
            mouseInside = false;
            restartAutoClose();
        });

        // Mobile: don't close during a touch/scroll gesture.
        panel.addEventListener('touchstart', function () {
            touching = true;
            pauseAutoClose();
        }, { passive: true });

        function endTouchInteraction() {
            touching = false;
            restartAutoClose();
        }

        panel.addEventListener('touchend', endTouchInteraction, { passive: true });
        panel.addEventListener('touchcancel', endTouchInteraction, { passive: true });

        // Reset the idle countdown for interactions inside the cart.
        panel.addEventListener('scroll', restartAutoClose, { passive: true, capture: true });
        panel.addEventListener('click', restartAutoClose);
        panel.addEventListener('keydown', restartAutoClose);
        panel.addEventListener('wheel', restartAutoClose, { passive: true });

        /*
         * ========================================
         * AJAX CART QUANTITY UPDATE
         * ========================================
         *
         * Uses Zen Cart's normal cart update action.
         * Gets confirmed cart data from stateUrl.
         */

        async function updateCartQuantity(
            cartId,
            delta,
            removeItem = false
        ) {

            if (panel.dataset.updating === '1') {
                return;
            }

            const item = data.items.find(function (entry) {
                return entry.cartId === cartId;
            });

            if (!item) return;

            const current = Number(item.quantity);

            const rules = item.quantityRules || {};
            const step = Math.max(1, Number(rules.units) || 1);
            const minimum = Math.max(1, Number(rules.min) || 1);
            const maximum = Math.max(0, Number(rules.max) || 0);
            const mixed = Boolean(rules.mixed);
            const sameProduct = data.items.filter(function (entry) {
                return String(entry.cartId).split('{')[0] === String(cartId).split('{')[0];
            });
            const otherQuantity = mixed
                ? sameProduct.reduce(function (total, entry) {
                    return total + (entry.cartId === cartId ? 0 : Number(entry.quantity));
                }, 0)
                : 0;
            const next = removeItem ? 0 : Math.max(0, current + delta * step);
            const checkedQuantity = next + otherQuantity;

            // Never silently force a requested quantity into a different value.
            // Server validates again; this is just immediate feedback.
            if (!removeItem && next > 0 &&
                (checkedQuantity < minimum ||
                 (maximum > 0 && checkedQuantity > maximum) ||
                 Math.abs(checkedQuantity / step - Math.round(checkedQuantity / step)) > 0.000001)) {
                status.textContent = 'Quantity must be at least ' + minimum +
                    (maximum ? ', at most ' + maximum : '') +
                    ', in increments of ' + step + '.';
                restartAutoClose();
                return;
            }

            if (next === current && !removeItem) {
                return;
            }

            panel.dataset.updating = '1';

            const buttons = panel.querySelectorAll(
                '.rcrmc-mini-cart-quantity button, ' +
                '.rcrmc-mini-cart-remove'
            );

            buttons.forEach(function (button) {
                button.disabled = true;
            });

            status.textContent = removeItem
                ? 'Removing product…'
                : 'Updating cart…';

            window.clearTimeout(autoCloseTimer);

            /*
             * Update ONLY this existing cart line.
             * The server retrieves its original selected attributes.
             */
            const fields = new URLSearchParams();
            fields.append('cartId', cartId);
            fields.append('quantity', String(next));
            fields.append('securityToken', data.securityToken);

            try {

                /*
                 * UPDATE CART THROUGH ZEN CART
                 */

                const response = await fetch(
                    data.quantityUrl,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        redirect: 'follow',
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded;charset=UTF-8'
                        },
                        body: fields.toString()
                    }
                );

                if (!response.ok) {

                    throw new Error(
                        'Cart update failed (' +
                        response.status + ')'
                    );
                }

                const updateResult = await response.json();
                if (!updateResult.ok) {
                    throw new Error(updateResult.error || 'Cart update rejected.');
                }

                /*
                 * RETRIEVE UPDATED CART
                 */

                const stateResponse = await fetch(
                    data.stateUrl,
                    {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (!stateResponse.ok) {

                    throw new Error(
                        'Unable to retrieve cart state.'
                    );
                }

                const state = await stateResponse.json();

                if (
                    !state ||
                    !state.ok ||
                    !Array.isArray(state.items)
                ) {

                    throw new Error(
                        'Invalid cart response.'
                    );
                }

                /*
                 * GET CONFIRMED QUANTITY
                 */

                const updated = state.items.find(
                    function (entry) {
                        return entry.cartId === cartId;
                    }
                );

                const actual = updated
                    ? Number(updated.quantity)
                    : 0;

                /*
                 * UPDATE MINI CART DATA
                 */

                data.items = state.items;
                data.subtotal = state.subtotal;

                /*
                 * REBUILD PRODUCT ROWS
                 */

                contents.replaceChildren();

                state.items.forEach(renderItem);

                /*
                 * UPDATE SUBTOTAL
                 */

                subtotal.textContent =
                    'Cart subtotal: ' + state.subtotal;

                /*
                 * NOTIFY OTHER CART COMPONENTS
                 */

                document.dispatchEvent(
                    new CustomEvent(
                        'rcrmc:cart-updated',
                        {
                            detail: {
                                count: state.count,
                                subtotal: state.subtotal
                            }
                        }
                    )
                );

                /*
                 * STATUS MESSAGE
                 */

                if (!state.items.length) {

                    status.textContent =
                        'Your cart is empty.';

                } else if (actual !== next) {

                    status.textContent =
                        'Zen Cart adjusted the quantity. ' +
                        'Please check stock and quantity limits.';

                } else {

                    status.textContent = '';
                }

            } catch (error) {

                console.error(
                    'RCR Mini Cart AJAX error:',
                    error
                );

                status.textContent = (error && error.message)
                    ? error.message
                    : 'Unable to update cart. Please use the shopping cart page.';

            } finally {

                panel.dataset.updating = '0';

                panel.querySelectorAll(
                    '.rcrmc-mini-cart-quantity button, ' +
                    '.rcrmc-mini-cart-remove'
                ).forEach(function (button) {
                    button.disabled = false;
                });

                restartAutoClose();
            }
        }

        /*
         * ========================================
         * RENDER CART PRODUCT
         * ========================================
         */

        function renderItem(item) {

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
            info.className = 'rcrmc-mini-cart-info';

            const name = document.createElement('a');

            name.href = item.url;
            name.textContent = item.name;

            info.appendChild(name);

            /*
             * SELECTED ATTRIBUTES
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
             * PRODUCT PRICE
             */

            if (item.catalogPriceHtml) {
                const catalogPrice = document.createElement('div');
                catalogPrice.className = 'rcrmc-mini-cart-catalog-price';
                catalogPrice.innerHTML = item.catalogPriceHtml;
                info.appendChild(catalogPrice);
            }

            const detail = document.createElement('small');
            detail.className = 'rcrmc-mini-cart-line-total';
            detail.textContent = 'Total: ' + item.price;
            info.appendChild(detail);

            /*
             * ========================================
             * QUANTITY CONTROLS
             * ========================================
             */

            const quantity = document.createElement('div');

            quantity.className =
                'rcrmc-mini-cart-quantity';

            /*
             * MINUS BUTTON
             */

            const minus = document.createElement('button');

            minus.type = 'button';
            minus.textContent = '−';

            minus.setAttribute(
                'aria-label',
                'Decrease quantity of ' + item.name
            );

            /*
             * QUANTITY DISPLAY
             */

            const count = document.createElement('span');

            count.textContent = String(item.quantity);

            count.setAttribute(
                'aria-label',
                'Quantity: ' + item.quantity
            );

            /*
             * PLUS BUTTON
             */

            const plus = document.createElement('button');

            plus.type = 'button';
            plus.textContent = '+';

            plus.setAttribute(
                'aria-label',
                'Increase quantity of ' + item.name
            );

            /*
             * QUANTITY BUTTON EVENTS
             */

            minus.addEventListener(
                'click',
                function () {

                    updateCartQuantity(
                        item.cartId,
                        -1
                    );
                }
            );

            plus.addEventListener(
                'click',
                function () {

                    updateCartQuantity(
                        item.cartId,
                        1
                    );
                }
            );

            quantity.append(
                minus,
                count,
                plus
            );

            info.appendChild(quantity);

            /*
             * ========================================
             * TRASH CAN BUTTON
             * ========================================
             */

            const remove = document.createElement('button');

            remove.type = 'button';

            remove.className =
                'rcrmc-mini-cart-remove';

            remove.setAttribute(
                'aria-label',
                'Remove ' + item.name + ' from cart'
            );

            remove.setAttribute(
                'title',
                'Remove from cart'
            );

            /*
             * TRASH CAN SVG
             */

            remove.innerHTML = `
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M3 6h18"/>
                    <path d="M8 6V4h8v2"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 10v7"/>
                    <path d="M14 10v7"/>
                </svg>
            `;

            /*
             * REMOVE SELECTED PRODUCT
             */

            remove.addEventListener(
                'click',
                function () {

                    updateCartQuantity(
                        item.cartId,
                        0,
                        true
                    );
                }
            );

            /*
             * BUILD PRODUCT ROW
             */

            row.appendChild(info);
            row.appendChild(remove);

            contents.appendChild(row);
        }

        /*
         * INITIAL PRODUCT DISPLAY
         */

        data.items.forEach(renderItem);

        /*
         * CLOSE BUTTON
         */

     
/*
 * ========================================
 * CLOSE BUTTON - FORCE MANUAL CLOSE
 * ========================================
 */

close.addEventListener('click', function (event) {

    event.preventDefault();
    event.stopPropagation();

    // Stop the inactivity timer.
    window.clearTimeout(autoCloseTimer);

    // Manually close the Mini Cart.
    // Do not depend on the inactivity timer.
    dismissed = true;

    panel.remove();

    // Remove the Escape key listener.
    document.removeEventListener('keydown', onKey);

});


        /*
 * ========================================
 * CONTINUE SHOPPING - FORCE MANUAL CLOSE
 * ========================================
 */

continueButton.addEventListener('click', function (event) {

    event.preventDefault();
    event.stopPropagation();

    // Stop the automatic close timer.
    window.clearTimeout(autoCloseTimer);

    // Mark the mini cart as dismissed.
    dismissed = true;

    // Close the mini cart immediately.
    panel.remove();

    // Remove the Escape key listener.
    document.removeEventListener('keydown', onKey);

});

        /*
         * ESCAPE KEY
         */

        function onKey(event) {

            if (event.key === 'Escape') {
                dismiss();
            }
        }

        document.addEventListener(
            'keydown',
            onKey
        );

        activeMiniCartCleanup = function () {
            window.clearTimeout(autoCloseTimer);
            document.removeEventListener('keydown', onKey);
            panel.remove();
        };

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

        /*
         * START INACTIVITY TIMER
         */

        restartAutoClose();

        /*
         * FOCUS CLOSE BUTTON
         */

        close.focus({
            preventScroll: true
        });
    }

    /*
     * INITIALIZE
     */

    /* ========================================
       FULL AJAX ADD TO CART
       Intercept ONLY native Add to Cart links and product forms.
       Attribute-choice links, checkout, and other links remain native.
       ======================================== */
    let addInProgress = false;

    function ajaxErrorNear(element, message) {
        document.querySelectorAll('.rcrmc-ajax-add-error').forEach(node => node.remove());
        const warning = document.createElement('div');
        warning.className = 'rcrmc-ajax-add-error';
        warning.setAttribute('role', 'alert');
        warning.style.cssText = 'color:#d93025;font-weight:600;margin:8px 0;';
        warning.textContent = message;
        const target = element.closest('.cart-add, #cartAdd, .cart-add-units') || element;
        target.insertAdjacentElement('afterend', warning);
    }

    async function ajaxAdd(fields, trigger) {
        if (addInProgress) return;
        addInProgress = true;
        try { sessionStorage.removeItem(rcrScrollKey); } catch (error) {}
        trigger.setAttribute('aria-busy', 'true');
        try {
            fields.set('securityToken', data.securityToken);
            const response = await fetch(data.addUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: fields.toString(), cache: 'no-store'
            });
            const result = await response.json().catch(() => null);
            if (!response.ok || !result || !result.ok) {
                throw new Error(result && result.error ? result.error : 'Unable to add to cart (' + response.status + ').');
            }
            const stateResponse = await fetch(data.stateUrl, {
                credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' }
            });
            const state = await stateResponse.json().catch(() => null);
            if (!stateResponse.ok || !state || !state.ok || !Array.isArray(state.items)) {
                throw new Error('Product added, but the Mini Cart could not refresh. Please check your shopping cart.');
            }
            data.items = state.items;
            data.subtotal = state.subtotal;
            data.show = true;
            document.querySelectorAll('.rcrmc-ajax-add-error').forEach(node => node.remove());
            document.querySelectorAll('.cart-count').forEach(node => {
                node.textContent = String(state.count);
            });
            document.dispatchEvent(new CustomEvent('rcrmc:cart-updated', {
                detail: { count: state.count, subtotal: state.subtotal }
            }));
            init();
        } catch (error) {
            ajaxErrorNear(trigger, error.message || 'Unable to add to cart. Please try again.');
            console.error('RCR Mini Cart AJAX add:', error);
        } finally {
            addInProgress = false;
            trigger.removeAttribute('aria-busy');
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || !link.closest('.cart-add') || event.defaultPrevented ||
            event.button !== 0 || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey ||
            link.target === '_blank' || link.hasAttribute('download')) return;
        let url;
        try { url = new URL(link.href, location.href); } catch (error) { return; }
        if (url.origin !== location.origin || url.searchParams.get('action') !== 'buy_now') return;
        const productId = url.searchParams.get('products_id');
        if (!productId || !/^\d+$/.test(productId)) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        const fields = new URLSearchParams();
        fields.set('products_id', productId);
        fields.set('mode', 'buy_now');
        ajaxAdd(fields, link);
    }, true);

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.matches('#addToCartForm, form[name="cart_quantity"]') ||
            !form.querySelector('[name="products_id"]')) return;
        if (!validateCartAttributes()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        const fields = new URLSearchParams();
        new FormData(form).forEach((value, key) => {
            if (typeof value === 'string') fields.append(key, value);
        });
        fields.set('mode', 'product');
        ajaxAdd(fields, form.querySelector('#addToCartButton, [type="submit"]') || form);
    }, true);

    // Some templates submit the product form programmatically from a click.
    // The normal submit listener above handles the resulting submit event.

    if (data.show) {
    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            init
        );

    } else {

        init();
    }
    }

})();
</script>
