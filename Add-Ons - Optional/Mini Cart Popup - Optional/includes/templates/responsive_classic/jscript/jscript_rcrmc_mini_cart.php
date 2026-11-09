
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

        // Product ID (remove attribute suffix if present)
        $rcrmcProductId = (int)($product['id'] ?? 0);

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

        // Get the correct Zen Cart product information page
        $rcrmcProductPage = zen_get_info_page($rcrmcProductId);

        // Product link
        $rcrmcProductUrl = zen_href_link(
            $rcrmcProductPage,
            'products_id=' . $rcrmcProductId
        );

        $rcrmcItems[] = [
            'name' => (string)($product['name'] ?? ''),
            'url' => $rcrmcProductUrl,
            'quantity' => (int)($product['quantity'] ?? 0),
            'price' => $currencies->format(
                (float)($product['final_price'] ?? $product['price'] ?? 0)
                * (int)($product['quantity'] ?? 0)
            ),
            'image' => DIR_WS_IMAGES . $rcrmcImage,
        ];
    }
}

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

  function init() {

    if (document.getElementById('rcrmc-mini-cart')) return;

    const panel = document.createElement('section');

    panel.id = 'rcrmc-mini-cart';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'false');
    panel.setAttribute('aria-labelledby', 'rcrmc-mini-cart-title');

    // Header
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

    // Cart contents
    const contents = document.createElement('div');
    contents.className = 'rcrmc-mini-cart-items';

    data.items.forEach(item => {

      const row = document.createElement('div');
      row.className = 'rcrmc-mini-cart-item';

      // Product image with fallback
      const img = document.createElement('img');

      img.src = item.image || data.defaultImage;
      img.alt = item.name;
      img.loading = 'lazy';

      // Fallback if image fails to load
      img.addEventListener('error', function () {

        if (!img.dataset.rcrmcFallback) {
          img.dataset.rcrmcFallback = '1';
          img.src = data.defaultImage;
        }

      });

      // Clickable product image
      const imageLink = document.createElement('a');
      imageLink.href = item.url;
      imageLink.setAttribute(
        'aria-label',
        'View product: ' + item.name
      );
      imageLink.appendChild(img);
      row.appendChild(imageLink);

      // Product information
      const info = document.createElement('div');

      // Clickable product name
      const name = document.createElement('a');
      name.href = item.url;
      name.textContent = item.name;

      // Product quantity and price
      const detail = document.createElement('small');
      detail.textContent =
        'Qty: ' + item.quantity + ' · ' + item.price;

      info.append(name, detail);
      row.appendChild(info);

      contents.appendChild(row);

    });

    // Subtotal
    const subtotal = document.createElement('div');
    subtotal.className = 'rcrmc-mini-cart-subtotal';
    subtotal.textContent = 'Cart subtotal: ' + data.subtotal;

    // Action buttons
    const actions = document.createElement('div');
    actions.className = 'rcrmc-mini-cart-actions';

    const continueButton = document.createElement('button');
    continueButton.type = 'button';
    continueButton.textContent = 'Continue Shopping';

    const checkout = document.createElement('a');
    checkout.href = data.checkout;
    checkout.textContent = 'Checkout';

    actions.append(continueButton, checkout);

    panel.append(header, contents, subtotal, actions);

    document.body.appendChild(panel);

    // Close popup
    const dismiss = () => panel.remove();

    close.addEventListener('click', dismiss);
    continueButton.addEventListener('click', dismiss);

    // Escape key
    document.addEventListener('keydown', function onKey(e) {

      if (e.key === 'Escape') {
        dismiss();
        document.removeEventListener('keydown', onKey);
      }

    });

    // Mobile swipe to dismiss
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
