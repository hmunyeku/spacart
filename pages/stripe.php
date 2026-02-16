<?php
$orderid = addslashes($get['1']);
$order = $db->row("SELECT * FROM orders WHERE orderid='".$orderid."' AND stripe_key='".addslashes($get['2'])."' AND stripe_processed='0'");
if (!$order) {
	redirect('home');
}

$_SESSION['cart'] = '';
q_load('order');

require_once(SITE_ROOT . '/stripe/init.php');
$stripe_skey = $db->field("SELECT param1 FROM payment_methods WHERE paymentid=7");
\Stripe\Stripe::setApiKey($stripe_skey);

$stripe_session = \Stripe\Checkout\Session::retrieve(
  $order['stripe_session']
);

$payment_indent = '';
$transaction_id = '';

if ($stripe_session->payment_intent) {
	$payment_intent = \Stripe\PaymentIntent::retrieve(
	  $stripe_session->payment_intent
	);

	$payment_indent = $stripe_session->payment_intent;
	$transaction_id = $payment_intent['charges']['data'][0]->balance_transaction;
}

	$db->query("UPDATE orders SET stripe_processed='1', payment_indent='".addslashes($payment_indent)."', transaction_id='".addslashes($transaction_id)."' WHERE orderid='".$orderid."'");
	order_status($orderid, 2);
	$subject = $company_name.': '.lng('Order').' #'.$orderid.' '.$order_statuses[2];
	$orderinfo = func_orderinfo($orderid);
	$template['order'] = $order = $orderinfo['order'];
	$template['products'] = $orderinfo['products'];
	$template['is_mail'] = 'Y';
	$message = get_template_contents('invoice/body.php');
	func_mail($order['firstname'].' '.$order['lastname'], $order['email'], $config['Company']['orders_department'], $subject, $message);
	func_mail($config['Company']['company_name'], $config['Company']['orders_department'], $order['email'], $subject, $message);
redirect('/invoice/'.$orderid.'/success');