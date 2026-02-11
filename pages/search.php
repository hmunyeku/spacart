<?php
/**
 * SpaCart - Search results page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';
require_once SPACART_PATH.'/includes/func/func.category.php';
require_once SPACART_PATH.'/includes/func/func.brands.php';

$query = '';
if (!empty($get[1])) {
    $query = urldecode($get[1]);
} elseif (!empty($_GET['q'])) {
    $query = $_GET['q'];
}

$page_num = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$sort = !empty($_GET['sort']) ? $_GET['sort'] : 'date_desc';

$filters = array();
if ($query) {
    $filters['search'] = $query;
}
if (!empty($_GET['price_min'])) $filters['price_min'] = (float) $_GET['price_min'];
if (!empty($_GET['price_max'])) $filters['price_max'] = (float) $_GET['price_max'];
if (!empty($_GET['category'])) $filters['category_id'] = (int) $_GET['category'];
if (!empty($_GET['brand'])) $filters['brand'] = (int) $_GET['brand'];

$result = spacart_get_products($filters, $sort, $page_num, 12);

$page_title = 'Recherche : '.htmlspecialchars($query).' - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Recherche : '.$query, 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$brands = spacart_get_brands();

$tpl_vars = array(
    'query' => $query,
    'products' => $result['items'],
    'total' => $result['total'],
    'total_pages' => $result['pages'],
    'current_page' => $page_num,
    'sort' => $sort,
    'filters' => $filters,
    'brands' => $brands,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/search/body.php', $tpl_vars);
