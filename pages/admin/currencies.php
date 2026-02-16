<?php
/**
 * SpaCart Admin - Currencies Management
 * Now reads from the "currencies" VIEW (backed by Dolibarr llx_multicurrency)
 * and writes directly to llx_multicurrency + llx_multicurrency_rate tables.
 */

$entity = 1;

// Get the shop base currency from config
$base_row = $db->row("SELECT value FROM llx_const WHERE name='SPACART_CURRENCY' AND entity IN (0,".$entity.") ORDER BY entity DESC");
$base_currency = !empty($base_row['value']) ? $base_row['value'] : 'EUR';

// Get base currency's Dolibarr rate (rate vs MAIN_MONNAIE)
$base_rate_row = $db->row("SELECT r.rate FROM llx_multicurrency m JOIN llx_multicurrency_rate r ON r.fk_multicurrency = m.rowid WHERE m.code='".$db->mysqli->real_escape_string($base_currency)."' AND m.entity=".$entity."");
$base_doli_rate = $base_rate_row ? floatval($base_rate_row['rate']) : 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    extract($_POST, EXTR_SKIP);
    if ($mode == 'add' && !empty($new_currency['code'])) {
        $new_code = strtoupper(trim($new_currency['code']));
        $new_rate = floatval($new_currency['rate']);
        $new_symbol = trim($new_currency['symbol']);

        // Check not duplicate
        $exists = $db->row("SELECT rowid FROM llx_multicurrency WHERE code='".$db->mysqli->real_escape_string($new_code)."' AND entity=".$entity);
        if (!$exists) {
            // Get name from llx_c_currencies
            $ref = $db->row("SELECT label FROM llx_c_currencies WHERE code_iso='".$db->mysqli->real_escape_string($new_code)."'");
            $name = $ref ? $ref['label'] : $new_code;

            // Insert into llx_multicurrency
            $db->query("INSERT INTO llx_multicurrency (code, name, entity, date_create) VALUES ('".$db->mysqli->real_escape_string($new_code)."', '".$db->mysqli->real_escape_string($name)."', ".$entity.", NOW())");
            $new_id = $db->mysqli->insert_id;

            if ($new_id) {
                // Convert shop rate (vs base currency) to Dolibarr rate (vs MAIN_MONNAIE)
                $dolibarr_rate = $new_rate * $base_doli_rate;
                $db->query("INSERT INTO llx_multicurrency_rate (fk_multicurrency, rate, date_sync, entity) VALUES (".$new_id.", ".$dolibarr_rate.", NOW(), ".$entity.")");

                // Store symbol in the mapping table
                if ($new_symbol) {
                    $db->query("INSERT INTO spacart_currency_symbols (code, symbol) VALUES ('".$db->mysqli->real_escape_string($new_code)."', '".$db->mysqli->real_escape_string($new_symbol)."') ON DUPLICATE KEY UPDATE symbol='".$db->mysqli->real_escape_string($new_symbol)."'");
                }
            }
        }
    } elseif ($mode == "delete" && !empty($to_delete)) {
        foreach ($to_delete as $k => $v) {
            $k = intval($k);
            // Don't delete the base currency
            $check = $db->row("SELECT code FROM llx_multicurrency WHERE rowid=".$k." AND entity=".$entity);
            if ($check && $check['code'] !== $base_currency) {
                $db->query("DELETE FROM llx_multicurrency_rate WHERE fk_multicurrency=".$k." AND entity=".$entity);
                $db->query("DELETE FROM llx_multicurrency WHERE rowid=".$k." AND entity=".$entity);
            }
        }
    } elseif ($mode == "update" && !empty($to_update)) {
        foreach ($to_update as $k => $v) {
            $k = intval($k);
            $shop_rate = floatval($v['rate']);
            $symbol = trim($v['symbol']);
            $code = strtoupper(trim($v['code']));

            // Convert shop rate to Dolibarr rate
            $dolibarr_rate = $shop_rate * $base_doli_rate;

            // Update rate
            $db->query("UPDATE llx_multicurrency_rate SET rate=".$dolibarr_rate.", date_sync=NOW() WHERE fk_multicurrency=".$k." AND entity=".$entity);

            // Update code if changed
            $db->query("UPDATE llx_multicurrency SET code='".$db->mysqli->real_escape_string($code)."' WHERE rowid=".$k." AND entity=".$entity);

            // Update symbol in the mapping table
            if ($symbol) {
                $db->query("INSERT INTO spacart_currency_symbols (code, symbol) VALUES ('".$db->mysqli->real_escape_string($code)."', '".$db->mysqli->real_escape_string($symbol)."') ON DUPLICATE KEY UPDATE symbol='".$db->mysqli->real_escape_string($symbol)."'");
            }
        }

        if (!empty($main_currency)) {
            $main_id = intval($main_currency);
            $new_main = $db->row("SELECT code FROM llx_multicurrency WHERE rowid=".$main_id." AND entity=".$entity);
            if ($new_main) {
                // Update SPACART_CURRENCY in llx_const
                $db->query("UPDATE llx_const SET value='".$db->mysqli->real_escape_string($new_main['code'])."' WHERE name='SPACART_CURRENCY' AND entity=".$entity);
            }
        }
    }

    redirect("/admin/currencies");
}

$template['location'] .= ' &gt; '.lng('Currencies management');
$currencies = $db->all("SELECT * FROM currencies ORDER BY active DESC, orderby, code");
$template["currencies"] = $currencies;

$template['head_title'] = lng('Currencies management').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/currencies.php');
