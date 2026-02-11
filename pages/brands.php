<?php
/**
 * SpaCart - Brands listing page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.category.php';
require_once SPACART_PATH.'/includes/func/func.brands.php';

$brand_id = !empty($get[1]) ? (int) $get[1] : 0;

if ($brand_id) {
    // Show products for specific brand
    $brand = spacart_get_brand($brand_id);

    if (!$brand) {
        $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Marque non trouvée</p></div>';
        $page_title = 'Marque non trouvée';
        $breadcrumbs_html = '';
        return;
    }

    $page_num = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $sort = !empty($_GET['sort']) ? $_GET['sort'] : 'date_desc';

    $filters = array('brand' => $brand_id);
    if (!empty($_GET['price_min'])) $filters['price_min'] = (float) $_GET['price_min'];
    if (!empty($_GET['price_max'])) $filters['price_max'] = (float) $_GET['price_max'];

    $result = spacart_get_products($filters, $sort, $page_num, 12);

    $page_title = htmlspecialchars($brand->label).' - '.$spacart_config['title'];

    $bc_items = array(
        array('label' => 'Accueil', 'url' => '#/'),
        array('label' => 'Marques', 'url' => '#/brands'),
        array('label' => $brand->label, 'url' => '')
    );
    $breadcrumbs_html = spacart_breadcrumbs($bc_items);

    $tpl_vars = array(
        'brand' => $brand,
        'products' => $result['items'],
        'total' => $result['total'],
        'total_pages' => $result['pages'],
        'current_page' => $page_num,
        'sort' => $sort,
        'filters' => $filters,
        'config' => $spacart_config,
        'category' => null,
        'subcategories' => array(),
        'brands' => spacart_get_brands()
    );

    $page_html = spacart_render(SPACART_TPL_PATH.'/category/body.php', $tpl_vars);
} else {
    // Show all brands
    $brands = spacart_get_brands();

    $page_title = 'Marques - '.$spacart_config['title'];

    $bc_items = array(
        array('label' => 'Accueil', 'url' => '#/'),
        array('label' => 'Marques', 'url' => '')
    );
    $breadcrumbs_html = spacart_breadcrumbs($bc_items);

    $tpl_vars = array(
        'brands' => $brands,
        'config' => $spacart_config
    );

    $page_html = spacart_render(SPACART_TPL_PATH.'/brands/body.php', $tpl_vars);
}
