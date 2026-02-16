<?php
/**
 * SpaCart Cron - Check pending Stripe payments
 * Verifies PaymentIntent status for orders stuck in "pending" state
 * Uses Dolibarr's native Stripe module
 * Run every 15 minutes via Dolibarr cron
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res = @include '../../../main.inc.php';
if (!$res) $res = @include '../../../../main.inc.php';
if (!$res) die('Dolibarr not found');

require_once dirname(__DIR__).'/lib/spacart.lib.php';

// Check Stripe module is enabled
if (!isModEnabled('stripe')) {
    print "SpaCart check_stripe: Stripe module not enabled in Dolibarr\n";
    exit(0);
}

require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

$status = getDolGlobalString('STRIPE_LIVE') ? 1 : 0;

// Find orders with pending Stripe payment (created more than 10 min ago, less than 24h)
$sql = "SELECT c.rowid, c.ref, c.ref_ext, c.note_private FROM ".MAIN_DB_PREFIX."commande c";
$sql .= " WHERE c.fk_statut = ".Commande::STATUS_DRAFT;
$sql .= " AND c.module_source = 'spacart'";
$sql .= " AND c.date_creation > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$sql .= " AND c.date_creation < DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
$sql .= " AND c.note_private LIKE '%stripe_payment_intent%'";

$resql = $db->query($sql);
$checked = 0;
$updated = 0;

if ($resql) {
    $techUser = new User($db);
    $techUser->fetch(getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1));

    while ($obj = $db->fetch_object($resql)) {
        // Extract PaymentIntent ID from note_private
        if (preg_match('/stripe_payment_intent[=: ]+(\w+)/i', $obj->note_private, $m)) {
            $piId = $m[1];
            $checked++;

            try {
                // Use Stripe SDK loaded by config.php
                $pi = \Stripe\PaymentIntent::retrieve($piId);

                if ($pi->status === 'succeeded') {
                    $order = new Commande($db);
                    $order->fetch($obj->rowid);
                    $order->valid($techUser);
                    $order->note_private .= "\n[Cron] Stripe payment confirmed: ".$piId." at ".date('Y-m-d H:i:s');
                    $order->update_note($order->note_private, '_private');
                    $updated++;
                    print "  Order ".$obj->ref.": PAID (validated)\n";

                    // Trigger complete sale chain (invoice + payment + stock)
                    // Extract SpaCart order ID from ref_ext (format: SPACART-{orderid})
                    $spacart_orderid = null;
                    if (preg_match('/^SPACART-(\d+)$/', $obj->ref_ext ?? '', $om)) {
                        $spacart_orderid = intval($om[1]);
                    } elseif (preg_match('/SpaCart Order #(\d+)/', $obj->note_private, $om)) {
                        $spacart_orderid = intval($om[1]);
                    }
                    if ($spacart_orderid) {
                        try {
                            // Boot SpaCart DB wrapper (func.dolibarr_sync uses SpaCart $db, not DoliDB)
                            $spacart_root = dirname(__DIR__);
                            require_once $spacart_root.'/includes/settings.php';
                            require_once $spacart_root.'/includes/database_mysqli.php';
                            require_once $spacart_root.'/includes/func/func.dolibarr_sync.php';
                            $doli_db_backup = $GLOBALS['db'];
                            $spacart_db = new Database();
                            $spacart_db->connect();
                            $spacart_db->setUTF8();
                            $GLOBALS['db'] = $spacart_db;
                            spacart_complete_sale_chain($spacart_orderid);
                            $GLOBALS['db'] = $doli_db_backup;
                            print "  -> Sale chain completed for SpaCart order #".$spacart_orderid."\n";
                        } catch (Exception $e) {
                            if (isset($doli_db_backup)) { $GLOBALS['db'] = $doli_db_backup; } else { $GLOBALS['db'] = $db; }
                            print "  -> Sale chain error: ".$e->getMessage()."\n";
                            error_log('SpaCart check_stripe: sale chain error for order #'.$spacart_orderid.': '.$e->getMessage());
                        }
                    }


                } elseif (in_array($pi->status, array('canceled', 'requires_payment_method'))) {
                    $order = new Commande($db);
                    $order->fetch($obj->rowid);
                    $order->note_private .= "\n[Cron] Stripe payment ".$pi->status.": ".$piId." at ".date('Y-m-d H:i:s');
                    $order->update_note($order->note_private, '_private');
                    print "  Order ".$obj->ref.": ".$pi->status."\n";

                } else {
                    print "  Order ".$obj->ref.": still ".$pi->status."\n";
                }
            } catch (Exception $e) {
                print "  Order ".$obj->ref.": Stripe API error - ".$e->getMessage()."\n";
            }
        }
    }
}

print "SpaCart check_stripe: ".$checked." checked, ".$updated." validated\n";
exit(0);
