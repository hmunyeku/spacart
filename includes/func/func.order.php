<?php
/**
 * SpaCart - Order functions
 * Creates Dolibarr commandes from spacart checkout
 */

/**
 * Create Dolibarr order from checkout
 */
function spacart_create_order($cartId, $customerId, $checkoutData)
{
    global $db;

    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
    require_once SPACART_PATH.'/includes/func/func.cart.php';
    require_once SPACART_PATH.'/includes/func/func.user.php';

    // Load cart
    $cart = spacart_load_cart($cartId);
    if (!$cart || empty($cart->items)) {
        return array('success' => false, 'message' => 'Panier vide');
    }

    // Get or determine fk_soc
    $fkSoc = 0;
    if ($customerId) {
        $customer = spacart_load_customer($customerId);
        if ($customer) {
            $fkSoc = (int) $customer->fk_soc;
        }
    }

    // Guest checkout: create a tiers
    if (!$fkSoc) {
        $firstname = $checkoutData['shipping_firstname'] ?? ($checkoutData['firstname'] ?? 'Client');
        $lastname = $checkoutData['shipping_lastname'] ?? ($checkoutData['lastname'] ?? 'Web');
        $email = $checkoutData['email'] ?? '';
        $phone = $checkoutData['phone'] ?? '';
        $company = $checkoutData['company'] ?? '';

        $fkSoc = spacart_create_dolibarr_tiers($firstname, $lastname, $email, $phone, $company);
        if (!$fkSoc) {
            return array('success' => false, 'message' => 'Erreur création du client');
        }

        // Link to spacart customer if logged in
        if ($customerId) {
            $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer SET fk_soc = ".(int) $fkSoc." WHERE rowid = ".(int) $customerId);
        }
    }

    // Technical user for operations
    $techUserId = getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1);
    $techUser = new User($db);
    $techUser->fetch($techUserId);

    // Create commande
    $commande = new Commande($db);
    $commande->socid = $fkSoc;
    $commande->date_commande = dol_now();
    $commande->source = 0;
    $commande->module_source = 'spacart';

    // Set note with shipping address
    $notePublic = '';
    if (!empty($checkoutData['shipping_address'])) {
        $notePublic .= "Adresse de livraison:\n";
        $notePublic .= ($checkoutData['shipping_firstname'] ?? '').' '.($checkoutData['shipping_lastname'] ?? '')."\n";
        $notePublic .= ($checkoutData['shipping_address'] ?? '')."\n";
        $notePublic .= ($checkoutData['shipping_zip'] ?? '').' '.($checkoutData['shipping_city'] ?? '')."\n";
        $notePublic .= ($checkoutData['shipping_phone'] ?? '')."\n";
    }

    $notePrivate = 'Commande SpaCart #'.$cartId;
    if (!empty($checkoutData['payment_method'])) {
        $notePrivate .= ' | Paiement: '.$checkoutData['payment_method'];
    }
    if (!empty($cart->coupon_code)) {
        $notePrivate .= ' | Coupon: '.$cart->coupon_code.' (-'.spacartFormatPrice($cart->coupon_discount).')';
    }

    $commande->note_public = $notePublic;
    $commande->note_private = $notePrivate;

    $result = $commande->create($techUser);
    if ($result <= 0) {
        return array('success' => false, 'message' => 'Erreur création commande: '.$commande->error);
    }

    $orderId = $commande->id;

    // Add order lines
    foreach ($cart->items as $item) {
        $commande->addline(
            $item->label,              // desc
            $item->price_ht,           // pu_ht
            $item->qty,                // qty
            $item->tva_tx,             // txtva
            0,                         // txlocaltax1
            0,                         // txlocaltax2
            $item->fk_product,         // fk_product
            0,                         // remise_percent
            0,                         // info_bits
            0,                         // fk_remise_except
            'HT',                      // price_base_type
            $item->price_ht,           // pu_ttc (recalculated)
            0,                         // date_start
            0                          // date_end
        );
    }

    // Add shipping line if applicable
    if ($cart->shipping_cost > 0) {
        $commande->addline(
            'Frais de livraison - '.($checkoutData['shipping_method_label'] ?? 'Standard'),
            $cart->shipping_cost,
            1,
            20,  // Default TVA on shipping
            0, 0, 0, 0, 0, 0, 'HT', 0, 0, 0
        );
    }

    // Validate the order
    $commande->valid($techUser);

    // Save billing address to commande if different
    if (!empty($checkoutData['billing_address']) && empty($checkoutData['same_billing'])) {
        $noteBilling = "\n\nAdresse de facturation:\n";
        $noteBilling .= ($checkoutData['billing_firstname'] ?? '').' '.($checkoutData['billing_lastname'] ?? '')."\n";
        $noteBilling .= ($checkoutData['billing_address'] ?? '')."\n";
        $noteBilling .= ($checkoutData['billing_zip'] ?? '').' '.($checkoutData['billing_city'] ?? '');
        $commande->note_public .= $noteBilling;
        $commande->update($techUser);
    }

    // Save addresses for customer
    if ($customerId) {
        spacart_save_checkout_addresses($customerId, $checkoutData);
    }

    // Update coupon usage
    if (!empty($cart->coupon_code)) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_coupon SET current_uses = current_uses + 1 WHERE code = '".$db->escape($cart->coupon_code)."'");
    }

    // Debit gift card
    if (!empty($cart->giftcard_code) && $cart->giftcard_amount > 0) {
        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_giftcard SET balance = balance - ".(float) $cart->giftcard_amount." WHERE code = '".$db->escape($cart->giftcard_code)."'");
    }

    // Clear cart
    spacart_clear_cart($cartId);

    return array(
        'success' => true,
        'message' => 'Commande créée avec succès',
        'order_id' => $orderId,
        'order_ref' => $commande->ref
    );
}

/**
 * Save checkout addresses as customer addresses
 */
function spacart_save_checkout_addresses($customerId, $data)
{
    // Shipping address
    if (!empty($data['shipping_address'])) {
        spacart_save_address($customerId, array(
            'type' => 'shipping',
            'firstname' => $data['shipping_firstname'] ?? '',
            'lastname' => $data['shipping_lastname'] ?? '',
            'address' => $data['shipping_address'] ?? '',
            'zip' => $data['shipping_zip'] ?? '',
            'city' => $data['shipping_city'] ?? '',
            'fk_country' => $data['shipping_country'] ?? 1,
            'phone' => $data['shipping_phone'] ?? '',
            'is_default' => 1
        ));
    }

    // Billing address (if different)
    if (!empty($data['billing_address']) && empty($data['same_billing'])) {
        spacart_save_address($customerId, array(
            'type' => 'billing',
            'firstname' => $data['billing_firstname'] ?? '',
            'lastname' => $data['billing_lastname'] ?? '',
            'address' => $data['billing_address'] ?? '',
            'zip' => $data['billing_zip'] ?? '',
            'city' => $data['billing_city'] ?? '',
            'fk_country' => $data['billing_country'] ?? 1,
            'phone' => $data['billing_phone'] ?? '',
            'is_default' => 1
        ));
    }
}

/**
 * Get order detail for invoice page
 */
function spacart_get_order_detail($orderId, $fkSoc = 0)
{
    global $db;

    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

    $commande = new Commande($db);
    $result = $commande->fetch($orderId);

    if ($result <= 0) return null;

    // Security: check ownership
    if ($fkSoc > 0 && $commande->socid != $fkSoc) {
        return null;
    }

    // Only show spacart orders
    if ($commande->module_source !== 'spacart') {
        return null;
    }

    // Fetch lines
    $commande->fetch_lines();

    return $commande;
}

/**
 * Get shipping methods available
 */
function spacart_get_shipping_methods($countryId = 0, $cartTotal = 0, $cartWeight = 0)
{
    global $db;
    $methods = array();

    $sql = "SELECT sm.rowid, sm.label, sm.description, sm.delivery_time,";
    $sql .= " sm.free_threshold";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_shipping_method sm";
    $sql .= " WHERE sm.active = 1";
    $sql .= " ORDER BY sm.position ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            // Check if free
            if ($obj->free_threshold > 0 && $cartTotal >= $obj->free_threshold) {
                $obj->cost = 0;
                $obj->is_free = true;
            } else {
                // Calculate rate
                $obj->cost = spacart_calculate_shipping_rate($obj->rowid, $countryId, $cartTotal, $cartWeight);
                $obj->is_free = false;
            }
            $methods[] = $obj;
        }
    }
    return $methods;
}

/**
 * Calculate shipping rate for a method
 */
function spacart_calculate_shipping_rate($methodId, $countryId, $amount, $weight)
{
    global $db;

    // Find zone for this country
    $zoneId = 0;
    $sqlZone = "SELECT sze.fk_zone FROM ".MAIN_DB_PREFIX."spacart_shipping_zone_elem sze";
    $sqlZone .= " WHERE sze.type = 'country' AND sze.value = '".(int) $countryId."'";
    $sqlZone .= " LIMIT 1";
    $resZone = $db->query($sqlZone);
    if ($resZone && $db->num_rows($resZone)) {
        $objZ = $db->fetch_object($resZone);
        $zoneId = (int) $objZ->fk_zone;
    }

    if (!$zoneId) {
        // Default zone
        $sqlDef = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_shipping_zone WHERE is_default = 1 LIMIT 1";
        $resDef = $db->query($sqlDef);
        if ($resDef && $db->num_rows($resDef)) {
            $zoneId = (int) $db->fetch_object($resDef)->rowid;
        }
    }

    // Get best matching rate
    $sql = "SELECT sr.price, sr.rate_type, sr.min_value, sr.max_value";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_shipping_rate sr";
    $sql .= " WHERE sr.fk_method = ".(int) $methodId;
    $sql .= " AND sr.fk_zone = ".(int) $zoneId;
    $sql .= " AND sr.active = 1";
    $sql .= " ORDER BY sr.min_value ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($rate = $db->fetch_object($resql)) {
            $compareValue = ($rate->rate_type === 'weight') ? $weight : $amount;
            if ($compareValue >= (float) $rate->min_value && ($rate->max_value <= 0 || $compareValue <= (float) $rate->max_value)) {
                return (float) $rate->price;
            }
        }
    }

    // Fallback flat rate
    return getDolGlobalString('SPACART_DEFAULT_SHIPPING_COST', '5.00');
}

/**
 * Calculate tax for a zone
 */
function spacart_calculate_tax($amount, $countryId)
{
    global $db;

    $sql = "SELECT tr.rate FROM ".MAIN_DB_PREFIX."spacart_tax_rate tr";
    $sql .= " INNER JOIN ".MAIN_DB_PREFIX."spacart_tax t ON t.rowid = tr.fk_tax";
    $sql .= " WHERE tr.fk_country = ".(int) $countryId;
    $sql .= " AND t.active = 1 AND tr.active = 1";
    $sql .= " LIMIT 1";

    $resql = $db->query($sql);
    if ($resql && $db->num_rows($resql)) {
        $obj = $db->fetch_object($resql);
        return $amount * (float) $obj->rate / 100;
    }

    return 0;
}
