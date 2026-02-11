<?php
/**
 * SpaCart Cron - Abandoned carts reminder
 * Send email to customers who have items in cart but didn't checkout
 * Run daily via Dolibarr cron
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res = @include '../../../main.inc.php';
if (!$res) $res = @include '../../../../main.inc.php';
if (!$res) die('Dolibarr not found');

require_once dirname(__DIR__).'/lib/spacart.lib.php';
require_once dirname(__DIR__).'/includes/func/func.core.php';

$hoursThreshold = getDolGlobalInt('SPACART_ABANDONED_CART_HOURS', 24);
$cutoff = date('Y-m-d H:i:s', time() - $hoursThreshold * 3600);

// Find abandoned carts with email
$sql = "SELECT c.rowid as cart_id, c.subtotal, c.tms as last_update,";
$sql .= " cu.email, cu.firstname, cu.lastname";
$sql .= " FROM ".MAIN_DB_PREFIX."spacart_cart c";
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."spacart_customer cu ON cu.rowid = c.fk_customer";
$sql .= " WHERE c.status = 'active'";
$sql .= " AND c.tms < '".$db->escape($cutoff)."'";
$sql .= " AND c.subtotal > 0";
$sql .= " AND cu.email IS NOT NULL AND cu.email != ''";
// Don't send if already notified (check note in cart)
$sql .= " AND c.rowid NOT IN (SELECT CAST(conf_value AS UNSIGNED) FROM ".MAIN_DB_PREFIX."spacart_config WHERE conf_name LIKE 'abandoned_notified_%')";

$resql = $db->query($sql);
$count = 0;

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $subject = 'Vous avez oublié quelque chose dans votre panier !';
        $body = 'Bonjour '.$obj->firstname.",\n\n";
        $body .= "Vous avez des articles dans votre panier d'une valeur de ".spacartFormatPrice($obj->subtotal).".\n";
        $body .= "Finalisez votre commande avant qu'il ne soit trop tard !\n\n";
        $body .= DOL_MAIN_URL_ROOT.'/custom/spacart/public/#/cart';
        $body .= "\n\nCordialement,\n".getDolGlobalString('SPACART_COMPANY_NAME', 'La boutique');

        $from = getDolGlobalString('SPACART_COMPANY_EMAIL', getDolGlobalString('MAIN_MAIL_EMAIL_FROM', ''));

        if (spacart_send_mail($obj->email, $subject, $body, $from)) {
            // Mark as notified
            $db->query("INSERT INTO ".MAIN_DB_PREFIX."spacart_config (conf_name, conf_value) VALUES ('abandoned_notified_".$obj->cart_id."', '".$obj->cart_id."')");
            $count++;
        }
    }
}

print "SpaCart abandoned carts: ".$count." emails sent\n";
exit(0);
