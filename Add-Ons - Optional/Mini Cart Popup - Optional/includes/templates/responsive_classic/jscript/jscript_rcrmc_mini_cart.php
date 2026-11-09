
<?php
/**
 * RCR Mini Cart
 * Normal Zen Cart page reload, no AJAX.
 * Includes default image fallback.
 *
 * Supports:
 * - Regular products
 * - Document products
 * - Music products
 * - Homepage and category listings
 *
 * Features:
 * - Selected product attributes
 * - Fallback to cart session attributes
 * - Zen Cart cartAttribsList UL / LI structure
 * - Hide duplicate Add to Cart success message
 * - Preserve other warnings and errors
 */

$rcrmcCart = $_SESSION['cart'] ?? null;
$rcrmcCount = $rcrmcCart ? (int)$rcrmcCart->count_contents() : 0;

$rcrmcPrevious = $_SESSION['rcrmc_mini_cart_previous_count'] ?? null;

// Show mini cart when a product is added on supported pages
$rcrmcShow = $rcrmcPrevious !== null
    && $rcrmcCount > (int)$rcrmcPrevious
    && in_array(
        $current_page_base ?? '',
        [
            'product_info',
            'document_product_info',
            'product_music_info',
            'index'
        ],
        true
    );

$_SESSION['rcrmc_mini_cart_previous_count'] = $rcrmcCount;

$rcrmcItems = [];

// Default Zen Cart image
$rcrmcDefaultImage = DIR_WS_IMAGES . 'no_picture.gif';

if ($rcrmcShow && $rcrmcCart) {

    foreach ($rcrmcCart->get_products() as $product) {

        // Full cart product ID (may contain attribute suffix)
        $rcrmcCartId = (string)($product['id'] ?? '');

        // Base product ID
        $rcrmcProductId = (int)$rcrmcCartId;

        // Product image
        $rcrmcImage = !empty($product['image'])
            ? ltrim((string)$product['image'], '/')
            : '';

        // Use default image if missing or file does not exist
        if (
            $rcrmcImage === '' ||
            !is_file(DIR_FS_CATALOG . DIR_WS_IMAGES . $rcrmcImage)
        ) {
            $rcrmcImage = 'no_picture.gif';
        }

        /*
         * ========================================
         * SELECTED PRODUCT ATTRIBUTES
         * ========================================
         */

        $rcrmcAttributes = [];

        // First use attributes returned by get_products()
        $rcrmcSelectedAttributes = $product['attributes'] ?? [];

        // Fallback to attributes stored in cart session
        if (
            empty($rcrmcSelectedAttributes) ||
            !is_array($rcrmcSelectedAttributes)
        ) {
            $rcrmcSelectedAttributes =
                $rcrmcCart->contents[$rcrmcCartId]['attributes'] ?? [];
        }

        if (
            !empty($rcrmcSelectedAttributes) &&
            is_array($rcrmcSelectedAttributes)
        ) {

            foreach (
                $rcrmcSelectedAttributes
                as $option => $value
            ) {

                // Standard Zen Cart attribute lookup
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

                // Handle text attributes
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
         * ========================================
         * PRODUCT LINK
         * ========================================
         */

        // Get correct product information page
        $rcrmcProductPage = zen_get_info_page($rcrmcProductId);

        $rcrmcProductUrl = zen_href_link(
            $rcrmcProductPage,
            'products_id=' . $rcrmcProductId
        );

        /*
         * ========================================
         * MINI CART PRODUCT DATA
         * ========================================
         */

        $rcrmcItems[] = [
            'name' => (string)($product['name'] ?? ''),
            'url' => $rcrmcProductUrl,
            'attributes' => $rcrmcAttributes,
            'quantity' => (int)($product['quantity'] ?? 0),
            'price' => $currencies->format(
                (float)($product['final_price'] ?? $product['price'] ?? 0)
                * (int)($product['quantity'] ?? 0)
            ),
            'image' => DIR_WS_IMAGES . $rcrmcImage,
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
    'checkout' => zen_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'),
    'defaultImage' => $rcrmcDefaultImage,
];
?>

<script>
(function () {
  'use strict';

  const data = <?php
    echo json_encode(
        $rcrmcPayload,
        JSON_HEX_TAG | JSON_HEX_AMP |
        JSON_HEX_APOS | JSON_HEX_QUOT
    );
  ?>;

  if (!data.show) return;

  /*
   * ========================================
   * HIDE DUPLICATE ADD TO CART MESSAGE
   * ========================================
   */

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

  function init() {

    if (document.getElementById('rcrmc-mini-cart')) return;

    // Hide duplicate success message
    hideDuplicateCartSuccess();

    /*
     * ========================================
     * MINI CART PANEL
     * ========================================
     */

    const panel = document.createElement('section');

    panel.id = 'rcrmc-mini-cart';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-labelledby', 'rcrmc-mini-cart-title');

    /*
     * ========================================
     * HEADER
     * ========================================
     */

    const header = document.createElement('div');
    header.className = 'rcrmc-mini-cart-header';

    const title = document.createElement('strong');
    title.id = 'rcrmc-mini-cart-title';
    title.textContent = '✓ Added to your cart';

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'rcrmc-mini-cart-close';
    close.setAttribute('aria-label', 'Close mini cart');
    close.textContent = '×';

    header.append(title, close);

    /*
     * ========================================
     * CART CONTENTS
     * ========================================
     */

    const contents = document.createElement('div');
    contents.className = 'rcrmc-mini-cart-items';

    data.items.forEach(item => {

      const row = document.createElement('div');
      row.className = 'rcrmc-mini-cart-item';

      /*
       * PRODUCT IMAGE
       */

      const img = document.createElement('img');

      img.src = item.image || data.defaultImage;
      img.alt = item.name;
      img.loading = 'lazy';

      // Image fallback
      img.addEventListener('error', function () {

        if (!img.dataset.rcrmcFallback) {
          img.dataset.rcrmcFallback = '1';
          img.src = data.defaultImage;
        }

      });

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

      // Clickable product name
      const name = document.createElement('a');
      name.href = item.url;
      name.textContent = item.name;

      info.appendChild(name);

      /*
       * ========================================
       * SELECTED PRODUCT ATTRIBUTES
       * UL / LI FORMAT
       * ========================================
       */

      if (
        Array.isArray(item.attributes) &&
        item.attributes.length > 0
      ) {

        const attributes = document.createElement('div');
        attributes.className = 'cartAttribsList';

        const list = document.createElement('ul');

        item.attributes.forEach(attribute => {

          const li = document.createElement('li');

          li.textContent =
            attribute.name + '\u00a0-\u00a0' + attribute.value;

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
        'Qty: ' + item.quantity + ' · ' + item.price;

      info.appendChild(detail);
      row.appendChild(info);

      contents.appendChild(row);

    });

    /*
     * ========================================
     * SUBTOTAL
     * ========================================
     */

    const subtotal = document.createElement('div');
    subtotal.className = 'rcrmc-mini-cart-subtotal';
    subtotal.textContent = 'Cart subtotal: ' + data.subtotal;

    /*
     * ========================================
     * ACTION BUTTONS
     * ========================================
     */

    const actions = document.createElement('div');
    actions.className = 'rcrmc-mini-cart-actions';

    const continueButton = document.createElement('button');
    continueButton.type = 'button';
    continueButton.textContent = 'Continue Shopping';

    const checkout = document.createElement('a');
    checkout.href = data.checkout;
    checkout.textContent = 'Checkout';

    actions.append(continueButton, checkout);

    /*
     * ========================================
     * BUILD POPUP
     * ========================================
     */

    panel.append(header, contents, subtotal, actions);

    document.body.appendChild(panel);

    /*
     * ========================================
     * CLOSE POPUP
     * ========================================
     */

    const dismiss = () => panel.remove();

    close.addEventListener('click', dismiss);
    continueButton.addEventListener('click', dismiss);

    /*
     * ========================================
     * ESCAPE KEY
     * ========================================
     */

    document.addEventListener('keydown', function onKey(e) {

      if (e.key === 'Escape') {
        dismiss();
        document.removeEventListener('keydown', onKey);
      }

    });

    /*
     * ========================================
     * MOBILE SWIPE TO DISMISS
     * ========================================
     */

    let startX = null;

    panel.addEventListener('touchstart', e => {
      startX = e.touches[0].clientX;
    }, { passive: true });

    panel.addEventListener('touchend', e => {

      if (
        startX !== null &&
        Math.abs(e.changedTouches[0].clientX - startX) > 75
      ) {
        dismiss();
      }

      startX = null;

    }, { passive: true });

    close.focus({ preventScroll: true });

  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
</script>
