<?php
/**
 * SpaCart Security Functions
 * File upload validation + Rate limiting
 * Added: 2026-02-15 (SEC-CRIT-4, SEC-CRIT-5)
 */

/**
 * Validate an uploaded file for security.
 * @param array $file Single $_FILES entry
 * @param int $max_size Max size in bytes (default 10MB)
 * @return array ['valid' => bool, 'error' => string, 'safe_name' => string]
 */
function spacart_validate_upload($file, $max_size = 10485760) {
    // Allowed extensions whitelist
    $allowed_ext = array('jpg','jpeg','png','gif','bmp','webp','pdf','doc','docx','xls','xlsx','csv','txt','zip','rar','7z');
    // Blocked extensions blacklist (double protection)
    $blocked_ext = array('php','php3','php4','php5','php7','phtml','pht','phps','cgi','pl','py','sh','bat','exe','com','dll','asp','aspx','jsp','htaccess','htpasswd','ini','env','phar','shtml');

    if (!isset($file['name']) || !isset($file['tmp_name'])) {
        return array('valid' => false, 'error' => 'Invalid file data');
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('valid' => false, 'error' => 'Upload error code: ' . $file['error']);
    }

    // Check size
    if ($file['size'] > $max_size) {
        return array('valid' => false, 'error' => 'File too large (max ' . round($max_size / 1048576) . 'MB)');
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $blocked_ext)) {
        return array('valid' => false, 'error' => 'File type not allowed: .' . $ext);
    }
    if (!in_array($ext, $allowed_ext)) {
        return array('valid' => false, 'error' => 'File type not supported: .' . $ext);
    }

    // Check for double extensions (e.g., file.php.jpg)
    $name_parts = explode('.', $file['name']);
    if (count($name_parts) > 2) {
        foreach ($name_parts as $part) {
            if (in_array(strtolower($part), $blocked_ext)) {
                return array('valid' => false, 'error' => 'Suspicious filename detected');
            }
        }
    }

    // Check MIME type for images
    if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'))) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp');
        if (!in_array($mime, $allowed_mimes)) {
            return array('valid' => false, 'error' => 'MIME type mismatch for image');
        }
    }

    // Sanitize filename (remove special chars, keep extension)
    $base = pathinfo($file['name'], PATHINFO_FILENAME);
    $safe_base = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base);
    $safe_name = $safe_base . '.' . $ext;

    return array('valid' => true, 'error' => '', 'safe_name' => $safe_name);
}

/**
 * Validate a single file from a multi-file upload array.
 * Extracts single-file entry from the multi-file $_FILES structure.
 * @param array $files The $_FILES array (multi-file structure)
 * @param int $index Index of the file in the multi-file array
 * @param int $max_size Max size in bytes
 * @return array ['valid' => bool, 'error' => string, 'safe_name' => string]
 */
function spacart_validate_upload_multi($files, $index, $max_size = 10485760) {
    $single = array(
        'name'     => $files['name'][$index],
        'type'     => $files['type'][$index],
        'tmp_name' => $files['tmp_name'][$index],
        'error'    => $files['error'][$index],
        'size'     => $files['size'][$index],
    );
    return spacart_validate_upload($single, $max_size);
}

// ====== Rate Limiting Functions (SEC-CRIT-5) ======

/**
 * Check rate limit for an action.
 * @param string $action 'login', 'password_reset', 'register'
 * @param int $max_attempts Max attempts in window
 * @param int $window_seconds Time window in seconds
 * @return bool true if allowed, false if rate limited
 */
function spacart_check_rate_limit($action, $max_attempts = 5, $window_seconds = 900) {
    global $db;
    $ip = addslashes(spacart_get_client_ip());
    $action = addslashes($action);

    // Clean old entries (> 24h) - runs occasionally
    if (rand(1, 10) === 1) {
        $db->query("DELETE FROM spacart_rate_limit WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }

    // Count recent attempts
    $count = $db->field("SELECT COUNT(*) FROM spacart_rate_limit
                         WHERE ip_address = '$ip' AND action_type = '$action'
                         AND attempt_time > DATE_SUB(NOW(), INTERVAL $window_seconds SECOND)");

    return intval($count) < $max_attempts;
}

/**
 * Record a rate limit attempt.
 * @param string $action 'login', 'password_reset', 'register'
 */
function spacart_record_attempt($action) {
    global $db;
    $ip = addslashes(spacart_get_client_ip());
    $action = addslashes($action);
    $db->query("INSERT INTO spacart_rate_limit (ip_address, action_type) VALUES ('$ip', '$action')");
}

/**
 * Get client IP address (handles proxies).
 * @return string IP address
 */
function spacart_get_client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
        // Validate IP format
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// ====== CSRF Token Functions (SEC-CRIT-6) ======

/**
 * Generate a CSRF token and store in session
 */
function spacart_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field
 */
function spacart_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . spacart_csrf_token() . '" />';
}

/**
 * Verify CSRF token from POST data
 * @return bool
 */
function spacart_csrf_verify() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    // Regenerate token after verification to prevent replay
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $valid;
}
