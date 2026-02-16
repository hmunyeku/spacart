<?php
if ($login) {
	if ($is_ajax)
		exit(lng('You are already logged in. Please, refresh the page'));

	redirect('/profile');
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
		redirect("/register");
	}

	$rc_key = $config['General']['recaptcha_key'];
	$rc_skey = $config['General']['recaptcha_skey'];
	if ($rc_key && $rc_skey) {
		$postdata = http_build_query(
    		array(
        		'secret' => $rc_skey,
		        'response' => $_POST['g-recaptcha-response']
    		)
		);

		$opts = array('http' =>
		    array(
        		'method'  => 'POST',
		        'header'  => 'Content-type: application/x-www-form-urlencoded',
        		'content' => $postdata
		    )
		);

		$context  = stream_context_create($opts);

		$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
		if (strstr($result, 'false') || !$_POST['g-recaptcha-response']) {
			$_SESSION['user'] = $_POST;
			if ($is_ajax)
				exit('C');

			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Captcha is incorrect.')
			);

			redirect('/register');
			exit;
		}
	}

	extract($_POST, EXTR_SKIP);
	if ($db->field("SELECT COUNT(*) as cnt FROM users WHERE email='".addslashes($email)."'")) {
		$_SESSION['user'] = $_POST;
		if ($is_ajax)
	    	exit('E');

		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> 'Your  E-mail already registered.<br><a href="#" onclick="return login();">Login</a> or <a href="#" onclick="return restore_password()">recover password</a>'
		);

		redirect('/register');
	} else {
		$salt = generateSalt();
		$insert = array(
			'email'			=> $email,
			'pending_membershipid'	=> $pending_membershipid,
			'password'		=> spacart_password_hash($password),
			'firstname'		=> $firstname,
			'lastname'		=> $lastname,
			'address'		=> $address,
			'city'			=> $city,
			'state'			=> $state,
			'country'		=> $country,
			'zipcode'		=> $zipcode,
			'phone'			=> $phone,
			'status'		=> 1,
			'registration_date'	=> time(),
			'salt'			=> $salt
		);

		$userid = $db->array2insert('users', $insert);

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Your account was created successfully'
		);

		$template['userinfo'] = $insert;
		$message = get_template_contents('mail/registered.php');
		$subject = $config['Company']['company_name'].': '.lng('Registration completed');
		func_mail($insert['firstname'].' '.$insert['lastname'], $insert['email'], '', $subject, $message);

		func_login($userid);
    	$_SESSION['user'] = '';
		if ($is_ajax)
	    	exit('G');

		redirect('/');
	}
}

if (empty($login) && !empty($social_login)) {
	if (!empty($social_login['profile']['name']['givenName'])) {
		$userinfo['firstname'] = $social_login['profile']['name']['givenName'];
		$userinfo['lastname'] = $social_login['profile']['name']['familyName'];
	} else {
		$tmp = explode(" ", $social_login['profile']['displayName']);
		$userinfo['firstname'] = $tmp['0'];
		$userinfo['lastname'] = $tmp['1'];
	}

	$userinfo['email'] = $social_login['profile']['verifiedEmail'];
	$template['userinfo'] = $userinfo;
}

$template['memberships'] = $db->all("SELECT * FROM memberships WHERE active='Y' ORDER BY orderby, membership");
if ($is_ajax && !$_GET['its_ajax_page']) {
	exit(get_template_contents('register/body.php'));
} else {
	$template['page'] = get_template_contents('register/body.php');
	$template['is_register_page'] = true;
}