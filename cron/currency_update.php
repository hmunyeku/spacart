<?php
/**
 * SpaCart Cron - Currency exchange rate update
 * Updates exchange rates from ECB (European Central Bank) into Dolibarr llx_multicurrency_rate
 * Run daily via Dolibarr cron or system crontab
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res = @include '../../../main.inc.php';
if (!$res) $res = @include '../../../../main.inc.php';
if (!$res) die('Dolibarr not found');

$entity = (int) $conf->entity;
$mainMonnaie = getDolGlobalString('MAIN_MONNAIE', 'EUR');

// Fetch ECB rates (base EUR)
$ecbUrl = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
$ch = curl_init($ecbUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$xml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($xml)) {
    print "SpaCart currency_update: Failed to fetch ECB rates (HTTP ".$httpCode.")
";
    exit(1);
}

$ecbRates = array('EUR' => 1.0);
$sxml = @simplexml_load_string($xml);
if ($sxml) {
    $cubeNodes = $sxml->Cube->Cube->Cube;
    foreach ($cubeNodes as $node) {
        $currency = (string) $node['currency'];
        $rate = (float) $node['rate'];
        if ($currency && $rate > 0) {
            $ecbRates[$currency] = $rate;
        }
    }
}

if (count($ecbRates) <= 1) {
    print "SpaCart currency_update: No rates parsed from ECB feed
";
    exit(1);
}

print "SpaCart currency_update: Fetched ".count($ecbRates)." rates from ECB
";

// ECB rates are relative to EUR. We need rates relative to MAIN_MONNAIE.
// If MAIN_MONNAIE is USD: rate_vs_USD = ecbRate / ecbRate[USD]
// If MAIN_MONNAIE is EUR: rate_vs_EUR = ecbRate (no conversion needed)
$mainRate = isset($ecbRates[$mainMonnaie]) ? $ecbRates[$mainMonnaie] : 1.0;

// Get all currencies defined in llx_multicurrency for this entity
$sql = "SELECT rowid, code FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity=".$entity;
$resql = $db->query($sql);
$updated = 0;

if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $code = $obj->code;
        $id = (int) $obj->rowid;

        if (!isset($ecbRates[$code])) {
            print "  ".$code.": not found in ECB data, skipping
";
            continue;
        }

        // Convert ECB rate (vs EUR) to rate vs MAIN_MONNAIE
        $newRate = $ecbRates[$code] / $mainRate;

        // Update the rate in llx_multicurrency_rate
        $db->query("UPDATE ".MAIN_DB_PREFIX."multicurrency_rate SET rate=".$newRate.", date_sync=NOW() WHERE fk_multicurrency=".$id." AND entity=".$entity);

        print "  ".$code.": ".round($newRate, 6)."
";
        $updated++;
    }
}

print "SpaCart currency_update: ".$updated." rates updated (base ".$mainMonnaie.", source ECB)
";
exit(0);
