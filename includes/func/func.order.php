<?php
function order_status($orderid, $status, $calculate_processed = false) {
	global $db, $order_statuses, $template, $company_name, $company_email, $config;
	$order = $db->row("SELECT * FROM orders WHERE orderid='".addslashes($orderid)."'");
	if ($order['status'] == $status && !$calculate_processed)
		return;

	$order_info = func_orderinfo($orderid);

	if (($status == 2 || $status == 3) && !$order_info['order']['gc_generated']) {
		$userid = $order_info['order']['userid'];
		foreach ($order_info['products'] as $k=>$v) {
			if ($v['gift_card']) {
				$db->query("INSERT INTO gift_cards SET gcid='".addslashes($v['gift_card'])."', userid='".$userid."', amount='".$v['price']."', amount_left='".$v['price']."', date='".time()."', status='Y'");
			}
		}

		$db->query("UPDATE orders SET gc_generated=1 WHERE orderid='".addslashes($orderid)."'");
		$order_info['order']['gc_generated'] = 1;
	}

	$order = array_merge($order, $order_info['order']);
	$old_status = $order['status'];
	$order['status'] = $status;
	$db->query("UPDATE orders SET status=".$status." WHERE orderid='".addslashes($orderid)."'");
	// Sync status to Dolibarr llx_commande
	$_dol_status_map = [1=>0, 2=>1, 3=>2, 4=>3, 5=>-1];
	if (isset($_dol_status_map[$status])) {
		$db->query("UPDATE llx_commande SET fk_statut=".$_dol_status_map[$status]." WHERE ref_ext='SPACART-".intval($orderid)."' AND entity=1");
	}
	$subject = $company_name.' :: Order status change #'.$orderid.' to "'.$order_statuses[$status].'"';

	$order_info = func_orderinfo($orderid);
	$products = $order_info['products'];
	if ($calculate_processed || (($status == 2 || $status == 3) && in_array($old_status, array(0, 1, 4)))) {
		func_decrease_quantity($orderid);
	} elseif ($status == 4 && in_array($old_status, array(2, 3))) {
		func_increase_quantity($orderid);
	}

	if (!$calculate_processed) {
		$template['order'] = $order;
		$template['products'] = $products;
		$template['is_mail'] = 'Y';
		$message = get_template_contents('invoice/body.php');
		func_mail($order['firstname'].' '.$order['lastname'], $order['email'], $config['Company']['orders_department'], $subject, $message);
	}
}

function func_orderinfo($orderid) {
	global $db;

	q_load('product');

	$order = $db->row("SELECT * FROM orders WHERE orderid='".addslashes($orderid)."'");
	if (!$order)
		return false;

	$order['tax_details'] = unserialize($order['tax_details']);

	$payment_method = $db->row("SELECT * FROM payment_methods WHERE paymentid='$order[paymentid]'");
	$order['payment_method'] = $payment_method['name'];
	$order['payment_details'] = $payment_method['details'];

	$shipping_method = $db->row("SELECT * FROM shipping WHERE shippingid='$order[shippingid]'");
	$order['shipping_method'] = $shipping_method;
	if ($order['local_pickup'])
		$order['warehouse'] = $db->row("SELECT * FROM warehouses WHERE wid='$order[wid]'");

	$order['countryname'] = $db->field("SELECT country FROM countries WHERE code='".addslashes($order['country'])."'");
	$order['statename'] = $db->field("SELECT state FROM states WHERE code='".addslashes($order['state'])."' AND country_code='".addslashes($order['country'])."'");
	if (!$order['statename'])
		$order['statename'] = $order['state'];

	$order['b_countryname'] = $db->field("SELECT country FROM countries WHERE code='".addslashes($order['b_country'])."'");
	$order['b_statename'] = $db->field("SELECT state FROM states WHERE code='".addslashes($order['b_state'])."' AND country_code='".addslashes($order['b_country'])."'");
	if (!$order['b_statename'])
		$order['b_statename'] = $order['b_state'];

	$products = $db->all("SELECT * FROM order_items WHERE orderid='".$order['orderid']."'");
	foreach ($products as $k=>$v) {
		$v = array_merge($v, unserialize($v['extra']));
		if (!$v['gift_card'])
			$v = array_merge(func_select_product($v['productid'], $v['variantid'], $v['amount']), $v);

		$products[$k] = $v;
	}

	$result = array(
		'order' => $order,
		'products' => $products
	);

	return $result;
}

function func_decrease_quantity($orderid) {
	global $db;

	$order = $db->row("SELECT * FROM orders WHERE orderid='$orderid'");
	if (!$order['gc_calc']) {
		$gc = $db->row("SELECT * FROM gift_cards WHERE gcid='".addslashes($order['gift_card'])."'");
		$db->query("UPDATE gift_cards SET amount_left='".($gc['amount_left'] - $order['gc_discount'])."' WHERE gcid='".addslashes($order['gift_card'])."'");
		$db->query("UPDATE orders SET gc_calc=1 WHERE orderid='".$orderid."'");
	}

	global $warehouse_enabled;
	$products = $db->all("SELECT * FROM order_items WHERE orderid='".$orderid."'");
	foreach ($products as $k=>$v) {
		// Skip stock updates for services (product_type=1)
		$pt = $db->field("SELECT fk_product_type FROM llx_product WHERE rowid='".$v['productid']."'");
		if ($pt == 1) continue;
		$extra = unserialize($v['extra']);
		if ($order['local_pickup'] && $order['wid']) {
			if ($extra['variantid']) {
				$avail = $db->field("SELECT avail FROM product_inventory WHERE rowid='".$v['productid']."' AND variantid='".$extra['variantid']."' AND wid='".$order['wid']."'");
				$new_avail = $avail - $v['quantity'];
				$db->query("UPDATE product_inventory SET avail=".$new_avail." WHERE variantid='".$extra['variantid']."' AND productid=".$v['productid']." AND wid='".$order['wid']."'");
			} else {
				$avail = $db->field("SELECT avail FROM product_inventory WHERE rowid='".$v['productid']."' AND variantid=0 AND wid='".$order['wid']."'");
				$new_avail = $avail - $v['quantity'];
				$db->query("UPDATE product_inventory SET avail=".$new_avail." WHERE variantid=0 AND productid=".$v['productid']." AND wid='".$order['wid']."'");
			}
		} elseif ($warehouse_enabled) {
			if ($extra['variantid']) {
				$avail_block = $db->field("SELECT avail_block FROM variants WHERE variantid='".$extra['variantid']."'");
				$new_avail = $avail_block + $v['quantity'];
				$db->query("UPDATE variants SET avail_block=".$new_avail." WHERE variantid='".$extra['variantid']."'");
			} else {
				$avail_block = $db->field("SELECT avail_block FROM products WHERE rowid='".$v['productid']."'");
				$new_avail = $avail_block + $v['quantity'];
				// Dolibarr stock now handled by spacart_sync_stock_movements()
			}
		} elseif ($extra['variantid']) {
			$avail = $db->field("SELECT avail FROM variants WHERE variantid='".$extra['variantid']."'");
			$new_avail = $avail - $v['quantity'];
			$db->query("UPDATE variants SET avail=".$new_avail." WHERE variantid='".$extra['variantid']."'");
		} else {
			$avail = $db->field("SELECT avail FROM products WHERE rowid='".$v['productid']."'");
			$new_avail = $avail - $v['quantity'];
			// Dolibarr stock now handled by spacart_sync_stock_movements()
		}
	}

	// Sync stock movements to Dolibarr (idempotent - safe if already done by sale chain)
	if (function_exists('spacart_sync_stock_movements')) {
		try { spacart_sync_stock_movements($orderid); } catch (Exception $e) {}
	}
}

function func_increase_quantity($orderid) {
	global $db, $userinfo, $login;

	$order = $db->row("SELECT * FROM orders WHERE orderid='$orderid'");
	if ($order['gc_calc'] == '1') {
		$gc = $db->row("SELECT * FROM gift_cards WHERE gcid='".addslashes($order['gift_card'])."'");
		$db->query("UPDATE gift_cards SET amount_left='".($gc['amount_left'] + $order['gc_discount'])."' WHERE gcid='".addslashes($order['gift_card'])."'");
		$db->query("UPDATE orders SET gc_calc=0 WHERE orderid='".$orderid."'");
	}

	$products = $db->all("SELECT * FROM order_items WHERE orderid='".$orderid."'");
	foreach ($products as $k=>$v) {
		// Skip stock updates for services (product_type=1)
		$pt = $db->field("SELECT fk_product_type FROM llx_product WHERE rowid='".$v['productid']."'");
		if ($pt == 1) continue;
		$extra = unserialize($v['extra']);
		if ($extra['variantid']) {
			$avail = $db->field("SELECT avail FROM variants WHERE variantid='".$extra['variantid']."'");
			$new_avail = $avail + $v['quantity'];
			$db->query("UPDATE variants SET avail=".$new_avail." WHERE variantid='".$extra['variantid']."'");
		} else {
			$avail = $db->field("SELECT avail FROM products WHERE rowid='".$v['productid']."'");
			$new_avail = $avail + $v['quantity'];
			// Dolibarr stock now handled by spacart_reverse_stock_movements()
		}
	}

	// Reverse stock movements in Dolibarr (for cancellations)
	if (function_exists('spacart_reverse_stock_movements')) {
		try { spacart_reverse_stock_movements($orderid); } catch (Exception $e) {}
	}
}