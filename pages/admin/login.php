<?php
q_load("user");
if ($login && $userinfo["usertype"] == "A") {
	redirect("/admin");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	extract($_POST, EXTR_SKIP);
	if ($_SESSION["token"] != $token) {
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Invalid form token. Please, try once again.")
		);

		redirect("/admin/login");
	}

	// SEC-CRIT-5: Rate limiting
	if (!spacart_check_rate_limit("admin_login", 5, 900)) {
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Too many login attempts. Please try again later.")
		);
		redirect("/admin/login");
	}

	$user = $db->row("SELECT * FROM users WHERE email='".addslashes($email)."' AND status=1");
	if (!spacart_password_verify($password, $user["password"], $user["salt"], $user["id"]))
		$user = array();

	if ($user) {
		func_login($user["id"]);
		if ($login_redirect)
			redirect($login_redirect);
		else
			redirect("/admin");
	} else {
		// SEC-CRIT-5: Record failed attempt
		spacart_record_attempt("admin_login");
		// SEC-CRIT-7: Unified error - no user enumeration
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("login_incorrect")
		);
		redirect("/admin");
	}
}

$template["js"][] = "admin_login";
$template["css"][] = "admin_login";
$template["token"] = $_SESSION["token"] = md5(time().rand(0,10000));
$template["page"] = get_template_contents("admin/pages/login.php");
