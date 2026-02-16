<?php
if (isset($_GET['q'])) {
	$_SESSION['substring'] = $substring = $_GET['q'];
}

$sort_by = array(
	'2'		=> 'Name',
	'3'		=> 'Price',
	'4'		=> 'Bestsellers',
	'5'		=> 'Most viewed',
	'6'		=> 'Newest'
);

$template['sort_by'] = $sort_by;

$template['bread_crumbs'][] = array('', "Search");

$search_condition = array();
$search_condition['substring'] = $_SESSION['substring'];
$search_condition['orderby'] = 'p.sales_stats DESC, p.views_stats DESC, p.add_date DESC';
if (!$_GET['sort']) {
	$_GET['sort'] = 2;
	$_GET['direction'] = 1;
	$is_sort = false;
} else
	$is_sort = true;

$search_condition['sort'] = $_GET['sort'];

include SITE_ROOT . '/includes/search.php';

$template['products'] = $products;

if ($_GET['q']) {
	$template["navigation_script"] = $current_location."/search?q=".$_GET['q']."&sort=".$_GET['sort'].'&direction='.$_GET['direction'];
	$template["sort_by_script"] = $current_location."/search?q=".$_GET['q'].'&';
} else {
	$template["navigation_script"] = $current_location."/search?sort=".$_GET['sort'].'&direction='.$_GET['direction'];
	$template["sort_by_script"] = $current_location."/search?";
}

if ($_GET['filter']) {
	$url_add = '';
	foreach ($_GET['filter'] as $k=>$v) {
		if (is_array($v)) {
			foreach ($v as $k2=>$v2) {
				if ($k == 'attr') {
					foreach ($v2 as $v3)
						$url_add .= '&filter['.$k.']['.$k2.'][]='.$v3;
				} else
					$url_add .= '&filter['.$k.']['.$k2.']='.$v2;
			}
		} else
			$url_add .= '&filter['.$k.']='.$v;
	}

	$template["navigation_script"] = $current_location."/search?q=".$_GET['q']."&sort=".$_GET['sort'].'&direction='.$_GET['direction'].$url_add.'&';
	$template["sort_by_script"] = $current_location."/search?q=".$_GET['q']."&".$url_add.'&';
}

$template['per_row'] = 4;
$template['products_results_html'] = get_template_contents('common/products_results.php');
if ($is_ajax && ($_GET['page'] || $is_sort || $_GET['filtered'])) {
	exit($template['products_results_html']);
}

$template['css'][] = 'products';
$template['js'][] = 'products';
$template['css'][] = 'popup';
$template['js'][] = 'popup';
$template['js'][] = 'jquery.zoom.min';

$template['head_title'] = 'Search results. '.$template['head_title'];
$template['page'] = get_template_contents('search/body.php');
