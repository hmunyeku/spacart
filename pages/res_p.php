<?php
if (isset($_GET['e'])) {
	// Always show the same response regardless of whether user exists (prevent user enumeration)
	echo '<button>'.lng('Restore').'</button>';
	exit;
}

if (isset($_GET['r'])) {
	// SEC-CRIT-5: Rate limiting on password reset (3 attempts per hour)
	if (!function_exists('spacart_check_rate_limit')) {
		require_once SITE_ROOT . '/includes/func/func.security.php';
	}
	if (!spacart_check_rate_limit('password_reset', 3, 3600)) {
		exit(lng('If an account exists with this email, password reset instructions have been sent.'));
	}
	spacart_record_attempt('password_reset');

	$email = addslashes($_GET['r']);
	$user = $db->row("SELECT id, firstname, lastname FROM users WHERE email='".$email."'");
	if ($user) {
		$salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCD!@#^&*%()*@#(*%#%)-=.,;][EFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
		$key = md5($salt.$id.$_SERVER['REMOTE_ADDR'].$salt.time().rand(0,100).$salt);
		$db->query("UPDATE users SET password_key='".$key."' WHERE id=".intval($user['id']));
		$subject = lng('Your password');
		$template['user'] = $user;
		$template['key'] = $key;
		$message = get_template_contents('mail/restore_password.php');
		func_mail($user['firstname'].' '.$user['lastname'], $email, '', $subject, $message);
	}

	// Always return same generic message (prevent user enumeration)
	exit(lng('If an account exists with this email, password reset instructions have been sent.'));
}