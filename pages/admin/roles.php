<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'update' && !empty($posted_data)) {
	# Update roles
		foreach ($posted_data as $id => $v) {
			$db->array2update("roles", $v, "roleid = '$id'");
		}
	} elseif ($mode == 'add' && !empty($_POST['title'])) {
		if (empty($_POST['pos']))
			$_POST['pos'] = $db->field("SELECT MAX(pos) FROM roles") + 1;

		$pages = '';
		foreach ($_POST['pages'] as $v)
			$pages .= '|'.$v.'|';

		$add = array(
			'title'	=> $_POST['title'],
			'pos'	=> $_POST['pos'],
			'pages'	=> $pages
		);
		$db->array2insert("roles", $add);
	} elseif ($mode == 'save' && !empty($_POST['title'])) {
		$pages = '';
		foreach ($_POST['pages'] as $v)
			$pages .= '|'.$v.'|';

		$update = array(
			'title'	=> $_POST['title'],
			'pos'	=> $_POST['pos'],
			'pages'	=> $pages
		);
		$db->array2update("roles", $update, "roleid='".addslashes($get['2'])."'");
	} elseif ($mode == 'delete' && !empty($to_delete)) {
	# Delete memerbship(s)
		$delete_string = "roleid IN ('".implode("','", $to_delete)."')";
		$db->query("DELETE FROM roles WHERE ".$delete_string);
		$db->array2update("users", array("roleid" => 0), $delete_string);
	}

	redirect('/admin/roles');
}

$template['head_title'] = lng('Roles').' :: '.$template['head_title'];

if ($get['2']) {
	$template['location'] .= ' &gt; <a href="/admin/roles">'.lng('Roles').'</a>';
	if ($get['2'] == 'new') {
		$template['location'] .= ' &gt; '.lng('New role');
	} else {
		$role = $db->row("SELECT * FROM roles WHERE roleid='".addslashes($get['2'])."'");
		$tmp = explode('|', $role['pages']);
		$pages = array();
		foreach ($tmp as $v) {
			if ($v == '|' || !$v)
				continue;

			$pages[] = $v;
		}

		$role['pages'] = $pages;
		$template["role"] = $role;
	}

	$template['page'] = get_template_contents('admin/pages/role_edit.php');
} else {
	$template["roles"] = $db->all("SELECT * FROM roles ORDER BY pos");
	$template['location'] .= ' &gt; '.lng('Roles');
	$template['page'] = get_template_contents('admin/pages/roles.php');
}

$template['css'][] = 'admin_roles';
$template['js'][] = 'admin_roles';
