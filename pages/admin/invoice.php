<?php
q_load('order', 'product');
if ($_POST['tracking'] || $_POST['tracking_url']) {
	$db->query("UPDATE orders SET tracking='".addslashes($_POST['tracking'])."', tracking_url='".addslashes($_POST['tracking_url'])."' WHERE orderid='".addslashes($get['2'])."'");
	redirect('/admin/invoice/'.$get['2']);
}

if ($get['3'] == 'status') {
	order_status($get['2'], $get['4']);
	redirect('/admin/invoice/'.$get['2']);
}

$orderinfo = func_orderinfo(addslashes($get['2']));
if ($orderinfo) {
	$template['order'] = $order = $orderinfo['order'];

	foreach ($orderinfo['products'] as $k=>$v) {
		$variantid = $v['variantid'] ? $v['variantid'] : 0;
		$orderinfo['products'][$k]['warehouses'] = $db->all("SELECT w.*, i.avail, oi.spent FROM warehouses w LEFT JOIN product_inventory i ON i.variantid='".$variantid."' AND w.wid=i.wid AND i.productid='".$v['productid']."' LEFT JOIN order_item_inventory oi ON oi.wid=w.wid AND oi.itemid='".$v['itemid']."' WHERE w.enabled=1 ORDER BY w.pos");
	}

	$template['products'] = $orderinfo['products'];
	$template['location'] .= ' &gt; <a href="/admin/orders">'.lng('Orders').'</a> &gt; '.lng('Order invoice').' #'.$orderinfo['order']['orderid'];
	$template['head_title'] = lng('Order invoice').' #'.$orderinfo['order']['orderid'].' :: '.$template['head_title'];
} else
	redirect('/');

if ($_POST['update_wh']) {
	foreach ($_POST['update_wh'] as $itemid=>$whs) {
		foreach ($orderinfo['products'] as $k=>$v) {
			if ($v['itemid'] == $itemid) {
				$product = $v;
				break;
			}
		}

		$variantid = $product['variantid'] ? $product['variantid'] : 0;
		if ($variantid) {
			$variant = $db->row("SELECT * FROM variants WHERE variantid='".$product['variantid']."'");
		}

		if ($variantid) {
			$blocked = $variant['avail_block'];
		} else
			$blocked = $product['avail_block'];

		$unblock = 0;
		foreach ($whs as $k=>$v) {
			$unblock += $v;
			$inventory = $db->field("SELECT avail FROM product_inventory WHERE rowid='".$product['productid']."' AND wid='".$k."' AND variantid='".$variantid."'");
			$spent = $db->field("SELECT spent FROM order_item_inventory WHERE itemid='".$itemid."' AND wid='".$k."'");
			if ($spent) {
				$blocked += $spent;
				$inventory += $spent;
			} else {
			}

			$db->query("DELETE FROM order_item_inventory WHERE itemid='$itemid' AND wid='$k'");
			$db->query("UPDATE product_inventory SET avail='".($inventory-$v)."' WHERE rowid='$product[productid]' AND wid='$k' AND variantid='".$variantid."'");
			if ($v)
				$db->query("INSERT INTO order_item_inventory SET spent='$v', itemid='$itemid', wid='$k'");
		}

		if ($variantid)
			$db->query("UPDATE variants SET avail_block='".($blocked-$unblock)."' WHERE variantid='".$variantid."'");
		else
			$db->query("UPDATE llx_product SET stock='".($blocked-$unblock)."' WHERE rowid='".$product['productid']."'");
	}

	exit;
}

$template['admin_display'] = 1;
$template['page'] = get_template_contents('admin/pages/invoice.php');
$template['css'][] = 'admin_order_invoice';
$template['js'][] = 'admin_order_invoice';