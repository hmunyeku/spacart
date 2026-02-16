<?php
if ($_GET['phone']) {
	extract($_GET, EXTR_SKIP);
	if ($_SESSION['b1c_sent']['time'] > time() - 10) {
		exit('N');
	}

	$product = $db->row("SELECT * FROM products WHERE productid='$productid'");
	$template['phone'] = $phone;
	$template['product'] = $product;
	$template['user'] = $_SERVER;
	$message = get_template_contents('mail/buy1click.php');
	$subject = $config['Company']['company_name'].': '.lng('Buy with one click');
	func_mail($config['Company']['company_name'], $config['Company']['orders_department'], '', $subject, $message, $config['Company']['orders_department']);
	$_SESSION['b1c_sent'] = array(
		'id'	=> $productid,
		'time'	=> time()
	);
}

exit;