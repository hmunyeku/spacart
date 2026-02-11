<?php
/**
 * SpaCart Cron - Currency exchange rate update
 * Updates exchange rates from ECB (European Central Bank)
 * Run daily via Dolibarr cron
 */

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res = @include '../../../main.inc.php';
if (!$res) $res = @include '../../../../main.inc.php';
if (!$res) die('Dolibarr not found');

require_once dirname(__DIR__).'/lib/spacart.lib.php';

$baseCurrency = getDolGlobalString('MAIN_MONNAIE', 'EUR');
$enabledCurrencies = getDolGlobalString('SPACART_CURRENCIES', '');

if (empty($enabledCurrencies)) {
    print "SpaCart currency_update: No additional currencies configured (SPACART_CURRENCIES)\n";
    exit(0);
}

$currencies = array_map('trim', explode(',', $enabledCurrencies));
$updated = 0;

// Fetch ECB rates (base EUR)
$ecbUrl = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';
$ch = curl_init($ecbUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$xml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($xml)) {
    print "SpaCart currency_update: Failed to fetch ECB rates (HTTP ".$httpCode.")\n";
    exit(1);
}

$rates = array('EUR' => 1.0);
$sxml = @simplexml_load_string($xml);
if ($sxml) {
    $cubeNodes = $sxml->Cube->Cube->Cube;
    foreach ($cubeNodes as $node) {
        $currency = (string) $node['currency'];
        $rate = (float) $node['rate'];
        if ($currency && $rate > 0) {
            $rates[$currency] = $rate;
        }
    }
}

if (count($rates) <= 1) {
    print "SpaCart currency_update: No rates parsed from ECB feed\n";
    exit(1);
}

// Convert rates to base currency if not EUR
$baseRate = $rates[$baseCurrency] ?? 1.0;

foreach ($currencies as $cur) {
    $cur = strtoupper($cur);
    if ($cur === $baseCurrency) continue;
    if (!isset($rates[$cur])) {
        print "  ".$cur.": not found in ECB data\n";
        continue;
    }

    $rate = $rates[$cur] / $baseRate;

    // Store in spacart_config
    $confName = 'currency_rate_'.$cur;
    $existing = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_config WHERE conf_name = '".$db->escape($confName)."'");
    if ($existing && $db->num_rows($existing) > 0) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_config SET conf_value = '".$db->escape((string) $rate)."' WHERE conf_name = '".$db->escape($confName)."'");
    } else {
        $db->query("INSERT INTO ".MAIN_DB_PREFIX."spacart_config (conf_name, conf_value) VALUES ('".$db->escape($confName)."', '".$db->escape((string) $rate)."')");
    }

    // Also update Dolibarr multicurrency if available
    if (isModEnabled('multicurrency')) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."multicurrency_rate SET rate = ".(float) $rate.", date_sync = NOW() WHERE fk_multicurrency IN (SELECT rowid FROM ".MAIN_DB_PREFIX."multicurrency WHERE code = '".$db->escape($cur)."') ORDER BY rowid DESC LIMIT 1");
    }

    print "  ".$cur.": ".$rate."\n";
    $updated++;
}

print "SpaCart currency_update: ".$updated." rates updated (base ".$baseCurrency.", source ECB)\n";
exit(0);
