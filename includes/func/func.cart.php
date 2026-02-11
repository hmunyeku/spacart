<?php
/**
 * SpaCart - Cart functions
 * Cart persistence: session + database (llx_spacart_cart / llx_spacart_cart_item)
 */

/**
 * Get or create a cart for the current session
 */
function spacart_get_or_create_cart($sessionId = '', $customerId = 0)
{
    global $db;

    $cart = null;

    // Try to find existing cart by customer
    if ($customerId > 0) {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_cart";
        $sql .= " WHERE fk_customer = ".(int) $customerId;
        $sql .= " AND status = 'active'";
        $sql .= " ORDER BY tms DESC LIMIT 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql)) {
            $obj = $db->fetch_object($resql);
            $cart = spacart_load_cart($obj->rowid);
        }
    }

    // Try by session
    if (!$cart && $sessionId) {
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_cart";
        $sql .= " WHERE session_id = '".$db->escape($sessionId)."'";
        $sql .= " AND status = 'active'";
        $sql .= " ORDER BY tms DESC LIMIT 1";
        $resql = $db->query($sql);
        if ($resql && $db->num_rows($resql)) {
            $obj = $db->fetch_object($resql);
            $cart = spacart_load_cart($obj->rowid);
        }
    }

    // Create new
    if (!$cart) {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_cart";
        $sql .= " (session_id, fk_customer, status, date_creation, tms)";
        $sql .= " VALUES ('".$db->escape($sessionId)."', ".(int) $customerId.", 'active', NOW(), NOW())";
        $db->query($sql);
        $cartId = $db->last_insert_id(MAIN_DB_PREFIX."spacart_cart");
        $cart = spacart_load_cart($cartId);
    }

    return $cart;
}

/**
 * Load cart with all items
 */
function spacart_load_cart($cartId)
{
    global $db;

    $sql = "SELECT c.rowid, c.session_id, c.fk_customer, c.fk_soc, c.status,";
    $sql .= " c.coupon_code, c.coupon_discount, c.giftcard_code, c.giftcard_amount,";
    $sql .= " c.shipping_method, c.shipping_cost, c.tax_amount,";
    $sql .= " c.subtotal, c.total, c.date_creation, c.tms";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_cart c";
    $sql .= " WHERE c.rowid = ".(int) $cartId;

    $resql = $db->query($sql);
    if (!$resql || !$db->num_rows($resql)) return null;

    $cart = $db->fetch_object($resql);
    $cart->items = spacart_get_cart_items($cartId);
    $cart->count = 0;

    foreach ($cart->items as $item) {
        $cart->count += (int) $item->qty;
    }

    return $cart;
}

/**
 * Get cart items
 */
function spacart_get_cart_items($cartId)
{
    global $db;
    $items = array();

    $sql = "SELECT ci.rowid, ci.fk_cart, ci.fk_product, ci.fk_variant, ci.options_json,";
    $sql .= " ci.qty, ci.price_ht, ci.price_ttc, ci.tva_tx, ci.label, ci.ref,";
    $sql .= " ci.weight, ci.date_creation,";
    $sql .= " p.label as product_label, p.ref as product_ref, p.stock_reel";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_cart_item ci";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = ci.fk_product";
    $sql .= " WHERE ci.fk_cart = ".(int) $cartId;
    $sql .= " ORDER BY ci.date_creation ASC";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $obj->label = $obj->label ?: $obj->product_label;
            $obj->ref = $obj->ref ?: $obj->product_ref;
            $obj->options = $obj->options_json ? json_decode($obj->options_json, true) : array();
            $items[] = $obj;
        }
    }
    return $items;
}

/**
 * Add item to cart
 */
function spacart_cart_add($cartId, $productId, $qty = 1, $variantId = 0, $options = array())
{
    global $db;

    // Load product info
    $sql = "SELECT rowid, ref, label, price, price_ttc, tva_tx, weight, stock_reel";
    $sql .= " FROM ".MAIN_DB_PREFIX."product WHERE rowid = ".(int) $productId;
    $resql = $db->query($sql);
    if (!$resql || !$db->num_rows($resql)) {
        return array('success' => false, 'message' => 'Produit introuvable');
    }
    $product = $db->fetch_object($resql);

    $price = (float) $product->price;
    $priceTtc = (float) $product->price_ttc;
    $tvaTx = (float) $product->tva_tx;
    $weight = (float) $product->weight;
    $label = $product->label;
    $ref = $product->ref;

    // Variant override
    if ($variantId > 0) {
        $sqlv = "SELECT rowid, label as vlabel, sku, price as vprice, weight as vweight, stock";
        $sqlv .= " FROM ".MAIN_DB_PREFIX."spacart_variant";
        $sqlv .= " WHERE rowid = ".(int) $variantId." AND fk_product = ".(int) $productId;
        $resv = $db->query($sqlv);
        if ($resv && $db->num_rows($resv)) {
            $variant = $db->fetch_object($resv);
            if ($variant->vprice > 0) $price = (float) $variant->vprice;
            if ($variant->vweight > 0) $weight = (float) $variant->vweight;
            if ($variant->sku) $ref = $variant->sku;
            if ($variant->vlabel) $label .= ' - '.$variant->vlabel;
        }
    }

    // Options price modifiers
    $optionsJson = '';
    if (!empty($options)) {
        $optDetails = array();
        foreach ($options as $groupId => $optionId) {
            $sqlo = "SELECT o.label, o.price_modifier, o.price_modifier_type, o.weight_modifier,";
            $sqlo .= " og.label as group_label";
            $sqlo .= " FROM ".MAIN_DB_PREFIX."spacart_option o";
            $sqlo .= " INNER JOIN ".MAIN_DB_PREFIX."spacart_option_group og ON og.rowid = o.fk_option_group";
            $sqlo .= " WHERE o.rowid = ".(int) $optionId;
            $reso = $db->query($sqlo);
            if ($reso && $db->num_rows($reso)) {
                $opt = $db->fetch_object($reso);
                if ($opt->price_modifier_type === 'percent') {
                    $price += $price * (float) $opt->price_modifier / 100;
                } else {
                    $price += (float) $opt->price_modifier;
                }
                $weight += (float) $opt->weight_modifier;
                $optDetails[] = array(
                    'group_id' => (int) $groupId,
                    'option_id' => (int) $optionId,
                    'group_label' => $opt->group_label,
                    'option_label' => $opt->label
                );
            }
        }
        $optionsJson = json_encode($optDetails);
    }

    // Recalculate TTC
    $priceTtc = $price * (1 + $tvaTx / 100);

    // Check if same item already in cart
    $existingId = 0;
    $sqlCheck = "SELECT rowid, qty FROM ".MAIN_DB_PREFIX."spacart_cart_item";
    $sqlCheck .= " WHERE fk_cart = ".(int) $cartId;
    $sqlCheck .= " AND fk_product = ".(int) $productId;
    $sqlCheck .= " AND COALESCE(fk_variant, 0) = ".(int) $variantId;
    if ($optionsJson) {
        $sqlCheck .= " AND options_json = '".$db->escape($optionsJson)."'";
    } else {
        $sqlCheck .= " AND (options_json IS NULL OR options_json = '')";
    }
    $resCheck = $db->query($sqlCheck);
    if ($resCheck && $db->num_rows($resCheck)) {
        $existing = $db->fetch_object($resCheck);
        $existingId = $existing->rowid;
        $newQty = (int) $existing->qty + (int) $qty;

        $sqlUp = "UPDATE ".MAIN_DB_PREFIX."spacart_cart_item";
        $sqlUp .= " SET qty = ".(int) $newQty.", tms = NOW()";
        $sqlUp .= " WHERE rowid = ".(int) $existingId;
        $db->query($sqlUp);
    } else {
        $sqlIns = "INSERT INTO ".MAIN_DB_PREFIX."spacart_cart_item";
        $sqlIns .= " (fk_cart, fk_product, fk_variant, options_json, qty, price_ht, price_ttc,";
        $sqlIns .= " tva_tx, label, ref, weight, date_creation, tms)";
        $sqlIns .= " VALUES (".(int) $cartId.", ".(int) $productId.", ".($variantId ? (int) $variantId : 'NULL').",";
        $sqlIns .= " ".($optionsJson ? "'".$db->escape($optionsJson)."'" : "NULL").",";
        $sqlIns .= " ".(int) $qty.", ".(float) $price.", ".(float) $priceTtc.",";
        $sqlIns .= " ".(float) $tvaTx.", '".$db->escape($label)."', '".$db->escape($ref)."',";
        $sqlIns .= " ".(float) $weight.", NOW(), NOW())";
        $db->query($sqlIns);
    }

    // Recalculate totals
    spacart_recalculate_cart($cartId);

    return array('success' => true, 'message' => 'Produit ajouté au panier');
}

/**
 * Update cart item quantity
 */
function spacart_cart_update_qty($cartId, $itemId, $qty)
{
    global $db;

    $qty = max(0, (int) $qty);

    if ($qty <= 0) {
        return spacart_cart_remove_item($cartId, $itemId);
    }

    $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_cart_item";
    $sql .= " SET qty = ".(int) $qty.", tms = NOW()";
    $sql .= " WHERE rowid = ".(int) $itemId." AND fk_cart = ".(int) $cartId;
    $db->query($sql);

    spacart_recalculate_cart($cartId);

    return array('success' => true, 'message' => 'Quantité mise à jour');
}

/**
 * Remove item from cart
 */
function spacart_cart_remove_item($cartId, $itemId)
{
    global $db;

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."spacart_cart_item";
    $sql .= " WHERE rowid = ".(int) $itemId." AND fk_cart = ".(int) $cartId;
    $db->query($sql);

    spacart_recalculate_cart($cartId);

    return array('success' => true, 'message' => 'Produit retiré du panier');
}

/**
 * Recalculate cart totals
 */
function spacart_recalculate_cart($cartId)
{
    global $db;

    // Calculate subtotal
    $sql = "SELECT SUM(price_ht * qty) as subtotal_ht, SUM(price_ttc * qty) as subtotal_ttc,";
    $sql .= " SUM((price_ttc - price_ht) * qty) as tax_total";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_cart_item";
    $sql .= " WHERE fk_cart = ".(int) $cartId;
    $resql = $db->query($sql);

    $subtotal = 0;
    $taxAmount = 0;
    if ($resql) {
        $obj = $db->fetch_object($resql);
        $subtotal = (float) $obj->subtotal_ht;
        $taxAmount = (float) $obj->tax_total;
    }

    // Get current cart for coupon/shipping info
    $sqlCart = "SELECT coupon_discount, giftcard_amount, shipping_cost FROM ".MAIN_DB_PREFIX."spacart_cart WHERE rowid = ".(int) $cartId;
    $resCart = $db->query($sqlCart);
    $cartInfo = $resCart ? $db->fetch_object($resCart) : null;

    $couponDiscount = $cartInfo ? (float) $cartInfo->coupon_discount : 0;
    $giftcardAmount = $cartInfo ? (float) $cartInfo->giftcard_amount : 0;
    $shippingCost = $cartInfo ? (float) $cartInfo->shipping_cost : 0;

    $total = $subtotal + $taxAmount + $shippingCost - $couponDiscount - $giftcardAmount;
    if ($total < 0) $total = 0;

    $sqlUpdate = "UPDATE ".MAIN_DB_PREFIX."spacart_cart";
    $sqlUpdate .= " SET subtotal = ".(float) $subtotal.",";
    $sqlUpdate .= " tax_amount = ".(float) $taxAmount.",";
    $sqlUpdate .= " total = ".(float) $total.",";
    $sqlUpdate .= " tms = NOW()";
    $sqlUpdate .= " WHERE rowid = ".(int) $cartId;
    $db->query($sqlUpdate);
}

/**
 * Apply coupon code to cart
 */
function spacart_cart_apply_coupon($cartId, $code)
{
    global $db;

    // Check coupon exists and is valid
    $sql = "SELECT rowid, code, type, value, min_order, max_uses, current_uses,";
    $sql .= " date_start, date_end, active";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_coupon";
    $sql .= " WHERE code = '".$db->escape($code)."' AND active = 1";
    $resql = $db->query($sql);

    if (!$resql || !$db->num_rows($resql)) {
        return array('success' => false, 'message' => 'Code promo invalide');
    }

    $coupon = $db->fetch_object($resql);

    // Check dates
    if ($coupon->date_start && strtotime($coupon->date_start) > time()) {
        return array('success' => false, 'message' => 'Ce code promo n\'est pas encore actif');
    }
    if ($coupon->date_end && strtotime($coupon->date_end) < time()) {
        return array('success' => false, 'message' => 'Ce code promo a expiré');
    }

    // Check max uses
    if ($coupon->max_uses > 0 && $coupon->current_uses >= $coupon->max_uses) {
        return array('success' => false, 'message' => 'Ce code promo a atteint sa limite d\'utilisation');
    }

    // Check min order
    $cart = spacart_load_cart($cartId);
    if ($coupon->min_order > 0 && $cart->subtotal < $coupon->min_order) {
        return array('success' => false, 'message' => 'Montant minimum de commande non atteint ('.spacartFormatPrice($coupon->min_order).')');
    }

    // Calculate discount
    $discount = 0;
    if ($coupon->type === 'percent') {
        $discount = $cart->subtotal * (float) $coupon->value / 100;
    } else {
        $discount = (float) $coupon->value;
    }

    // Don't exceed cart total
    if ($discount > $cart->subtotal) {
        $discount = $cart->subtotal;
    }

    // Apply
    $sqlUp = "UPDATE ".MAIN_DB_PREFIX."spacart_cart";
    $sqlUp .= " SET coupon_code = '".$db->escape($code)."',";
    $sqlUp .= " coupon_discount = ".(float) $discount.",";
    $sqlUp .= " tms = NOW()";
    $sqlUp .= " WHERE rowid = ".(int) $cartId;
    $db->query($sqlUp);

    spacart_recalculate_cart($cartId);

    return array('success' => true, 'message' => 'Code promo appliqué ! -'.spacartFormatPrice($discount));
}

/**
 * Apply gift card code to cart
 */
function spacart_cart_apply_giftcard($cartId, $code)
{
    global $db;

    $sql = "SELECT rowid, code, initial_amount, balance, active, date_expiry";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_giftcard";
    $sql .= " WHERE code = '".$db->escape($code)."' AND active = 1";
    $resql = $db->query($sql);

    if (!$resql || !$db->num_rows($resql)) {
        return array('success' => false, 'message' => 'Carte cadeau invalide');
    }

    $gc = $db->fetch_object($resql);

    if ($gc->date_expiry && strtotime($gc->date_expiry) < time()) {
        return array('success' => false, 'message' => 'Cette carte cadeau a expiré');
    }

    if ($gc->balance <= 0) {
        return array('success' => false, 'message' => 'Le solde de cette carte est épuisé');
    }

    $cart = spacart_load_cart($cartId);
    $amount = min((float) $gc->balance, (float) $cart->total);

    $sqlUp = "UPDATE ".MAIN_DB_PREFIX."spacart_cart";
    $sqlUp .= " SET giftcard_code = '".$db->escape($code)."',";
    $sqlUp .= " giftcard_amount = ".(float) $amount.",";
    $sqlUp .= " tms = NOW()";
    $sqlUp .= " WHERE rowid = ".(int) $cartId;
    $db->query($sqlUp);

    spacart_recalculate_cart($cartId);

    return array('success' => true, 'message' => 'Carte cadeau appliquée ! -'.spacartFormatPrice($amount).' (solde: '.spacartFormatPrice($gc->balance - $amount).')');
}

/**
 * Merge anonymous cart into customer cart after login
 */
function spacart_merge_carts($anonymousCartId, $customerCartId)
{
    global $db;

    // Get items from anonymous cart
    $items = spacart_get_cart_items($anonymousCartId);

    foreach ($items as $item) {
        spacart_cart_add(
            $customerCartId,
            $item->fk_product,
            $item->qty,
            $item->fk_variant ?: 0,
            $item->options
        );
    }

    // Deactivate anonymous cart
    $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_cart SET status = 'merged' WHERE rowid = ".(int) $anonymousCartId;
    $db->query($sql);
}

/**
 * Clear cart (after order)
 */
function spacart_clear_cart($cartId)
{
    global $db;

    $db->query("DELETE FROM ".MAIN_DB_PREFIX."spacart_cart_item WHERE fk_cart = ".(int) $cartId);
    $sql = "UPDATE ".MAIN_DB_PREFIX."spacart_cart";
    $sql .= " SET status = 'completed', subtotal = 0, total = 0, tax_amount = 0,";
    $sql .= " shipping_cost = 0, coupon_code = NULL, coupon_discount = 0,";
    $sql .= " giftcard_code = NULL, giftcard_amount = 0, tms = NOW()";
    $sql .= " WHERE rowid = ".(int) $cartId;
    $db->query($sql);
}

/**
 * Get cart summary (for badge/minicart)
 */
function spacart_get_cart_summary($cartId)
{
    global $db;

    $sql = "SELECT COUNT(*) as item_count, SUM(qty) as total_qty, SUM(price_ht * qty) as subtotal";
    $sql .= " FROM ".MAIN_DB_PREFIX."spacart_cart_item";
    $sql .= " WHERE fk_cart = ".(int) $cartId;
    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        return array(
            'count' => (int) $obj->total_qty,
            'subtotal' => (float) $obj->subtotal
        );
    }
    return array('count' => 0, 'subtotal' => 0);
}
