<?php
q_load('order');
if ($get['2'] == 'reset') {
	$_SESSION['search_orders'] = array();
	redirect('/admin/orders');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'search') {
		foreach ($_POST as $k=>$v)
			$_POST[$k] = addslashes($v);

		$_SESSION['search_orders'] = $_POST;
	} else
		foreach ($status as $k=>$v)
			order_status($k, $v);

	redirect('/admin/orders'.($get['2'] ? '/'.$get['2'] : ''));
}

$template['location'] .= ' &gt; '.lng('Orders');
$template['head_title'] = lng('Orders').' :: '.$template['head_title'];
if ($get['2'] == 'recent') {
	$tmp = $db->all("SELECT COUNT(orderid) as cnt FROM orders LIMIT 90");
	$total_items = $tmp['0']['cnt'];
	if ($total_items > 0) {
		$objects_per_page = 30;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/orders/recent?";
		$template['orders'] = $db->all("SELECT * FROM orders".$search_condition." ORDER BY orderid DESC LIMIT $first_page, $objects_per_page");
	}
} else {
	$where = array();
	$end_date = time();
	if ($search_orders['date_period'] == 1) {
		$where[] = "date>=".mktime(0, 0, 0, date('n'), date('j'), date('Y'));
	} elseif ($search_orders['date_period'] == 2) {
		$first_weekday = time() - date("w") * 86400;
		$where[] = "date>=".mktime(0, 0, 0, date("n", $first_weekday), date("j", $first_weekday), date("Y", $first_weekday));
	} elseif ($search_orders['date_period'] == 3) {
		$where[] = "date>=".mktime(0, 0, 0, date("n"), 1, date("Y"));
	} elseif ($search_orders['date_period'] == 4) {
		$tmp = explode('/', $search_orders['date_from']);
		$condition = array();
		if ($tmp['0'])
			$condition[] = 'date>='.mktime(0,0,0,$tmp['0'],$tmp['1'],$tmp['2']);

		$tmp = explode('/', $search_orders['date_to']);
		if ($tmp['0'])
			$condition[] = 'date<='.mktime(23,59,59,$tmp['0'],$tmp['1'],$tmp['2']);

		$where[] = implode(' AND ', $condition);
	}

	if ($search_orders['orderid'])
		$where[] = "orderid LIKE '%".$search_orders['orderid']."%'";

	if ($search_orders['email'])
		$where[] = "email LIKE '%".$search_orders['email']."%'";

	if ($search_orders['customer'])
		$where[] = "(CONCAT(firstname, ' ', lastname) LIKE '%".$search_orders['customer']."%' OR CONCAT(b_firstname, ' ', b_lastname) LIKE '%".$search_orders['customer']."%')";

	if ($search_orders['status'])
		$where[] = "status='".$search_orders['status']."'";

	if (!empty($where))
		$search_condition = " WHERE ".implode(" AND ", $where);

	$total_items = $db->field("SELECT COUNT(orderid) FROM orders".$search_condition);
	if ($total_items > 0) {
		$objects_per_page = 30;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/orders?";
		$template['orders'] = $db->all("SELECT * FROM orders".$search_condition." ORDER BY orderid DESC LIMIT $first_page, $objects_per_page");
	}
}

$template['page'] = get_template_contents('admin/pages/orders.php');
$template['css'][] = 'admin_orders';
$template['js'][] = 'admin_orders';