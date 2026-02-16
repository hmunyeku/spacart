<?php
// CSRF check
if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (!spacart_csrf_verify()) {
		http_response_code(403);
		exit("Invalid CSRF token");
	}
}

if ($_GET["phone"]) {
	$productid = intval($_GET["productid"]);
	$phone = addslashes($_GET["phone"]);

	if ($_SESSION["b1c_sent"]["time"] > time() - 10) {
		exit("N");
	}

	$product = $db->row("SELECT * FROM products WHERE productid='" . intval($productid) . "'");
	$template["phone"] = $phone;
	$template["product"] = $product;
	$template["user"] = $_SERVER;
	$message = get_template_contents("mail/buy1click.php");
	$subject = $config["Company"]["company_name"] . ": " . lng("Buy with one click");
	func_mail($config["Company"]["company_name"], $config["Company"]["orders_department"], "", $subject, $message, $config["Company"]["orders_department"]);
	$_SESSION["b1c_sent"] = array(
		"id"   => $productid,
		"time" => time()
	);
}

exit;
