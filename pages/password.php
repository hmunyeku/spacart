<?php
$get = array_map('addslashes', $get);
if (!$get['2'])
	redirect('/');

q_load('user');
$id = $db->field("SELECT id FROM users WHERE id='".$get['1']."' AND password_key='".$get['2']."'");
if (!$id) {
	$_SESSION['alerts'][] = array(
		'type'		=> 'e',
		'content'	=> 'Link to change password is incorrect.'
	);

	redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// SEC-CRIT-6: CSRF token verification
	if (!spacart_csrf_verify()) {
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Session expired. Please try again.")
		);
		redirect("/");
	}

	extract($_POST, EXTR_SKIP);
	$salt = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCD!@#^&&*%*($)*@#($%*#%)-=.,;\'][EFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8 );
	$key = md5(time().rand(0, 100).$_SERVER['REMOTE_ADDR'].$salt.rand(0,100));
	$db->query("UPDATE users SET salt='".addslashes($salt)."', password='".spacart_password_hash($new_pswd)."', password_key='".$key."' WHERE id='".$id."'");

	// SEC-CRIT-3: Regenerate session ID on login to prevent session fixation
	session_regenerate_id(true);
	$_SESSION['login'] = $id;
	$salt = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCD!@#^&&*%*($)*@#($%*#%)-=.,;\'][EFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8);
	$pswd = md5($salt.$id.$_SERVER['REMOTE_ADDR'].$salt.time().rand(0,100).$salt);
	func_setcookie('remember', $pswd);
	$db->query("REPLACE INTO users_remember SET userid=".$id.", pswd='".addslashes($pswd)."'");

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> 'Password has been changed.'
	);

	redirect('/');
}

$template['page'] = get_template_contents('register/password.php');
$template['css'][] = 'password';
$template['js'][] = 'password';
