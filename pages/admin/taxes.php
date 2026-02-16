<?php
if (!$get['2'])
	redirect('/admin/taxes/1');

extract($_GET, EXTR_SKIP);
extract($_POST, EXTR_SKIP);

if (!in_array($mode, array('add', 'details', 'delete', 'update', 'rate_details', 'delete_rates', 'update_rates', 'tax_options', 'apply')))
	$mode = '';

$taxid = intval($taxid);

// ---- Dolibarr bridge notice ----
// tax_rates is now a VIEW on Dolibarr's llx_c_tva table.
// Rate modifications (add/edit/delete) must be done in Dolibarr's tax admin.
$_tax_rates_is_view = true;
$_dolibarr_tax_admin_url = '/compta/tax/admin.php'; // Dolibarr tax admin
// Block rate-modifying operations on the VIEW
if ($_SERVER['REQUEST_METHOD'] == 'POST' && in_array($mode, array('rate_details', 'delete_rates', 'update_rates'))) {
    $_SESSION['alerts'][] = array(
        'type'    => 'e',
        'content' => 'Les taux de TVA sont synchronises depuis Dolibarr. Modifiez-les dans Configuration > Taxes de Dolibarr.'
    );
    redirect('/admin/taxes/' . ($get['2'] ? $get['2'] : '1'));
}
// ---- End Dolibarr bridge notice ----

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($mode == 'tax_options') {
		if (is_array($posted_data)) {
			foreach ($posted_data as $k=>$v) {
				if (!in_array($v, array('Y','N')))
					$v = 'N';

				$db->query("UPDATE config SET value='$v' WHERE name='$k' AND category='Taxes'");
			}
		}
	} elseif ($mode == 'delete') {
		if (!empty($to_delete) && is_array($to_delete))
			foreach ($to_delete as $k=>$v) {
				$db->query("DELETE FROM taxes WHERE taxid='$k'");
				$db->query("DELETE FROM tax_rates WHERE taxid='$k'");
				$db->query("DELETE FROM product_taxes WHERE taxid='$k'");
				$rateids = $db->column("SELECT rateid FROM tax_rates WHERE taxid='$k'");
				if (!empty($rateids))
					$db->query("DELETE FROM tax_rate_memberships WHERE rateid IN ('".implode("','", $rateids)."')");
			}
	} elseif ($mode == 'update') {
		if (!empty($posted_data) && is_array($posted_data))
			foreach ($posted_data as $k=>$v)
				$db->query("UPDATE taxes SET active='$v[active]', priority='$v[tax_priority]' WHERE taxid='$k'");
	} elseif ($mode == 'details') {
		$taxid = $get['2'];
		$tax_formula = preg_replace("/^=/", '', $tax_formula);
		$price_includes_tax = (!empty($price_includes_tax) ? 'Y' : 'N');
		$display_including_tax = (!empty($display_including_tax) ? 'Y' : 'N');
		$tax_priority = intval($tax_priority);
		if (!in_array($address_type, array('S','B')))
			$address_type = 'S';

		if (!in_array($display_info, array('R','V','A')))
			$display_info = '';

		$tax_service_name = trim($tax_service_name);
		$error = false;
		if (strlen($tax_service_name) == 0) {
			$error = true;
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Please fill in all required fields')
			);
		} elseif ($db->field("SELECT COUNT(*) FROM taxes WHERE tax_name='".$tax_service_name."' AND taxid!='".$taxid."'") > 0) {
			$error = true;
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content' => lng('The tax with specified name already exists, please select other')
			);
		}

		if ($error) {
			$tax_details['taxid'] = $taxid;
			$tax_details['tax_name'] = $tax_service_name;
			$tax_details['tax_display_name'] = $tax_display_name;
			$tax_details['active'] = $active;
			$tax_details['formula'] = $tax_formula;
			$tax_details['address_type'] = $address_type;
			$tax_details['price_includes_tax'] = $price_includes_tax;
			$tax_details['display_including_tax'] = $display_including_tax;
			$tax_details['display_info'] = $display_info;
			$tax_details['regnumber'] = $tax_regnumber;
			$tax_details['priority'] = $tax_priority;
			$_SESSION['tax_details'] = $tax_details;
		} else {
			$_SESSION['tax_details'] = '';
			if ($taxid == 'add') {
				$db->query("INSERT INTO taxes (tax_name) VALUES ('".addslashes($tax_service_name)."')");
				$taxid = $db->insert_id();
			}

			$query_data = array(
				'tax_display_name'	=> $tax_display_name ? $tax_display_name : $tax_service_name,
				'tax_name' => $tax_service_name,
				'formula' => $tax_formula,
				'address_type' => $address_type,
				'active' => $active,
				'price_includes_tax' => $price_includes_tax,
				'display_including_tax' => $display_including_tax,
				'display_info' => $display_info,
				'regnumber' => $tax_regnumber,
				'priority' => $tax_priority
			);

			$db->array2update('taxes', $query_data, "taxid='".$taxid."'");
		}
	} elseif ($mode == 'delete_rates' && !empty($get['2'])) {
		$taxid = $get['2'];
		if (!empty($to_delete) && is_array($to_delete)) {
			$rate_ids = $db->column("SELECT rateid FROM tax_rates WHERE rateid IN ('".implode("','", array_keys($to_delete))."') ");
			$db->query("DELETE FROM tax_rates WHERE rateid IN ('".implode("','", $rate_ids)."')");
			$db->query("DELETE FROM tax_rate_memberships WHERE rateid IN ('".implode("','", $rate_ids)."')");

			$_SESSION['alerts'][] = array(
				'type'		=> 'i',
				'content'	=> lng('Tax rate has been deleted')
			);
		}

		redirect('/admin/taxes/'.$taxid);
	} elseif ($mode == 'update_rates' && !empty($get['2'])) {
		$taxid = $get['2'];
		if (!empty($posted_data) && is_array($posted_data)) {
			foreach ($posted_data as $rateid=>$v) {
				$rate_value = $v['rate_value'];
				$rate_type = $v['rate_type'];
				if (!in_array($rate_type, array("%","$")))
					$rate_type = "%";

				$db->query("UPDATE tax_rates SET rate_value='$rate_value', rate_type='$rate_type' WHERE rateid='$rateid' ");
			}
		}

		redirect('/admin/taxes/'.$taxid.'#rates');
	} elseif ($mode == 'rate_details' && !empty($taxid)) {
		$rateid = intval($rateid);
		$zoneid = intval($zoneid);
		if (!in_array($rate_type, array("%","$")))
			$rate_type = "%";

		if (empty($membershipids) || in_array(-1, $membershipids))
			$membershipids_where = " IS NULL";
		else
			$membershipids_where = " IN ('".implode("','", $membershipids)."')";

		if ($db->field("SELECT COUNT(*) FROM tax_rates LEFT JOIN tax_rate_memberships ON tax_rates.rateid = tax_rate_memberships.rateid WHERE tax_rates.taxid = '$taxid' AND tax_rates.rateid != '$rateid' AND tax_rates.zoneid = '$zoneid' AND tax_rates.shipping = '$shipping' AND tax_rate_memberships.membershipid".$membershipids_where) == 0) {
			$query_data = array(
				'zoneid'		=> $zoneid,
				'formula'		=> $formula,
				'shipping'		=> $shipping,
				'rate_value'	=> $rate_value,
				'rate_type'		=> $rate_type
			);

			if ($rateid) {
				$db->array2update('tax_rates', $query_data, "rateid='$rateid' ");
				$db->query("DELETE FROM tax_rate_memberships WHERE rateid='$rateid'");
			} else {
				$query_data['taxid'] = $taxid;
				$rateid = $db->array2insert('tax_rates', $query_data);
			}

			if (!empty($membershipids))
				if (!in_array(-1, $membershipids))
    		        foreach ($membershipids as $v)
            		    $db->query("INSERT INTO tax_rate_memberships VALUES ('" . $rateid . "','" . $v . "')");
		} else
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Tax rate for specified zone and membership already exists')
			);
	} elseif ($mode == 'apply' && !empty($to_delete) && is_array($to_delete)) {
		$res = $db->query("SELECT productid FROM products");
		if ($res) {
			$to_delete = array_keys($to_delete);
			while ($p = $db->fetch_array($res))
				foreach ($to_delete as $k) {
					$query_data = array(
						'productid' => $p['productid'],
						'taxid' => $k
					);

					$db->array2insert('product_taxes', $query_data, true);
				}
		}
	}

	redirect('/admin/taxes/'.$taxid);
}

$template["memberships"] = $memberships = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");
if ($get['2'] == 'add' || !empty($get['2'])) {
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/taxes">'.lng('Taxes').'</a>';
	$template['location'] .= ' &gt; '.lng('Tax details');

	if ($get['2'] == 'add')
		$tax_details = $_SESSION['tax_details'];
	else
		$tax_details = $db->row("SELECT * FROM taxes WHERE taxid='".$get['2']."'");

	if (!empty($tax_details)) {
		$tax_rates = $db->all("SELECT tax_rates.*, zones.zone_name FROM tax_rates LEFT JOIN zones ON tax_rates.zoneid=zones.zoneid WHERE tax_rates.taxid='".$get['2']."' ORDER BY zones.zone_name, tax_rates.rate_value");
		if (!empty($tax_rates)) {
			foreach ($tax_rates as $k => $v) {
				$tmp = $memberships;
				$keys = $db->column("SELECT membershipid FROM tax_rate_memberships WHERE rateid='".$v['rateid']."'");
				if (!empty($tmp) && !empty($keys)) {
					$tax_rates[$k]['membershipids'] = array();
					foreach ($tmp as $m) {
						if (in_array($m['membershipid'], $keys))
							$tax_rates[$k]['membershipids'][$m['membershipid']] = $m['membership'];
					}
				}
			}
		}

		$template['tax_rates'] = $tax_rates;

		if (!empty($rateid) && !empty($tax_rates) && is_array($tax_rates)) {
			$rate_formula = '';
			foreach ($tax_rates as $k=>$v) {
				if ($v['rateid'] == $rateid) {
					$rate_details = $v;
					break;
				}
			}

			$template['rate_details'] = $rate_details;
		}

		$zones = $db->all("SELECT * FROM zones ORDER BY zone_name");
		$template['zones'] = $zones;
	}

	$template['memberships'] = $db->all("SELECT * FROM memberships ORDER BY orderby, membership");

	if (is_array($taxes_units)) {
		$_taxes = $db->all("SELECT taxid, tax_name, tax_display_name FROM taxes WHERE taxid!='".$get['2']."' ORDER BY tax_name");
		if (is_array($_taxes)) {
			foreach ($_taxes as $k=>$v) {
				$taxes_units[$v['tax_name']] = $v['tax_display_name'];
			}
		}

		$template['taxes_units'] = $taxes_units;
	}

	if (isset($tax_details))
		$template['tax_details'] = $tax_details;

	$template['page'] = get_template_contents('admin/pages/tax_edit.php');
} else {
	$template['location'] .= ' &gt; '.lng('Taxes');
	$template['taxes'] = $db->all("SELECT taxes.*, COUNT(tax_rates.taxid) as rates_count FROM taxes LEFT JOIN tax_rates ON tax_rates.taxid=taxes.taxid $provider_condition GROUP BY taxes.taxid ORDER BY priority, tax_name");
	$template['page'] = get_template_contents('admin/pages/taxes.php');
}

$template['head_title'] = lng('Taxes').' :: '.$template['head_title'];

$template['css'][] = 'admin_taxes';
$template['js'][] = 'admin_taxes';