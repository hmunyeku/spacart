<?php
/**
 * SpaCart - Product detail page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.category.php';
require_once SPACART_PATH.'/includes/func/func.brands.php';

$product_id = !empty($get[1]) ? (int) $get[1] : 0;

if (!$product_id) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Produit non trouvé</p></div>';
    $page_title = 'Produit non trouvé';
    $breadcrumbs_html = '';
    return;
}

$product = spacart_get_product($product_id);

if (!$product || !$product->tosell) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Produit non trouvé</p></div>';
    $page_title = 'Produit non trouvé';
    $breadcrumbs_html = '';
    return;
}

// Track view
spacart_track_product_view($product_id);

// Get brand
$product->brand = spacart_get_product_brand($product_id);

// Wishlist status
$customer_id = !empty($spacart_customer) ? $spacart_customer->rowid : 0;
$product->in_wishlist = spacart_is_in_wishlist($product_id, $customer_id);

// Recently viewed
$recently_viewed = spacart_get_recently_viewed(4);

// Quick view mode?
$is_quickview = !empty($_GET['quickview']);

$page_title = htmlspecialchars($product->label).' - '.$spacart_config['title'];

// Build breadcrumbs
$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Produits', 'url' => '#/products')
);
if (!empty($product->categories)) {
    $cat = $product->categories[0];
    $catChain = spacart_get_category_breadcrumb($cat->rowid);
    foreach ($catChain as $c) {
        $bc_items[] = array('label' => $c['label'], 'url' => $c['url']);
    }
}
$bc_items[] = array('label' => $product->label, 'url' => '');

$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'product' => $product,
    'recently_viewed' => $recently_viewed,
    'is_quickview' => $is_quickview,
    'is_logged_in' => $is_logged_in,
    'config' => $spacart_config
);

if ($is_quickview) {
    $page_html = spacart_render(SPACART_TPL_PATH.'/common/popup_product.php', $tpl_vars);
} else {
    $page_html = spacart_render(SPACART_TPL_PATH.'/product/body.php', $tpl_vars);
}
