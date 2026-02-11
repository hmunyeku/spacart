<?php
/**
 * SpaCart - Home page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.category.php';
require_once SPACART_PATH.'/includes/func/func.brands.php';

// Banners
$banners = array();
$sqlBanners = "SELECT b.rowid, b.title, b.subtitle, b.image, b.link, b.position";
$sqlBanners .= " FROM ".MAIN_DB_PREFIX."spacart_banner b";
$sqlBanners .= " WHERE b.active = 1 AND b.location = 'home'";
$sqlBanners .= " AND (b.date_start IS NULL OR b.date_start <= NOW())";
$sqlBanners .= " AND (b.date_end IS NULL OR b.date_end >= NOW())";
$sqlBanners .= " ORDER BY b.position ASC";
$resBanners = $db->query($sqlBanners);
if ($resBanners) {
    while ($obj = $db->fetch_object($resBanners)) {
        $banners[] = $obj;
    }
}

// Featured products
$featured = spacart_get_featured_products(8);

// New products
$new_products = spacart_get_new_products(8);

// Best sellers
$bestsellers = spacart_get_bestsellers(8);

// Most viewed
$most_viewed = spacart_get_most_viewed(4);

// Testimonials
$testimonials = array();
$sqlTest = "SELECT t.rowid, t.customer_name, t.content, t.rating, t.photo, t.date_creation";
$sqlTest .= " FROM ".MAIN_DB_PREFIX."spacart_testimonial t";
$sqlTest .= " WHERE t.active = 1";
$sqlTest .= " ORDER BY t.date_creation DESC LIMIT 6";
$resTest = $db->query($sqlTest);
if ($resTest) {
    while ($obj = $db->fetch_object($resTest)) {
        $testimonials[] = $obj;
    }
}

// Top categories
$top_categories = spacart_get_subcategories(0);
if (count($top_categories) > 6) {
    $top_categories = array_slice($top_categories, 0, 6);
}

$page_title = $spacart_config['title'];

$tpl_vars = array(
    'banners' => $banners,
    'featured' => $featured,
    'new_products' => $new_products,
    'bestsellers' => $bestsellers,
    'most_viewed' => $most_viewed,
    'testimonials' => $testimonials,
    'top_categories' => $top_categories,
    'config' => $spacart_config
);

$breadcrumbs_html = '';

$page_html = spacart_render(SPACART_TPL_PATH.'/home/body.php', $tpl_vars);
