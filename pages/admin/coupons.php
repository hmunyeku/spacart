<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'delete') {
		if ($to_delete)
			foreach ($to_delete as $k=>$v) {
				$k = addslashes($k);
				if (empty($k))
					$db->query("DELETE FROM coupons WHERE coupon=''");
				else
					$db->query("DELETE FROM coupons WHERE coupon='$k'");
			}
	} else {
		if ($posted_data) {
			foreach ($posted_data as $k=>$v) {
				$k = addslashes($k);
				$v['per_customer'] = $v['per_customer'] ? $v['per_customer'] : 0;
				$db->array2update("coupons", $v, "coupon='$k'");
			}
		}

		if ($add_coupon['coupon']) {
			$add_coupon['coupon'] = addslashes($add_coupon['coupon']);
			$exists = $db->field("SELECT COUNT(*) FROM coupons WHERE coupon='$add_coupon[coupon]'");
			if ($exists)
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> 'This coupon already exists'
				);
			else
				$db->array2insert("coupons", $add_coupon);
		}
	}

	redirect("/admin/coupons");
}

$template['location'] .= ' &gt; Coupons ';
$total_items = $db->field("SELECT COUNT(*) FROM coupons");
if ($total_items > 0) {
	$objects_per_page = 30;
	require SITE_ROOT."/includes/navigation.php";
	$template["navigation_script"] = $current_location."/admin/coupons?";
	$coupons = $db->all("SELECT * FROM coupons ORDER BY coupon LIMIT $first_page, $objects_per_page");
	$template["coupons"] = $coupons;
}

$template['page'] = get_template_contents('admin/pages/coupons.php');
$template['head_title'] = 'Coupons :: '.$template['head_title'];
$template['css'][] = 'admin_coupons';
