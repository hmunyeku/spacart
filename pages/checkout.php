<?php
q_load('cart', 'product', 'order', 'user');

require_once(SITE_ROOT."/bt/lib/autoload.php");
$bt_params = $db->row("SELECT * FROM payment_methods WHERE paymentid=2 AND enabled='1'");
if ($bt_params['param1'] && $bt_params['param2'] && $bt_params['param3']) {
	if ($bt_params['live']) {
		Braintree_Configuration::environment('production');
	} else {
		Braintree_Configuration::environment('sandbox');
	}

	Braintree_Configuration::merchantId($bt_params['param1']);
	Braintree_Configuration::publicKey($bt_params['param2']);
	Braintree_Configuration::privateKey($bt_params['param3']);
	try {
		$template['client_token'] = Braintree\ClientToken::generate();
	} catch (Exception $e) {
		try {
			$template['client_token'] = Braintree\ClientToken::generate();
		} catch (Exception $e) {
			try {
				$template['client_token'] = Braintree\ClientToken::generate();
			} catch (Exception $e) {
				$template['client_token'] = Braintree\ClientToken::generate();
			}
		}
	}
}

if (isset($_GET['coupon'])) {
	if (empty($_GET['coupon']))
		exit('Please, enter coupon');

	$coupon_code = addslashes($_GET['coupon']);
	$coupon = $db->row("SELECT * FROM coupons WHERE coupon='".$coupon_code."'");
	if (!$coupon) {
		exit('Coupon not exists');
	} elseif ($coupon['status'] != 'Y') {
		exit('Coupon is disabled');
	} elseif ($coupon['per_customer']) {
		if (!$login)
			exit('Please, login to apply this coupon');

		$used_count = $db->field("SELECT COUNT(*) FROM orders WHERE userid='$login' AND coupon='".$coupon_code."' AND status NOT IN (5, 4)");
		if ($used_count >= $coupon['times'])
			exit('Coupon was already used');
	} elseif ($coupon['times_used'] >= $coupon['times'])
		exit('Coupon is inactive');

	$cart['coupon'] = $coupon;
	$_SESSION['cart'] = $cart;

	exit('S');
}

if (isset($_GET['gc'])) {
	if (empty($_GET['gc']))
		exit('Please, enter Gift Card');

	$gc_code = addslashes($_GET['gc']);
	$gc = $db->row("SELECT * FROM gift_cards WHERE gcid='".$gc_code."' AND amount_left>0");
	if (!$gc) {
		exit('Gift Card not exists or expired');
	} elseif ($gc['status'] != 'Y') {
		exit('Gift Card is disabled');
	}

	$cart['gc'] = $gc;
	$cart['gift_card'] = $gc_code;
	$_SESSION['cart'] = $cart;

	exit('S');
}

if ($get['1'] == 'remove_coupon') {
	unset($cart['coupon']);
	$_SESSION['cart'] = $cart;
	exit;
}

if ($get['1'] == 'remove_gc') {
	unset($cart['gc']);
	unset($cart['gift_card']);
	unset($cart['gc_discount']);
	$_SESSION['cart'] = $cart;
	exit;
}

if ($cart['products']) {
	$cart = func_calculate();
	$_SESSION['cart'] = $cart;
	$template['products'] = $cart['products'];
} else
	redirect('/cart');

if ($get['1'] == 'user_form') {
	extract($_POST, EXTR_SKIP);
	extract($posted_data, EXTR_SKIP);

	if ($db->field("SELECT COUNT(*) as cnt FROM users WHERE id<>'".$login."' AND email='".addslashes($email)."'"))
		exit('Email');

	$update = array(
		'firstname'		=> $firstname,
		'lastname'		=> $lastname,
		'address'		=> $address,
		'city'			=> $city,
		'state'			=> $state,
		'country'		=> $country,
		'zipcode'		=> $zipcode,
		'phone'			=> $phone,
	);

	if ($same_address) {
		foreach ($update as $k=>$v) {
			$update['b_'.$k] = $posted_data[$k];
		}
	} else {
		foreach ($update as $k=>$v) {
			$update['b_'.$k] = $posted_data['b_'.$k];
		}
	}

	$update['email'] = $email;
	$update['same_address']	= $same_address;
	if ($login)
		$db->array2update('users', $update, "id='".$login."'");
	else {
		$userinfo = $update;
		$_SESSION['user'] = $update;
	}

	func_save_cart();
	$_SESSION['userinfo'] = $userinfo = array_merge($userinfo, $update);
	$calculations = func_cart_calculations($update);
	$template['cart'] = $cart = $_SESSION['cart'] = $calculations['cart'];
	$template['shipping_methods'] = $calculations['shipping_methods'];
	$template['payment_methods'] =$calculations['payment_methods'];

	exit(get_template_contents('checkout/right_part.php'));
} elseif ($_GET['shippingid']) {
	$cart['shippingid'] = $_GET['shippingid'];
	if ($login)
		$calculations = func_cart_calculations($userinfo);
	else
		$calculations = func_cart_calculations($_SESSION['user']);

	$template['cart'] = $cart = $_SESSION['cart'] = $calculations['cart'];
	$template['shipping_methods'] = $calculations['shipping_methods'];
	$template['payment_methods'] =$calculations['payment_methods'];

	exit(get_template_contents('checkout/right_part.php'));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
		redirect("/checkout");
	}

	extract($_POST, EXTR_SKIP);
	if ($action == 'register') {
		if ($login && $db->field("SELECT COUNT(*) as cnt FROM users WHERE email='".addslashes($email)."' AND id<>'".$login."'"))
			exit(lng('This E-mail already registered.'));
		elseif (!$login && $db->field("SELECT COUNT(*) as cnt FROM users WHERE email='".addslashes($email)."'"))
			exit(lng('This E-mail already registered.').'<br><a href="#" onclick="return login();">'.lng('Join').'</a> '.lng('or').' <a href="#" onclick="return restore_password()">'.lng('Recovery your password').'</a>');
		else {
			if ($password) {
				$insert = array(
					'email'				=> $email,
					'password'			=> spacart_password_hash($password),
					'firstname'			=> mb_substr($firstname, 0, 32, 'utf-8'),
					'lastname'			=> mb_substr($lastname, 0, 32, 'utf-8'),
					'address'			=> $address,
					'city'				=> $city,
					'zipcode'			=> $zipcode,
					'phone'				=> $phone,
					'status'			=> 1,
					'registration_date'	=> time()
				);

				$userid = $db->array2insert('users', $insert);
				// Sync new customer to Dolibarr
				require_once(SITE_ROOT . '/includes/func/func.dolibarr_sync.php');
				try {
				    spacart_sync_customer(array_merge($insert, array('id' => $userid)));
				} catch (Exception $e) {
				    error_log('SpaCart: customer sync error at registration: ' . $e->getMessage());
				}
				func_login($userid);
    			$_SESSION['user'] = '';
				exit('1');
			} else
				exit('1');
		}
	} else {
		if ($login)
			extract($userinfo, EXTR_SKIP);
		else
			extract($_SESSION['user'], EXTR_SKIP);

		if ($login)
			$calculations = func_cart_calculations($userinfo);
		else
			$calculations = func_cart_calculations($_SESSION['user']);

		$template['cart'] = $cart = $_SESSION['cart'] = $calculations['cart'];

		$checkout_reason = func_check_checkout();
		if ($checkout_reason != 1) {
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> $checkout_reason
			);

			redirect('/checkout');
		}

		if ($paymentid == 2) {
			$nonce = $_POST["payment_method_nonce"];
			$result = Braintree\Transaction::sale([
			    'amount' => round($cart['total'], 2),
			    'paymentMethodNonce' => $nonce,
			    'options' => [
			        'submitForSettlement' => true
			    ],
			    'shipping'	=> [
			    	'firstName'			=> mb_substr($firstname, 0, 32, 'utf-8'),
			    	'lastName'			=> mb_substr($lastname, 0, 32, 'utf-8'),
			    	'locality'			=> $city,
			    	'region'			=> $state,
			    	'countryName'		=> $country,
			    	'postalCode'		=> $zipcode,
			    	'streetAddress'		=> $address,
			    ],
			    'billing'	=> [
			    	'firstName'			=> mb_substr($b_firstname, 0, 32, 'utf-8'),
			    	'lastName'			=> mb_substr($b_lastname, 0, 32, 'utf-8'),
			    	'locality'			=> $b_city,
			    	'region'			=> $b_state,
			    	'countryName'		=> $b_country,
			    	'postalCode'		=> $b_zipcode,
			    	'streetAddress'		=> $b_address,
			    ],
			    'customer'	=> [
				    'firstName'			=> mb_substr($b_firstname, 0, 32, 'utf-8'),
			    	'lastName'			=> mb_substr($b_lastname, 0, 32, 'utf-8'),
				    'email'				=> $email,
				    'phone'				=> $b_phone,
				],
				'taxAmount'	=> round($cart['tax'], 2)
			]);

			if ($result->success) {
				$stripe_processed = 1;
				$transaction = $result->transaction;
				$transaction_id = $transaction->id;
			} else {
				exit('StripeError');
			}
		}

		$stripe_key = md5(time().rand(0,100000));
		$insert = array(
			'token'			=> md5(time().rand(0,1000000)),
			'transaction_id'=> $transaction_id,
			'stripe_key'	=> $stripe_key,
			'stripe_session'=> '',
			'gift_card'		=> $cart['gift_card'],
			'gc_discount'	=> $cart['gc_discount'],
			'shippingid'	=> $cart['shippingid'],
			'local_pickup'	=> $local_pickup,
			'wid'			=> $wid,
			'paymentid'		=> $paymentid,
			'userid'		=> $login,
			'email'			=> $email,
			'firstname'		=> mb_substr($firstname, 0, 32, 'utf-8'),
			'lastname'		=> mb_substr($lastname, 0, 32, 'utf-8'),
			'address'		=> $address,
			'city'			=> $city,
			'state'			=> $state,
			'country'		=> $country,
			'zipcode'		=> $zipcode,
			'phone'			=> $phone,
			'b_firstname'	=> mb_substr($b_firstname, 0, 32, 'utf-8'),
			'b_lastname'	=> mb_substr($b_lastname, 0, 32, 'utf-8'),
			'b_address'		=> $b_address,
			'b_city'		=> $b_city,
			'b_state'		=> $b_state,
			'b_country'		=> $b_country,
			'b_zipcode'		=> $b_zipcode,
			'b_phone'		=> $b_phone,
			'notes'			=> $notes,
			'status'		=> 1,
			'date'			=> time(),
			'subtotal'		=> $cart['subtotal'],
			'coupon'		=> $cart['coupon']['coupon'],
			'coupon_discount'=> $cart['coupon_discount'],
			'shipping'		=> $cart['shipping_cost'],
			'tax'			=> round($cart['tax'], 2),
			'tax_details'	=> serialize($cart['tax_details']),
			'total'			=> $cart['total']
		);

		if (empty($cart['total']))
			$stripe_processed = 1;

		if ($stripe_processed == 1) {
			$order_status = $insert['status'] = 2;
		}

		if ($cart['coupon'])
			$db->query("UPDATE coupons SET times_used='".($cart['coupon']['times_used'] + 1)."' WHERE coupon='".$cart['coupon']['coupon']."'");

		$order_status = $insert['status'];
		$orderid = $db->array2insert('orders', $insert);
		session_start();
		$_SESSION['invoices'][] = $orderid;
		$total = $insert['total'];
		foreach ($cart['products'] as $k=>$v) {
			$insert = array(
				'orderid'	=> $orderid,
				'productid'	=> $v['productid'],
				'price'		=> $v['price'],
				'weight'	=> $v['weight'],
				'quantity'	=> $v['quantity'],
				'extra'		=> serialize(array(
						'product_options'	=> $v['product_options'],
						'options'			=> $v['options'],
						'variantid'			=> $v['variantid']
					)
				)
			);

			if ($v['gift_card']) {
				$insert['gift_card'] = $v['gift_card'];
				$insert['price'] = $v['amount'];
			}

			$db->array2insert('order_items', $insert);

			$count = $db->field("SELECT COUNT(*) FROM order_items WHERE productid='$v[productid]'");
			$db->query("INSERT INTO products_stats (productid, sales_stats) VALUES ('".$v['productid']."', ".$count.") ON DUPLICATE KEY UPDATE sales_stats=".$count."");
		}

		
		// ---- Sync order to Dolibarr ----
		require_once(SITE_ROOT . '/includes/func/func.dolibarr_sync.php');
		spacart_sync_order_to_dolibarr($orderid);
		// ---- End sync ----

		if ($stripe_processed == 1) {
			order_status($orderid, 2, true);
		}

		// ---- Complete sale chain (invoice + payment + stock) ----
		// For immediate payments: Braintree (paymentid=2), COD (4), Virement (5), Check (3), Fax (6), Bon de commande (1)
		// For Stripe (7) and PayPal (8): chain is triggered after payment confirmation in cron/check_stripe.php or paypal IPN
		if ($paymentid != 7 && $paymentid != 8) {
			try {
				spacart_complete_sale_chain($orderid);
			} catch (Exception $e) {
				// Never block checkout -- errors are logged in spacart_sync_log
				error_log("SpaCart: sale chain error for order #" . $orderid . ": " . $e->getMessage());
			}
		}
		// ---- End sale chain ----

		if ($paymentid == 7) {
			require_once(SITE_ROOT . '/stripe/init.php');
			$stripe_skey = $db->field("SELECT param1 FROM payment_methods WHERE paymentid=7");
			// ---- Dolibarr bridge: try Dolibarr Stripe keys if SpaCart has test/empty keys ----
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
			// ---- End Dolibarr bridge ----
			\Stripe\Stripe::setApiKey($stripe_skey);
			$items = array();
				$items[] = array(
				   	'name' => 'Order #'.$orderid,
	    			'amount' => round($cart['total'] * 100),
			    	'currency' => strtoupper($payment_currency),
				    'quantity' => 1,
				 );

			$session = \Stripe\Checkout\Session::create([
			  'payment_method_types' => ['card'],
			  'line_items' => $items,
			  'success_url' => $current_location.'/stripe/'.$orderid.'/'.$stripe_key,
			  'cancel_url' => $current_location.'/invoice/'.$orderid,
			]);

			$stripe_session = $session->id;
            $db->query("UPDATE orders SET stripe_session='".addslashes($stripe_session)."' WHERE orderid='".$orderid."'");
            exit($stripe_session);
		} elseif ($paymentid == 8) {
			$payment_method = $db->row("SELECT * FROM payment_methods WHERE paymentid=8");
			$paypal_email = $payment_method['param1'];
			// ---- Dolibarr bridge: if SpaCart has demo PayPal email, try Dolibarr config ----
			$_demo_emails = array('xcart@ya.ru', 'test@example.com', '');
			if (in_array($paypal_email, $_demo_emails)) {
				$_dol_pp = $db->field("SELECT value FROM llx_const WHERE name='PAYPAL_BUSINESS' AND value != '' AND entity IN (0,1) LIMIT 1");
				if (empty($_dol_pp)) $_dol_pp = $db->field("SELECT value FROM llx_const WHERE name='PAYPAL_API_USER' AND value != '' AND entity IN (0,1) LIMIT 1");
				if (!empty($_dol_pp)) $paypal_email = $_dol_pp;
				unset($_dol_pp);
			}
			unset($_demo_emails);
			// ---- End Dolibarr bridge ----
			if ($payment_method['live'])
				$url = 'https://www.paypal.com/cgi-bin/webscr';
			else
				$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
?>
<form action="<?php echo $url; ?>" method="post" name="paypalform" style="display: none;">
    <input name="business" type="hidden" value="<?php echo $paypal_email; ?>">
<!--  <input type="hidden" name="business" value="EMAIL">-->
  <input type="text" name="amount" value="<?php echo price_format($cart['total']); ?>" />
    <br>
    <input name="currency_code" type="hidden" value="<?php echo strtoupper($payment_currency); ?>">
    <input name="cancel_return" type="hidden" value="<?php echo $current_location; ?>/invoice/<?php echo $orderid; ?>/failed">
    <input name="notify_url" type="hidden" value="<?php echo $current_location; ?>/paypal/<?php echo $orderid; ?>">
    <input name="return" type="hidden" value="<?php echo $current_location; ?>/invoice/<?php echo $orderid; ?>/success">
    <input name="cmd" type="hidden" value="_xclick">
    <input name="item_name" type="hidden" value="Order payment">
    <input type="hidden" name="no_shipping" value="1">
    <input name="lc" type="hidden" value="EN">
    <input name="rm" type="hidden" value="2">
    <input name="bn" type="hidden" value="PP-BuyNowBF">
    <input type="image" src="https://www.paypalobjects.com/en_US/CH/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
    <img alt="" border="0" src="https://www.paypalobjects.com/fr_FR/i/scr/pixel.gif" width="1" height="1">
    </form>
<?php
			$_SESSION['cart'] = array();
			func_remove_cart();
			exit;
		}

		$template['total'] = $total;

		q_load('order');
		$subject = $company_name.': '.lng('Order').' #'.$orderid.' '.$order_statuses[$order_status];
		if ($order_status == 2)
			func_decrease_quantity($orderid);

		$orderinfo = func_orderinfo($orderid);
		$template['order'] = $order = $orderinfo['order'];
		$template['products'] = $orderinfo['products'];
		$template['is_mail'] = 'Y';
		$message = get_template_contents('invoice/body.php');
		func_mail($order['firstname'].' '.$order['lastname'], $order['email'], $config['Company']['orders_department'], $subject, $message);
		func_mail($config['Company']['company_name'], $config['Company']['orders_department'], $order['email'], $subject, $message);

		$_SESSION['cart'] = array();
		func_remove_cart();
		if ($is_ajax) {
			exit($orderid.'/');
		} else
			redirect('/invoice/'.$orderid);
	}
}

if (empty($login) && !empty($social_login)) {
	if (!empty($social_login['profile']['name']['givenName'])) {
		$_SESSION['user']['firstname'] = $social_login['profile']['name']['givenName'];
		$_SESSION['user']['lastname'] = $social_login['profile']['name']['familyName'];
	} else {
		$tmp = explode(" ", $social_login['profile']['displayName']);
		$_SESSION['user']['firstname'] = $tmp['0'];
		$_SESSION['user']['lastname'] = $tmp['1'];
	}

	$_SESSION['user']['email'] = $social_login['profile']['verifiedEmail'];
	$template['userinfo'] = $_SESSION['user'];
} elseif (empty($login))
	$template['userinfo'] = $_SESSION['user'];

$calculations = func_cart_calculations($template['userinfo']);
$template['cart'] = $cart = $_SESSION['cart'] = $calculations['cart'];
$template['shipping_methods'] = $calculations['shipping_methods'];
$template['payment_methods'] = $calculations['payment_methods'];

$template['no_left_menu'] = 'Y';
$template['page'] = get_template_contents('checkout/body.php');

if ($is_ajax) {
	$page_title = lng('Checkout');
	$result = array($template['page'], $page_title, $template['bread_crumbs_html'], $get['0'], $template['parentid']);
	exit(json_encode($result));
}