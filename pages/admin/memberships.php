<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'update' && !empty($posted_data)) {
	# Update memberships
		foreach ($posted_data as $id => $v) {
			$v['active'] = $v['active'];
			$db->array2update("memberships", $v, "membershipid = '$id'");
		}
	} elseif ($mode == 'add' && !empty($add['membership'])) {
	# Add membership
		if (empty($add['orderby']))
			$add['orderby'] = $db->field("SELECT MAX(orderby) FROM memberships") + 1;

		$add['active'] = $add['active'];
		$db->array2insert("memberships", $add);
	} elseif ($mode == 'delete' && !empty($to_delete)) {
	# Delete memerbship(s)
		$delete_string = "membershipid IN ('".implode("','", $to_delete)."')";
		$db->query("DELETE FROM memberships WHERE ".$delete_string);
		$db->query("DELETE FROM wholesale_prices WHERE ".$delete_string);
		$db->array2update("users", array("membershipid" => 0), $delete_string);
		$db->array2update("users", array("pending_membershipid" => 0), "pending_".$delete_string);
	}

	redirect('/admin/memberships');
}

$template["memberships"] = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");

$template['location'] .= ' &gt; '.lng('Membership levels');
$template['head_title'] = lng('Membership levels').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/memberships.php');
$template['css'][] = 'admin_memberships';
$template['js'][] = 'admin_memberships';
