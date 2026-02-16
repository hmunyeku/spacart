<?php
$orderid = addslashes($get['1']);
$order = $db->row("SELECT * FROM orders WHERE orderid='".$orderid."' AND stripe_key='".addslashes($get['2'])."' AND stripe_processed='0'");
if (!$order) {
	redirect('home');
}

$_SESSION['cart'] = '';
q_load('order');

require_once(SITE_ROOT . '/stripe/init.php');

// ---- Dolibarr bridge: try Dolibarr STRIPE keys if SpaCart has test/empty keys ----
$stripe_skey = $db->field("SELECT param1 FROM payment_methods WHERE paymentid=7");
$_test_prefixes = array('sk_test_', '');
$_is_demo_key = empty($stripe_skey);
if (!$_is_demo_key) {
	foreach ($_test_prefixes as $_pfx) {
		if ($_pfx !== '' && strpos($stripe_skey, $_pfx) === 0) {
			$_is_demo_key = true;
			break;
		}
	}
}
// If SpaCart key looks like test/demo AND Dolibarr has live Stripe keys, prefer Dolibarr's
if ($_is_demo_key) {
	$_dol_stripe_live = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_LIVE' AND value='1' AND entity IN (0,1) LIMIT 1");
	if ($_dol_stripe_live) {
		$_dol_skey = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
		if (empty($_dol_skey)) {
			$_dol_skey = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
		}
		if (!empty($_dol_skey)) {
			$stripe_skey = $_dol_skey;
		}
	} else {
		// Try Dolibarr test keys
		$_dol_skey = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY' AND value != '' AND entity IN (0,1) LIMIT 1");
		if (!empty($_dol_skey)) {
			$stripe_skey = $_dol_skey;
		}
	}
}
unset($_test_prefixes, $_is_demo_key, $_dol_stripe_live, $_dol_skey);
// ---- End Dolibarr bridge ----

\Stripe\Stripe::setApiKey($stripe_skey);

try {
	$stripe_session = \Stripe\Checkout\Session::retrieve(
	  $order['stripe_session']
	);
} catch (\Stripe\Exception\ApiErrorException $e) {
	error_log("SpaCart Stripe: session retrieve error for order #" . $orderid . ": " . $e->getMessage());
	redirect('/invoice/'.$orderid.'/failed');
}

// Verify payment status
if ($stripe_session->payment_status !== 'paid') {
	error_log("SpaCart Stripe: order #" . $orderid . " session not paid (status: " . $stripe_session->payment_status . ")");
	redirect('/invoice/'.$orderid.'/failed');
}

$payment_indent = '';
$transaction_id = '';

if ($stripe_session->payment_intent) {
	try {
		$payment_intent = \Stripe\PaymentIntent::retrieve(
		  $stripe_session->payment_intent
		);

		$payment_indent = $stripe_session->payment_intent;
		// Stripe API v2+ uses latest_charge instead of charges.data
		if (!empty($payment_intent->latest_charge)) {
			$charge = \Stripe\Charge::retrieve($payment_intent->latest_charge);
			$transaction_id = $charge->balance_transaction;
		} elseif (!empty($payment_intent['charges']['data'][0])) {
			$transaction_id = $payment_intent['charges']['data'][0]->balance_transaction;
		}
	} catch (\Stripe\Exception\ApiErrorException $e) {
		error_log("SpaCart Stripe: payment intent retrieve error for order #" . $orderid . ": " . $e->getMessage());
		// Continue anyway — the session was paid
	}
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

// Trigger Dolibarr sale chain after successful Stripe payment
if (function_exists('spacart_complete_sale_chain')) {
	try {
		spacart_complete_sale_chain($orderid);
	} catch (Exception $e) {
		error_log("SpaCart: Stripe sale chain error for order #" . $orderid . ": " . $e->getMessage());
	}
}

redirect('/invoice/'.$orderid.'/success');
