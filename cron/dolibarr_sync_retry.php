<?php
/**
 * SpaCart Cron - Retry failed Dolibarr synchronizations
 * Retries invoice/payment/stock creation for orders that failed initial sync.
 * Max 5 attempts per order/step. Runs every 15 minutes via Dolibarr cron.
 */

if (!defined("NOTOKENRENEWAL")) define("NOTOKENRENEWAL", "1");
if (!defined("NOREQUIREMENU"))  define("NOREQUIREMENU", "1");
if (!defined("NOREQUIREHTML"))  define("NOREQUIREHTML", "1");

// Load Dolibarr
$res = @include "../../../main.inc.php";
if (!$res) $res = @include "../../../../main.inc.php";
if (!$res) die("Dolibarr not found\n");

// Boot SpaCart DB wrapper
$spacart_root = dirname(__DIR__);
if (file_exists($spacart_root."/includes/settings.php")) {
    require_once $spacart_root."/includes/settings.php";
}
if (file_exists($spacart_root."/includes/database_mysqli.php")) {
    require_once $spacart_root."/includes/database_mysqli.php";
}

// Load sync functions
require_once $spacart_root."/includes/func/func.dolibarr_sync.php";

// Swap to SpaCart DB
$doli_db_backup = $GLOBALS["db"];
$spacart_db = new Database();
$spacart_db->connect();
$spacart_db->setUTF8();
$GLOBALS["db"] = $spacart_db;

$db = $spacart_db;

print "SpaCart sync retry: started at ".date("Y-m-d H:i:s")."\n";

// Find failed sync entries with < 5 attempts
$sql = "SELECT DISTINCT spacart_order_id 
        FROM spacart_sync_log 
        WHERE status = failed AND attempts < 5 
        ORDER BY updated_at ASC 
        LIMIT 20";
$result = $db->all($sql);

$retried = 0;
$success = 0;
$failed  = 0;

if ($result) {
    foreach ($result as $row) {
        $oid = intval($row["spacart_order_id"]);
        $retried++;
        print "  Retrying order #$oid ... ";

        try {
            $ok = spacart_complete_sale_chain($oid);
            if ($ok) {
                $success++;
                print "SUCCESS\n";
            } else {
                $failed++;
                print "PARTIAL FAILURE (check spacart_sync_log)\n";
            }
        } catch (Exception $e) {
            $failed++;
            print "EXCEPTION: ".$e->getMessage()."\n";
            error_log("SpaCart sync_retry: order #$oid exception: ".$e->getMessage());
        }
    }
}

// Restore Dolibarr DB
$GLOBALS["db"] = $doli_db_backup;

print "SpaCart sync retry: done. Retried=$retried, Success=$success, Failed=$failed\n";
exit(0);
