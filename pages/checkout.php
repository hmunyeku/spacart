<?php
/**
 * SpaCart - Checkout page handler (3-step tunnel)
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.cart.php';
require_once SPACART_PATH.'/includes/func/func.user.php';
require_once SPACART_PATH.'/includes/func/func.order.php';

// Check cart has items
$cartId = !empty($_SESSION['spacart_cart_id']) ? (int) $_SESSION['spacart_cart_id'] : 0;
$cartData = $cartId ? spacart_load_cart($cartId) : null;

if (!$cartData || empty($cartData->items)) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">shopping_cart</i><p>Votre panier est vide</p><a href="#/products" class="btn spacart-spa-link">Voir les produits</a></div>';
    $page_title = 'Panier vide';
    $breadcrumbs_html = '';
    return;
}

// Get customer info if logged in
$customer = null;
$addresses = array();
if ($is_logged_in && $spacart_customer) {
    $customer = $spacart_customer;
    $addresses = spacart_get_customer_addresses($customer->rowid);
}

// Get shipping methods
$shippingMethods = spacart_get_shipping_methods(1, $cartData->subtotal, 0);

// Get countries for address form
$countries = array();
$sqlCountries = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."c_country WHERE active = 1 ORDER BY label ASC";
$resCountries = $db->query($sqlCountries);
if ($resCountries) {
    while ($obj = $db->fetch_object($resCountries)) {
        $countries[] = $obj;
    }
}

$page_title = 'Commander - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Panier', 'url' => '#/cart'),
    array('label' => 'Commander', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

// Get payment methods from Dolibarr modules
$stripeEnabled = isModEnabled('stripe');
$paypalEnabled = isModEnabled('paypal');
$stripePk = '';
if ($stripeEnabled) {
    $stripePk = getDolGlobalString('STRIPE_LIVE')
        ? getDolGlobalString('STRIPE_LIVE_PUBLISHABLE_KEY')
        : getDolGlobalString('STRIPE_TEST_PUBLISHABLE_KEY');
}

$tpl_vars = array(
    'cart' => $cartData,
    'customer' => $customer,
    'addresses' => $addresses,
    'shipping_methods' => $shippingMethods,
    'countries' => $countries,
    'is_logged_in' => $is_logged_in,
    'config' => $spacart_config,
    'stripe_enabled' => $stripeEnabled,
    'stripe_pk' => $stripePk,
    'paypal_enabled' => $paypalEnabled,
);

$page_html = spacart_render(SPACART_TPL_PATH.'/checkout/body.php', $tpl_vars);
