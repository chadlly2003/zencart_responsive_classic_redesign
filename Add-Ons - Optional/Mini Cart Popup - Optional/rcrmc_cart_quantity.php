<?php
/**
 * RCR Mini Cart: update an EXISTING cart line while preserving its attributes.
 * Install in the catalog root, next to index.php.
 * This endpoint never adds a new product or changes its selected options.
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
