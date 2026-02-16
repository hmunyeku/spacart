<?php
/**
 * SpaCart Settings Bridge for Dolibarr
 * This file replaces the default settings.php to connect SpaCart
 * to the Dolibarr database and environment.
 */

define('DEVELOPMENT', false);
define('DEMO', false);

// SITE_ROOT = spacart module directory
define('SITE_ROOT', dirname(dirname(__FILE__)));

// Use MySQLi (SpaCart default)
$is_mysqli = true;

// DB credentials
$sql_server = 'localhost';
$sql_user = 'spacart_user';
$sql_password = 'SpAcArT2026xCoex';
$sql_database = 'erp_main';

// Domain configuration
$http_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'erp.coexdis.com';
$https_domain = $http_domain;
$internal_http_domain = $http_domain; // Preserved for internal operations when proxy changes $http_domain

// Warehouse
$warehouse_enabled = false;

// Design mode
$design_mode = 0;

// Web directory (SpaCart is inside Dolibarr custom modules)
// Internal path always stays /custom/spacart (for filesystem operations)
$internal_web_dir = '/custom/spacart';
$web_dir = $internal_web_dir;

// ---- Reverse Proxy / Public URL Configuration ----
// Check for SPACART_PUBLIC_URL in Dolibarr constants
// This runs early (before boot.php DB connection) so we use a direct MySQLi query
$current_protocol = 'https';
try {
    $proxy_url = '';
    $_proxy_conn = new mysqli($sql_server, $sql_user, $sql_password, $sql_database);
    if (!$_proxy_conn->connect_error) {
        $_proxy_conn->set_charset('utf8');
        $_proxy_result = $_proxy_conn->query("SELECT value FROM llx_const WHERE name='SPACART_PUBLIC_URL' AND entity=1 AND value != '' LIMIT 1");
        if ($_proxy_result && $_proxy_row = $_proxy_result->fetch_assoc()) {
            $proxy_url = trim($_proxy_row['value']);
        }
        $_proxy_conn->close();
    }
    unset($_proxy_conn, $_proxy_result, $_proxy_row);

    if (!empty($proxy_url)) {
        $parsed = parse_url($proxy_url);
        if (!empty($parsed['host'])) {
            $http_domain = $parsed['host'];
            $https_domain = $http_domain;
            $web_dir = rtrim($parsed['path'] ?? '', '/');
            if (empty($web_dir)) $web_dir = '';
            $current_protocol = $parsed['scheme'] ?? 'https';
        }
    }
    unset($proxy_url, $parsed);
} catch (Exception $e) {
    // Silently fall back to default
}

define('INTERNAL_WEB_DIR', $internal_web_dir);

// Location URLs
$http_location = 'http://' . $http_domain . $web_dir;
$https_location = 'https://' . $http_domain . $web_dir;

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $current_location = $https_location;
    $current_protocol = 'https';
} else {
    $current_location = $http_location;
}

// If proxy is configured, always use the configured protocol
if (INTERNAL_WEB_DIR !== $web_dir) {
    $current_location = $current_protocol . '://' . $http_domain . $web_dir;
}

// AJAX delimiter (same as SpaCart default)
$ajax_delimiter = '|-|+|=|';

// Date formats
$date_format = 'd/m/Y';
$datetime_format = 'H:i:s d/m/Y';

// ImageMagick
$is_image_magick = false;
$image_magick_quality = 75;

// Payment currency
$payment_currency = 'EUR';

// Default language
$lng = 'fr';

// CSS/JS cache buster
$css_js_cache = 1;
