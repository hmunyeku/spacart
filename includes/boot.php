<?php
/**
 * Functions that need to be loaded on every request.
 */

error_reporting(E_ERROR | E_PARSE);
ini_set('memory_limit', '128M');

ini_set('magic_quotes_runtime', '0');
setlocale(LC_ALL, 'C');

define('REPLACE_FLAGS', ENT_SUBSTITUTE);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$pass = array('pswd', 'new_pswd', 'password', 'descr', 'fulldescr', 'content', 'message', 'data', 'lbl', 'translate');
	foreach ($_POST as $k=>$v) {
		if (in_array($k, $pass))
			continue;

		if (is_array($v)) {
			foreach ($v as $k2=>$v2) {
				if (is_array($v2)) {
					foreach ($v2 as $k3=>$v3) {
						if (is_array($v3)) {
							foreach ($v3 as $k4=>$v4)
								$_POST[$k][$k2][$k3][$k4] = htmlspecialchars($v4, REPLACE_FLAGS);
						} else
							$_POST[$k][$k2][$k3] = htmlspecialchars($v3, REPLACE_FLAGS);
					}
				} else
					$_POST[$k][$k2] = htmlspecialchars($v2, REPLACE_FLAGS);
			}
		} else
			$_POST[$k] = htmlspecialchars($v, REPLACE_FLAGS);
	}
}

$_GET['q'] = htmlspecialchars($_GET['q'], REPLACE_FLAGS);

include_once 'includes/settings.php';
$qloaded_functions = array();
include_once SITE_ROOT . '/includes/func/func.core.php';
include_once SITE_ROOT . '/includes/logging.php';

$cookie_domain = $http_domain;

if ($is_mysqli)
	include_once SITE_ROOT . '/includes/database_mysqli.php';
else
	include_once SITE_ROOT . '/includes/database.php';

$db = new Database();
$db->connect();

if ($is_mysqli)
	$db->setUTF8();
else
	mysql_set_charset("utf8");

$db->query("SET sql_mode = '';");

# Start session
session_start();

// SEC-CRIT-3: Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

# Recovery var directory
$var_dir = SITE_ROOT.'/var';
if (!is_dir($var_dir)) {
	mkdir($var_dir, 0777) || die('Cannot create '.$var_dir.' directory. Please, check permissions.');
	copy(SITE_ROOT.'/includes/index_file', $var_dir.'/index.php');

	$dirs = array('cache', 'log', 'photo', 'photo/blog', 'photo/brand', 'photo/category', 'photo/product', 'photo/variant', 'cache/other', 'cache/other/css', 'cache/other/images', 'cache/en', 'cache/en/js', 'cache/ru', 'cache/ru/js');
	foreach ($dirs as $v) {
		$dir = $var_dir.'/'.$v;
		mkdir($dir, 0777) || die('Cannot create '.$dir.' directory. Please, check permissions.');
		copy(SITE_ROOT.'/includes/index_file', $dir.'/index.php');
	}

	$dest = $var_dir.'/cache/other/images';
	$source = SITE_ROOT.'/images';
	foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
		if ($item->isDir())
			mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0777);
		else
			copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
	}
}

# Parse url - strip $web_dir prefix for Dolibarr module path
# Handles both reverse proxy access (e.g. /boutique/...) and direct access (e.g. /custom/spacart/...)
$get = array();
$_parse_uri = $_SERVER['REQUEST_URI'];
// First try the public $web_dir (e.g. /boutique when behind proxy)
if (!empty($web_dir) && strpos($_parse_uri, $web_dir) === 0) {
	$_parse_uri = substr($_parse_uri, strlen($web_dir));
	if ($_parse_uri === '' || $_parse_uri === false) $_parse_uri = '/';
}
// Also try internal path if proxy is active and URI still starts with internal path
// (direct access to erp.coexdis.com/custom/spacart/ while proxy is configured)
elseif (defined('INTERNAL_WEB_DIR') && INTERNAL_WEB_DIR !== $web_dir && strpos($_parse_uri, INTERNAL_WEB_DIR) === 0) {
	$_parse_uri = substr($_parse_uri, strlen(INTERNAL_WEB_DIR));
	if ($_parse_uri === '' || $_parse_uri === false) $_parse_uri = '/';
}
$tmp = explode("?", $_parse_uri);
$tmp2 = explode("/", $tmp[0]);
if (!empty($tmp2))
	foreach ($tmp2 as $v)
		if ($v != '')
			$get[] = addslashes($v);

if ($_SESSION['current_language']) {
	$lng = $_SESSION['current_language']['code'];
}

$taxes_units = array(
	'ST'  => lng('Subtotal'),
	'DST' => lng('Discounted subtotal'),
	'SH'  => lng('Shipping cost')
);

# Get config
$config = array();
$tmp = $db->all("SELECT * FROM config ORDER BY category, orderby");
foreach ($tmp as $v) {
	if ($v['category'])
		$config[$v['category']][$v['name']] = $v['value'];
	else
		$config[$v['name']] = $v['value'];
}

if ($get['0'] != 'admin' && $_SESSION['current_currency']) {
	$config['General']['currency_symbol'] = $_SESSION['current_currency']['symbol'];
}

$config['Company']['location_countryname'] = $db->field("SELECT country FROM countries WHERE code='".$config['Company']['location_country']."'");
$config['Company']['location_statename'] = $db->field("SELECT state FROM states WHERE code='".$config['Company']['location_state']."' AND country_code='".$config['Company']['location_country']."'");
if (!$config['Company']['location_statename'])
	$config['Company']['location_statename'] = $config['Company']['location_state'];

$config['Company']['company_mail_from'] = htmlspecialchars_decode($config['Company']['company_mail_from']);

$company_email = $config['Company']['company_mail_from'];
$company_name = $config['Company']['company_name'];
$company_slogan = $config['Company']['company_slogan'];

if ($get['0'] == 'admin') {
	define('ADMIN_AREA', 1);
	$config['General']['shop_closed'] = '';
} else {
	define('ADMIN_AREA', 0);
	if ($_GET['shopkey'] == $config['General']['shop_closed_key'])
		$_SESSION['shop_not_closed'] = 'Y';

	if ($_SESSION['shop_not_closed'] == 'Y')
		unset($config['General']['shop_closed']);
}

$template = array();

$template['domain'] = $http_domain;
$template['http_location'] = $http_location;
$template['https_location'] = $https_location;

if ($_SERVER['HTTPS'])
	$template['current_protocol'] = 'https';
else
	$template['current_protocol'] = 'http';

$template['warehouse_enabled'] = $warehouse_enabled;
$template['ajax_delimiter'] = $ajax_delimiter;
$template['payment_currency'] = $payment_currency;

if (preg_match('/bot|andex|oogle|robot|spider|crawl|curl|search|^$/i', $_SERVER['HTTP_USER_AGENT']))
	$template['bot'] = $bot = 'Y';

if ($company_slogan)
	$template['head_title'] = $company_name.' - '.$company_slogan;
else
	$template['head_title'] = $company_name;

$template['company_name'] = $company_name;
$template['company_slogan'] = $company_slogan;

$template['css'][] = 'style';
if ($get['0'] != 'admin') {
/*
	$template['js'][] = 'jquery.min';
	$template['js'][] = 'jquery.ui.core.min';
	$template['js'][] = 'jquery.ui.widget.min';
	$template['js'][] = 'jquery.ui.mouse.min';
	$template['js'][] = 'jquery.ui.position.min';
	$template['js'][] = 'jquery.ui.draggable.min';
	$template['js'][] = 'jquery.ui.droppable.min';
*/
	$template['js'][] = 'jquery.ui.tooltip';
}

$template['js'][] = 'scripts';
$template['css'][] = 'jquery.ui.tooltip';
# Get templates
$tmp = $db->all("SELECT template, time, lng FROM templates WHERE lng IN ('".$lng."', 'css', 'js')");
if (!empty($tmp)) {
	$templates = array();
	foreach ($tmp as $v)
		$templates[$v['lng']][$v['template']] = $v['time'];
}

$template['templates'] = $templates;

# Config
# ---- Dolibarr SPACART_* constants bridge ----
# Override SpaCart config with Dolibarr llx_const values when they exist
$_dol_prefix = "llx_";
$_spacart_consts = $db->all("SELECT name, value FROM ".$_dol_prefix."const WHERE name LIKE 'SPACART_%' AND value != '' AND entity IN (0,1)");
if (!empty($_spacart_consts)) {
    $_dol_map = array(
        "SPACART_TITLE"              => array("Company", "company_name"),
        "SPACART_COMPANY_NAME"       => array("Company", "company_name"),
        "SPACART_COMPANY_EMAIL"      => array("Company", "company_mail_from"),
        "SPACART_COMPANY_ADDRESS"    => array("Company", "location_address"),
        "SPACART_COMPANY_PHONE"      => array("Company", "company_phone"),
        "SPACART_COMPANY_FAX"        => array("Company", "company_fax"),
        "SPACART_CURRENCY_SYMBOL"    => array("General", "currency_symbol"),
        "SPACART_WEIGHT_SYMBOL"      => array("General", "weight_symbol"),
        "SPACART_THEME_COLOR"        => array(null, "theme_color"),
        "SPACART_THEME_COLOR_2"      => array(null, "theme_color_2"),
        "SPACART_SHOP_CLOSED"        => array("General", "shop_closed"),
        "SPACART_TAWKTO_ID"          => array("General", "tawk_to_site_id"),
        "SPACART_ANALYTICS_ID"       => array("General", "analytics_id"),
        "SPACART_RECAPTCHA_SITE_KEY" => array("General", "recaptcha_key"),
        "SPACART_RECAPTCHA_SECRET_KEY" => array("General", "recaptcha_skey"),
        "SPACART_META_TITLE"         => array(null, "meta_title"),
        "SPACART_META_DESCRIPTION"   => array(null, "meta_description"),
        "SPACART_META_KEYWORDS"      => array(null, "meta_keywords"),
        "SPACART_FREE_SHIPPING_THRESHOLD" => array("General", "free_shipping_threshold"),
        "SPACART_PRODUCTS_PER_PAGE"  => array("General", "products_per_page"),
        "SPACART_LOGO_URL"           => array("Company", "company_logo"),
        "SPACART_FAVICON"            => array("General", "favicon"),
        "SPACART_FOOTER_LOGO_URL"    => array("Company", "footer_logo"),
        "SPACART_FACEBOOK_URL"       => array("General", "social_facebook"),
        "SPACART_TWITTER_URL"        => array("General", "social_twitter"),
        "SPACART_INSTAGRAM_URL"      => array("General", "social_instagram"),
        "SPACART_YOUTUBE_URL"        => array("General", "social_youtube"),
        "SPACART_LINKEDIN_URL"       => array("General", "social_linkedin"),
        "SPACART_WHATSAPP_URL"       => array("General", "social_whatsapp"),
        "SPACART_COMPANY_ADDRESS"    => array("Company", "company_address"),
        "SPACART_COMPANY_WEBSITE"    => array("Company", "company_website"),
        "SPACART_COMPANY_SLOGAN"     => array("Company", "company_slogan"),
        "SPACART_GUEST_CHECKOUT"     => array("General", "guest_checkout"),
        "SPACART_ABANDONED_CART_DELAY" => array("General", "abandoned_cart_delay"),
        "SPACART_SHOW_SERVICES"      => array("General", "show_services"),
        "SPACART_TAX_ENABLED"        => array("General", "tax_enabled"),
        "SPACART_AUTOTRANSLATE_ENABLED"  => array("General", "autotranslate_enabled"),
        "SPACART_AUTOTRANSLATE_SOURCE_LANG" => array("General", "autotranslate_source_lang"),
        "SPACART_AUTOTRANSLATE_EXCLUDE_SELECTORS" => array("General", "autotranslate_exclude"),
    );
    foreach ($_spacart_consts as $_sc) {
        if (isset($_dol_map[$_sc["name"]])) {
            $_cat = $_dol_map[$_sc["name"]][0];
            $_key = $_dol_map[$_sc["name"]][1];
            if ($_cat !== null) {
                $config[$_cat][$_key] = $_sc["value"];
            } else {
                $config[$_key] = $_sc["value"];
            }
        }
    }
    // Re-derive company globals
    if (!empty($config["Company"]["company_mail_from"])) $company_email = $config["Company"]["company_mail_from"];
    if (!empty($config["Company"]["company_name"])) $company_name = $config["Company"]["company_name"];
    if (!empty($config["Company"]["company_slogan"])) $company_slogan = $config["Company"]["company_slogan"];
    // Override payment_currency from settings.php if SPACART_CURRENCY is set
    foreach ($_spacart_consts as $_sc) {
        if ($_sc["name"] == "SPACART_CURRENCY" && !empty($_sc["value"])) {
            $payment_currency = $_sc["value"];
            $template["payment_currency"] = $payment_currency;
        }
    }
    // Override language from SPACART_DEFAULT_LANGUAGE
    foreach ($_spacart_consts as $_sc) {
        if ($_sc["name"] == "SPACART_DEFAULT_LANGUAGE" && !empty($_sc["value"])) {
            $config["General"]["default_language"] = $_sc["value"];
            // Only set $lng from default if no session language is active
            if (empty($_SESSION['current_language']['code'])) {
                $lng = substr($_sc["value"], 0, 2); // fr_FR -> fr
            }
        }
    }
}

// Re-apply session currency override AFTER Dolibarr bridge (which may have overwritten it)
if ($get['0'] != 'admin' && $_SESSION['current_currency']) {
	$config['General']['currency_symbol'] = $_SESSION['current_currency']['symbol'];
}

// AutoTranslate - expose config to template
$template["autotranslate_enabled"] = !empty($config["General"]["autotranslate_enabled"]) ? 1 : 0;
$template["autotranslate_source_lang"] = !empty($config["General"]["autotranslate_source_lang"]) ? $config["General"]["autotranslate_source_lang"] : "French";
$template["autotranslate_exclude"] = !empty($config["General"]["autotranslate_exclude"]) ? $config["General"]["autotranslate_exclude"] : ".notranslate, .price, .ef-price";
unset($_spacart_consts, $_dol_map, $_dol_prefix, $_sc, $_cat, $_key);

# ---- Dolibarr payment bridge: Stripe + PayPal keys ----
# If SpaCart payment_methods has test/demo keys, try to read from Dolibarr's llx_const
// Stripe: param1=secret key, param2=public key (paymentid=7)
$_sc_stripe = $db->row("SELECT param1, param2, live FROM payment_methods WHERE paymentid=7");
if ($_sc_stripe) {
    $_stripe_is_test = empty($_sc_stripe['param1']) || strpos($_sc_stripe['param1'], 'sk_test_') === 0;
    if ($_stripe_is_test) {
        $_dol_live = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_LIVE' AND value='1' AND entity IN (0,1)");
        if ($_dol_live) {
            $_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY_LIVE' AND value != '' AND entity IN (0,1)");
            if (empty($_dol_sk)) $_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_KEY_LIVE' AND value != '' AND entity IN (0,1)");
            $_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_PUBLISHABLE_KEY_LIVE' AND value != '' AND entity IN (0,1)");
            if (empty($_dol_pk)) $_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_PUBLISHABLE_KEY_LIVE' AND value != '' AND entity IN (0,1)");
            if (!empty($_dol_sk)) {
                $db->query("UPDATE payment_methods SET param1='" . addslashes($_dol_sk) . "'" . (!empty($_dol_pk) ? ", param2='" . addslashes($_dol_pk) . "'" : "") . ", live=1 WHERE paymentid=7");
            }
        } else {
            $_dol_sk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_SECRET_KEY' AND value != '' AND entity IN (0,1)");
            $_dol_pk = $db->field("SELECT value FROM llx_const WHERE name='STRIPE_TEST_PUBLISHABLE_KEY' AND value != '' AND entity IN (0,1)");
            if (!empty($_dol_sk)) {
                $db->query("UPDATE payment_methods SET param1='" . addslashes($_dol_sk) . "'" . (!empty($_dol_pk) ? ", param2='" . addslashes($_dol_pk) . "'" : "") . ", live=0 WHERE paymentid=7");
            }
        }
    }
    unset($_stripe_is_test, $_dol_live, $_dol_sk, $_dol_pk);
}
unset($_sc_stripe);

// PayPal: param1=receiver email (paymentid=8)
$_sc_paypal = $db->row("SELECT param1, live FROM payment_methods WHERE paymentid=8");
if ($_sc_paypal) {
    $_pp_demo = array('xcart@ya.ru', 'test@example.com', '');
    if (in_array($_sc_paypal['param1'], $_pp_demo)) {
        $_dol_pp_email = $db->field("SELECT value FROM llx_const WHERE name='PAYPAL_BUSINESS' AND value != '' AND entity IN (0,1)");
        if (empty($_dol_pp_email)) {
            $_dol_pp_email = $db->field("SELECT value FROM llx_const WHERE name='PAYPAL_API_USER' AND value != '' AND entity IN (0,1)");
        }
        if (!empty($_dol_pp_email)) {
            $_pp_sandbox = $db->field("SELECT value FROM llx_const WHERE name='PAYPAL_API_SANDBOX' AND entity IN (0,1)");
            $_pp_live = ($_pp_sandbox === '0' || $_pp_sandbox === '') ? 1 : 0;
            $db->query("UPDATE payment_methods SET param1='" . addslashes($_dol_pp_email) . "', live=" . $_pp_live . " WHERE paymentid=8");
        }
    }
    unset($_pp_demo, $_dol_pp_email, $_pp_sandbox, $_pp_live);
}
unset($_sc_paypal);
# ---- End Dolibarr payment bridge ----

# ---- Dolibarr entity fallback for social links ----
# If SPACART social links are still empty, try Dolibarr MAIN_INFO_SOCIETE_* constants
$_dol_fallback_map = array(
    "MAIN_INFO_SOCIETE_FACEBOOK_URL"  => array("General", "social_facebook"),
    "MAIN_INFO_SOCIETE_LINKEDIN_URL"  => array("General", "social_linkedin"),
    "MAIN_INFO_SOCIETE_WHATSAPP_URL"  => array("General", "social_whatsapp"),
);
$_need_fallback = false;
foreach ($_dol_fallback_map as $_fb_const => $_fb_target) {
    if (empty($config[$_fb_target[0]][$_fb_target[1]])) {
        $_need_fallback = true;
        break;
    }
}
if ($_need_fallback) {
    $_fb_rows = $db->all("SELECT name, value FROM llx_const WHERE name IN ('MAIN_INFO_SOCIETE_FACEBOOK_URL','MAIN_INFO_SOCIETE_LINKEDIN_URL','MAIN_INFO_SOCIETE_WHATSAPP_URL') AND value != '' AND entity = 1");
    if (!empty($_fb_rows)) {
        foreach ($_fb_rows as $_fb) {
            if (isset($_dol_fallback_map[$_fb['name']])) {
                $_cat = $_dol_fallback_map[$_fb['name']][0];
                $_key = $_dol_fallback_map[$_fb['name']][1];
                if (empty($config[$_cat][$_key])) {
                    $config[$_cat][$_key] = $_fb['value'];
                }
            }
        }
    }
}
unset($_dol_fallback_map, $_need_fallback, $_fb_rows, $_fb, $_fb_const, $_fb_target, $_cat, $_key);
# ---- Dolibarr native config bridge (MAIN_*) ----
# Bridge currency from MAIN_MONNAIE (overrides settings.php default)
$_dol_monnaie = $db->field("SELECT value FROM llx_const WHERE name='MAIN_MONNAIE' AND value != '' AND entity=1");
if (!empty($_dol_monnaie)) {
    $payment_currency = $_dol_monnaie;
    $template['payment_currency'] = $payment_currency;
    // Map currency code to symbol
    $_currency_symbols = array('USD'=>'$','EUR'=>'€','GBP'=>'£','XAF'=>'FCFA','XOF'=>'FCFA','CDF'=>'FC','JPY'=>'¥','CHF'=>'CHF','CAD'=>'CA$','AUD'=>'A$','ZAR'=>'R','BRL'=>'R$','INR'=>'₹','CNY'=>'¥','RUB'=>'₽');
    if (isset($_currency_symbols[$_dol_monnaie])) {
        $config['General']['currency_symbol'] = $_currency_symbols[$_dol_monnaie];
    }
    unset($_currency_symbols);
}
unset($_dol_monnaie);

# Bridge company info from MAIN_INFO_SOCIETE_* (fallback if SPACART_ config not set)
$_dol_societe_map = array(
    'MAIN_INFO_SOCIETE_NOM'     => array('Company', 'company_name'),
    'MAIN_INFO_SOCIETE_MAIL'    => array('Company', 'company_mail_from'),
    'MAIN_INFO_SOCIETE_TEL'     => array('Company', 'company_phone'),
    'MAIN_INFO_SOCIETE_ADDRESS' => array('Company', 'location_address'),
    'MAIN_INFO_SOCIETE_TOWN'    => array('Company', 'location_city'),
    'MAIN_INFO_SOCIETE_ZIP'     => array('Company', 'location_zipcode'),
);
$_dol_soc_rows = $db->all("SELECT name, value FROM llx_const WHERE name LIKE 'MAIN_INFO_SOCIETE_%' AND value != '' AND entity=1");
if (!empty($_dol_soc_rows)) {
    foreach ($_dol_soc_rows as $_sr) {
        if (isset($_dol_societe_map[$_sr['name']])) {
            $_cat = $_dol_societe_map[$_sr['name']][0];
            $_key = $_dol_societe_map[$_sr['name']][1];
            if (empty($config[$_cat][$_key])) {
                $config[$_cat][$_key] = $_sr['value'];
            }
        }
    }
    if (!empty($config['Company']['company_name'])) $company_name = $config['Company']['company_name'];
    if (!empty($config['Company']['company_mail_from'])) $company_email = $config['Company']['company_mail_from'];
}
unset($_dol_societe_map, $_dol_soc_rows, $_sr, $_cat, $_key);

# ---- End Dolibarr bridge ----
$config['company_name'] = $config['Company']['company_name'];
$config['company_url'] = $config['Company']['company_website'];
$template['config'] = $config;

$template['db'] = $db;

if ($_POST['login'] || $_GET['login'] || $_POST['get'] || $_GET['get'] || $_POST['userinfo'] || $_GET['userinfo'])
	redirect('/');

extract($_SESSION, EXTR_SKIP);

if (!$session_id) {
	$salt = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCD!@#^&&*%*($)*@#($%*#%)-=.,;\'][EFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 8 );
	$_SESSION['session_id'] = $session_id = md5($salt.$user_id.$_SERVER['REMOTE_ADDR'].$salt.time().rand(0,100).$salt);
}

if ($_GET['ac_email']) {
	if (!$_SESSION['cart']) {
		$tmp = $db->field("SELECT cart FROM users_carts WHERE email='".addslashes($_GET['ac_email'])."'");
		$_SESSION['userinfo']['email'] = $_GET['ac_email'];
		if ($tmp) {
			$_SESSION['cart'] = $cart = unserialize($tmp);
		}
	}
}

// SEC-CRIT-3: Removed set_rem GET parameter backdoor (session hijack via URL)
// Previously allowed setting remember cookie via ?set_rem=<token> GET parameter


if (empty($_SESSION['login'])) {
	if ($_COOKIE['remember']) {
		$login = $db->field("SELECT userid FROM users_remember WHERE pswd='".addslashes($_COOKIE['remember'])."'");
		$usertype = $db->field("SELECT usertype FROM users WHERE id='".$login."'");
		if ($usertype == 'A' || !$usertype) {
			func_setcookie('remember', '');
		} elseif ($login) {
			$_SESSION['login'] = $login;
			redirect($_SERVER['REQUEST_URI']);
		} else
			func_setcookie('remember', '');
	}

	$userinfo = array(
		'membershipid'	=> '0',
		'state'			=> '',
		'b_state'		=> '',
	);
	$template['userinfo'] = $userinfo;
	$template['js'][] = 'login';
	$template['css'][] = 'login';
	unset($login);
} else {
	if (!$_SESSION['cart']) {
		$tmp = $db->field("SELECT cart FROM users_carts WHERE userid='$login'");
		if ($tmp) {
			$_SESSION['cart'] = $cart = unserialize($tmp);
		}
	}

	$template['css'][] = 'logged';
	$template['js'][] = 'logged';
	$userinfo = $db->row("SELECT * FROM users WHERE id=".$login." AND status=1");
	if (empty($userinfo)) {
        $_SESSION['login'] = '';
		func_setcookie('remember', '');
		redirect('/');
	}

    $template['userinfo'] = $userinfo;
	$tmp = $db->all("SELECT * FROM user_sessions WHERE userid='".$login."'");
	if (!empty($tmp)) {
		$sessions = array();
		foreach ($tmp as $v) {
			$sessions[$v['name']] = arrayMap('stripslashes', unserialize($v['value']));
		}

		if (!$sessions['to_remove'])
			$to_remove = '';

		extract($sessions, EXTR_SKIP);
	}

	if ($_SESSION['recently']) {
		$db->query("REPLACE INTO user_sessions SET userid='".$login."', name='recently', value='".addslashes(serialize($_SESSION['recently']))."'");
	} elseif ($sessions['recently']) {
		$_SESSION['recently'] = $sessions['recently'];
	}
}

$template['css'][] = 'register';
$template['js'][] = 'register';

$template['date_format'] = $date_format;
$template['datetime_format'] = $datetime_format;

$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

if (preg_match('/msie/', $userAgent) || preg_match('/rv:11.0/', $userAgent)) {
	$template['ie'] = true;
	$template['browser'] = 1;
} elseif (preg_match('/opera/', $userAgent)) {
	$template['browser'] = 4;
} elseif (preg_match('/chrome/', $userAgent))
	$template['browser'] = 2;
elseif (preg_match('/safari/', $userAgent))
	$template['browser'] = 3;
else
	$template['browser'] = 5;

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || $get['0'] == 'ajax') {
	$template['is_ajax'] = $is_ajax = true;
} else {
	$is_ajax = false;
}

$template['order_statuses'] = $order_statuses = array(
	'4'	=> 'Declined',
	'5'	=> 'Failed',
	'1'	=> 'Queued',
	'2'	=> 'Paid',
	'6'	=> 'Shipped',
	'3'	=> 'Completed',
);

$template['shipping_fee'] = $shipping_fee = 9.95;

$template['email_header'] = $email_header = 'You have received this email because you or someone else with your email registered on our site '.$company_name.'.<br /><br />';
$template['signature'] = $signature = '--<br />Warmest regards,<br />'.$company_name.'<br />'.$company_slogan;

// SEC-CRIT-6: Load security functions (CSRF, upload validation, rate limiting)
require_once SITE_ROOT . '/includes/func/func.security.php';
$template['csrf_token'] = spacart_csrf_token();

require_once SITE_ROOT . '/vendor/autoload.php';