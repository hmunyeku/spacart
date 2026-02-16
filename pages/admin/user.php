<?php
q_load('user');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($password) {
		$salt = generateSalt();
		$posted_data['password'] = spacart_password_hash($password);
		$posted_data['salt'] = $salt;
	}

	if ($get['2'] == 'new') {
		$insert = array(
			'registration_date'			=> time()
		);

		$id = $db->array2insert('users', $insert);
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'New user has been successfully added'
		);
	} else {
		$id = $get['2'];
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'User has been successfully saved'
		);
	}

	if (!$root_admin) {
		unset($posted_data['usertype']);
		unset($posted_data['roleid']);
	}

	$db->array2update("users", $posted_data, "id='".$id."'");

	redirect('/admin/user/'.$id);
}

$template["memberships"] = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");

if ($get['2'] == 'new') {
	$user = $_SESSION['new_user'];
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/users">'.lng('Users management').'</a>';
	$template['location'] .= ' &gt; New user';
	$template['head_title'] = lng('New user').' :: '.$template['head_title'];
} else {
	$user = $db->row("SELECT * FROM users WHERE id='".$get['2']."'");
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/users">'.lng('Users management').'</a>';
	$template['location'] .= ' &gt; '.$user['firstname'].' '.$user['lastname'];
	$template['head_title'] = lng('User').' '.$user['firstname'].' '.$user['lastname'].' :: '.$template['head_title'];
}

$template['user'] = $user;

$template["roles"] = $db->all("SELECT * FROM roles ORDER BY pos");
$template['page'] = get_template_contents('admin/pages/user.php');

$template['css'][] = 'admin_user';
$template['js'][] = 'states';