<?php
/** RCR Mini Cart AJAX Add - install in catalog root alongside index.php. */
require 'includes/application_top.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
function rcrmcAddReply(int $status, bool $ok, string $message = ''): void {
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'error' => $message], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') rcrmcAddReply(405, false, 'POST required.');
$expected = (string)($_SESSION['securityToken'] ?? '');
$provided = (string)($_POST['securityToken'] ?? '');
if ($expected === '' || !hash_equals($expected, $provided)) rcrmcAddReply(403, false, 'Your session expired. Refresh the page and try again.');
$rawId = $_POST['products_id'] ?? null;
if (!is_scalar($rawId) || !preg_match('/^[1-9][0-9]*$/D', (string)$rawId)) rcrmcAddReply(400, false, 'Invalid product.');
$pid = (int)$rawId;
$cart = $_SESSION['cart'] ?? null;
if (!is_object($cart)) rcrmcAddReply(500, false, 'Shopping cart unavailable.');
if (zen_get_products_allow_add_to_cart($pid) !== 'Y') rcrmcAddReply(422, false, 'This product cannot be added to your cart.');
$mode = (string)($_POST['mode'] ?? '');
if (!in_array($mode, ['buy_now', 'product'], true)) rcrmcAddReply(400, false, 'Invalid cart action.');
$attrs = $_POST['id'] ?? [];
if (!is_array($attrs)) rcrmcAddReply(422, false, 'Invalid product options.');
if ($mode === 'buy_now' && zen_requires_attribute_selection($pid)) {
    rcrmcAddReply(422, false, 'Please choose the product options before adding to cart.');
}
if ($mode === 'product' && zen_requires_attribute_selection($pid) && !$attrs) {
    rcrmcAddReply(422, false, 'Please select the required product options.');
}
foreach ($attrs as $key => $value) {
    if (!is_scalar($value) || !zen_get_attributes_valid($pid, $key, $value)) {
        rcrmcAddReply(422, false, 'Please check the selected product options.');
    }
}
if ($mode === 'buy_now') {
    $addQty = (float)zen_get_buy_now_qty($pid);
} else {
    $rawQty = $_POST['cart_quantity'] ?? '1';
    if (!is_scalar($rawQty) || !is_numeric($rawQty)) rcrmcAddReply(422, false, 'Enter a valid quantity.');
    $addQty = (float)$rawQty;
}
if (!is_finite($addQty) || $addQty <= 0 || $addQty > 99999) rcrmcAddReply(422, false, 'Enter a valid quantity.');
$uprid = zen_get_uprid($pid, $attrs);
$existing = (float)$cart->get_quantity($uprid);
$max = (float)zen_get_products_quantity_order_max($pid);
if ($max > 0 && $existing + $addQty > $max) rcrmcAddReply(422, false, 'Maximum quantity for this product has been reached.');
$stock = (float)zen_get_products_stock($pid);
if ($stock <= 0) rcrmcAddReply(422, false, 'This product is sold out.');
if (defined('STOCK_ALLOW_CHECKOUT') && STOCK_ALLOW_CHECKOUT === 'false' && $existing + $addQty > $stock) {
    rcrmcAddReply(422, false, 'Not enough stock is available.');
}
$before = (float)$cart->count_contents();
try {
    // Zen Cart native cart class handles price, attributes, order units, and notifications.
    $cart->add_cart($pid, $existing + $addQty, $attrs);
} catch (Throwable $e) {
    error_log('RCR AJAX Add error: ' . $e->getMessage());
    rcrmcAddReply(500, false, 'Unable to add this product.');
}
$after = (float)$cart->count_contents();
if ($after <= $before) rcrmcAddReply(422, false, 'The product could not be added. Check stock or quantity limits.');
$_SESSION['rcrmc_mini_cart_previous_count'] = (int)$after;
rcrmcAddReply(200, true);
