<?php
if (!$login) {
	redirect('/register');
}

q_load('user');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// SEC-CRIT-6: CSRF token verification
	if (!spacart_csrf_verify()) {
		if ($is_ajax) {
			header("X-CSRF-Token: " . spacart_csrf_token());
			exit("X");
		}
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Session expired. Please try again.")
		);
		redirect("/profile");
	}

	extract($_POST, EXTR_SKIP);
	extract($posted_data, EXTR_SKIP);
	$update = array(
		'pending_membershipid'	=> $pending_membershipid,
		'firstname'		=> $firstname,
		'lastname'		=> $lastname,
		'address'		=> $address,
		'city'			=> $city,
		'state'			=> $state,
		'country'		=> $country,
		'zipcode'		=> $zipcode,
		'phone'			=> $phone,
	);

	if ($password) {
		$salt = generateSalt();
		$update['password'] = spacart_password_hash($password);
		$update['salt'] = $salt;
	}

	if ($db->field("SELECT COUNT(*) as cnt FROM users WHERE id<>'".$login."' AND email='".addslashes($email)."'")) {
		if ($is_ajax)
			exit('Email');
		else
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> 'This email already registered'
			);
	} else {
		$update['email'] = $email;
		if (!$is_ajax)
			$_SESSION['alerts'][] = array(
				'type'		=> 'i',
				'content'	=> 'You updated your profile'
			);
	}

	$db->array2update('users', $update, "id='".$login."'");
	if ($is_ajax)
		exit;

	redirect('/profile');
}

$template['section'] = $get['1'];

if ($get['1'] == 'orders') {
	$template['orders'] = $db->all("SELECT * FROM orders WHERE userid=".$login." ORDER BY orderid DESC");
} else {
	$template['user'] = $userinfo;
	$template['js'][] = 'states';
}

if (false && $is_ajax) {
	exit(get_template_contents('profile/body.php'));
} else {
	$template['memberships'] = $db->all("SELECT * FROM memberships WHERE active='Y' ORDER BY orderby, membership");
	$template['head_title'] = 'Profile. '.$template['head_title'];
	$template['page_profile'] = 'Y';
	$template['page'] = get_template_contents('profile/body.php');
	$template['css'][] = 'register';
	$template['js'][] = 'register';
	$template['is_profile_page'] = true;
}

if ($is_ajax && !$_GET['its_ajax_page']) {
	$result = array($template['page'], $page_title, $template['bread_crumbs_html'], $get['0'], $template['parentid']);
	exit(json_encode($result));
}