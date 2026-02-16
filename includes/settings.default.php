<?php
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
# LOCAL VERSION FOR DEVELOPMENT TO KEEP SAME SETTINGS FILE
	# It recreate cached files on every call
	define('DEVELOPMENT', true);
	define('DEMO', false);
} else {
	# so here it's false
	define('DEVELOPMENT', false);
	define('DEMO', false);
}

$tmp = str_replace('/includes', '', dirname(__FILE__));
$tmp = str_replace('\\includes', '', $tmp);

define('SITE_ROOT', $tmp);

# Use MySQLi
$is_mysqli = true;

# MySQL server host
$sql_server = '{{sql_server}}';
if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' && php_sapi_name() != 'cli') {
# LOCAL VERSION FOR DEVELOPMENT TO KEEP SAME SETTINGS FILE
	$sql_user = '{{sql_user}}';
	$sql_password = '{{sql_password}}';
	$sql_database = '{{sql_database}}';

	$http_domain = '{{http_host}}';
	$https_domain = '{{http_host}}';
} else {
	$sql_user = '{{sql_user}}';
	$sql_password = '{{sql_password}}';
	$sql_database = '{{sql_database}}';

	$http_domain = '{{http_host}}';
	$https_domain = '{{http_host}}';
}

# Use warehouse false or true
$warehouse_enabled = false;

# Design theme color editor
# 1 - on
# 0 - off
$design_mode = '{{design_mode}}';


# Below require advanced knowledges
$web_dir = '';

if ($_SERVER['HTTP_HOST'] != $http_domain && php_sapi_name() != 'cli') {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: http://".$http_domain.$_SERVER['REQUEST_URI']);
	exit;
}

$http_location = 'http://'.$http_domain;
$https_location = 'https://'.$https_domain;

if ($_SERVER['HTTPS'])
	$current_location = $https_location.$web_dir;
else
	$current_location = $http_location.$web_dir;

$ajax_delimiter = '|-|+|=|';
$date_format = 'm/d/Y';
$datetime_format = 'H:i:s m/d/Y';

# Image resize to use ImageMagick. Set False if use PHP GD.
$is_image_magick = '{{is_image_magick}}';
$image_magick_quality = 75;

# PAYMENT CURRENCY FOR Stripe and Paypal as internal script uses. Use currency code per https://support.stripe.com/questions/which-currencies-does-stripe-support , Bank Accounts column in table
$payment_currency = '{{payment_currency}}';

# Set default lanugage
$lng = 'en';

# Design theme color editor
# 1 - on
# 0 - off
$design_mode = 1;

# Chagne that value after you edit CSS or JS and want browser cache to be updated. For example script.js will looks like script.js?1 or ?2 etc. Each new value will load CSS and JS into cache once again.
$css_js_cache = 1;