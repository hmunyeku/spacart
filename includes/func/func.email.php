<?php
/**
 * SpaCart Email Functions
 * 
 * Provides SMTP email sending using Dolibarr's SMTP configuration.
 * Falls back to PHP mail() if SMTP is not configured.
 * 
 * Dolibarr settings read from llx_const:
 *   MAIN_MAIL_SENDMODE, MAIN_MAIL_SMTP_SERVER, MAIN_MAIL_SMTP_PORT,
 *   MAIN_MAIL_SMTPS_ID, MAIN_MAIL_SMTPS_PW
 */

/**
 * Get Dolibarr SMTP settings from llx_const
 * 
 * @return array|false SMTP settings or false if not configured
 */
function spacart_get_smtp_settings() {
    static $cache = null;
    if ($cache !== null) return $cache;
    
    $doli_db = spacart_get_dolibarr_db();
    if (!$doli_db) {
        $cache = false;
        return false;
    }
    
    $keys = array(
        'MAIN_MAIL_SENDMODE',
        'MAIN_MAIL_SMTP_SERVER',
        'MAIN_MAIL_SMTP_PORT',
        'MAIN_MAIL_SMTPS_ID',
        'MAIN_MAIL_SMTPS_PW'
    );
    
    $settings = array();
    $in = "'" . implode("','", $keys) . "'";
    $result = $doli_db->query("SELECT name, value FROM llx_const WHERE name IN ($in) AND entity IN (0,1) ORDER BY entity DESC");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // entity=1 overrides entity=0 (later rows override earlier due to ORDER BY)
            if (!isset($settings[$row['name']]) || true) {
                $settings[$row['name']] = $row['value'];
            }
        }
    }
    
    // Check if SMTP is actually configured
    $sendmode = isset($settings['MAIN_MAIL_SENDMODE']) ? $settings['MAIN_MAIL_SENDMODE'] : '';
    if (!in_array($sendmode, array('smtps', 'smtp'))) {
        $cache = false;
        return false;
    }
    
    if (empty($settings['MAIN_MAIL_SMTP_SERVER'])) {
        $cache = false;
        return false;
    }
    
    $cache = array(
        'mode'     => $sendmode,
        'host'     => $settings['MAIN_MAIL_SMTP_SERVER'],
        'port'     => !empty($settings['MAIN_MAIL_SMTP_PORT']) ? intval($settings['MAIN_MAIL_SMTP_PORT']) : 587,
        'username' => isset($settings['MAIN_MAIL_SMTPS_ID']) ? $settings['MAIN_MAIL_SMTPS_ID'] : '',
        'password' => isset($settings['MAIN_MAIL_SMTPS_PW']) ? $settings['MAIN_MAIL_SMTPS_PW'] : '',
    );
    
    return $cache;
}

/**
 * Send email using Dolibarr SMTP settings (via PHPMailer) or PHP mail() as fallback
 * 
 * @param string $to      Recipient email address
 * @param string $subject Email subject
 * @param string $body    HTML email body
 * @param string $from    Sender email (optional, defaults to company email)
 * @return bool           True on success, false on failure
 */
function spacart_send_email($to, $subject, $body, $from = '') {
    global $config, $company_email;
    
    if (!$from) {
        $from = !empty($config['Company']['support_department']) ? $config['Company']['support_department'] : $company_email;
    }
    
    $company_name = !empty($config['Company']['company_name']) ? $config['Company']['company_name'] : 'SpaCart';
    
    // Try SMTP via PHPMailer first
    $smtp = spacart_get_smtp_settings();
    
    if ($smtp && class_exists('PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            
            // Configure SMTP
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->Port       = $smtp['port'];
            $mail->SMTPSecure = ($smtp['mode'] === 'smtps') ? PHPMailer::ENCRYPTION_STARTTLS : '';
            
            // Port-based encryption detection
            if ($smtp['port'] == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtp['port'] == 587) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            if (!empty($smtp['username'])) {
                $mail->SMTPAuth = true;
                $mail->Username = $smtp['username'];
                $mail->Password = $smtp['password'];
            } else {
                $mail->SMTPAuth = false;
            }
            
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->setFrom($from, $company_name);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('SpaCart SMTP mail error: ' . $e->getMessage());
            // Fall through to PHP mail()
        }
    }
    
    // Fallback: use PHP mail()
    $headers  = "From: " . $company_name . " <" . $from . ">\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    
    return @mail($to, $subject, $body, $headers);
}
