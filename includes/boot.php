<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * SpaCart Bootstrap - Initializes Dolibarr context for the public shop
 * This file is included by public/index.php and api/index.php
 */

// Prevent direct access
if (!defined('SPACART_BOOT')) {
	die('Direct access not allowed');
}

// Dolibarr environment (NOLOGIN mode)
if (!defined("NOLOGIN")) define("NOLOGIN", '1');
if (!defined("NOCSRFCHECK")) define("NOCSRFCHECK", '1');
if (!defined("NOIPCHECK")) define("NOIPCHECK", '1');
if (!defined("NOBROWSERNOTIF")) define("NOBROWSERNOTIF", '1');

// Load Dolibarr main
$res = 0;
if (!$res && file_exists(dirname(__FILE__)."/../../main.inc.php")) {
	$res = @include dirname(__FILE__)."/../../main.inc.php";
}
if (!$res && file_exists(dirname(__FILE__)."/../../../main.inc.php")) {
	$res = @include dirname(__FILE__)."/../../../main.inc.php";
}
if (!$res) {
	http_response_code(500);
	die('Dolibarr main.inc.php not found');
}

// Load SpaCart lib
require_once DOL_DOCUMENT_ROOT.'/custom/spacart/lib/spacart.lib.php';

// Session for cart
if (session_status() === PHP_SESSION_NONE) {
	session_name('spacart_session');
	session_start();
}

// Generate session token if missing
if (empty($_SESSION['spacart_token'])) {
	$_SESSION['spacart_token'] = spacartGenerateToken();
}

// SpaCart base paths
define('SPACART_PATH', DOL_DOCUMENT_ROOT.'/custom/spacart');
define('SPACART_URL', DOL_URL_ROOT.'/custom/spacart');
define('SPACART_PUBLIC_URL', SPACART_URL.'/public');
define('SPACART_API_URL', SPACART_URL.'/api');
define('SPACART_TPL_PATH', SPACART_PATH.'/templates');
define('SPACART_CACHE_PATH', DOL_DATA_ROOT.'/spacart/cache');
define('SPACART_PHOTOS_PATH', DOL_DATA_ROOT.'/spacart/photos');

// SpaCart configuration (loaded from Dolibarr constants)
$spacart_config = array(
	// General
	'enabled'          => getDolGlobalString('SPACART_ENABLED', '1'),
	'title'            => getDolGlobalString('SPACART_TITLE', 'Boutique en ligne'),
	'company_name'     => getDolGlobalString('SPACART_COMPANY_NAME', 'CoexDis'),
	'company_email'    => getDolGlobalString('SPACART_COMPANY_EMAIL', ''),
	'company_slogan'   => getDolGlobalString('SPACART_COMPANY_SLOGAN', ''),
	'currency'         => getDolGlobalString('SPACART_CURRENCY', 'EUR'),
	'currency_symbol'  => getDolGlobalString('SPACART_CURRENCY_SYMBOL', '€'),
	'weight_symbol'    => getDolGlobalString('SPACART_WEIGHT_SYMBOL', 'kg'),
	'products_per_page'=> (int) getDolGlobalString('SPACART_PRODUCTS_PER_PAGE', '12'),
	'guest_checkout'   => (int) getDolGlobalString('SPACART_GUEST_CHECKOUT', '1'),
	'shop_closed'      => (int) getDolGlobalString('SPACART_SHOP_CLOSED', '0'),
	'free_shipping'    => (float) getDolGlobalString('SPACART_FREE_SHIPPING_THRESHOLD', '0'),

	// Theme
	'theme_color'      => getDolGlobalString('SPACART_THEME_COLOR', '#2196F3'),
	'theme_color_2'    => getDolGlobalString('SPACART_THEME_COLOR_2', '#1976D2'),

	// Payment: Use Dolibarr's native Stripe & PayPal modules
	'stripe_enabled'   => isModEnabled('stripe'),
	'stripe_pk'        => isModEnabled('stripe') ? (getDolGlobalString('STRIPE_LIVE') ? getDolGlobalString('STRIPE_LIVE_PUBLISHABLE_KEY') : getDolGlobalString('STRIPE_TEST_PUBLISHABLE_KEY')) : '',
	'paypal_enabled'   => isModEnabled('paypal'),
	'payment_url'      => DOL_URL_ROOT.'/public/payment/newpayment.php',

	// Integrations
	'recaptcha_site'   => getDolGlobalString('SPACART_RECAPTCHA_SITE_KEY', ''),
	'recaptcha_secret' => getDolGlobalString('SPACART_RECAPTCHA_SECRET_KEY', ''),
	'tawkto_id'        => getDolGlobalString('SPACART_TAWKTO_ID', ''),
	'analytics_id'     => getDolGlobalString('SPACART_ANALYTICS_ID', ''),

	// URLs
	'base_url'         => SPACART_PUBLIC_URL,
	'api_url'          => SPACART_API_URL,
	'photos_url'       => DOL_URL_ROOT.'/document.php',
);

// Load cart from session/database
$spacart_cart = array(
	'items'     => array(),
	'count'     => 0,
	'subtotal'  => 0,
	'tax'       => 0,
	'shipping'  => 0,
	'discount'  => 0,
	'total'     => 0,
);

if (!empty($_SESSION['spacart_cart_id'])) {
	// Load cart from DB
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."spacart_cart WHERE rowid = ".((int) $_SESSION['spacart_cart_id'])." AND entity = ".$conf->entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$cart_row = $db->fetch_object($resql);
		$spacart_cart['subtotal'] = (float) $cart_row->subtotal_ht;
		$spacart_cart['tax'] = (float) $cart_row->total_tva;
		$spacart_cart['shipping'] = (float) $cart_row->shipping_cost;
		$spacart_cart['total'] = (float) $cart_row->total_ttc;

		// Load cart items
		$sql2 = "SELECT ci.*, p.ref, p.label, p.description, p.price, p.price_ttc, p.tva_tx, p.weight, p.stock, p.tosell";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."spacart_cart_item as ci";
		$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ci.fk_product = p.rowid";
		$sql2 .= " WHERE ci.fk_cart = ".((int) $cart_row->rowid);
		$sql2 .= " ORDER BY ci.rowid ASC";
		$resql2 = $db->query($sql2);
		if ($resql2) {
			while ($item = $db->fetch_object($resql2)) {
				$spacart_cart['items'][] = $item;
				$spacart_cart['count'] += (int) $item->qty;
			}
		}
	}
}

// Load logged-in customer from session
$spacart_customer = null;
if (!empty($_SESSION['spacart_customer_id'])) {
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."spacart_customer WHERE rowid = ".((int) $_SESSION['spacart_customer_id'])." AND entity = ".$conf->entity." AND status = 1";
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$spacart_customer = $db->fetch_object($resql);
	} else {
		unset($_SESSION['spacart_customer_id']);
	}
}

// Check remember-me cookie
if (empty($spacart_customer) && !empty($_COOKIE['spacart_remember'])) {
	$token = $_COOKIE['spacart_remember'];
	$sql = "SELECT * FROM ".MAIN_DB_PREFIX."spacart_customer WHERE remember_token = '".$db->escape($token)."' AND entity = ".$conf->entity." AND status = 1";
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$spacart_customer = $db->fetch_object($resql);
		$_SESSION['spacart_customer_id'] = $spacart_customer->rowid;
	}
}

// Load categories for navigation
$spacart_categories = array();
$sql = "SELECT c.rowid, c.label, c.description, c.fk_parent";
$sql .= " FROM ".MAIN_DB_PREFIX."categorie as c";
$sql .= " WHERE c.type = 0"; // type 0 = product categories
$sql .= " AND c.entity IN (".getEntity('categorie').")";
$sql .= " AND c.visible = 1";
$sql .= " ORDER BY c.fk_parent ASC, c.label ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($cat = $db->fetch_object($resql)) {
		$spacart_categories[] = $cat;
	}
}

// Load CMS pages for footer/menu
$spacart_pages = array();
$sql = "SELECT rowid, title, slug, show_in_menu FROM ".MAIN_DB_PREFIX."spacart_page WHERE status = 1 AND entity = ".$conf->entity." ORDER BY position ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($pg = $db->fetch_object($resql)) {
		$spacart_pages[] = $pg;
	}
}

// Recently viewed products (from session)
if (!isset($_SESSION['spacart_recently_viewed'])) {
	$_SESSION['spacart_recently_viewed'] = array();
}

// AJAX detection
$is_ajax = (
	(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
	|| !empty($_GET['ajax'])
);

// Delimiter for SPA AJAX responses (same as SpaCart)
$ajax_delimiter = '|-|+|=|';
