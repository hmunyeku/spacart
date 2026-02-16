<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * SpaCart Backoffice - Authentication & Session Management
 * Standalone admin panel authentication (not inside Dolibarr UI)
 * Uses bcrypt for passwords, separate session, CSRF protection
 */

// ============================================================
// 1. Load Dolibarr environment (NOLOGIN mode)
// ============================================================
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('MAIN_DISABLE_DEBUGBAR')) {
	define('MAIN_DISABLE_DEBUGBAR', '1');
}
// Prevent debugbar from loading (wraps $db with TraceableDB)
$_GET['dol_use_jmobile'] = 1;

// Load Dolibarr main.inc.php going up from backoffice/includes/
$res = 0;
if (!$res && file_exists(dirname(__FILE__).'/../../../main.inc.php')) {
	$res = @include dirname(__FILE__).'/../../../main.inc.php';
}
if (!$res && file_exists(dirname(__FILE__).'/../../../../main.inc.php')) {
	$res = @include dirname(__FILE__).'/../../../../main.inc.php';
}
if (!$res) {
	http_response_code(500);
	die('Dolibarr main.inc.php not found');
}

// Load SpaCart lib (for spacartHashPassword, spacartGenerateToken, etc.)
require_once DOL_DOCUMENT_ROOT.'/custom/spacart/lib/spacart.lib.php';

// ============================================================
// 2. Start separate admin session
// ============================================================
if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}
session_name('spacart_admin_session');
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// ============================================================
// 3. Auto-create llx_spacart_admin table if not exists
// ============================================================
$_spacart_admin_table = MAIN_DB_PREFIX.'spacart_admin';

$sql_create = "CREATE TABLE IF NOT EXISTS ".$_spacart_admin_table." (
	rowid        INT AUTO_INCREMENT PRIMARY KEY,
	email        VARCHAR(255) NOT NULL,
	password     VARCHAR(255) NOT NULL,
	firstname    VARCHAR(128) DEFAULT '',
	lastname     VARCHAR(128) DEFAULT '',
	role         VARCHAR(50) DEFAULT 'admin',
	status       SMALLINT DEFAULT 1,
	fk_user      INT DEFAULT 0,
	last_login   DATETIME DEFAULT NULL,
	remember_token VARCHAR(255) DEFAULT NULL,
	entity       INT DEFAULT 1,
	date_creation DATETIME DEFAULT NULL,
	tms          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$db->query($sql_create);


// ============================================================
// 4. Authentication functions
// ============================================================

/**
 * Admin login - verify credentials against spacart_admin table and Dolibarr users
 *
 * @param  string $email    Admin email
 * @param  string $password Plain text password
 * @param  bool   $remember Whether to set a remember-me cookie
 * @return array  Result with 'success' boolean and 'message'
 */
function spacartAdminLogin($email, $password, $remember = false)
{
	global $db, $conf;

	$email = trim($email);
	if (empty($email) || empty($password)) {
		return array('success' => false, 'message' => 'Email et mot de passe requis');
	}

	$admin = null;
	$source = ''; // 'spacart' or 'dolibarr'

	// --- Try spacart_admin table first ---
	$sql = "SELECT rowid, email, password, firstname, lastname, role, status, fk_user, entity";
	$sql .= " FROM ".MAIN_DB_PREFIX."spacart_admin";
	$sql .= " WHERE email = '".$db->escape($email)."'";
	$sql .= " AND entity = ".(int) $conf->entity;
	$resql = $db->query($sql);

	if ($resql && $db->num_rows($resql) > 0) {
		$row = $db->fetch_object($resql);

		if ((int) $row->status !== 1) {
			return array('success' => false, 'message' => 'Ce compte administrateur est desactive');
		}

		if (!password_verify($password, $row->password)) {
			return array('success' => false, 'message' => 'Identifiants incorrects');
		}

		$admin = $row;
		$source = 'spacart';
	}

	// --- Fallback: try Dolibarr user table (admin=1 or has spacart rights) ---
	if (!$admin) {
		$sql2 = "SELECT u.rowid, u.email, u.login, u.pass_crypted, u.firstname, u.lastname, u.admin, u.statut, u.entity";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " WHERE u.email = '".$db->escape($email)."'";
		$sql2 .= " AND u.statut = 1";
		$sql2 .= " AND u.entity IN (0, ".(int) $conf->entity.")";
		$resql2 = $db->query($sql2);

		if ($resql2 && $db->num_rows($resql2) > 0) {
			$dol_user = $db->fetch_object($resql2);

			// Check if Dolibarr superadmin or has spacart module rights
			$has_access = false;
			if ((int) $dol_user->admin === 1) {
				$has_access = true;
			} else {
				// Check rights table for spacart module access
				$sql_rights = "SELECT r.id FROM ".MAIN_DB_PREFIX."user_rights as r";
				$sql_rights .= " INNER JOIN ".MAIN_DB_PREFIX."rights_def as rd ON rd.id = r.fk_id";
				$sql_rights .= " WHERE r.fk_user = ".(int) $dol_user->rowid;
				$sql_rights .= " AND rd.module = 'spacart'";
				$res_rights = $db->query($sql_rights);
				if ($res_rights && $db->num_rows($res_rights) > 0) {
					$has_access = true;
				}
			}

			if (!$has_access) {
				return array('success' => false, 'message' => 'Acces refuse - droits insuffisants');
			}

			// Verify password: Dolibarr uses various hashing methods
			// Try password_verify first (bcrypt/argon2), then MD5 fallback
			$password_ok = false;
			if (!empty($dol_user->pass_crypted)) {
				if (password_verify($password, $dol_user->pass_crypted)) {
					$password_ok = true;
				} elseif (md5($password) === $dol_user->pass_crypted) {
					$password_ok = true;
				}
			}

			if (!$password_ok) {
				return array('success' => false, 'message' => 'Identifiants incorrects');
			}

			// Build a compatible admin object from Dolibarr user
			$admin = new stdClass();
			$admin->rowid = 0; // No spacart_admin row
			$admin->email = $dol_user->email;
			$admin->firstname = $dol_user->firstname;
			$admin->lastname = $dol_user->lastname;
			$admin->role = ((int) $dol_user->admin === 1) ? 'superadmin' : 'admin';
			$admin->status = 1;
			$admin->fk_user = (int) $dol_user->rowid;
			$admin->entity = (int) $conf->entity;
			$source = 'dolibarr';
		}
	}

	// --- No valid admin found ---
	if (!$admin) {
		return array('success' => false, 'message' => 'Identifiants incorrects');
	}

	// --- Login success: set session ---
	$_SESSION['spacart_admin_id'] = (int) $admin->rowid;
	$_SESSION['spacart_admin_email'] = $admin->email;
	$_SESSION['spacart_admin_firstname'] = $admin->firstname;
	$_SESSION['spacart_admin_lastname'] = $admin->lastname;
	$_SESSION['spacart_admin_role'] = $admin->role;
	$_SESSION['spacart_admin_fk_user'] = isset($admin->fk_user) ? (int) $admin->fk_user : 0;
	$_SESSION['spacart_admin_source'] = $source;
	$_SESSION['spacart_admin_entity'] = (int) $admin->entity;
	$_SESSION['spacart_admin_logged_in'] = true;

	// Update last_login for spacart_admin entries
	if ($source === 'spacart' && (int) $admin->rowid > 0) {
		$db->query("UPDATE ".MAIN_DB_PREFIX."spacart_admin SET last_login = NOW() WHERE rowid = ".(int) $admin->rowid);
	}

	// Remember-me cookie
	if ($remember && $source === 'spacart' && (int) $admin->rowid > 0) {
		$token = bin2hex(random_bytes(32));
		$db->query("UPDATE ".MAIN_DB_PREFIX."spacart_admin SET remember_token = '".$db->escape($token)."' WHERE rowid = ".(int) $admin->rowid);
		setcookie('spacart_admin_remember', $token, time() + 30 * 86400, '/', '', true, true);
	}

	return array('success' => true, 'message' => 'Connexion reussie');
}

/**
 * Admin logout - destroy session and clear remember cookie
 */
function spacartAdminLogout()
{
	global $db;

	// Clear remember token in DB
	if (!empty($_SESSION['spacart_admin_id']) && (int) $_SESSION['spacart_admin_id'] > 0) {
		$db->query("UPDATE ".MAIN_DB_PREFIX."spacart_admin SET remember_token = NULL WHERE rowid = ".(int) $_SESSION['spacart_admin_id']);
	}

	// Clear all admin session vars
	$keys = array(
		'spacart_admin_id', 'spacart_admin_email', 'spacart_admin_firstname',
		'spacart_admin_lastname', 'spacart_admin_role', 'spacart_admin_fk_user',
		'spacart_admin_source', 'spacart_admin_entity', 'spacart_admin_logged_in',
		'spacart_admin_csrf_token'
	);
	foreach ($keys as $k) {
		unset($_SESSION[$k]);
	}

	// Clear remember cookie
	if (isset($_COOKIE['spacart_admin_remember'])) {
		setcookie('spacart_admin_remember', '', time() - 3600, '/', '', true, true);
	}

	// Destroy session entirely
	session_destroy();
}

/**
 * Check if an admin is currently logged in
 *
 * Returns admin object if logged in, null otherwise.
 * Checks session first, then remember-me cookie.
 *
 * @return object|null Admin user object or null
 */
function spacartAdminCheck()
{
	global $db, $conf;

	// --- Check session first ---
	if (!empty($_SESSION['spacart_admin_logged_in']) && $_SESSION['spacart_admin_logged_in'] === true) {
		$admin = new stdClass();
		$admin->rowid = (int) ($_SESSION['spacart_admin_id'] ?? 0);
		$admin->email = $_SESSION['spacart_admin_email'] ?? '';
		$admin->firstname = $_SESSION['spacart_admin_firstname'] ?? '';
		$admin->lastname = $_SESSION['spacart_admin_lastname'] ?? '';
		$admin->role = $_SESSION['spacart_admin_role'] ?? 'admin';
		$admin->fk_user = (int) ($_SESSION['spacart_admin_fk_user'] ?? 0);
		$admin->source = $_SESSION['spacart_admin_source'] ?? 'spacart';
		$admin->entity = (int) ($_SESSION['spacart_admin_entity'] ?? $conf->entity);
		return $admin;
	}

	// --- Check remember-me cookie ---
	if (!empty($_COOKIE['spacart_admin_remember'])) {
		$token = $_COOKIE['spacart_admin_remember'];

		$sql = "SELECT rowid, email, firstname, lastname, role, status, fk_user, entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."spacart_admin";
		$sql .= " WHERE remember_token = '".$db->escape($token)."'";
		$sql .= " AND entity = ".(int) $conf->entity;
		$sql .= " AND status = 1";
		$resql = $db->query($sql);

		if ($resql && $db->num_rows($resql) > 0) {
			$admin = $db->fetch_object($resql);

			// Restore session from remember token
			$_SESSION['spacart_admin_id'] = (int) $admin->rowid;
			$_SESSION['spacart_admin_email'] = $admin->email;
			$_SESSION['spacart_admin_firstname'] = $admin->firstname;
			$_SESSION['spacart_admin_lastname'] = $admin->lastname;
			$_SESSION['spacart_admin_role'] = $admin->role;
			$_SESSION['spacart_admin_fk_user'] = (int) $admin->fk_user;
			$_SESSION['spacart_admin_source'] = 'spacart';
			$_SESSION['spacart_admin_entity'] = (int) $admin->entity;
			$_SESSION['spacart_admin_logged_in'] = true;

			// Update last_login
			$db->query("UPDATE ".MAIN_DB_PREFIX."spacart_admin SET last_login = NOW() WHERE rowid = ".(int) $admin->rowid);

			// Rotate remember token for security
			$new_token = bin2hex(random_bytes(32));
			$db->query("UPDATE ".MAIN_DB_PREFIX."spacart_admin SET remember_token = '".$db->escape($new_token)."' WHERE rowid = ".(int) $admin->rowid);
			setcookie('spacart_admin_remember', $new_token, time() + 30 * 86400, '/', '', true, true);

			$admin->source = 'spacart';
			return $admin;
		}

		// Invalid token - clear cookie
		setcookie('spacart_admin_remember', '', time() - 3600, '/', '', true, true);
	}

	return null;
}

/**
 * Require authentication - redirect to login if not logged in
 *
 * Call this at the top of every protected admin page.
 *
 * @return object Admin user object (guaranteed non-null if function returns)
 */
function spacartAdminRequireAuth()
{
	$admin = spacartAdminCheck();

	if (!$admin) {
		// Build login URL relative to backoffice root
		$login_url = dirname($_SERVER['SCRIPT_NAME']);
		// Go up if we're in a subdirectory
		if (basename($login_url) === 'includes' || basename($login_url) === 'pages') {
			$login_url = dirname($login_url);
		}
		$login_url = rtrim($login_url, '/').'/login.php';

		header('Location: '.$login_url);
		exit;
	}

	return $admin;
}

/**
 * Generate or retrieve CSRF token from session
 *
 * @return string CSRF token
 */
function spacartAdminGetCSRFToken()
{
	if (empty($_SESSION['spacart_admin_csrf_token'])) {
		$_SESSION['spacart_admin_csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['spacart_admin_csrf_token'];
}

/**
 * Verify that a POST request contains a valid CSRF token
 *
 * @return bool True if valid, false otherwise
 */
function spacartAdminCheckCSRF()
{
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		return false;
	}

	$submitted = $_POST['_csrf_token'] ?? '';
	$expected = $_SESSION['spacart_admin_csrf_token'] ?? '';

	if (empty($submitted) || empty($expected)) {
		return false;
	}

	return hash_equals($expected, $submitted);
}

/**
 * Get current logged-in admin user object from session
 *
 * @return object|null Admin user object or null if not logged in
 */
function spacartAdminCurrentUser()
{
	if (empty($_SESSION['spacart_admin_logged_in']) || $_SESSION['spacart_admin_logged_in'] !== true) {
		return null;
	}

	$admin = new stdClass();
	$admin->rowid = (int) ($_SESSION['spacart_admin_id'] ?? 0);
	$admin->email = $_SESSION['spacart_admin_email'] ?? '';
	$admin->firstname = $_SESSION['spacart_admin_firstname'] ?? '';
	$admin->lastname = $_SESSION['spacart_admin_lastname'] ?? '';
	$admin->role = $_SESSION['spacart_admin_role'] ?? 'admin';
	$admin->fk_user = (int) ($_SESSION['spacart_admin_fk_user'] ?? 0);
	$admin->source = $_SESSION['spacart_admin_source'] ?? 'spacart';
	$admin->entity = (int) ($_SESSION['spacart_admin_entity'] ?? 1);
	$admin->fullname = trim($admin->firstname.' '.$admin->lastname);

	return $admin;
}


// ============================================================
// 5. Auto-create default admin if table is empty
// ============================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."spacart_admin WHERE entity = ".(int) $conf->entity;
$res_count = $db->query($sql_count);
if ($res_count) {
	$obj_count = $db->fetch_object($res_count);
	if ((int) $obj_count->nb === 0) {
		$default_email = 'admin@coexdis.com';
		$default_password = password_hash('admin123', PASSWORD_BCRYPT, array('cost' => 12));

		$sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."spacart_admin";
		$sql_insert .= " (email, password, firstname, lastname, role, status, fk_user, entity, date_creation)";
		$sql_insert .= " VALUES (";
		$sql_insert .= "'".$db->escape($default_email)."',";
		$sql_insert .= " '".$db->escape($default_password)."',";
		$sql_insert .= " 'Admin',";
		$sql_insert .= " 'SpaCart',";
		$sql_insert .= " 'superadmin',";
		$sql_insert .= " 1,";
		$sql_insert .= " 0,";
		$sql_insert .= " ".(int) $conf->entity.",";
		$sql_insert .= " NOW()";
		$sql_insert .= ")";
		$db->query($sql_insert);
	}
}
