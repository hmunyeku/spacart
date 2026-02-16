<?php
if (!$login) {
	redirect('/login');
}

q_load('order', 'product');

$template['no_left_menu'] = 'Y';

if ($get['1'] && is_numeric($get['1'])) {
	// Order detail view
	$orderinfo = func_orderinfo(addslashes($get['1']));
	if (!$orderinfo || $orderinfo['order']['userid'] != $login) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('Vous n\'avez pas acces a cette commande')
		);
		redirect('/orders');
	}

	$template['order'] = $orderinfo['order'];
	$template['products'] = $orderinfo['products'];
	$template['view'] = 'detail';
	$template['head_title'] = lng('Commande').' #'.$get['1'].' - '.$template['head_title'];
} else {
	// Orders list view
	$template['orders'] = $db->all("SELECT o.*, pm.name as payment_name, s.shipping as shipping_name FROM orders o LEFT JOIN payment_methods pm ON pm.paymentid=o.paymentid LEFT JOIN shipping s ON s.shippingid=o.shippingid WHERE o.userid=".$login." ORDER BY o.orderid DESC");
	$template['view'] = 'list';
	$template['head_title'] = lng('Mes commandes').' - '.$template['head_title'];
}

$template['css'][] = 'orders';
$template['page'] = get_template_contents('orders/body.php');
