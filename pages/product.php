<?php
q_load('product');
$product = func_select_product($productid);

if ($_GET['popup']) {
	if (!$product)
		exit('No such product available');
} else {
	if (!$product)
		redirect('/');
	elseif ($get['1'] == $productid && !empty($product['cleanurl']))
		redirect('/'.$product['cleanurl'].'.html', false, true);
}

if ($_POST['mode'] == 'add_review') {
	extract($_POST, EXTR_SKIP);

	if (!$name || !$message) {
		exit('1');
	}

	$rc_key = $config['General']['recaptcha_key'];
	$rc_skey = $config['General']['recaptcha_skey'];
	if ($rc_key && $rc_skey) {
		$postdata = http_build_query(
    		array(
        		'secret' => $rc_skey,
		        'response' => $_POST['g-recaptcha-response']
    		)
		);

		$opts = array('http' =>
		    array(
        		'method'  => 'POST',
		        'header'  => 'Content-type: application/x-www-form-urlencoded',
        		'content' => $postdata
		    )
		);

		$context  = stream_context_create($opts);

		$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
		if (strstr($result, 'false') || !$_POST['g-recaptcha-response']) {
			exit('2');
		}
	}

	$new_review = array(
		'rating'	=> $rating,
		'name'		=> $name,
		'message'	=> $message,
		'remote_ip'	=> $_SERVER['REMOTE_ADDR'],
		'productid'	=> $review_productid,
		'date'		=> time(),
		'status'	=> 0
	);

	$db->array2insert("reviews", $new_review);
	$subject = $company_name.': '.lng('New review');
	$template['message'] = $message;
	$template['product'] = $product;
	$template['rating'] = $rating;
	$template['name'] = $name;
	$message = get_template_contents('mail/new_review.php');
	func_mail($config['Company']['company_name'], $config['Company']['site_administrator'], '', $subject, $message, $config['Company']['site_administrator']);

	exit('3');
}

if ($_POST['mode'] == 'send_to_friend') {
	extract($_POST, EXTR_SKIP);
	if (!$_POST['is_msg'])
		$is_msg = '';

	$_SESSION['send_to_friend']['name'] = $name;
	$_SESSION['send_to_friend']['email'] = $email;

	$antibot_err = false;
	$rc_key = $config['General']['recaptcha_key'];
	$rc_skey = $config['General']['recaptcha_skey'];
	if ($rc_key && $rc_skey) {
		$postdata = http_build_query(
    		array(
        		'secret' => $rc_skey,
		        'response' => $_POST['g-recaptcha-response']
    		)
		);

		$opts = array('http' =>
		    array(
        		'method'  => 'POST',
		        'header'  => 'Content-type: application/x-www-form-urlencoded',
        		'content' => $postdata
		    )
		);

		$context  = stream_context_create($opts);

		$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
		if (strstr($result, 'false')) {
			$antibot_err = true;
		}
	}

	if (!$name || !$email || !$friend || $antibot_err) {
		$_SESSION['send_to_friend']['friend'] = $friend;
		$_SESSION['send_to_friend']['is_msg'] = $is_msg;
		$_SESSION['send_to_friend']['message'] = $message;
		if ($antibot_err)
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Captcha is incorrect.')
			);
		else
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Please, enter all required fields')
			);

		redirect($_SERVER['REQUEST_URI']);
	} else
		$_SESSION['send_to_friend']['friend'] = $_SESSION['send_to_friend']['is_msg'] = $_SESSION['send_to_friend']['message'] = '';


	$subject = $company_name.': '.$name.' recommendation';
	if ($is_msg)
		$template['message'] = $message;

	$template['product'] = $product;
	$template['name'] = $name;
	$template['email'] = $email;
	$message = get_template_contents('mail/send_to_friend.php');
	func_mail('', $friend, $email, $subject, $message);

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> lng('Thank you for recommending our product to your friend.')
	);

	if ($product['cleanurl'])
		redirect('/'.$product['cleanurl'].'.html', false, true);
	else
		redirect('/product/'.$product['productid']);
}

$template['head_title'] = $product['name'].'. '.$template['head_title'];
$db->query("INSERT INTO products_stats (productid, views_stats) VALUES ('$product[productid]', 1) ON DUPLICATE KEY UPDATE views_stats=views_stats+1");

$parentid = $product['categoryid'];
$tmp = array();
while ($parent = $db->row("SELECT * FROM categories WHERE categoryid='".$parentid."'")) {
	$parentid = $parent['parentid'];
	$template['parentid'] = $parent['categoryid'];
	$tmp[] = $parent;
}

if (!empty($tmp)) {
	krsort($tmp);
	foreach ($tmp as $v)
		$template['bread_crumbs'][] = array('/'.($v['cleanurl'] ? $v['cleanurl'] : $v['categoryid']), $v['title']);
}

$template['bread_crumbs'][] = array('', $product['name']);

$template['photos'] = $db->all("SELECT * FROM products_photos WHERE productid='".$productid."' ORDER BY pos, photoid DESC");
$template['product'] = $product;

if ($userinfo['membershipid'] > 0)
	$membership_query = " AND membershipid IN (0,".$userinfo['membershipid'].")";
else
	$membership_query = " AND membershipid='0'";

$template["wholesale"] = $db->all("SELECT * FROM wholesale_prices WHERE productid='".$productid."' AND variantid='0'".$membership_query." ORDER BY membershipid, quantity");

$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$productid."' ORDER BY orderby, name, fullname, groupid");
if (!empty($option_groups)) {
	foreach ($option_groups as $k=>$v) {
		$option_groups[$k]['options'] = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, name");
	}

	$template['option_groups'] = $option_groups;
	$tmp = $db->all("SELECT * FROM options_ex WHERE productid='".$productid."'");
	if (!empty($tmp)) {
		$options_ex = array();
		foreach ($tmp as $v) {
			foreach ($v as $v2) {
				foreach ($option_groups as $g)
					if ($g['options'])
						foreach ($g['options'] as $o)
							if ($o['optionid'] == $v2)
								$options_ex[$v['exceptionid']][$o['groupid']] = $o['optionid'];
			}
		}

		$template['options_ex'] = $options_ex;
	}

	$tmp = $db->all("SELECT * FROM variants WHERE productid='".$productid."' ORDER BY variantid");
	if (!empty($tmp)) {
		$variants = array();
		foreach ($tmp as $tmp2) {
			$tmp2['options'] = $db->all("SELECT og.name, o.optionid, og.groupid, og.name FROM variant_items as vi INNER JOIN options AS o ON vi.optionid=o.optionid INNER JOIN option_groups AS og ON og.groupid=o.groupid WHERE vi.variantid='".$tmp2['variantid']."' ORDER BY og.orderby, o.orderby, o.name");
			$tmp2['images'] = $db->all("SELECT * FROM variant_images WHERE variantid='".$tmp2['variantid']."' ORDER BY pos, imageid");
			$tmp2['wholesale'] = $db->all("SELECT * FROM wholesale_prices WHERE variantid='".$tmp2['variantid']."'".$membership_query." ORDER BY membershipid, quantity");
			if (!$tmp2['avail'])
				$tmp2['avail'] = 0;

			$variants[] = $tmp2;
		}

		$template['variants'] = $variants;
	}
}

if (!$_SESSION['recently'])
	$_SESSION['recently'] = array();

if (in_array($productid, $_SESSION['recently'])) {
	foreach ($_SESSION['recently'] as $k=>$v) {
		if ($v == $productid) {
			unset($_SESSION['recently'][$k]);
		}
	}
}

$_SESSION['recently'][] = $productid;
if (count($_SESSION['recently']) > 20)
	$_SESSION['recently'] = array_slice($_SESSION['recently'], 1, 21);

$reviews = $db->all("SELECT * FROM reviews WHERE productid='".$productid."' AND status=1 ORDER BY id DESC");
if ($reviews) {
	$average = $db->field("SELECT AVG(rating) FROM reviews WHERE productid='".$productid."' AND status='1'");
	$template['reviews'] = $reviews;
	$template['average_rating'] = round($average * 100 / 5);
}

if ($is_ajax && $_GET['popup']) {
	exit(get_template_contents('common/popup_product.php'));
} else {
	if ($login) {
		if (!$send_to_friend['name'])
			$_SESSION['send_to_friend']['name'] = $userinfo['firstname'].' '.$userinfo['lastname'];

		if (!$send_to_friend['email'])
			$_SESSION['send_to_friend']['email'] = $userinfo['email'];
	}

	$related_products = $db->all("SELECT p.* FROM related_products r, products p WHERE r.productid2=p.productid AND r.productid1='".$productid."' ORDER BY r.orderby, p.name");
	if (!empty($related_products)) {
		foreach ($related_products as $k=>$v) {
			if ($v['photoid'])
				$related_products[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
		}

		$template['related_products'] = $related_products;
	}

	// Smart product recommendations: same category > same brand > popularity
	$rec_limit = 8;
	$recommends = array();
	$exclude_ids = array($productid);
	$current_brandid = intval($product['brandid']);

	// Get current product category IDs
	$cat_rows = $db->all("SELECT categoryid FROM category_products WHERE productid='".intval($productid)."'");
	$current_catids = array();
	foreach ($cat_rows as $cr) {
		$current_catids[] = intval($cr['categoryid']);
	}

	// 1) Same category products, weighted by popularity
	if (!empty($current_catids)) {
		$cat_list = implode(',', $current_catids);
		$cat_products = $db->all("SELECT DISTINCT p.* FROM products p
			INNER JOIN category_products cp ON cp.productid = p.productid
			WHERE cp.categoryid IN (".$cat_list.")
			AND p.productid <> '".intval($productid)."'
			AND p.status = '1'
			AND (p.deleted IS NULL OR p.deleted = 0)
			ORDER BY (p.sales_stats * 3 + p.views_stats) DESC
			LIMIT ".$rec_limit);
		foreach ($cat_products as $cp) {
			if (count($recommends) >= $rec_limit) break;
			$recommends[$cp['productid']] = $cp;
			$exclude_ids[] = $cp['productid'];
		}
	}

	// 2) Same brand products, weighted by popularity
	if (count($recommends) < $rec_limit && $current_brandid > 0) {
		$excl = implode(',', array_map('intval', $exclude_ids));
		$brand_products = $db->all("SELECT * FROM products p
			WHERE p.brandid = '".intval($current_brandid)."'
			AND p.productid NOT IN (".$excl.")
			AND p.status = '1'
			AND (p.deleted IS NULL OR p.deleted = 0)
			ORDER BY (p.sales_stats * 3 + p.views_stats) DESC
			LIMIT ".($rec_limit - count($recommends)));
		foreach ($brand_products as $bp) {
			if (count($recommends) >= $rec_limit) break;
			$recommends[$bp['productid']] = $bp;
			$exclude_ids[] = $bp['productid'];
		}
	}

	// 3) Fallback: any active product by popularity
	if (count($recommends) < $rec_limit) {
		$excl = implode(',', array_map('intval', $exclude_ids));
		$fallback_products = $db->all("SELECT * FROM products p
			WHERE p.productid NOT IN (".$excl.")
			AND p.status = '1'
			AND (p.deleted IS NULL OR p.deleted = 0)
			ORDER BY (p.sales_stats * 3 + p.views_stats) DESC
			LIMIT ".($rec_limit - count($recommends)));
		foreach ($fallback_products as $fp) {
			if (count($recommends) >= $rec_limit) break;
			$recommends[$fp['productid']] = $fp;
		}
	}

	// Re-index and attach photos
	$recommends = array_values($recommends);
	if (!empty($recommends)) {
		foreach ($recommends as $k => $v) {
			if ($v['photoid'])
				$recommends[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
		}
		$template['recommends'] = $recommends;
	}

	$template['css'][] = 'home';
	$template['css'][] = 'popup';
	$template['css'][] = 'products';
	$template['js'][] = 'products';
	$template['js'][] = 'home';
	$template['js'][] = 'popup';
	$template['js'][] = 'browser';
	$template['js'][] = 'jquery.zoom.min';

	$template['no_left_menu'] = 'Y';

	$template['page'] = get_template_contents('product/body.php');
}