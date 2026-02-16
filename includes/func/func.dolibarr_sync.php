<?php
/**
 * SpaCart -> Dolibarr Synchronization
 * 
 * Complete sale chain: Order -> Invoice -> Payment -> Stock
 * Called after order creation and payment confirmation
 * 
 * Functions:
 *  - spacart_sync_order_to_dolibarr($orderid)      -- Create llx_commande (existing, improved)
 *  - spacart_get_or_create_societe($order)           -- Find/create thirdparty (existing, improved)
 *  - spacart_complete_sale_chain($orderid)            -- Orchestrator: invoice + payment + stock
 *  - spacart_create_invoice($orderid)                 -- Create llx_facture from llx_commande
 *  - spacart_record_payment($orderid, $facture_id)    -- Create llx_paiement + bank entry
 *  - spacart_sync_stock_movements($orderid)           -- Create llx_stock_mouvement entries
 *  - spacart_sync_log($orderid, $step, $status, $ref, $error) -- Log to spacart_sync_log
 *  - spacart_get_payment_map($method_id)              -- Get Dolibarr payment code + bank ID
 *  - spacart_get_dolibarr_user()                      -- Get/cache Dolibarr admin user object
 *  - spacart_load_dolibarr_classes()                  -- Load required Dolibarr class files
 */

// ============================================================
// DOLIBARR CLASS LOADER
// ============================================================

/**
 * Load Dolibarr class files needed for the sale chain.
 * Safe to call multiple times (uses require_once).
 */
function spacart_load_dolibarr_classes() {
    if (!defined('DOL_DOCUMENT_ROOT')) {
        $candidates = array(
            '/var/www/vhosts/coexdis.com/erp/htdocs',
            dirname(dirname(dirname(dirname(__DIR__)))),
        );
        foreach ($candidates as $c) {
            if (file_exists($c . '/main.inc.php')) {
                define('DOL_DOCUMENT_ROOT', $c);
                break;
            }
        }
    }
    if (!defined('DOL_DOCUMENT_ROOT')) return false;

    $classes = array(
        '/commande/class/commande.class.php',
        '/compta/facture/class/facture.class.php',
        '/compta/paiement/class/paiement.class.php',
        '/product/stock/class/mouvementstock.class.php',
        '/societe/class/societe.class.php',
        '/contact/class/contact.class.php',
        '/user/class/user.class.php',
    );
    foreach ($classes as $f) {
        $path = DOL_DOCUMENT_ROOT . $f;
        if (file_exists($path)) {
            require_once $path;
        }
    }
    return true;
}

// ============================================================
// DOLIBARR USER CACHE
// ============================================================

function spacart_get_dolibarr_user() {
    static $dol_user = null;
    if ($dol_user !== null) return $dol_user;

    global $db;
    spacart_load_dolibarr_classes();

    if (!class_exists('User')) return null;

    $dol_user = new User($db->pdo ? $db : $GLOBALS['db']);
    $dol_db = spacart_get_dolibarr_db();
    if ($dol_db) {
        $dol_user = new User($dol_db);
    }
    $dol_user->fetch(1);
    return $dol_user;
}

function spacart_get_dolibarr_db() {
    if (isset($GLOBALS['dolibarr_db'])) return $GLOBALS['dolibarr_db'];

    $main_paths = array(
        '/var/www/vhosts/coexdis.com/erp/htdocs/master.inc.php',
        DOL_DOCUMENT_ROOT . '/master.inc.php',
    );

    foreach ($GLOBALS as $k => $v) {
        if (is_object($v) && ($v instanceof DoliDB || (method_exists($v, 'query') && method_exists($v, 'fetch_object') && property_exists($v, 'db')))) {
            if ($k !== 'db' || get_class($v) !== 'db') {
                if (property_exists($v, 'type') && in_array($v->type, array('mysqli', 'pgsql', 'sqlite3'))) {
                    $GLOBALS['dolibarr_db'] = $v;
                    return $v;
                }
            }
        }
    }

    if (defined('DOL_DOCUMENT_ROOT') && file_exists(DOL_DOCUMENT_ROOT . '/core/db/DoliDB.class.php')) {
        require_once DOL_DOCUMENT_ROOT . '/core/db/DoliDB.class.php';
        require_once DOL_DOCUMENT_ROOT . '/core/db/mysqli.class.php';

        if (!empty($GLOBALS['conf']->db->host)) {
            $c = $GLOBALS['conf']->db;
            $dol_db = new DoliDBMysqli($c->type, $c->host, $c->user, $c->pass, $c->name, (int)$c->port);
            $GLOBALS['dolibarr_db'] = $dol_db;
            return $dol_db;
        }
    }

    return null;
}

// ============================================================
// SYNC LOG
// ============================================================

function spacart_sync_log($orderid, $step, $status, $ref = null, $error = null) {
    global $db;
    $orderid = intval($orderid);
    $step    = addslashes($step);
    $status  = addslashes($status);
    $ref     = $ref ? "'" . addslashes($ref) . "'" : 'NULL';
    $error   = $error ? "'" . addslashes(mb_substr($error, 0, 2000)) . "'" : 'NULL';

    $sql = "INSERT INTO spacart_sync_log (spacart_order_id, sync_step, status, dolibarr_ref, error_message, attempts)
            VALUES ($orderid, '$step', '$status', $ref, $error, 1)
            ON DUPLICATE KEY UPDATE 
                status = '$status',
                dolibarr_ref = COALESCE($ref, dolibarr_ref),
                error_message = $error,
                attempts = attempts + 1,
                updated_at = NOW()";
    $db->query($sql);
}

// ============================================================
// PAYMENT MAP
// ============================================================

function spacart_get_payment_map($method_id) {
    global $db;
    $method_id = intval($method_id);
    $row = $db->row("SELECT dolibarr_paiement_code, dolibarr_bank_account_id 
                      FROM spacart_payment_map 
                      WHERE spacart_method_id = $method_id");
    if ($row) {
        return array(
            'code'       => $row['dolibarr_paiement_code'],
            'bank_id'    => intval($row['dolibarr_bank_account_id']),
        );
    }
    return array('code' => 'CB', 'bank_id' => 1);
}

// ============================================================
// WAREHOUSE MAP
// ============================================================

function spacart_get_warehouse_id($spacart_wid = 0) {
    global $db;
    if ($spacart_wid > 0) {
        $eid = $db->field("SELECT dolibarr_entrepot_id FROM spacart_warehouse_map WHERE spacart_wid = " . intval($spacart_wid));
        if ($eid) return intval($eid);
    }
    return 1; // Default: Kinshasa
}

// ============================================================
// ORDER SYNC (existing, improved)
// ============================================================

function spacart_sync_order_to_dolibarr($orderid) {
    global $db;

    $existing = $db->field("SELECT rowid FROM llx_commande WHERE ref_ext = 'SPACART-" . intval($orderid) . "' AND entity = 1");
    if ($existing) {
        spacart_sync_log($orderid, 'order', 'success', 'existing-' . $existing);
        return intval($existing);
    }

    $order = $db->row("SELECT * FROM orders WHERE orderid='" . addslashes($orderid) . "'");
    if (!$order) {
        spacart_sync_log($orderid, 'order', 'failed', null, 'Order not found in SpaCart');
        return false;
    }

    $items = $db->all("SELECT oi.*, p.label AS product_name, p.ref AS product_ref, p.fk_product_type AS product_type, p.tva_tx 
                        FROM order_items oi 
                        LEFT JOIN llx_product p ON p.rowid = oi.productid 
                        WHERE oi.orderid='$orderid'");

    $fk_soc = spacart_get_or_create_societe($order);
    if (!$fk_soc) {
        spacart_sync_log($orderid, 'order', 'failed', null, 'Could not find/create societe');
        return false;
    }

    $prefix = 'CO';
    $year = date('ym');
    $last_ref = $db->field("SELECT MAX(ref) FROM llx_commande WHERE ref LIKE '{$prefix}{$year}-%' AND entity=1");
    if ($last_ref) {
        $num = intval(substr($last_ref, strrpos($last_ref, '-') + 1)) + 1;
    } else {
        $num = 1;
    }
    $ref = $prefix . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

    $status_map = array(1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => -1);
    $fk_statut = isset($status_map[$order['status']]) ? $status_map[$order['status']] : 0;

    $now = date('Y-m-d H:i:s');
    $date_commande = date('Y-m-d', $order['date']);

    $total_ht  = floatval($order['subtotal']);
    $total_tva = floatval($order['tax']);
    $total_ttc = floatval($order['total']);

    $sql = "INSERT INTO llx_commande (ref, entity, ref_ext, fk_soc, date_creation, date_commande, 
            fk_statut, amount_ht, total_tva, total_ht, total_ttc, 
            note_private, note_public, module_source, fk_currency, source)
            VALUES (
                '" . addslashes($ref) . "', 1, 
                'SPACART-" . intval($orderid) . "', 
                " . intval($fk_soc) . ",
                '$now', '$date_commande', $fk_statut,
                $total_ht, $total_tva, $total_ht, $total_ttc,
                '" . addslashes("SpaCart Order #$orderid\nClient: {$order['firstname']} {$order['lastname']}\nEmail: {$order['email']}\nPhone: {$order['phone']}") . "',
                '" . addslashes($order['notes']) . "',
                'spacart', 'EUR', 4
            )";
    $db->query($sql);
    $commande_id = $db->field("SELECT LAST_INSERT_ID()");

    if (!$commande_id) {
        spacart_sync_log($orderid, 'order', 'failed', null, 'INSERT llx_commande failed');
        return false;
    }

    $rang = 1;
    foreach ($items as $item) {
        $qty         = intval($item['quantity']);
        $price_unit  = floatval($item['price']);
        $total_line_ht  = $price_unit * $qty;
        $product_type   = intval($item['product_type']);
        $tva_tx         = floatval($item['tva_tx']);
        $total_line_tva = round($total_line_ht * $tva_tx / 100, 2);
        $total_line_ttc = $total_line_ht + $total_line_tva;

        $sql = "INSERT INTO llx_commandedet (fk_commande, fk_product, label, description,
                qty, subprice, price, total_ht, total_tva, total_ttc, tva_tx,
                product_type, rang)
                VALUES (
                    $commande_id, " . intval($item['productid']) . ",
                    '" . addslashes($item['product_name']) . "',
                    '" . addslashes($item['product_name']) . "',
                    $qty, $price_unit, $price_unit,
                    $total_line_ht, $total_line_tva, $total_line_ttc, $tva_tx,
                    $product_type, $rang
                )";
        $db->query($sql);
        $rang++;
    }

    $db->query("UPDATE orders SET token='" . addslashes($ref) . "' WHERE orderid='$orderid' AND token=''");
    // Link SpaCart order to Dolibarr commande
    $db->query("UPDATE orders SET fk_commande = " . intval($commande_id) . " WHERE orderid = '" . intval($orderid) . "'");

    spacart_sync_log($orderid, 'order', 'success', $ref);
    return $commande_id;
}

function spacart_get_or_create_societe($order) {
    global $db;

    $email = addslashes($order['email']);

    if (!empty($order['userid'])) {
        $fk_soc = $db->field("SELECT fk_soc FROM users WHERE id = '" . addslashes($order['userid']) . "' AND fk_soc IS NOT NULL AND fk_soc > 0");
        if ($fk_soc) return intval($fk_soc);
    }

    $soc_id = $db->field("SELECT rowid FROM llx_societe WHERE email='$email' AND entity=1 LIMIT 1");
    if ($soc_id) {
        if (!empty($order['userid'])) {
            $db->query("UPDATE users SET fk_soc = " . intval($soc_id) . " WHERE id = '" . addslashes($order['userid']) . "'");
        }
        return intval($soc_id);
    }

    $soc_id = $db->field("SELECT fk_soc FROM llx_socpeople WHERE email='$email' AND fk_soc > 0 LIMIT 1");
    if ($soc_id) {
        if (!empty($order['userid'])) {
            $db->query("UPDATE users SET fk_soc = " . intval($soc_id) . " WHERE id = '" . addslashes($order['userid']) . "'");
        }
        return intval($soc_id);
    }

    $nom = addslashes(trim($order['firstname'] . ' ' . $order['lastname']));
    $now = date('Y-m-d H:i:s');

    $last_code = $db->field("SELECT MAX(code_client) FROM llx_societe WHERE code_client LIKE 'CU%' AND entity=1");
    if ($last_code) {
        $num = intval(substr($last_code, 2)) + 1;
    } else {
        $num = 1;
    }
    $code_client = 'CU' . str_pad($num, 5, '0', STR_PAD_LEFT);

    $sql = "INSERT INTO llx_societe (nom, name_alias, entity, client, fournisseur, 
            code_client, address, zip, town, state_id,
            email, phone, datec, status, canvas, ref_ext)
            VALUES (
                '$nom', '', 1, 1, 0, '$code_client',
                '" . addslashes($order['address']) . "',
                '" . addslashes($order['zipcode']) . "',
                '" . addslashes($order['city']) . "',
                NULL, '$email',
                '" . addslashes($order['phone']) . "',
                '$now', 1, NULL,
                'SPACART-USER-" . intval($order['userid']) . "'
            )";
    $db->query($sql);
    $soc_id = $db->field("SELECT LAST_INSERT_ID()");

    if ($soc_id && !empty($order['userid'])) {
        $db->query("UPDATE users SET fk_soc = " . intval($soc_id) . " WHERE id = '" . addslashes($order['userid']) . "'");
    }

    return $soc_id ? intval($soc_id) : false;
}

// ============================================================
// COMPLETE SALE CHAIN (NEW)
// ============================================================

function spacart_complete_sale_chain($orderid) {
    $orderid = intval($orderid);
    $success = true;

    try {
        $facture_id = spacart_create_invoice($orderid);
        if (!$facture_id) {
            $success = false;
        }
    } catch (Exception $e) {
        spacart_sync_log($orderid, 'invoice', 'failed', null, 'Exception: ' . $e->getMessage());
        $success = false;
        $facture_id = null;
    }

    if ($facture_id) {
        try {
            $payment_ok = spacart_record_payment($orderid, $facture_id);
            if (!$payment_ok) {
                $success = false;
            }
        } catch (Exception $e) {
            spacart_sync_log($orderid, 'payment', 'failed', null, 'Exception: ' . $e->getMessage());
            $success = false;
        }
    }

    try {
        $stock_ok = spacart_sync_stock_movements($orderid);
        if (!$stock_ok) {
            $success = false;
        }
    } catch (Exception $e) {
        spacart_sync_log($orderid, 'stock', 'failed', null, 'Exception: ' . $e->getMessage());
        $success = false;
    }

    return $success;
}

// ============================================================
// CREATE INVOICE (NEW)
// ============================================================

function spacart_create_invoice($orderid) {
    global $db;
    $orderid = intval($orderid);
    $ref_ext = 'SPACART-' . $orderid;

    $existing = $db->field("SELECT rowid FROM llx_facture WHERE ref_ext = '" . addslashes($ref_ext) . "' AND entity = 1");
    if ($existing) {
        spacart_sync_log($orderid, 'invoice', 'success', 'existing-' . $existing);
        return intval($existing);
    }

    $commande = $db->row("SELECT rowid, ref, fk_soc, total_ht, total_tva, total_ttc, fk_currency, date_commande 
                           FROM llx_commande 
                           WHERE ref_ext = '" . addslashes($ref_ext) . "' AND entity = 1");
    if (!$commande) {
        spacart_sync_log($orderid, 'invoice', 'failed', null, 'No llx_commande found for ref_ext=' . $ref_ext);
        return false;
    }

    $prefix = 'FA';
    $year = date('ym');
    $last_ref = $db->field("SELECT MAX(ref) FROM llx_facture WHERE ref LIKE '{$prefix}{$year}-%' AND entity=1");
    if ($last_ref) {
        $num = intval(substr($last_ref, strrpos($last_ref, '-') + 1)) + 1;
    } else {
        $num = 1;
    }
    $facture_ref = $prefix . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

    $now = date('Y-m-d H:i:s');
    $date_facture = date('Y-m-d');

    $sql = "INSERT INTO llx_facture (
                ref, entity, ref_ext, type, fk_soc,
                datec, datef, date_lim_reglement,
                fk_statut, paye,
                total_ht, total_tva, total_ttc,
                fk_currency, note_private, module_source, fk_commande
            ) VALUES (
                '" . addslashes($facture_ref) . "', 1,
                '" . addslashes($ref_ext) . "', 0,
                " . intval($commande['fk_soc']) . ",
                '$now', '$date_facture',
                '" . date('Y-m-d', strtotime('+30 days')) . "',
                1, 0,
                " . floatval($commande['total_ht']) . ",
                " . floatval($commande['total_tva']) . ",
                " . floatval($commande['total_ttc']) . ",
                '" . addslashes($commande['fk_currency'] ?: 'EUR') . "',
                'Auto-generated from SpaCart order #$orderid',
                'spacart',
                " . intval($commande['rowid']) . "
            )";
    $db->query($sql);
    $facture_id = $db->field("SELECT LAST_INSERT_ID()");

    if (!$facture_id) {
        spacart_sync_log($orderid, 'invoice', 'failed', null, 'INSERT llx_facture failed');
        return false;
    }

    $lines = $db->all("SELECT * FROM llx_commandedet WHERE fk_commande = " . intval($commande['rowid']) . " ORDER BY rang");
    $rang = 1;
    foreach ($lines as $line) {
        $sql = "INSERT INTO llx_facturedet (
                    fk_facture, fk_product, label, description,
                    qty, subprice, price, total_ht, total_tva, total_ttc, tva_tx,
                    product_type, rang, fk_commande, fk_commandedet
                ) VALUES (
                    $facture_id, " . intval($line['fk_product']) . ",
                    '" . addslashes($line['label']) . "',
                    '" . addslashes($line['description']) . "',
                    " . floatval($line['qty']) . ",
                    " . floatval($line['subprice']) . ",
                    " . floatval($line['price']) . ",
                    " . floatval($line['total_ht']) . ",
                    " . floatval($line['total_tva']) . ",
                    " . floatval($line['total_ttc']) . ",
                    " . floatval($line['tva_tx']) . ",
                    " . intval($line['product_type']) . ",
                    $rang,
                    " . intval($commande['rowid']) . ",
                    " . intval($line['rowid']) . "
                )";
        $db->query($sql);
        $rang++;
    }

    // Link SpaCart order to Dolibarr facture
    $db->query("UPDATE orders SET fk_facture = " . intval($facture_id) . " WHERE orderid = '" . intval($orderid) . "'");

    spacart_sync_log($orderid, 'invoice', 'success', $facture_ref);
    return intval($facture_id);
}

// ============================================================
// RECORD PAYMENT (NEW)
// ============================================================

function spacart_record_payment($orderid, $facture_id) {
    global $db;
    $orderid = intval($orderid);
    $facture_id = intval($facture_id);

    $existing_payment = $db->field("SELECT pf.fk_paiement FROM llx_paiement_facture pf WHERE pf.fk_facture = $facture_id LIMIT 1");
    if ($existing_payment) {
        spacart_sync_log($orderid, 'payment', 'success', 'existing-pmt-' . $existing_payment);
        return true;
    }

    $order = $db->row("SELECT paymentid, total, date FROM orders WHERE orderid = '$orderid'");
    if (!$order) {
        spacart_sync_log($orderid, 'payment', 'failed', null, 'SpaCart order not found');
        return false;
    }

    $facture = $db->row("SELECT rowid, ref, total_ttc, fk_soc FROM llx_facture WHERE rowid = $facture_id");
    if (!$facture) {
        spacart_sync_log($orderid, 'payment', 'failed', null, 'Facture not found: ' . $facture_id);
        return false;
    }

    $amount = floatval($facture['total_ttc']);
    $payment_map = spacart_get_payment_map($order['paymentid']);
    $now = date('Y-m-d H:i:s');
    $date_paiement = date('Y-m-d', $order['date']);

    $paiement_type_id = $db->field("SELECT id FROM llx_c_paiement WHERE code = '" . addslashes($payment_map['code']) . "' AND active = 1 LIMIT 1");
    if (!$paiement_type_id) {
        $paiement_type_id = $db->field("SELECT id FROM llx_c_paiement WHERE code = 'CB' AND active = 1 LIMIT 1");
    }

    $pay_num = 'SPACART-PAY-' . $orderid;

    $sql = "INSERT INTO llx_paiement (entity, ref, datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)
            VALUES (1, '(PROV)', '$now', '$date_paiement', $amount,
                " . intval($paiement_type_id) . ",
                '" . addslashes($pay_num) . "',
                'SpaCart order #$orderid auto-payment', 1
            )";
    $db->query($sql);
    $paiement_id = $db->field("SELECT LAST_INSERT_ID()");

    if (!$paiement_id) {
        spacart_sync_log($orderid, 'payment', 'failed', null, 'INSERT llx_paiement failed');
        return false;
    }

    $pay_ref = str_pad($paiement_id, 6, '0', STR_PAD_LEFT);
    $db->query("UPDATE llx_paiement SET ref = '$pay_ref' WHERE rowid = $paiement_id");

    $db->query("INSERT INTO llx_paiement_facture (fk_paiement, fk_facture, amount, multicurrency_amount)
                VALUES ($paiement_id, $facture_id, $amount, $amount)");

    $bank_id = intval($payment_map['bank_id']);
    $sql = "INSERT INTO llx_bank (datec, datev, dateo, amount, label, fk_account, fk_type, num_releve, numero_compte, rappro, fk_user_author)
            VALUES ('$now', '$date_paiement', '$date_paiement', $amount,
                '(CustomerInvoicePayment)', $bank_id,
                '" . addslashes($payment_map['code']) . "', '', '', 0, 1
            )";
    $db->query($sql);
    $bank_line_id = $db->field("SELECT LAST_INSERT_ID()");

    if ($bank_line_id) {
        $db->query("INSERT INTO llx_bank_url (fk_bank, url_id, url, label, type)
                    VALUES ($bank_line_id, $paiement_id, '/compta/paiement/card.php?id=$paiement_id', '" . addslashes($pay_ref) . "', 'payment')");
        $db->query("INSERT INTO llx_bank_url (fk_bank, url_id, url, label, type)
                    VALUES ($bank_line_id, " . intval($facture['fk_soc']) . ", '/societe/card.php?socid=" . intval($facture['fk_soc']) . "', 'SpaCart client', 'company')");
    }

    $db->query("UPDATE llx_facture SET fk_statut = 2, paye = 1, close_code = 'discount_vat', close_note = 'SpaCart auto-close' WHERE rowid = $facture_id");
    $db->query("UPDATE llx_commande SET facture = 1, billed = 1 WHERE ref_ext = 'SPACART-$orderid' AND entity = 1");

    spacart_sync_log($orderid, 'payment', 'success', $pay_ref);
    return true;
}

// ============================================================
// STOCK MOVEMENTS (NEW)
// ============================================================

function spacart_sync_stock_movements($orderid) {
    global $db;
    $orderid = intval($orderid);

    $existing = $db->field("SELECT id FROM spacart_sync_log 
                            WHERE spacart_order_id = $orderid AND sync_step = 'stock' AND status = 'success'");
    if ($existing) {
        return true;
    }

    $order = $db->row("SELECT wid, local_pickup, date FROM orders WHERE orderid = '$orderid'");
    if (!$order) {
        spacart_sync_log($orderid, 'stock', 'failed', null, 'Order not found');
        return false;
    }

    $warehouse_id = 1;
    if ($order['local_pickup'] && $order['wid']) {
        $warehouse_id = spacart_get_warehouse_id($order['wid']);
    }

    $items = $db->all("SELECT oi.productid, oi.quantity, oi.price, p.fk_product_type
                        FROM order_items oi
                        JOIN llx_product p ON p.rowid = oi.productid
                        WHERE oi.orderid = '$orderid' AND p.fk_product_type = 0");

    if (empty($items)) {
        spacart_sync_log($orderid, 'stock', 'success', 'no-physical-products');
        return true;
    }

    $now = date('Y-m-d H:i:s');
    $errors = array();

    foreach ($items as $item) {
        $pid = intval($item['productid']);
        $qty = intval($item['quantity']);
        $price = floatval($item['price']);

        if ($qty <= 0) continue;

        $ps_exists = $db->field("SELECT rowid FROM llx_product_stock WHERE fk_product = $pid AND fk_entrepot = $warehouse_id");
        if (!$ps_exists) {
            $db->query("INSERT INTO llx_product_stock (fk_product, fk_entrepot, reel) VALUES ($pid, $warehouse_id, 0)");
            $ps_exists = $db->field("SELECT LAST_INSERT_ID()");
        }

        $sql = "INSERT INTO llx_stock_mouvement (
                    datem, fk_product, fk_entrepot, value, type_mouvement, 
                    label, fk_user_author, price, inventorycode, origintype, fk_origin, batch
                ) VALUES (
                    '$now', $pid, $warehouse_id, -$qty, 2,
                    'SpaCart order #$orderid', 1, $price,
                    'SPACART-$orderid', 'commande', 0, NULL
                )";
        $result = $db->query($sql);

        if ($result) {
            $db->query("UPDATE llx_product_stock SET reel = reel - $qty WHERE fk_product = $pid AND fk_entrepot = $warehouse_id");
            $new_stock = $db->field("SELECT SUM(reel) FROM llx_product_stock WHERE fk_product = $pid");
            $db->query("UPDATE llx_product SET stock = " . floatval($new_stock) . " WHERE rowid = $pid");
        } else {
            $errors[] = "Failed stock movement for product $pid";
        }
    }

    if (!empty($errors)) {
        spacart_sync_log($orderid, 'stock', 'failed', null, implode('; ', $errors));
        return false;
    }

    spacart_sync_log($orderid, 'stock', 'success', $warehouse_id . ':' . count($items) . ' products');
    return true;
}

function spacart_reverse_stock_movements($orderid) {
    global $db;
    $orderid = intval($orderid);

    $order = $db->row("SELECT wid, local_pickup FROM orders WHERE orderid = '$orderid'");
    if (!$order) return false;

    $warehouse_id = 1;
    if ($order['local_pickup'] && $order['wid']) {
        $warehouse_id = spacart_get_warehouse_id($order['wid']);
    }

    $items = $db->all("SELECT oi.productid, oi.quantity, oi.price, p.fk_product_type
                        FROM order_items oi
                        JOIN llx_product p ON p.rowid = oi.productid
                        WHERE oi.orderid = '$orderid' AND p.fk_product_type = 0");

    $now = date('Y-m-d H:i:s');

    foreach ($items as $item) {
        $pid = intval($item['productid']);
        $qty = intval($item['quantity']);
        $price = floatval($item['price']);

        if ($qty <= 0) continue;

        $db->query("INSERT INTO llx_stock_mouvement (
                        datem, fk_product, fk_entrepot, value, type_mouvement,
                        label, fk_user_author, price, inventorycode, origintype, fk_origin
                    ) VALUES (
                        '$now', $pid, $warehouse_id, $qty, 0,
                        'SpaCart cancel order #$orderid', 1, $price,
                        'SPACART-CANCEL-$orderid', 'commande', 0
                    )");

        $db->query("UPDATE llx_product_stock SET reel = reel + $qty WHERE fk_product = $pid AND fk_entrepot = $warehouse_id");
        $new_stock = $db->field("SELECT SUM(reel) FROM llx_product_stock WHERE fk_product = $pid");
        $db->query("UPDATE llx_product SET stock = " . floatval($new_stock) . " WHERE rowid = $pid");
    }

    return true;
}

// ============================================================
// CUSTOMER SYNC (Phase 3: Configurable customers)
// ============================================================

/**
 * Sync a SpaCart user to Dolibarr (create tiers or contact).
 * Mode depends on SPACART_CUSTOMER_MODE constant.
 *
 * @param array $user_data Array with keys: id, email, firstname, lastname, phone, address, city, zipcode, country
 * @return array ['soc_id' => int|null, 'contact_id' => int|null]
 */
function spacart_sync_customer($user_data) {
    global $db;
    $uid = intval($user_data['id']);

    // Check if already linked
    $existing = $db->row("SELECT fk_soc, fk_socpeople FROM users WHERE id = $uid");
    if ($existing && $existing['fk_soc'] > 0) {
        return array('soc_id' => intval($existing['fk_soc']), 'contact_id' => intval($existing['fk_socpeople']));
    }

    $mode = $db->field("SELECT value FROM llx_const WHERE name = 'SPACART_CUSTOMER_MODE' AND entity IN (0,1) LIMIT 1");
    if (!$mode) $mode = 'individual';

    $email     = addslashes($user_data['email']);
    $firstname = addslashes($user_data['firstname']);
    $lastname  = addslashes($user_data['lastname']);
    $phone     = addslashes($user_data['phone'] ?? '');
    $address   = addslashes($user_data['address'] ?? '');
    $city      = addslashes($user_data['city'] ?? '');
    $zipcode   = addslashes($user_data['zipcode'] ?? '');
    $now       = date('Y-m-d H:i:s');

    if ($mode === 'generic') {
        // ---- GENERIC MODE: create contact under the generic tiers ----
        $generic_soc_id = $db->field("SELECT value FROM llx_const WHERE name = 'SPACART_GENERIC_CUSTOMER_ID' AND entity IN (0,1) LIMIT 1");
        if (!$generic_soc_id) {
            $generic_soc_id = spacart_create_generic_customer();
        }
        $generic_soc_id = intval($generic_soc_id);

        // Check if contact already exists by email
        $contact_id = $db->field("SELECT rowid FROM llx_socpeople WHERE email = '$email' AND fk_soc = $generic_soc_id LIMIT 1");

        if (!$contact_id) {
            $db->query("INSERT INTO llx_socpeople (entity, fk_soc, lastname, firstname, email, phone_mobile, datec, statut, ref_ext)
                        VALUES (1, $generic_soc_id, '$lastname', '$firstname', '$email', '$phone', '$now', 1, 'SPACART-USER-$uid')");
            $contact_id = $db->field("SELECT LAST_INSERT_ID()");
        }

        $db->query("UPDATE users SET fk_soc = $generic_soc_id, fk_socpeople = " . intval($contact_id) . " WHERE id = $uid");

        return array('soc_id' => $generic_soc_id, 'contact_id' => intval($contact_id));

    } else {
        // ---- INDIVIDUAL MODE: create a thirdparty per customer ----
        $soc_id = $db->field("SELECT rowid FROM llx_societe WHERE email = '$email' AND entity = 1 LIMIT 1");

        if (!$soc_id) {
            $soc_id = $db->field("SELECT rowid FROM llx_societe WHERE ref_ext = 'SPACART-USER-$uid' AND entity = 1 LIMIT 1");
        }

        if (!$soc_id) {
            $last_code = $db->field("SELECT MAX(CAST(SUBSTRING(code_client, 4) AS UNSIGNED)) FROM llx_societe WHERE code_client LIKE 'CU-%' AND entity=1");
            $num = $last_code ? intval($last_code) + 1 : 1;
            $code_client = 'CU-' . $num;

            $nom = addslashes(trim($user_data['firstname'] . ' ' . $user_data['lastname']));
            $db->query("INSERT INTO llx_societe (nom, name_alias, entity, client, fournisseur, code_client,
                         address, zip, town, email, phone, datec, status, ref_ext, fk_pays)
                        VALUES ('$nom', '', 1, 1, 0, '$code_client', '$address', '$zipcode', '$city',
                                '$email', '$phone', '$now', 1, 'SPACART-USER-$uid', 73)");
            $soc_id = $db->field("SELECT LAST_INSERT_ID()");

            if ($soc_id) {
                spacart_tag_ecommerce_customer(intval($soc_id));
            }
        }

        if ($soc_id) {
            $db->query("UPDATE users SET fk_soc = " . intval($soc_id) . " WHERE id = $uid");
        }

        return array('soc_id' => intval($soc_id), 'contact_id' => null);
    }
}

/**
 * Create the generic "CLIENTS WEB SPACART" tiers and store its ID in llx_const.
 * @return int The societe rowid
 */
function spacart_create_generic_customer() {
    global $db;

    $soc_id = $db->field("SELECT rowid FROM llx_societe WHERE ref_ext = 'SPACART-GENERIC' AND entity = 1 LIMIT 1");
    if ($soc_id) {
        $db->query("INSERT INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_GENERIC_CUSTOMER_ID', '$soc_id', 'chaine', 1, 0)
                     ON DUPLICATE KEY UPDATE value = '$soc_id'");
        return intval($soc_id);
    }

    $last_code = $db->field("SELECT MAX(CAST(SUBSTRING(code_client, 4) AS UNSIGNED)) FROM llx_societe WHERE code_client LIKE 'CU-%' AND entity=1");
    $num = $last_code ? intval($last_code) + 1 : 1;
    $code_client = 'CU-' . $num;

    $now = date('Y-m-d H:i:s');
    $db->query("INSERT INTO llx_societe (nom, name_alias, entity, client, fournisseur, code_client,
                 datec, status, ref_ext, note_private, fk_pays)
                VALUES ('CLIENTS WEB SPACART', 'Web Customers', 1, 1, 0, '$code_client',
                        '$now', 1, 'SPACART-GENERIC', 'Tiers generique pour les clients e-commerce SpaCart', 73)");
    $soc_id = $db->field("SELECT LAST_INSERT_ID()");

    if ($soc_id) {
        $db->query("INSERT INTO llx_const (name, value, type, entity, visible) VALUES ('SPACART_GENERIC_CUSTOMER_ID', '$soc_id', 'chaine', 1, 0)
                     ON DUPLICATE KEY UPDATE value = '$soc_id'");
        spacart_tag_ecommerce_customer(intval($soc_id));
    }

    return intval($soc_id);
}

/**
 * Tag a thirdparty with the "E-commerce SpaCart" category.
 * Creates the category if it does not exist.
 */
function spacart_tag_ecommerce_customer($soc_id) {
    global $db;

    $cat_id = $db->field("SELECT rowid FROM llx_categorie WHERE label = 'E-commerce SpaCart' AND type = 2 AND entity = 1 LIMIT 1");
    if (!$cat_id) {
        $db->query("INSERT INTO llx_categorie (entity, label, type, description, color, visible, date_creation)
                    VALUES (1, 'E-commerce SpaCart', 2, 'Clients provenant du site e-commerce SpaCart', '#4CAF50', 1, NOW())");
        $cat_id = $db->field("SELECT LAST_INSERT_ID()");
    }

    if ($cat_id && $soc_id) {
        $db->query("INSERT IGNORE INTO llx_categorie_societe (fk_categorie, fk_soc) VALUES (" . intval($cat_id) . ", " . intval($soc_id) . ")");
    }
}


// ============================================================
// EXPEDITION / SHIPMENT SYNC (Task 5)
// ============================================================

/**
 * Create a Dolibarr expedition (shipment) for a SpaCart order.
 * Called when order status changes to shipped (3).
 *
 * @param int $orderid SpaCart order ID
 * @return int|false Expedition rowid or false on failure
 */
function spacart_create_expedition($orderid) {
    global $db;
    $orderid = intval($orderid);
    $ref_ext = 'SPACART-' . $orderid;

    // Idempotence: check if expedition already exists
    $existing = $db->field("SELECT rowid FROM llx_expedition WHERE ref_ext = '" . addslashes($ref_ext) . "' AND entity = 1");
    if ($existing) {
        $db->query("UPDATE orders SET fk_expedition = " . intval($existing) . " WHERE orderid = '" . $orderid . "'");
        spacart_sync_log($orderid, 'expedition', 'success', 'existing-' . $existing);
        return intval($existing);
    }

    // Get the linked Dolibarr commande
    $commande = $db->row("SELECT rowid, ref, fk_soc FROM llx_commande WHERE ref_ext = '" . addslashes($ref_ext) . "' AND entity = 1");
    if (!$commande) {
        spacart_sync_log($orderid, 'expedition', 'failed', null, 'No llx_commande found for ref_ext=' . $ref_ext);
        return false;
    }

    // Get SpaCart order info for shipping method and tracking
    $order = $db->row("SELECT shippingid, tracking, tracking_url FROM orders WHERE orderid = '$orderid'");

    // Map shipping method to Dolibarr mode
    $shipping_mode_id = null;
    if ($order && $order['shippingid']) {
        $mode_id = $db->field("SELECT dolibarr_mode_id FROM shipping WHERE shippingid = " . intval($order['shippingid']));
        if ($mode_id) $shipping_mode_id = intval($mode_id);
    }

    // Generate expedition ref: EX + YYMM + - + sequential number
    $prefix = 'EX';
    $year = date('ym');
    $last_ref = $db->field("SELECT MAX(ref) FROM llx_expedition WHERE ref LIKE '" . $prefix . $year . "-%' AND entity=1");
    if ($last_ref) {
        $num = intval(substr($last_ref, strrpos($last_ref, '-') + 1)) + 1;
    } else {
        $num = 1;
    }
    $exp_ref = $prefix . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);

    $now = date('Y-m-d H:i:s');
    $tracking = addslashes($order['tracking'] ?? '');

    $sql = "INSERT INTO llx_expedition (
                ref, entity, ref_ext, fk_soc, fk_shipping_method,
                tracking_number, date_creation, date_delivery,
                fk_statut, note_private, model_pdf
            ) VALUES (
                '" . addslashes($exp_ref) . "',
                1,
                '" . addslashes($ref_ext) . "',
                " . intval($commande['fk_soc']) . ",
                " . ($shipping_mode_id ? intval($shipping_mode_id) : 'NULL') . ",
                '$tracking',
                '$now',
                '$now',
                1,
                'Auto-created from SpaCart order #$orderid',
                'rouget'
            )";
    $db->query($sql);
    $exp_id = $db->field("SELECT LAST_INSERT_ID()");

    if (!$exp_id) {
        spacart_sync_log($orderid, 'expedition', 'failed', null, 'INSERT llx_expedition failed');
        return false;
    }

    // Copy commande lines to expedition lines
    $lines = $db->all("SELECT rowid, fk_product, qty FROM llx_commandedet WHERE fk_commande = " . intval($commande['rowid']));
    $rang = 1;
    foreach ($lines as $line) {
        $db->query("INSERT INTO llx_expeditiondet (fk_expedition, fk_origin_line, qty, rang)
                    VALUES ($exp_id, " . intval($line['rowid']) . ", " . floatval($line['qty']) . ", $rang)");
        $rang++;
    }

    // Link expedition to commande via element_element
    $db->query("INSERT IGNORE INTO llx_element_element (fk_source, sourcetype, fk_target, targettype)
                VALUES (" . intval($commande['rowid']) . ", 'commande', $exp_id, 'shipping')");

    // Update fk_expedition on SpaCart orders table
    $db->query("UPDATE orders SET fk_expedition = " . intval($exp_id) . " WHERE orderid = '" . $orderid . "'");

    spacart_sync_log($orderid, 'expedition', 'success', $exp_ref);
    return intval($exp_id);
}

/**
 * Update tracking number on an existing Dolibarr expedition.
 * Called when tracking info is updated on a SpaCart order.
 *
 * @param int $orderid SpaCart order ID
 * @param string $tracking Tracking number
 * @return bool Success
 */
function spacart_update_expedition_tracking($orderid, $tracking) {
    global $db;
    $orderid = intval($orderid);
    $ref_ext = 'SPACART-' . $orderid;

    $exp_id = $db->field("SELECT rowid FROM llx_expedition WHERE ref_ext = '" . addslashes($ref_ext) . "' AND entity = 1");
    if (!$exp_id) return false;

    $db->query("UPDATE llx_expedition SET tracking_number = '" . addslashes($tracking) . "' WHERE rowid = " . intval($exp_id));
    return true;
}

// ============================================================
// TICKET SYNC
// ============================================================

/**
 * Sync a SpaCart ticket to Dolibarr llx_ticket
 */
function spacart_sync_ticket_to_dolibarr($ticket_data) {
    $doli_db = spacart_get_dolibarr_db();
    if (!$doli_db) return false;
    
    // Check if already synced
    $ref = 'SPC-TK-' . intval($ticket_data['ticketid']);
    $check = $doli_db->query("SELECT rowid FROM llx_ticket WHERE ref = '" . $doli_db->real_escape_string($ref) . "'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        return $row['rowid'];
    }
    
    // Map SpaCart status to Dolibarr: 0=open->0(not read), 1=answered->3(answered), 2=closed->8(closed)
    $status_map = [0 => 0, 1 => 3, 2 => 8];
    $doli_status = isset($status_map[$ticket_data['status']]) ? $status_map[$ticket_data['status']] : 0;
    
    // Map priority: 0=low, 1=normal, 2=high
    $priority = isset($ticket_data['priority']) ? intval($ticket_data['priority']) : 1;
    
    $fk_user = spacart_get_dolibarr_user();
    $now = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO llx_ticket (ref, type_code, category_code, severity_code, subject, message, fk_statut, resolution, progress, timing, fk_user_create, fk_user_assign, datec, date_read, date_close, entity, notify_tiers_at_create)
            VALUES (
                '" . $doli_db->real_escape_string($ref) . "',
                'OTHER',
                'OTHER',
                'NORMAL',
                '" . $doli_db->real_escape_string($ticket_data['subject'] ?? '') . "',
                '" . $doli_db->real_escape_string($ticket_data['message'] ?? '') . "',
                " . intval($doli_status) . ",
                0,
                '',
                0,
                " . intval($fk_user) . ",
                " . intval($fk_user) . ",
                '" . $doli_db->real_escape_string($ticket_data['date'] ?? $now) . "',
                NULL,
                " . ($doli_status == 8 ? "'" . $doli_db->real_escape_string($now) . "'" : "NULL") . ",
                1,
                0
            )";
    
    if ($doli_db->query($sql)) {
        $ticket_id = $doli_db->insert_id;
        spacart_sync_log($ticket_data['ticketid'], 'ticket', 'success');
        return $ticket_id;
    } else {
        spacart_sync_log($ticket_data['ticketid'], 'ticket', 'failed', null, $doli_db->error);
        return false;
    }
}

/**
 * Sync a ticket message/reply to Dolibarr
 */
function spacart_sync_ticket_message($ticket_ref, $message_data) {
    $doli_db = spacart_get_dolibarr_db();
    if (!$doli_db) return false;
    
    $ref = 'SPC-TK-' . intval($ticket_ref);
    
    // Get Dolibarr ticket ID
    $check = $doli_db->query("SELECT rowid FROM llx_ticket WHERE ref = '" . $doli_db->real_escape_string($ref) . "'");
    if (!$check || $check->num_rows == 0) return false;
    $ticket = $check->fetch_assoc();
    $fk_ticket = $ticket['rowid'];
    
    $fk_user = spacart_get_dolibarr_user();
    
    // Insert into actioncomm (Dolibarr events table, used for ticket messages)
    $sql = "INSERT INTO llx_actioncomm (code, type_code, label, note, datep, datef, fk_action, fk_element, elementtype, fk_user_author, entity, percent)
            VALUES (
                'TICKET_MSG',
                'AC_OTH_AUTO',
                'Message from SpaCart',
                '" . $doli_db->real_escape_string($message_data['message'] ?? '') . "',
                '" . $doli_db->real_escape_string($message_data['date'] ?? date('Y-m-d H:i:s')) . "',
                '" . $doli_db->real_escape_string($message_data['date'] ?? date('Y-m-d H:i:s')) . "',
                0,
                " . intval($fk_ticket) . ",
                'ticket',
                " . intval($fk_user) . ",
                1,
                -1
            )";
    
    return $doli_db->query($sql);
}
