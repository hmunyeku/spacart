<?php
function generateSalt($length = 16) {
    $characters = '!@#$%^&*()_+-=;":|\/?.,><[]{}0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


/**
 * Hash a password using bcrypt (replaces md5).
 * @param string $password  Plain text password
 * @return string  Bcrypt hash (60 chars, starts with $2y$)
 */
function spacart_password_hash($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify a password against a stored hash.
 * Supports both bcrypt (new) and MD5+salt (legacy).
 * Auto-rehashes legacy MD5 passwords to bcrypt on successful login.
 *
 * @param string $password     Plain text password
 * @param string $stored_hash  Hash from DB
 * @param string $salt         Legacy salt (for MD5 hashes)
 * @param int    $user_id      User ID (for auto-rehash UPDATE)
 * @return bool
 */
function spacart_password_verify($password, $stored_hash, $salt = '', $user_id = 0) {
    global $db;

    // New bcrypt hash (starts with $2y$)
    if (substr($stored_hash, 0, 4) === '$2y$') {
        return password_verify($password, $stored_hash);
    }

    // Legacy MD5 check: try md5(password.salt) first, then md5(password) alone
    if (md5($password . $salt) === $stored_hash || md5($password) === $stored_hash) {
        // Auto-rehash to bcrypt so next login is secure
        if ($user_id > 0) {
            $new_hash = spacart_password_hash($password);
            $db->query("UPDATE users SET password='" . addslashes($new_hash) . "' WHERE id='" . intval($user_id) . "'");
        }
        return true;
    }

    return false;
}
