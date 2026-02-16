<?php
q_load("user");
if ($login) {
	if ($is_ajax)
		exit(lng("You are already logged in. Please, refresh the page"));

	redirect("/");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	extract($_POST, EXTR_SKIP);

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
		redirect("/login");
	}

	// SEC-CRIT-5: Rate limiting
	if (!spacart_check_rate_limit("login", 5, 900)) {
		if ($is_ajax) {
			header("X-CSRF-Token: " . spacart_csrf_token());
			exit("R");
		}
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Too many login attempts. Please try again later.")
		);
		redirect("/login");
	}

	$user = $db->row("SELECT * FROM users WHERE email='".addslashes($email)."' AND status=1");

	if (!spacart_password_verify($password, $user["password"], $user["salt"], $user["id"]))
		$user = array();

	if ($user) {
		func_login($user["id"]);
		if ($is_ajax) {
			header("X-CSRF-Token: " . spacart_csrf_token());
			exit("G");
		}

		redirect($_SERVER["HTTP_REFERER"], 1);
	} else {
		// SEC-CRIT-5: Record failed attempt
		spacart_record_attempt("login");
		// SEC-CRIT-7: Unified error - no user enumeration
		if ($is_ajax) {
			header("X-CSRF-Token: " . spacart_csrf_token());
			exit("F");
		}

		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("login_incorrect")
		);
		redirect("/login");
	}
}

if ($is_ajax && !$_GET["its_ajax_page"]) {
	exit(get_template_contents("login/body.php"));
} else {
	$template["page"] = get_template_contents("login/body.php");
	$template["is_login_page"] = true;
}
