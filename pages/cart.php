<?php
/**
 * SpaCart - Cart page handler
 * Handles cart display and AJAX cart operations
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.cart.php';

$action = !empty($_POST['action']) ? $_POST['action'] : (!empty($_GET['action']) ? $_GET['action'] : '');

// === AJAX Actions ===
if ($action) {
    $cartId = !empty($_SESSION['spacart_cart_id']) ? (int) $_SESSION['spacart_cart_id'] : 0;

    if (!$cartId) {
        $sessionId = $_SESSION['spacart_token'] ?? session_id();
        $customerId = !empty($spacart_customer) ? $spacart_customer->rowid : 0;
        $cartObj = spacart_get_or_create_cart($sessionId, $customerId);
        if ($cartObj) {
            $cartId = $cartObj->rowid;
            $_SESSION['spacart_cart_id'] = $cartId;
        }
    }

    switch ($action) {
        case 'add':
            $productId = (int) ($_POST['product_id'] ?? 0);
            $qty = max(1, (int) ($_POST['qty'] ?? 1));
            $variantId = (int) ($_POST['variant_id'] ?? 0);
            $options = array();
            foreach ($_POST as $k => $v) {
                if (strpos($k, 'option_') === 0) {
                    $gid = substr($k, 7);
                    $options[$gid] = (int) $v;
                }
            }
            $result = spacart_cart_add($cartId, $productId, $qty, $variantId, $options);
            $summary = spacart_get_cart_summary($cartId);
            $result['cart_count'] = $summary['count'];
            $result['cart_total'] = $summary['subtotal'];
            spacart_json_response($result);
            exit;

        case 'update':
            $itemId = (int) ($_POST['item_id'] ?? 0);
            $qty = (int) ($_POST['qty'] ?? 0);
            $result = spacart_cart_update_qty($cartId, $itemId, $qty);
            $summary = spacart_get_cart_summary($cartId);
            $result['cart_count'] = $summary['count'];
            $result['cart_total'] = $summary['subtotal'];
            spacart_json_response($result);
            exit;

        case 'remove':
            $itemId = (int) ($_POST['item_id'] ?? ($_GET['item_id'] ?? 0));
            if (!$itemId && !empty($get[2])) $itemId = (int) $get[2];
            $result = spacart_cart_remove_item($cartId, $itemId);
            $summary = spacart_get_cart_summary($cartId);
            $result['cart_count'] = $summary['count'];
            $result['cart_total'] = $summary['subtotal'];
            spacart_json_response($result);
            exit;

        case 'coupon':
            $code = trim($_POST['code'] ?? '');
            $result = spacart_cart_apply_coupon($cartId, $code);
            $summary = spacart_get_cart_summary($cartId);
            $result['cart_count'] = $summary['count'];
            $result['cart_total'] = $summary['subtotal'];
            spacart_json_response($result);
            exit;

        case 'giftcard':
            $code = trim($_POST['code'] ?? '');
            $result = spacart_cart_apply_giftcard($cartId, $code);
            $summary = spacart_get_cart_summary($cartId);
            $result['cart_count'] = $summary['count'];
            $result['cart_total'] = $summary['subtotal'];
            spacart_json_response($result);
            exit;
    }
}

// === Mini-cart AJAX ===
if (!empty($_GET['minicart'])) {
    $cartId = !empty($_SESSION['spacart_cart_id']) ? (int) $_SESSION['spacart_cart_id'] : 0;
    $cartData = $cartId ? spacart_load_cart($cartId) : null;

    $tpl_vars = array(
        'cart' => array(
            'count' => $cartData ? $cartData->count : 0,
            'total' => $cartData ? $cartData->total : 0,
            'items' => $cartData ? $cartData->items : array()
        )
    );

    $page_html = spacart_render(SPACART_TPL_PATH.'/common/minicart.php', $tpl_vars);
    $page_title = '';
    $breadcrumbs_html = '';
    return;
}

// === Cart page display ===
$cartId = !empty($_SESSION['spacart_cart_id']) ? (int) $_SESSION['spacart_cart_id'] : 0;
$cartData = $cartId ? spacart_load_cart($cartId) : null;

$page_title = 'Panier - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Panier', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'cart' => $cartData,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/cart/body.php', $tpl_vars);
