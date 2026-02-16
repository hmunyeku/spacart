<?php
// CSRF check for POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (!spacart_csrf_verify()) {
		http_response_code(403);
		exit("Invalid CSRF token");
	}
}

$email = isset($_POST["email"]) ? trim($_POST["email"]) : (isset($_GET["email"]) ? trim($_GET["email"]) : "");
$title = isset($_POST["title"]) ? $_POST["title"] : (isset($_GET["title"]) ? $_GET["title"] : "");
$name  = isset($_POST["name"])  ? $_POST["name"]  : (isset($_GET["name"])  ? $_GET["name"]  : "");

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	exit(lng("Invalid email address"));
}

$email_escaped = addslashes($email);

if ($db->field("SELECT COUNT(*) FROM subscribers WHERE email='" . $email_escaped . "'")) {
	exit(lng("You are already subscribed"));
} else {
	$insert = array(
		"title"  => $title,
		"name"   => $name,
		"email"  => $email,
		"date"   => time()
	);

	$db->array2insert("subscribers", $insert);

	exit(lng("Thank you for your subscription"));
}
