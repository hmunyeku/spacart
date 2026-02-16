<?php
if ($_GET['q']) {
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
$search_condition['per_page'] = 10;

include SITE_ROOT . '/includes/search.php';

$template['products'] = $products;
$template['html'] = get_template_contents('common/instant_search.php');
exit(get_template_contents('common/instant_search.php'));