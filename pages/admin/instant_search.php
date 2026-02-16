<?php
if ($_GET['q']) {
	$_SESSION['substring'] = $substring = $q = addslashes($_GET['q']);
}

if ($_GET['where'] == '1') {
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
	$search_condition['per_page'] = 30;

	include SITE_ROOT . '/includes/search.php';
	$template['products'] = $products;
} elseif ($_GET['where'] == '2') {
	$fields = array(
		'email',
		'firstname',
		'lastname',
		"CONCAT(firstname, ' ', lastname)",
		'phone',
		'phone',
		'address',
		'city',
		'state',
		'country',
		'zipcode',
		'phone',
		'b_firstname',
		'b_lastname',
		"CONCAT(b_firstname, ' ', b_lastname)",
		'b_address',
		'b_city',
		'b_state',
		'b_country',
		'b_zipcode',
		'b_phone',
	);

	$where_sql = array();
	foreach ($fields as $k=>$v) {
		$where_sql[] = $v." LIKE '%".$q."%'";
	}

	$users = $db->all("SELECT * FROM users WHERE ".implode(" OR ", $where_sql)." ORDER BY firstname, lastname LIMIT 30");
	$template['users'] = $users;
	$template['total_items'] = $db->field("SELECT COUNT(*) FROM users WHERE ".implode(" OR ", $where_sql)."");
} elseif ($_GET['where'] == '3') {
	$fields = array(
		'orderid',
		'email',
		'firstname',
		'lastname',
		"CONCAT(firstname, ' ', lastname)",
		'phone',
		'phone',
		'address',
		'city',
		'state',
		'country',
		'zipcode',
		'phone',
		'b_firstname',
		'b_lastname',
		"CONCAT(b_firstname, ' ', b_lastname)",
		'b_address',
		'b_city',
		'b_state',
		'b_country',
		'b_zipcode',
		'b_phone',
	);

	$where_sql = array();
	foreach ($fields as $k=>$v) {
		$where_sql[] = $v." LIKE '%".$q."%'";
	}

	$orders = $db->all("SELECT * FROM orders WHERE ".implode(" OR ", $where_sql)." ORDER BY orderid DESC LIMIT 30");
	$template['orders'] = $orders;
	$template['total_items'] = $db->field("SELECT COUNT(*) FROM orders WHERE ".implode(" OR ", $where_sql)."");
}

$template['html'] = get_template_contents('common/instant_search_admin.php');
exit($template['html']);