<?php
/**
 * RCR Mini Cart - combined AJAX endpoint.
 * Actions: quantity (POST), state (GET), validate (POST).
 * Combines the three existing root handlers without changing Zen Cart core.
 */
require 'includes/application_top.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$action = (string)($_GET['action'] ?? '');

switch ($action) {
    case 'quantity': {
        /**
         * RCR Mini Cart: update an EXISTING cart line while preserving its attributes.
         * Install in the catalog root, next to index.php.
         * This endpoint never adds a new product or changes its selected options.
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

        function rcrmcReply(int $status, bool $ok, string $message = ''): void
        {
            http_response_code($status);
            echo json_encode(['ok' => $ok, 'error' => $message]);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            rcrmcReply(405, false, 'POST required.');
        }

        $expectedToken = (string)($_SESSION['securityToken'] ?? '');
        $receivedToken = (string)($_POST['securityToken'] ?? '');
        if ($expectedToken === '' || !hash_equals($expectedToken, $receivedToken)) {
            rcrmcReply(403, false, 'Invalid security token.');
        }

        $cart = $_SESSION['cart'] ?? null;
        $cartId = $_POST['cartId'] ?? null;
        $rawQuantity = $_POST['quantity'] ?? null;

        if (!is_object($cart) || !is_string($cartId) || $cartId === '' ||
            !isset($cart->contents[$cartId]) ||
            !is_scalar($rawQuantity) ||
            !preg_match('/^[0-9]+$/D', (string)$rawQuantity)) {
            rcrmcReply(400, false, 'Invalid cart item or quantity.');
        }

        $quantity = (int)$rawQuantity;
        if ($quantity > 99999) {
            rcrmcReply(400, false, 'Quantity is too large.');
        }

        /* Enforce Zen Cart quantity rules before changing the existing cart line. */
        $productId = (int)$cartId;
        $rules = rcrmcQuantityRules($productId);
        $minimum = (float)$rules['min'];
        $maximum = (float)$rules['max'];
        $units = (float)$rules['units'];

        // When mixed quantities are enabled, quantities of the same product with
        // different attributes count together toward the configured limits.
        $otherQuantity = 0.0;
        if ($rules['mixed']) {
            foreach ($cart->contents as $existingId => $existingLine) {
                if ((string)$existingId !== $cartId && (int)$existingId === $productId) {
                    $otherQuantity += (float)($existingLine['qty'] ?? 0);
                }
            }
        }

        if ($quantity > 0) {
            $totalQuantity = $quantity + $otherQuantity;
            if ($totalQuantity + 0.000001 < $minimum) {
                rcrmcReply(422, false, 'Minimum order quantity is ' . $minimum . '.');
            }
            if ($maximum > 0 && $totalQuantity > $maximum + 0.000001) {
                rcrmcReply(422, false, 'Maximum order quantity is ' . $maximum . '.');
            }
            if (abs($totalQuantity / $units - round($totalQuantity / $units)) > 0.000001) {
                rcrmcReply(422, false, 'Quantity must be in increments of ' . $units . '.');
            }
        } elseif ($rules['mixed'] && $otherQuantity > 0) {
            // Removing one variant must not leave an invalid aggregate of other variants.
            if ($otherQuantity + 0.000001 < $minimum ||
                ($maximum > 0 && $otherQuantity > $maximum + 0.000001) ||
                abs($otherQuantity / $units - round($otherQuantity / $units)) > 0.000001) {
                rcrmcReply(422, false, 'Removing this item would leave an invalid quantity for the product.');
            }
        }

        // Capture the original selection BEFORE modifying the cart line.
        $attributes = $cart->contents[$cartId]['attributes'] ?? [];
        if (!is_array($attributes)) {
            $attributes = [];
        }

        try {
            if ($quantity === 0) {
                $cart->remove($cartId);
            } else {
                // Native Zen Cart method: preserve the same cart ID and selected options.
                $cart->update_quantity($cartId, $quantity, $attributes);
            }
        } catch (Throwable $error) {
            error_log('RCR Mini Cart quantity update failed: ' . $error->getMessage());
            rcrmcReply(500, false, 'Unable to update this cart item.');
        }

        rcrmcReply(200, true);
        break;
    }
    case 'state': {
        /**
         * RCR Mini Cart: read-only AJAX cart state.
         * Install in the Zen Cart catalog root, alongside index.php.
         * Cart updates themselves are handled by Zen Cart's native update_product action.
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
        break;
    }
    case 'validate': {
        /**
         * RCR Mini Cart - product-page Add to Cart quantity preflight.
         * Checks Zen Cart product minimum, maximum and quantity units
         * against the current cart, without changing the cart.
         * Install in catalog root beside index.php.
         */




        function rcrmcCheckReply(int $status, bool $ok, string $error = ''): void
        {
            http_response_code($status);
            echo json_encode(['ok' => $ok, 'error' => $error]);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            rcrmcCheckReply(405, false, 'POST required.');
        }
        $expected = (string)($_SESSION['securityToken'] ?? '');
        $received = (string)($_POST['securityToken'] ?? '');
        if ($expected === '' || !hash_equals($expected, $received)) {
            rcrmcCheckReply(403, false, 'Invalid security token.');
        }

        $productId = filter_var($_POST['products_id'] ?? null, FILTER_VALIDATE_INT);
        $rawQty = $_POST['cart_quantity'] ?? null;
        if (!$productId || $productId < 1 || !is_scalar($rawQty) ||
            !is_numeric((string)$rawQty) || (float)$rawQty <= 0 ||
            (float)$rawQty > 99999) {
            rcrmcCheckReply(422, false, 'Please enter a valid quantity.');
        }
        $requested = (float)$rawQty;

        $sql = 'SELECT products_quantity_order_min, products_quantity_order_max, '
             . 'products_quantity_order_units, products_quantity_mixed '
             . 'FROM ' . TABLE_PRODUCTS . ' WHERE products_id = :pid LIMIT 1';
        $sql = $db->bindVars($sql, ':pid', (int)$productId, 'integer');
        $record = $db->Execute($sql);
        if ($record->EOF) {
            rcrmcCheckReply(404, false, 'Product not found.');
        }
        $min = max(1.0, (float)($record->fields['products_quantity_order_min'] ?? 1));
        $max = max(0.0, (float)($record->fields['products_quantity_order_max'] ?? 0));
        $units = max(0.000001, (float)($record->fields['products_quantity_order_units'] ?? 1));
        $mixed = (int)($record->fields['products_quantity_mixed'] ?? 0) === 1;

        // Match an existing attribute combination only when the selected
        // options are directly comparable. For mixed products, count all variants.
        $chosen = $_POST['id'] ?? [];
        if (!is_array($chosen)) $chosen = [];
        $cart = $_SESSION['cart'] ?? null;
        $existing = 0.0;
        if (is_object($cart)) {
            foreach (($cart->contents ?? []) as $cartId => $line) {
                if ((int)$cartId !== (int)$productId) continue;
                if (!$mixed) {
                    $saved = $line['attributes'] ?? [];
                    if (!is_array($saved)) $saved = [];
                    // Compare option IDs and selected values; don't conflate
                    // distinct attribute combinations.
                    $normalize = static function (array $values): array {
                        $out = [];
                        foreach ($values as $key => $value) {
                            if (is_array($value)) {
                                $value = array_map('strval', $value);
                                sort($value);
                            } else {
                                $value = (string)$value;
                            }
                            $out[(string)$key] = $value;
                        }
                        ksort($out);
                        return $out;
                    };
                    if ($normalize($chosen) !== $normalize($saved)) continue;
                }
                $existing += (float)($line['qty'] ?? 0);
            }
        }
        $total = $existing + $requested;
        $epsilon = 0.00001;
        if ($total + $epsilon < $min) {
            rcrmcCheckReply(422, false, 'Minimum order quantity for this product is ' . (float)$min . '.');
        }
        if ($max > 0 && $total > $max + $epsilon) {
            rcrmcCheckReply(422, false, 'Maximum order quantity for this product is ' . (float)$max . '. You already have ' . (float)$existing . ' in your cart.');
        }
        if (abs($total / $units - round($total / $units)) > $epsilon) {
            rcrmcCheckReply(422, false, 'This product must be ordered in increments of ' . (float)$units . '.');
        }
        rcrmcCheckReply(200, true);
        break;
    }
    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown Mini Cart action.']);
        exit;
}
