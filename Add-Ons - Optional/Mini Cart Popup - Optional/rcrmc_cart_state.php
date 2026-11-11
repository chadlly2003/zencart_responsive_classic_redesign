<?php
/**
 * RCR Mini Cart: read-only AJAX cart state.
 * Install in the Zen Cart catalog root, alongside index.php.
 * Cart updates themselves are handled by Zen Cart's native update_product action.
 */
require 'includes/application_top.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');


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

$cart = $_SESSION['cart'] ?? null;
$items = [];
if ($cart) {
    foreach ($cart->get_products() as $product) {
        $id = (string)($product['id'] ?? '');
        $pid = (int)$id;
        $image = ltrim((string)($product['image'] ?? ''), '/');
        if ($image === '' || !is_file(DIR_FS_CATALOG . DIR_WS_IMAGES . $image)) {
            $image = 'no_picture.gif';
        }
        $attributes = [];
        $selected = $product['attributes'] ?? ($cart->contents[$id]['attributes'] ?? []);
        if (is_array($selected)) {
            foreach ($selected as $option => $value) {
                // Read attribute names through the same Zen Cart tables as the original popup.
                $sql = "SELECT popt.products_options_name, poval.products_options_values_name
                    FROM " . TABLE_PRODUCTS_OPTIONS . " popt,
                         " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval,
                         " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                    WHERE pa.products_id = :pid AND pa.options_id = :oid
                      AND pa.options_id = popt.products_options_id
                      AND pa.options_values_id = :vid
                      AND pa.options_values_id = poval.products_options_values_id
                      AND popt.language_id = :lid AND poval.language_id = :lid";
                foreach ([':pid' => $pid, ':oid' => (int)$option, ':vid' => (int)$value,
                          ':lid' => (int)($_SESSION['languages_id'] ?? 1)] as $key => $val) {
                    $sql = $db->bindVars($sql, $key, $val, 'integer');
                }
                $result = $db->Execute($sql);
                if ($result->EOF) continue;
                $valueText = (defined('PRODUCTS_OPTIONS_VALUES_TEXT_ID') &&
                    $value == PRODUCTS_OPTIONS_VALUES_TEXT_ID)
                    ? (string)($product['attributes_values'][$option] ?? '')
                    : (string)($result->fields['products_options_values_name'] ?? '');
                $attributes[] = [
                    'name' => (string)($result->fields['products_options_name'] ?? ''),
                    'value' => $valueText
                ];
            }
        }
        $qty = (int)($product['quantity'] ?? 0);
        $catalogPriceHtml = function_exists('zen_get_products_display_price')
            ? (string)zen_get_products_display_price($pid)
            : '';

        $items[] = [
            'cartId' => $id,
            'name' => (string)($product['name'] ?? ''),
            'url' => zen_href_link(zen_get_info_page($pid), 'products_id=' . $pid),
            'image' => DIR_WS_IMAGES . $image,
            'attributes' => $attributes,
            'quantity' => $qty,
            'quantityRules' => rcrmcQuantityRules($pid),
            'catalogPriceHtml' => $catalogPriceHtml,
            'price' => $currencies->format((float)($product['final_price'] ?? $product['price'] ?? 0) * $qty)
        ];
    }
}
echo json_encode([
    'ok' => true,
    'items' => $items,
    'count' => $cart ? (int)$cart->count_contents() : 0,
    'subtotal' => $cart ? $currencies->format($cart->show_total()) : ''
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
exit;
