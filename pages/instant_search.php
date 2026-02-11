<?php
/**
 * SpaCart - Instant search (AJAX live search)
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.product.php';

$query = !empty($_GET['q']) ? trim($_GET['q']) : '';
$limit = 6;

if (strlen($query) < 2) {
    $page_html = '';
    $page_title = '';
    $breadcrumbs_html = '';
    return;
}

$filters = array('search' => $query);
$result = spacart_get_products($filters, 'date_desc', 1, $limit);

$html = '';
if (!empty($result['items'])) {
    foreach ($result['items'] as $item) {
        $name = htmlspecialchars($item->label);
        $price = spacartFormatPrice($item->price);
        $photo = spacart_product_photo_url($item->rowid, $item->ref);

        $html .= '<a href="#/product/'.$item->rowid.'" class="spacart-search-result-item spacart-spa-link">';
        $html .= '<img src="'.$photo.'" alt="'.$name.'">';
        $html .= '<div class="search-item-info">';
        $html .= '<span class="search-item-name">'.$name.'</span>';
        $html .= '<span class="search-item-price">'.$price.'</span>';
        $html .= '</div>';
        $html .= '</a>';
    }

    if ($result['total'] > $limit) {
        $searchUrl = '#/search/'.urlencode($query);
        $html .= '<a href="'.$searchUrl.'" class="spacart-search-more spacart-spa-link">';
        $html .= 'Voir les '.$result['total'].' résultats →';
        $html .= '</a>';
    }
}

$page_html = $html;
$page_title = '';
$breadcrumbs_html = '';
