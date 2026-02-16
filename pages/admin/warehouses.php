<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'delete') {
		if ($to_delete)
			foreach ($to_delete as $k=>$v) {
				$db->query("DELETE FROM warehouses WHERE wid='$k'");
			}
	} else {
		if ($posted_data) {
			foreach ($posted_data as $k=>$v) {
				$v['enabled'] = $v['enabled'] ? 1 : 0;
				$db->array2update("warehouses", $v, "wid='$k'");
			}
		}

		if ($add_warehouse['title']) {
			$db->array2insert("warehouses", $add_warehouse);
		}
	}

	redirect("/admin/warehouses");
}

$template['location'] .= ' &gt; Warehouses ';
$total_items = $db->field("SELECT COUNT(*) FROM warehouses");
if ($total_items > 0) {
	$objects_per_page = 100;
	require SITE_ROOT."/includes/navigation.php";
	$template["navigation_script"] = $current_location."/admin/warehouses?";
	$warehouses = $db->all("SELECT * FROM warehouses ORDER BY pos LIMIT $first_page, $objects_per_page");
	$template["warehouses"] = $warehouses;
}

$template['new_pos'] = $db->field("SELECT MAX(pos) FROM warehouses") + 10;
$template['page'] = get_template_contents('admin/pages/warehouses.php');

$template['head_title'] = 'Warehouses :: '.$template['head_title'];

$template['js'][] = 'admin_warehouses';
$template['css'][] = 'admin_warehouses';
