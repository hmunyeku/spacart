<?php
q_load('user');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'delete' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM users WHERE id=$k");
		}
	} elseif ($mode == 'update' && !empty($status)) {
		foreach ($status as $k=>$v)
			$db->query("UPDATE users SET status='".$v."' WHERE id='".$k."'");
	} elseif ($get['2'] == 'search') {
		if ($_POST['substring'] && (!$_POST['email'] && !$_POST['firstname'] && !$_POST['lastname']))
			$_POST['email'] = $_POST['firstname'] = $_POST['lastname'] = 1;

		$_SESSION['users_search'] = $_POST;
	}

	redirect('/admin/users/search');
}

if ($get['2'] == 'search') {
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/users">'.lng('Users management').'</a>';
	$template['location'] .= ' &gt; '.lng('Search results').'';
	$data = $templage['users_search'] = $_SESSION['users_search'];
	$conditions = array();
	if (!empty($data)) {
		foreach ($data as $k=>$v)
			$data[$k] = addslashes($v);

		if ($data['substring']) {
			$condition = array();
			if ($data['email'])
				$condition[] = "email LIKE '%".$data['substring']."%'";

			if ($data['firstname'])
				$condition[] = "firstname LIKE '%".$data['substring']."%'";

			if ($data['lastname'])
				$condition[] = "firstname LIKE '%".$data['substring']."%'";

			$conditions[] = implode(" OR ", $condition);
		}

		if ($data['status'] || $data['status'] == '0')
			$conditions[] = "status='".$data['status']."'";

		if ($data['usertype'])
			$conditions[] = "usertype='".$data['usertype']."'";

		if ($data['pending_membership'])
			$conditions[] = "pending_membershipid>0";

		if ($data['membershipid'])
			$conditions[] = "membershipid='".$data['membershipid']."'";
	}

	if (!empty($conditions))
		$search_condition = " WHERE ".implode(' AND ', $conditions);
	else
		$search_condition = "";

	$total_items = $db->field("SELECT COUNT(*) FROM users ".$search_condition);
	if ($total_items > 0) {
		$objects_per_page = 20;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/users/search?";
		$users = $db->all("SELECT * FROM users ".$search_condition." ORDER BY firstname, lastname, email LIMIT $first_page, $objects_per_page");
	}

	if ($users)
		$template['users'] = $users;
	else {
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'No users found'
		);
		redirect('/admin/users');
	}
} else {
	$template['location'] .= ' &gt; '.lng('Users management');
	$template["memberships"] = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");
}

$template['head_title'] = lng('Users management').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/users.php');

$template['css'][] = 'admin_users';