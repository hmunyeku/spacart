<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * SpaCart Backoffice - Utility Functions
 * Helpers for the standalone admin panel: flash messages, pagination,
 * file uploads, formatting, URL building, status labels & badges.
 */

// Prevent direct access - this file must be included after auth.php
// which sets up $db, $conf, and the admin session.
if (!function_exists('spacartAdminCheck')) {
	die('Direct access not allowed');
}


/**
 * Store a flash message in session
 *
 * @param string $message The message text
 * @param string $type    Bootstrap alert type: success, danger, warning, info
 */
function spacartAdminFlash($message, $type = 'success')
{
	if (!isset($_SESSION['spacart_admin_flash'])) {
		$_SESSION['spacart_admin_flash'] = array();
	}
	$_SESSION['spacart_admin_flash'][] = array(
		'message' => $message,
		'type'    => $type,
	);
}

/**
 * Retrieve and clear all flash messages from session
 *
 * @return array Array of flash message arrays ['message' => ..., 'type' => ...]
 */
function spacartAdminGetFlash()
{
	$messages = array();
	if (!empty($_SESSION['spacart_admin_flash'])) {
		$messages = $_SESSION['spacart_admin_flash'];
		unset($_SESSION['spacart_admin_flash']);
	}
	return $messages;
}

/**
 * Calculate pagination data from a COUNT query
 *
 * @param  string $sql_count  Full SQL query that returns a COUNT(*) as nb
 * @param  int    $page       Current page number (1-based)
 * @param  int    $per_page   Items per page (default 20)
 * @return array  Pagination data: offset, limit, total, total_pages, current_page
 */
function spacartAdminPaginate($sql_count, $page = 1, $per_page = 20)
{
	global $db;

	$page = max(1, (int) $page);
	$per_page = max(1, (int) $per_page);

	$total = 0;
	$resql = $db->query($sql_count);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$total = (int) $obj->nb;
	}

	$total_pages = ($total > 0) ? (int) ceil($total / $per_page) : 1;

	// Clamp page to valid range
	if ($page > $total_pages) {
		$page = $total_pages;
	}

	$offset = ($page - 1) * $per_page;

	return array(
		'offset'       => $offset,
		'limit'        => $per_page,
		'total'        => $total,
		'total_pages'  => $total_pages,
		'current_page' => $page,
	);
}

/**
 * Handle an image file upload with validation
 *
 * @param  string $fieldname    Name of the file input field
 * @param  string $dest_dir     Absolute path to the destination directory
 * @param  array  $allowed_ext  Allowed file extensions (lowercase)
 * @param  int    $max_size     Maximum file size in bytes (default 5 MB)
 * @return string|false         Filename on success, false on failure
 */
function spacartAdminUploadImage($fieldname, $dest_dir, $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp'), $max_size = 5242880)
{
	if (empty($_FILES[$fieldname]) || $_FILES[$fieldname]['error'] !== UPLOAD_ERR_OK) {
		return false;
	}

	$file = $_FILES[$fieldname];

	// Validate file size
	if ($file['size'] > $max_size) {
		return false;
	}

	// Validate extension
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if (!in_array($ext, $allowed_ext)) {
		return false;
	}

	// Validate MIME type
	$allowed_mimes = array(
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'webp' => 'image/webp',
	);
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($finfo, $file['tmp_name']);
	finfo_close($finfo);

	if (!isset($allowed_mimes[$ext]) || $allowed_mimes[$ext] !== $mime) {
		return false;
	}

	// Ensure destination directory exists
	if (!is_dir($dest_dir)) {
		@mkdir($dest_dir, 0755, true);
	}

	// Generate unique filename to avoid collisions
	$filename = date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;

	$dest_path = rtrim($dest_dir, '/').'/'.$filename;

	if (move_uploaded_file($file['tmp_name'], $dest_path)) {
		return $filename;
	}

	return false;
}

/**
 * Format a price amount with the configured currency symbol
 *
 * @param  float  $amount Amount to format
 * @return string Formatted price string
 */
function spacartAdminFormatPrice($amount)
{
	$symbol = getDolGlobalString('SPACART_CURRENCY_SYMBOL', '€');
	return number_format((float) $amount, 2, ',', ' ').' '.$symbol;
}

/**
 * Format a date/datetime string
 *
 * @param  string $date   Date string (any format accepted by strtotime)
 * @param  string $format PHP date() format string
 * @return string Formatted date, or empty string if invalid
 */
function spacartAdminFormatDate($date, $format = 'd/m/Y H:i')
{
	if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') {
		return '';
	}

	$ts = strtotime($date);
	if ($ts === false || $ts < 0) {
		return '';
	}

	return date($format, $ts);
}

/**
 * Build an admin panel URL with query parameters
 *
 * @param  string $page   Page filename (e.g., 'orders.php', 'products.php')
 * @param  array  $params Associative array of query string parameters
 * @return string Full URL
 */
function spacartAdminBuildUrl($page, $params = array())
{
	// Base URL: derive from current script location
	$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

	// If we're in a subdirectory (includes/, pages/), go up one level
	$basename = basename($base);
	if ($basename === 'includes' || $basename === 'pages') {
		$base = dirname($base);
	}

	$url = rtrim($base, '/').'/'.$page;

	if (!empty($params)) {
		$url .= '?'.http_build_query($params);
	}

	return $url;
}

/**
 * Get a SpaCart configuration value (wrapper for getDolGlobalString)
 *
 * @param  string $key     Configuration key (without SPACART_ prefix if desired)
 * @param  string $default Default value if not set
 * @return string Configuration value
 */
function spacartAdminGetConfig($key, $default = '')
{
	return getDolGlobalString($key, $default);
}

/**
 * Escape a string for safe HTML output
 *
 * @param  string $str Input string
 * @return string Escaped string
 */
function spacartAdminEscape($str)
{
	return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

/**
 * Truncate a string to a maximum length with ellipsis
 *
 * @param  string $str Input string
 * @param  int    $len Maximum length (default 100)
 * @return string Truncated string
 */
function spacartAdminTruncate($str, $len = 100)
{
	$str = (string) $str;
	if (mb_strlen($str, 'UTF-8') <= $len) {
		return $str;
	}
	return mb_substr($str, 0, $len, 'UTF-8').'...';
}

/**
 * Get a human-readable French label for a Dolibarr order status code
 *
 * Dolibarr commande.fk_statut values:
 *   -1 = Annulee
 *    0 = Brouillon
 *    1 = Validee
 *    2 = En cours (expedition/livraison)
 *    3 = Livree
 *
 * @param  int    $status Dolibarr order status code
 * @return string French label
 */
function spacartAdminOrderStatusLabel($status)
{
	$labels = array(
		-1 => 'Annulee',
		0  => 'Brouillon',
		1  => 'Validee',
		2  => 'En cours',
		3  => 'Livree',
	);

	return isset($labels[(int) $status]) ? $labels[(int) $status] : 'Inconnue';
}

/**
 * Get a premium badge-status HTML element for a Dolibarr order status code
 *
 * Uses the badge-status system with pulsing dot for active states.
 *
 * @param  int    $status Dolibarr order status code
 * @return string HTML badge element
 */
function spacartAdminOrderStatusBadge($status)
{
	$status = (int) $status;
	$label = spacartAdminOrderStatusLabel($status);

	$classes = array(
		-1 => 'status-cancelled',
		0  => 'status-draft',
		1  => 'status-validated',
		2  => 'status-processing',
		3  => 'status-delivered',
	);

	// Pulse dot for "in progress" statuses
	$pulse_statuses = array(2);

	$class = isset($classes[$status]) ? $classes[$status] : 'status-draft';
	$dot_class = in_array($status, $pulse_statuses) ? 'status-dot pulse' : 'status-dot';

	return '<span class="badge-status '.$class.'"><span class="'.$dot_class.'"></span>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</span>';
}

/**
 * Get a premium badge-status HTML element for a generic status string
 *
 * @param  string $status  Status key: active, inactive, draft, archived, published, pending, etc.
 * @param  string $label   Display label
 * @param  bool   $pulse   Show pulsing dot
 * @return string HTML badge element
 */
function spacartAdminStatusBadge($status, $label = '', $pulse = false)
{
	if ($label === '') {
		$label = ucfirst($status);
	}
	$dot_class = $pulse ? 'status-dot pulse' : 'status-dot';
	return '<span class="badge-status status-'.htmlspecialchars($status, ENT_QUOTES, 'UTF-8').'"><span class="'.$dot_class.'"></span>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</span>';
}
