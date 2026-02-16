<?php
ini_set('log_errors','On');
ini_set('display_errors','Off');
extract($_GET, EXTR_SKIP);
if (php_sapi_name() != 'cli' && $pswd != '01230') {
	header("Location: /");
	exit;
}

ini_set('memory_limit', '524288000');
set_time_limit(36000);

include 'includes/boot.php';

q_load('order');
require_once(SITE_ROOT . '/stripe/init.php');
$stripe_skey = $db->field("SELECT param1 FROM payment_methods WHERE paymentid=7");
// Dolibarr bridge: fallback to Dolibarr Stripe keys if SpaCart key is test/empty
$_is_test = empty($stripe_skey) || strpos($stripe_skey, 'sk_test_') === 0;
if ($_is_test) {
	$_dol_live = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_LIVE' AND value='1' AND entity IN (0,1) LIMIT 1");
	if ($_dol_live) {
		$_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
		if (empty($_dol_sk)) $_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
	} else {
		$_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY' AND value != '' AND entity IN (0,1) LIMIT 1");
	}
	if (!empty($_dol_sk)) $stripe_skey = $_dol_sk;
	unset($_dol_live, $_dol_sk, $_is_test);
}
\Stripe\Stripe::setApiKey($stripe_skey);
$events = \Stripe\Event::all([
  'type' => 'checkout.session.completed',
  'created' => [
    // Check for events created in the last 24 hours.
    'gte' => time() - 2 * 60 * 60,
  ],
]);

foreach ($events->autoPagingIterator() as $event) {
	$session = $event->data->object;
	$order = $db->row("SELECT * FROM orders WHERE stripe_session='".addslashes($session['id'])."' AND stripe_processed=0");
	if ($order) {
		$payment_indent = $event['data']['object']['payment_intent'];
		$transaction_id = '';
		if ($payment_indent) {
			$payment_intent = \Stripe\PaymentIntent::retrieve(
				$payment_indent
			);

			$transaction_id = $payment_intent['charges']['data'][0]->balance_transaction;
		}

		$orderid = $order['orderid'];
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
	}
}

if ($config['General']['currency_api_key']) {
	if ($config['currency_update_time'] < (time() - 12 * 3600)) {
		$currencies = $db->all("SELECT * FROM currencies WHERE active='1' AND main<>'1'");
		$main_currency = $db->field("SELECT code FROM currencies WHERE main='1'");
		if ($currencies && $main_currency) {
			$codes = '';
			foreach ($currencies as $v)
				$codes .= $v['code'].',';

			$api_data = file_get_contents('http://apilayer.net/api/live?access_key='.$config['General']['currency_api_key'].'&source='.$main_currency.'&currencies='.$codes.'&format=1');//USD,AUD,CAD,EUR,GBP
			$data = json_decode($api_data);
			foreach ($data->quotes as $k=>$v) {
				$code = str_replace($main_currency, '', $k);
				$db->query("UPDATE currencies SET rate='".$v."' WHERE code='".$code."'");
			}

			$db->query("DELETE FROM config WHERE name='currency_update_time'");
			$db->query("INSERT INTO config SET value='".time()."', name='currency_update_time'");
		}
	}
}

$times = array(3600, 3 * 3600);
foreach ($times as $i=>$time) {
	if ($i)
		$carts = $db->all("SELECT * FROM users_carts WHERE reminded_2=0 AND date<'".(time() - $time)."'");
	else
		$carts = $db->all("SELECT * FROM users_carts WHERE reminded_1=0 AND date<'".(time() - $time)."'");

	if ($carts) {
		foreach ($carts as $k=>$v) {
			$cart = unserialize($v['cart']);
			if ($v['userid']) {
				$user = $db->row("SELECT * FROM users WHERE id='".$v['userid']."'");
				$template['user'] = $user;
				$email = $user['email'];
				$rem = $db->field("SELECT pswd FROM users_remember WHERE userid='".$v['userid']."'");
				$template['link_add'] = 'set_rem='.$rem;
			} else {
				$template['link_add'] = 'ac_email='.$v['email'];
				$email = $v['email'];
			}

			$template['email'] = $email;
			$template['cart'] = $cart;
			$message = get_template_contents('mail/cart_reminder.php');
			$subject = $config['Company']['company_name'].': '.lng('Cart reminder');
			func_mail($user['firstname'].' '.$user['lastname'], $email, '', $subject, $message, $config['Company']['orders_department']);
			if ($i)
				$db->query("UPDATE users_carts SET reminded_2=1 WHERE id='$v[id]'");
			else
				$db->query("UPDATE users_carts SET reminded_1=1 WHERE id='$v[id]'");
		}
	}
}

exit('Done');