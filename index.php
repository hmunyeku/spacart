<?php
/*
Enable this to redirect to HTTPS always
*/
/*
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ' . $redirect);
    exit();
}
*/
header("Content-type: text/html;charset=utf-8");

ini_set('log_errors','On'); // enable or disable php error logging (use 'On' or 'Off')
ini_set('display_errors','Off'); // enable or disable public display of errors (use 'On' or 'Off')

if (!file_exists('includes/settings.php')) {
    header('Location: /install/');
    exit;
}

include 'includes/logging.php';
include 'includes/boot.php';

if (file_exists(SITE_ROOT . '/dev.php'))
    include_once(SITE_ROOT . '/dev.php');

func_save_cart();
func_remove_cart();
$device = $template['device'] = 'computer';
if (!$login) {
    iF (!$user)
        $user = array();

	if (!$user['city'] && $config['General']['default_city']) {
		$user['city'] = $config['General']['default_city'];
	}

	if (!$user['state'] && $config['General']['default_state']) {
		$user['state'] = $config['General']['default_state'];
	}

	if (!$user['country'] && $config['General']['default_country']) {
		$user['country'] = $config['General']['default_country'];
	}

	if (!$user['zipcode'] && $config['General']['default_zipcode']) {
		$user['zipcode'] = $config['General']['default_zipcode'];
	}

	$_SESSION['user'] = $user;
}

$template['get'] = $get;

if ($get['0'] == 'set_currency') {
	$tmp = $db->row("SELECT * FROM currencies WHERE active=1 AND id='".addslashes($get['1'])."'");
	if ($tmp)
		$_SESSION['current_currency'] = $tmp;

	exit;
}

if ($get['0'] == 'set_language') {
	$tmp = $db->row("SELECT * FROM languages_codes WHERE active=1 AND (id='".addslashes($get['1'])."' OR `code`='".addslashes($get['1'])."')");
	if ($tmp) {
		$_SESSION['current_language'] = $tmp;
        if (!is_numeric($get['1']))
            redirect('');
    }

    if (!$is_ajax)
        redirect('');

	exit;
}


$template['bread_crumbs'][] = array('/', $company_name.' '.lng('home page'));

if (!$_SESSION['countries']) {
	$countries = $db->all("SELECT *, IF(code='US',0,1) as US, IF(code='CA',0,1) as CA FROM countries ORDER BY US, CA, country");
	$states_count = 0;
	foreach ($countries as $k=>$v) {
		$states = $db->all("SELECT * FROM states WHERE country_code='$v[code]'");
		if (!empty($states)) {
			$countries[$k]['states'] = $states;
			$states_count++;
		}
	}

	$_SESSION['countries'] = $countries;
	$_SESSION['states_count'] = $states_count;
}

$template['states_count'] = $states_count;
$template['countries'] = $countries;

$languages = $db->all("SELECT * FROM languages_codes WHERE active=1 ORDER BY orderby, code");
if (count($languages) > 1)
	$template["languages"] = $languages;

$currencies = $db->all("SELECT * FROM currencies WHERE active=1 ORDER BY orderby, code");
if (count($currencies) > 1)
	$template["currencies"] = $currencies;

if ($config['General']['shop_closed'] == 'Y' && $get['0'] != 'res_p') {
	$template['page'] = get_template_contents('closed.php');
} elseif ($get['0']) {
	if ($_SESSION['substring'] && $get['0'] != 'search')
		$_SESSION['substring'] = '';

	if (empty($login)) {
		$not_allowed_pages = array('photos', 'images', 'ckeditor', 'includes', 'pages', 'templates', 'var');
		if (in_array($get['0'], $not_allowed_pages))
			redirect('/');
	}

	if ($get['0'] == 'home')
		redirect('/');

	# Check for clean URLs
	if (is_numeric($get['0']))
		$categoryid = $db->field("SELECT categoryid FROM categories WHERE categoryid='".$get['0']."' AND enabled=1");
	else
		$categoryid = $db->field("SELECT categoryid FROM categories WHERE cleanurl='".$get['0']."' AND enabled=1");
	if ($categoryid) {
		$get['0'] = 'category';
		$template['get'] = $get;
		include 'pages/category.php';
	} else {
		if ($get['0'] == 'product' && $get['1']) {
			$productid = $get['1'];
			include 'pages/product.php';
		} else {
			$productid = $db->field("SELECT productid FROM products WHERE cleanurl='".str_replace('.html', '', $get['0'])."' AND status<>2");
			if ($productid) {
				$get['0'] = 'product';
				$template['get'] = $get;
				include 'pages/product.php';
			} else {
				$script = 'pages/'.$get['0'].'.php';
				if (file_exists($script))
					include $script;
				else
					redirect('/');
			}
		}
	}
} else {
	$get['0'] = 'home';
	include SITE_ROOT . '/pages/home.php';
}

if ($device == 'mobile' && $get['0']) {
	$mobile_pages = array(
		'home', 'password', 'category', 'login', 'help', 'register', 'profile', 'search',
		'product', 'cart', 'checkout', 'invoice', 'blog', 'brands', 'page', 'wishlist', 'gift_cards'
	);

	if (!in_array($get['0'], $mobile_pages))
		redirect('/');
}

if ($get['0'] != 'admin' && $_SESSION['recently']) {
	q_load('product');
	$recently = array();
	foreach ($_SESSION['recently'] as $pid) {
		$recently[] = func_select_product($pid);
	}

	krsort($recently);
	$template['recently'] = $recently;
}

$page_title = '';
if ($get['0'] == 'home' && lng('Homepage meta title')) {
	$page_title = lng('Homepage meta title');
}elseif ($template['product']['title_tag']) {
	$page_title = $product['title_tag'];
} elseif ($template['category']['meta_title']) {
	$page_title = $category['meta_title'];
} elseif ($template['static_page']['meta_title']) {
	$page_title = $template['static_page']['meta_title'];
} elseif ($template['blog']['meta_title']) {
	$page_title = $blog['meta_title'];
} elseif ($template['brand']['meta_title']) {
	$page_title = $brand['meta_title'];
} else
	$page_title = $template['head_title'];

$template['page_title'] = $page_title;
$template['bread_crumbs_html'] = get_template_contents('bread_crumbs.php');
if ($get['0'] == 'admin') {
	if ($_SESSION['alerts']) {
		$template['alerts'] = $_SESSION['alerts'];
	}

	$template['ajax_container'] = get_template_contents('admin/ajax_container.php');
	if ($_GET['its_ajax_page'])
		$_SESSION['alerts'] = array();
} else
	$template['ajax_container'] = get_template_contents('ajax_container.php');

if ($is_ajax) {
	$_SESSION['alerts'] = array();
	$result = array($template['ajax_container'], $page_title, $template['bread_crumbs_html'], $get['0'], $template['parentid'], $brand['brandid']);
	exit(json_encode($result));
}

$template['get'] = $get;

if (
	$get['0'] == 'home' || $get['0'] == 'cart' || $get['0'] == 'checkout' || $get['0'] == 'help' || $get['0'] == 'page' ||
	$get['0'] == 'invoice' || $get['0'] == 'testimonials' || $get['0'] == 'search' || $get['0'] == 'category' ||
	$get['0'] == 'brands' || $get['0'] == 'blog' || $get['0'] == 'product' || $get['0'] == 'news' ||
	$get['0'] == 'ticket' || $get['0'] == 'support_desk' || $get['0'] == 'gift_cards' || $get['0'] == 'login' || $get['0'] == 'register'
)
	$template['is_ajax_page'] = '1';
else
	$template['is_ajax_page'] = '0';

if ($get['0'] == 'admin') {
	if ($login && $userinfo['usertype'] == 'A') {
		$template['head'] = get_template_contents('admin/head.php');
		$template['menu'] = get_template_contents('admin/menu.php');
	}

	$template['foot'] = get_template_contents('admin/foot.php');
} else {
	if ($device == 'mobile')
		$template['minicart'] = get_template_contents('common/minicart_mobile.php');
	else
		$template['minicart'] = get_template_contents('common/minicart.php');

	q_load('category');
	$template['categories_top_menu'] = func_categories_tree();
	$template['brands_menu'] = $db->all("SELECT * FROM brands WHERE active='Y' ORDER BY orderby, brandid");
	$template['head'] = get_template_contents('head.php');
	$template['left_menu'] = get_template_contents('left_menu.php');
	$template['foot'] = get_template_contents('foot.php');
	$template['css'] = array_merge(func_get_ajax_css(), $template['css']);
	$template['js'] = array_merge(func_get_ajax_js(), $template['js']);
}

if (!ADMIN_AREA)
	$template['css'][] = 'responsive';

if ($template['browser'] == 1)
	$template['css'][] = 'ie';
elseif ($template['browser'] == 3)
	$template['css'][] = 'safari';
elseif ($template['browser'] == 2) {

} elseif ($template['browser'] == 4)
	$template['css'][] = 'opera';
else
	$template['css'][] = 'ff';

if ($bot == 'Y')
	$template['css'][] = 'bot';

if ($device == 'mobile') {
	$template['css'][] = 'mobile';
	$template['js'][] = 'mobile';
}

if ($get['0'] == 'admin')
	$main = get_template_contents('admin/body.php');
else {
	$template['stripe_pkey'] = $db->field("SELECT param2 FROM payment_methods WHERE paymentid=7");
	// Dolibarr bridge: fallback to Dolibarr Stripe publishable key if SpaCart key is test/empty
	$_is_test_pk = empty($template['stripe_pkey']) || strpos($template['stripe_pkey'], 'pk_test_') === 0;
	if ($_is_test_pk) {
		$_dol_live = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_LIVE' AND value='1' AND entity IN (0,1) LIMIT 1");
		if ($_dol_live) {
			$_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_PUBLISHABLE_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
			if (empty($_dol_pk)) $_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_PUBLISHABLE_KEY_LIVE' AND value != '' AND entity IN (0,1) LIMIT 1");
		} else {
			$_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_PUBLISHABLE_KEY' AND value != '' AND entity IN (0,1) LIMIT 1");
		}
		if (!empty($_dol_pk)) $template['stripe_pkey'] = $_dol_pk;
		unset($_dol_live, $_dol_pk, $_is_test_pk);
	}
	$main = get_template_contents('body.php');
}

echo $main;

$_SESSION['alerts'] = array();