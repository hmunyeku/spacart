<?php
if ($_SERVER['REQUEST_METHOD'] != 'POST')
	exit;

$orderid = $get['1'];
// STEP 1: read POST data

// Reading POSTed data directly from $_POST causes serialization issues with array data in the POST.
// Instead, read raw POST data from the input stream.
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();
foreach ($raw_post_array as $keyval) {
	$keyval = explode ('=', $keyval);
	if (count($keyval) == 2)
		$myPost[$keyval[0]] = urldecode($keyval[1]);
}

// read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
$req = 'cmd=_notify-validate';
if (function_exists('get_magic_quotes_gpc')) {
	$get_magic_quotes_exists = true;
}

foreach ($myPost as $key => $value) {
	if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
		$value = urlencode(stripslashes($value));
	} else {
		$value = urlencode($value);
	}

	$req .= "&$key=$value";
}


// Step 2: POST IPN data back to PayPal to validate

$payment_method = $db->row("SELECT * FROM payment_methods WHERE paymentid=8");
if ($payment_method['live'])
	$url = 'https://www.paypal.com/cgi-bin/webscr';
else
	$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close', 'User-Agent: OwOhHo'));

// In wamp-like environments that do not come bundled with root authority certificates,
// please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set
// the directory path of the certificate as shown below:
// curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
if (!($res = curl_exec($ch))) {
	$fp = fopen(SITE_ROOT.'/var/log/paypal_ipn_error.log', 'w');
	fputs($fp, date('m.d.Y h:i:s')."\n"."Got " . curl_error($ch) . " when processing IPN data\n\n");
	fclose($fp);
    curl_close($ch);
    exit;
}

$fp = fopen(SITE_ROOT.'/var/log/paypal_ipn.log', 'a+');
fputs($fp, date('m.d.Y h:i:s')."\n"."Order #".$orderid.': '.$res."\n\n");

if ($res == 'VERIFIED') {
	fputs($fp, "Order #".$orderid.". Status updated.\n\n");
	
	q_load('order');
	$orderinfo = func_orderinfo($orderid);
	$order = $orderinfo['order'];
	if ($order['total'] != $myPost['mc_gross'])
		exit;

	order_status($orderid, 2);
	$subject = $company_name.': '.lng('Order').' #'.$orderid.' '.$order_statuses[2];
	$orderinfo = func_orderinfo($orderid);
	$template['order'] = $order = $orderinfo['order'];
	$template['products'] = $orderinfo['products'];
	$template['is_mail'] = 'Y';
	$message = get_template_contents('invoice/body.php');
	func_mail($order['firstname'].' '.$order['lastname'], $order['email'], $config['Company']['orders_department'], $subject, $message);
	func_mail($config['Company']['company_name'], $config['Company']['orders_department'], $order['email'], $subject, $message);
} else {
}

fclose($fp);
curl_close($ch);
exit;
