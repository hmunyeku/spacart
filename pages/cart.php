<?php
q_load('cart', 'product');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($get['1'] == 'add_gc') {
		if (!is_numeric($get['2']))
			exit;

		$cart['products'][] = array(
			'cartid'	=> func_get_max_cartid() + 1,
			'amount'	=> $get['2'],
			'gift_card'	=> func_giftcert_generate()
		);

		$cart = func_calculate();
		$_SESSION['cart'] = $cart;
		if ($is_ajax) {
			$template['cart'] = $cart;
			if ($device == 'mobile')
				exit(get_template_contents('common/minicart_mobile.php'));
			else
				exit(get_template_contents('common/minicart.php'));
		} else
			redirect('/cart');

		exit;
	} elseif ($get['1'] == 'add') {
		if ($options_ex == 1)
			exit('1');

		$productid = intval($productid);
		$product = func_select_product($productid);
		if (!$product)
			exit;

		$amount = intval($amount);
		if (!$product_options)
			$product_options = func_get_default_options($productid, $amount, $userinfo['membershipid']);
		else {
			if (!func_check_product_options($productid, $product_options))
				exit('1');
		}

		if (!$cart)
			$cart = array();

		$found = false;
		if (!$found)
			$cart['products'][] = array(
				'cartid'	=> func_get_max_cartid() + 1,
				'productid'	=> $productid,
				'quantity'	=> $amount,
				'options'	=> $product_options
			);

		$cart = func_calculate();
		$_SESSION['cart'] = $cart;
		if ($is_ajax) {
			q_load('product');
			if (!empty($product_options))
				$variantid = func_get_variantid($product_options, $productid);
			else
				$variantid = false;

			$template['product'] = $product = func_select_product($productid, $variantid, $amount);
			$template['cart'] = $cart;
			if ($product_options) {
				$original_price = $product['price'];
				foreach ($product_options as $k=>$v) {
					$group = $db->row("SELECT * FROM option_groups WHERE groupid='".addslashes($k)."'");
					if ($group['type'] == 'g') {
						$option = $db->row("SELECT * FROM options WHERE optionid='".addslashes($v)."'");
						$product_options[$k] = array(
							'group' => $group,
							'option'	=> $option
						);

						if (!$group['variant']) {
							if ($option['price_modifier_type'] == '$')
								$product['price'] += $option['price_modifier'];
							else
								$product['price'] += $original_price * $option['price_modifier'] / 100;
						}
					} else {
						$product_options[$k] = array(
							'group' => $group,
							'value'	=> $v
						);
					}
				}

				$template['product_options'] = $product_options;
				$template['product'] = $product;
			}

			if ($device == 'mobile')
				exit(get_template_contents('common/minicart_mobile.php').$ajax_delimiter.get_template_contents('common/popup_product_added.php'));
			else
				exit(get_template_contents('common/minicart.php').$ajax_delimiter.get_template_contents('common/popup_product_added.php'));
		} else
			redirect('/cart');
	} else {
		foreach ($quantity as $k=>$v)
			foreach ($cart['products'] as $k2=>$v2)
				if ($v2['cartid'] == $k) {
					if (is_numeric($v) && $v > 0) {
						$_SESSION['cart']['products'][$k2]['quantity'] = $v;
					}

					break;
				}

		redirect('/cart');
	}
}

if ($get['1'] == 'remove') {
	foreach ($cart['products'] as $k=>$v)
		if ($v['cartid'] == $get['2']) {
			unset($_SESSION['cart']['products'][$k]);
			break;
		}

	if (empty($_SESSION['cart']['products']))
		$_SESSION['cart'] = array();

	func_save_cart();
	func_remove_cart();
	if ($is_ajax) {
		$template['cart'] = $_SESSION['cart'];
		if ($device == 'mobile')
			exit(get_template_contents('common/minicart_mobile.php'));
		else
			exit(get_template_contents('common/minicart.php'));
	} else
		redirect('/cart');
} elseif ($get['1'] == 'clear') {
	$_SESSION['cart'] = array();
	func_save_cart();
	func_remove_cart();
	if ($is_ajax) {
		$template['cart'] = array();
		if ($device == 'mobile')
			exit(get_template_contents('common/minicart_mobile.php'));
		else
			exit(get_template_contents('common/minicart.php'));
	} else
		redirect('/');
}

if ($cart['products']) {
	$cart = func_calculate();
	if ($login)
		$calculations = func_cart_calculations($userinfo);
	else
		$calculations = func_cart_calculations($_SESSION['user']);

	$template['cart'] = $cart = $_SESSION['cart'] = $calculations['cart'];
	$template['products'] = $cart['products'];
}

$template['page'] = get_template_contents('cart/body.php');

if ($is_ajax) {
	$page_title = lng('Cart');
	$result = array($template['page'], $page_title, $template['bread_crumbs_html'], $get['0'], $template['parentid']);
	exit(json_encode($result));
}