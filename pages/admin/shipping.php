<?php
/**
 * SpaCart Admin - Shipping Methods
 *
 * Updated 2026-02-15: Writes to llx_spacart_shipping_method (Dolibarr-integrated)
 * instead of the legacy shipping table (now a VIEW).
 * Reads still use the shipping VIEW for backward compatibility.
 */

$max_input_vars = ini_get('max_input_vars');
if (empty($max_input_vars)) {
    $max_input_vars = 1000;
}

extract($_POST, EXTR_SKIP);
extract($_GET, EXTR_SKIP);

// No realtime/intershipper carriers in integrated mode
$intershipper_cond = '';

// Carriers are now Dolibarr shipment modes
$carriers = $db->all("SELECT c.code, c.libelle as shipping, c.rowid as dolibarr_id
    FROM llx_c_shipment_mode c WHERE c.active=1 ORDER BY c.code");

$carrier_valid = false;

if (!empty($carriers)) {
    foreach ($carriers as $k=>$v) {
        if ($v['code'] == $carrier)
            $carrier_valid = true;

        // Count spacart methods linked to this Dolibarr mode
        $_carrier_total_enabled = $db->field("SELECT COUNT(*) FROM llx_spacart_shipping_method WHERE fk_shipment_mode='".$v['dolibarr_id']."' AND status=1 AND entity=1");
        $carriers[$k]['total_enabled'] = $_carrier_total_enabled;
    }
}

if (!$carrier_valid)
    $carrier = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($data)) {
        foreach ($data as $id => $arr) {
            // Map VIEW columns to llx_spacart_shipping_method columns
            $update_data = array();

            if (isset($arr['shipping']))
                $update_data['label'] = $arr['shipping'];

            if (isset($arr['shipping_time']))
                $update_data['description'] = $arr['shipping_time'];

            if (isset($arr['destination']))
                $update_data['destination'] = $arr['destination'];

            if (isset($arr['orderby']))
                $update_data['position'] = (int)$arr['orderby'];

            if (isset($arr['active']))
                $update_data['status'] = ($arr['active'] == 'Y') ? 1 : 0;
            else
                $update_data['status'] = 0;

            if (!empty($update_data)) {
                $db->array2update('llx_spacart_shipping_method', $update_data, "rowid = '".(int)$id."' AND entity=1");
            }
        }
    }

    if (!empty($add['shipping'])) {
        $insert_data = array(
            'label'       => $add['shipping'],
            'description' => !empty($add['shipping_time']) ? $add['shipping_time'] : '',
            'destination' => !empty($add['destination']) ? $add['destination'] : 'N',
            'position'    => !empty($add['orderby']) ? (int)$add['orderby'] : 0,
            'status'      => (!empty($add['active']) && $add['active'] == 'Y') ? 1 : 0,
            'entity'      => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'price'       => 0,
            'free_above'  => 0,
        );

        // Link to Dolibarr shipment mode if code provided
        if (!empty($add['code'])) {
            $dol_mode_id = $db->field("SELECT rowid FROM llx_c_shipment_mode WHERE code='".addslashes($add['code'])."' AND active=1 LIMIT 1");
            if ($dol_mode_id)
                $insert_data['fk_shipment_mode'] = (int)$dol_mode_id;
        }

        $db->array2insert('llx_spacart_shipping_method', $insert_data);
    }

    redirect('/admin/shipping/'.(!empty($carrier) ? "?carrier=$carrier" : ''));
}

if ($mode == 'delete') {
    $db->query("DELETE FROM llx_spacart_shipping_method WHERE rowid='".(int)$shippingid."' AND entity=1");
    $db->query("DELETE FROM shipping_rates WHERE shippingid='".(int)$shippingid."'");

    redirect('/admin/shipping');
}

$condition = '';

$template['head_title'] = lng('Shipping methods').' :: '.$template['head_title'];
$template['location'] .= ' &gt; '.lng('Shipping methods');

// Read from the VIEW (backward compatible)
$shipping = $db->all("SELECT * FROM shipping WHERE 1 $condition ORDER BY orderby, shipping");
$new_shipping = '';
$active_shipping_vars = 0;

if (!empty($shipping)) {
    foreach ($shipping as $v) {
        if ($v['active'] == 'Y') {
            $active_shipping_vars = $active_shipping_vars + 7;
        }
    }
}

if ($active_shipping_vars >= $max_input_vars && $_GET['alert'] != 'Y') {
    $_SESSION['alerts'][] = array(
   		'type'		=> 'e',
   		'content'	=> 'PHP variable max_input_vars is '.$max_input_vars.' when shipping methods are '.$active_shipping_vars
    );

    redirect($_SERVER['REQUEST_URI'].'?&alert=Y');
}

$template['shipping'] = $shipping;
$template['new_shipping'] = $new_shipping;
$template['carriers'] = $carriers;
$template['carrier'] = $carrier;

$template['page'] = get_template_contents('admin/pages/shipping.php');
$template['css'][] = 'admin_shipping';
$template['js'][] = 'admin_shipping';
