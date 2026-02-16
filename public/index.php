<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * SpaCart - Public SPA Entry Point
 * Single Page Application - all navigation via AJAX
 */

define('SPACART_BOOT', true);

// Load bootstrap
require_once dirname(__FILE__).'/../includes/boot.php';
require_once SPACART_PATH.'/includes/func/func.core.php';

// Check if shop is closed
if ($spacart_config['shop_closed'] && empty($_GET['key'])) {
	echo '<!DOCTYPE html><html><head><title>Boutique fermee</title></head><body style="text-align:center;padding:100px;font-family:sans-serif;">';
	echo '<h1>Boutique temporairement fermee</h1><p>Nous revenons bientot.</p></body></html>';
	exit;
}

// Parse URL for SPA routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(SPACART_PUBLIC_URL, PHP_URL_PATH);
$path = str_replace($base_path, '', parse_url($request_uri, PHP_URL_PATH));
$path = trim($path, '/');

// Sitemap XML (special route, no SPA)
if ($path === 'sitemap.xml' || $path === 'sitemap') {
	include SPACART_PATH.'/pages/sitemap.php';
	exit;
}

// Split path into segments
$get = explode('/', $path);
if (empty($get[0])) {
	$get[0] = 'home';
}

// Allowed pages (SPA routes)
$allowed_pages = array(
	'home', 'products', 'product', 'category', 'cart', 'checkout', 'login', 'register',
	'profile', 'invoice', 'wishlist', 'search', 'blog', 'news', 'page',
	'testimonials', 'brands', 'instant_search', 'gift_cards', 'stripe', 'paypal',
	'password', 'help', 'support_desk'
);

$current_page = $get[0];
if (!in_array($current_page, $allowed_pages)) {
	$current_page = 'home';
}

// Handle AJAX requests (SPA navigation)
if ($is_ajax) {
	// Load page handler
	$page_file = SPACART_PATH.'/pages/'.$current_page.'.php';
	$page_html = '';
	$page_title = $spacart_config['title'];
	$breadcrumbs_html = '';

	if (file_exists($page_file)) {
		include $page_file;
	}
	if (empty($page_html)) {
		$page_html = '<div class="spacart-error"><h2>Page non trouvee</h2></div>';
	}

	// Return AJAX response as JSON array [html, title, breadcrumbs, page_id, extra_data]
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'html'        => $page_html,
		'title'       => $page_title,
		'breadcrumbs' => $breadcrumbs_html,
		'page'        => $current_page,
		'cart_count'  => $spacart_cart['count'],
		'cart_total'  => $spacart_cart['total'],
	), JSON_UNESCAPED_UNICODE);
	exit;
}

// Full page load (initial SPA shell)
// Build template variables
$tpl_vars = array(
	'config'      => $spacart_config,
	'cart'        => $spacart_cart,
	'customer'    => $spacart_customer,
	'categories'  => $spacart_categories,
	'cat_tree'    => spacart_build_category_tree($spacart_categories),
	'pages'       => $spacart_pages,
	'current_page'=> $current_page,
	'session_token' => $_SESSION['spacart_token'],
	'is_logged_in'  => !empty($spacart_customer),
);

// Load initial page content
$page_file = SPACART_PATH.'/pages/'.$current_page.'.php';
$page_html = '';
if (file_exists($page_file)) {
	include $page_file;
}
$tpl_vars['initial_content'] = $page_html;
$tpl_vars['page_title'] = isset($page_title) ? $page_title : $spacart_config['title'];

// Render full SPA shell
echo spacart_render(SPACART_TPL_PATH.'/body.php', $tpl_vars, true);
