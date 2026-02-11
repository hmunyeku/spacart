<?php
/**
 * SpaCart - Category / product listing page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.category.php';
require_once SPACART_PATH.'/includes/func/func.brands.php';

$category_id = !empty($get[1]) ? (int) $get[1] : 0;
$page_num = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$limit = 12;

// Build filters
$filters = array();

if ($category_id) {
    $filters['category_id'] = $category_id;
}

// Price range
if (!empty($_GET['price_min'])) {
    $filters['price_min'] = (float) $_GET['price_min'];
}
if (!empty($_GET['price_max'])) {
    $filters['price_max'] = (float) $_GET['price_max'];
}

// Brand
if (!empty($_GET['brand'])) {
    $filters['brand'] = (int) $_GET['brand'];
}

// In stock
if (!empty($_GET['in_stock'])) {
    $filters['in_stock'] = true;
}

// Get products
$result = spacart_get_products($filters, $sort, $page_num, $limit);
$products = $result['items'];
$total = $result['total'];
$total_pages = $result['pages'];

// Get current category info
$category = null;
if ($category_id) {
    $category = spacart_get_category($category_id);
}

// Subcategories
$subcategories = array();
if ($category_id) {
    $subcategories = spacart_get_subcategories($category_id);
} else {
    $subcategories = spacart_get_subcategories(0);
}

// Brands for filter sidebar
$brands = spacart_get_brands();

// Page title
if ($category) {
    $page_title = htmlspecialchars($category->label).' - '.$spacart_config['title'];
} else {
    $page_title = 'Tous les produits - '.$spacart_config['title'];
}

// Breadcrumbs
$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/')
);
if ($category) {
    $catChain = spacart_get_category_breadcrumb($category_id);
    foreach ($catChain as $c) {
        $bc_items[] = array('label' => $c['label'], 'url' => $c['url']);
    }
} else {
    $bc_items[] = array('label' => 'Tous les produits', 'url' => '');
}
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'category' => $category,
    'products' => $products,
    'subcategories' => $subcategories,
    'brands' => $brands,
    'total' => $total,
    'total_pages' => $total_pages,
    'current_page' => $page_num,
    'sort' => $sort,
    'filters' => $filters,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/category/body.php', $tpl_vars);
