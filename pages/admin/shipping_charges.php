<?php
extract($_GET, EXTR_SKIP);
extract($_POST, EXTR_SKIP);
$maxvalue = 9999999.99;
if ($type == 'R') {
    if ($config['Shipping']['realtime_shipping'] != 'Y') {
    	$_SESSION['alerts'][] = array(
    		'type'		=> 'e',
    		'content'	=> lng('Realtime shipping is disabled.')
    	);

        redirect("/configuration/Shipping");
    }
} else
    $type = 'D';

$type_condition = " AND type = '".$type."'";
function func_shipping_set_apply_to_parameter($zone_id, $ship_id, $apply_to) {
    global $type_condition, $db;

    $upd_condition = "zoneid = '".$zone_id."' AND shippingid = '".$ship_id."'".$type_condition;
    $avail_types = array('ST', 'DST');
    $query_data = array(
        'apply_to' => in_array($apply_to, $avail_types) ? $apply_to : 'DST',
    );

    $db->array2update('shipping_rates', $query_data, $upd_condition);
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if ($mode == 'delete') {
        if (is_array($posted_data)) {
            $deleted = false;
            foreach ($posted_data as $rateid=>$v) {
                if (empty($v['to_delete']))
                    continue;

                $db->query("DELETE FROM shipping_rates WHERE rateid='".$rateid."' ".$type_condition);
            }
        }
    }

    if ($mode == 'update') {
        if (is_array($posted_data)) {
            foreach ($posted_data as $rateid => $v) {
                $query_data = array(
                    'minweight'        => floatval($v['minweight']),
                    'maxweight'        => floatval($v['maxweight']),
                    'mintotal'         => floatval($v['mintotal']),
                    'maxtotal'         => floatval($v['maxtotal']),
                    'rate'             => floatval($v['rate']),
                    'item_rate'        => floatval($v['item_rate']),
                    'rate_p'           => floatval($v['rate_p']),
                    'weight_rate'      => floatval($v['weight_rate']),
                );

                $db->array2update('shipping_rates', $query_data, "rateid = '".$rateid."' ".$type_condition);
            }
        }

        if (is_array($apply_to)) {
            foreach ($apply_to as $zone_id => $shipping_methods) {
                if (!is_array($shipping_methods))
                	continue;

                foreach ($shipping_methods as $ship_id => $value)
                    func_shipping_set_apply_to_parameter($zone_id, $ship_id, $value);
            }
        }
    }

    if ($mode == 'add') {
        if ($shippingid_new) {
            $query_data = array(
                'shippingid'      => $shippingid_new,
                'minweight'       => floatval($minweight_new),
                'maxweight'       => floatval($maxweight_new),
                'mintotal'        => floatval($mintotal_new),
                'maxtotal'        => floatval($maxtotal_new),
                'rate'            => floatval($rate_new),
                'item_rate'       => floatval($item_rate_new),
                'rate_p'          => floatval($rate_p_new),
                'weight_rate'     => floatval($weight_rate_new),
                'zoneid'          => $zoneid_new,
                'type'            => $type,
                'apply_to'        => $apply_to_new,
            );

            $db->array2insert('shipping_rates', $query_data);
            func_shipping_set_apply_to_parameter($zoneid_new, $shippingid_new, $apply_to_new);
        }
    }

    redirect("/admin/shipping_charges?zoneid=".$zoneid."&shippingid=".$shippingid."&type=".$type);
}

$zoneid = addslashes($zoneid);
$shippingid = addslashes($shippingid);
$zone_condition = (!empty($zoneid) ? " and shipping_rates.zoneid='$zoneid'" : "");
$method_condition = (!empty($shippingid) ? " and shipping_rates.shippingid='$shippingid'" : "");
$realtime_condition = ($config['Shipping']['realtime_shipping']=="Y"?" AND shipping.code=''":"");
$shipping_rates = $db->all("SELECT shipping_rates.*, shipping.shipping, shipping.shipping_time, shipping.destination FROM shipping, shipping_rates WHERE shipping_rates.shippingid=shipping.shippingid AND shipping.active='Y'".$type_condition.$zone_condition.$method_condition.($type=="R"?" AND code!='' ":$realtime_condition)." ORDER BY shipping.orderby, shipping_rates.maxweight");
$zones = array(array('zoneid'=>0,'zone'=>lng('Default zone')));
$_tmp = $db->all("SELECT zoneid, zone_name as zone FROM zones ORDER BY zoneid");
if (!empty($_tmp))
    $zones = array_merge($zones,$_tmp);

if (is_array($zones) && is_array($shipping_rates)) {
    foreach ($zones as $zone) {
        $shipping_rates_list = array();
        foreach ($shipping_rates as $shipping_rate) {
            if ($shipping_rate['zoneid'] != $zone['zoneid'])
                continue;

            $shipping_rates_list[$shipping_rate['shippingid']]['shipping'] = $shipping_rate['shipping'];
            $shipping_rates_list[$shipping_rate['shippingid']]['destination'] = $shipping_rate['destination'];
            $shipping_rates_list[$shipping_rate['shippingid']]['apply_to'] = $shipping_rate['apply_to'];
            $shipping_rates_list[$shipping_rate['shippingid']]['rates'][] = $shipping_rate;
        }

        $_zones_list = array();
        $_zones_list['zone'] = $zone;
        $_zones_list['shipping_methods'] = $shipping_rates_list;
        $zones_list[] = $_zones_list;
    }
}

if ($type == 'R')
    $shipping = $db->all("SELECT * FROM shipping WHERE active='Y' AND code!='' ORDER BY shipping, orderby");
else
    $shipping = $db->all("SELECT * FROM shipping WHERE active='Y' $realtime_condition ORDER BY shipping, orderby");

$template['shipping'] = $shipping;
$template['zones'] = $zones;
$template['shipping_rates'] = $shipping_rates;
$template['shipping_rates_avail'] = (is_array($shipping_rates) ? count($shipping_rates) : 0);
$template['zones_list'] = $zones_list;
$template['type'] = $type;
$template['zoneid'] = $zoneid;
$template['shippingid'] = $shippingid;
$template['maxvalue'] = $maxvalue;

$template['head_title'] = lng('Shipping charges').' :: '.$template['head_title'];
$template['location'] .= ' &gt; '.lng('Shipping charges');

$template['page'] = get_template_contents('admin/pages/shipping_charges.php');
$template['css'][] = 'admin_shipping';
$template['js'][] = 'admin_shipping';