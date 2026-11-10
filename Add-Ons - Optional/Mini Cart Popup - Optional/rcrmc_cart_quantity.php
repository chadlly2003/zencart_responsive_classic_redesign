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
