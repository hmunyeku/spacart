<?php
function func_get_max_cartid() {
	global $cart;

	if (empty($cart['products']))
		return 0;
	else {
		$ids = array();
		foreach ($cart['products'] as $v)
			$ids[] = $v['cartid'];

		return max($ids);
	}
}

function func_calculate() {
	global $db, $cart;

	$subtotal = 0;
	foreach ($cart['products'] as $k=>$v) {
		if ($v['gift_card']) {
			$subtotal_gc += $v['amount'];
			$subtotal += $v['amount'];
			continue;
		}

		if ($v['options']) {
			$v['variantid'] = func_get_variantid($v['options'], $v['productid']);
		}

		$v = array_merge($v, func_select_product($v['productid'], $v['variantid'], $v['quantity']));

		if ($v['options']) {
			$product_options = array();
			$original_price = $v['price'];
			$original_weight = $v['weight'];
			foreach ($v['options'] as $g=>$o) {
				$group = $db->row("SELECT * FROM option_groups WHERE groupid='".addslashes($g)."'");
				if ($group['view_type'] == 't' || $group['view_type'] == 'i') {
					$product_options[$g] = $group;
					$product_options[$g]['option'] = array('name' => $o);
				} else {
					$product_options[$g] = $group;
					$o = $db->row("SELECT * FROM options WHERE optionid='".addslashes($o)."'");;
					$product_options[$g]['option'] = $o;
					if (!$group['variant']) {
						if ($o['price_modifier_type'] == '$')
							$v['price'] += $o['price_modifier'];
						else
							$v['price'] += $original_price * $o['price_modifier'] / 100;

						if ($o['weight_modifier_type'] == '$')
							$v['weight'] += $o['weight_modifier'];
						else
							$v['weight'] += $o['weight_modifier'] * $original_weight / 100;
					}
				}
			}

			$v['price'] = $v['price'];
			$v['weight'] = $v['weight'];
			$v['product_options'] = $product_options;
		}

		$cart['products'][$k] = $v;
		$subtotal += $v['price'] * $v['quantity'];
		$subtotal_taxed += $v['price'] * $v['quantity'];
	}

	if ($cart['coupon']) {
		if ($cart['coupon']['discount_type'] == 'A')
			$cart['coupon_discount']= $cart['coupon']['discount'];
		else
			$cart['coupon_discount']=  $subtotal * $cart['coupon']['discount'] / 100;
	}

	$cart['subtotal'] = $subtotal;

	if ($cart['coupon']) {
		$cart['discounted_subtotal'] = $cart['subtotal'] - $cart['coupon_discount'];
	} else
		$cart['discounted_subtotal'] = $cart['subtotal'];

	if ($cart['gift_card']) {
	} else {
		unset($cart['shipping_cost_gc']);
		unset($cart['gc_left']);
		unset($cart['gc_discount']);
		unset($cart['gc']);
	}

	$cart['subtotal_taxed'] = $subtotal_taxed;

	$cart['total'] = $cart['discounted_subtotal'];

	return func_normilze_cart($cart);
}

function func_normilze_cart($cart) {
	global $db;
	$found = array();
	$new_products = array();
	foreach ($cart['products'] as $k=>$v) {
		if ($v['gift_card']) {
			$new_products[] = $v;
			continue;
		}

		$product = $db->field("SELECT productid FROM products WHERE productid='".$v['productid']."' AND status IN (1,3)");
		if (!$product)
			continue;

		$key = $v['productid'].serialize($v['options']);
		if (in_array($key, $found))
			$new_products[$key]['quantity'] += $v['quantity'];
		else {
			$found[] = $key;
			$new_products[$key] = $v;
		}
	}

	$cart['products'] = array();
	foreach ($new_products as $v)
		$cart['products'][] = $v;

	return $cart;
}

function func_cart_calculations($userinfo) {
	global $db, $cart, $config, $_SESSION, $userinfo;

	$return = array();
	$customer_zone = 0;
	$zones = $db->column("SELECT z.zoneid FROM zones z, zone_element e WHERE z.zoneid=e.zoneid AND e.field='".$userinfo['country']."' AND e.field_type='C' GROUP BY e.zoneid");
	if ($zones) {
		foreach ($zones as $zoneid) {
			$found = false;
			$state_found = false;
			# Check for state
			$zone_states = $db->column("SELECT field FROM zone_element WHERE zoneid='$zoneid' AND field_type='S' GROUP BY field");
			if (!$zone_states || in_array($userinfo['country'].'_'.$userinfo['state'], $zone_states))
				$found = true;
			else
				$found = false;

			# Check for city
			if ($found) {
				$zone_cities = $db->column("SELECT field FROM zone_element WHERE zoneid='$zoneid' AND field_type='T' GROUP BY field");
				if (!$zone_cities)
					$found = true;
				else {
					$city_found = false;
					foreach ($zone_cities as $city) {
						if (strstr($city, '%'))
							$city = str_replace('%', '', $city);

						if (strstr($userinfo['city'], $city)) {
							$city_found = true;
							break;
						}
					}

					if ($city_found)
						$found = true;
					else
						$found = false;
				}
			}

			# Check for zipcode
			if ($found) {
				$zone_zipcodes = $db->column("SELECT field FROM zone_element WHERE zoneid='$zoneid' AND field_type='Z' GROUP BY field");
				if (!$zone_zipcodes)
					$found = true;
				else {
					$zip_found = false;
					foreach ($zone_zipcodes as $zip) {
						if (strstr($zip, '%'))
							$zip = str_replace('%', '', $zip);

						if (strstr($userinfo['zipcode'], $zip)) {
							$zip_found = true;
							break;
						}
					}

					if ($zip_found)
						$found = true;
					else
						$found = false;
				}
			}

			if ($found) {
				$customer_zone = $zoneid;
				break;
			}
		}
	}

	global $warehouse_enabled;
	if ($warehouse_enabled && $cart['products']) {
		$local_pickup = true;
		$warehouses = $db->all("SELECT * FROM warehouses WHERE enabled=1 ORDER BY pos");
		$good_warehouses = array();
		if ($warehouses) {
			foreach ($warehouses as $k=>$v) {
				$good = true;
				foreach ($cart['products'] as $k2=>$v2) {
					$variantid = $v2['variantid'] ? $v2['variantid'] :0 ;
					$in_wh_avail = $db->field("SELECT avail FROM product_inventory WHERE wid='$v[wid]' AND productid='$v2[productid]' AND variantid='".$variantid."'");
					if ($v2['avail_block'])
						$in_wh_avail -= $v2['avail_block'];

					if ($in_wh_avail < $v2['quantity']) {
						$good = false;
						break;
					}
				}

				if ($good) {
					$good_warehouses[] = $v;
				}
			}
		}

		$cart['warehouses'] = $good_warehouses;
		if ($good_warehouses) {
			$cart['local_pickup'] = 1;
		} else {
			$cart['local_pickup'] = 0;
		}
	}

	$need_shipping = 0;
	foreach ($cart['products'] as $v)
		if (!$v['gift_card'] && (!isset($v['product_type']) || $v['product_type'] != 1))
			$need_shipping = 1;

	$destination = $userinfo['country'] == $config['Company']['location_country'] ? 'N' : 'I';
	if ($need_shipping)
		$shipping_methods = $db->all("SELECT * FROM shipping WHERE active='Y' AND destination='$destination' ORDER BY orderby");

	$cart['need_shipping'] = $need_shipping;
	if ($shipping_methods) {
		$weight = 0;
		$items_count = 0;
		foreach ($cart['products'] as $v)
			$items_count += $v['quantity'];

		foreach ($cart['products'] as $v) {
			$weight += $v['weight'] * $v['quantity'];
		}

		foreach ($shipping_methods as $k=>$v) {
			$rate = $db->row("SELECT * FROM shipping_rates WHERE shippingid='$v[shippingid]' AND zoneid='$customer_zone' AND mintotal<='".$cart['subtotal']."' AND maxtotal>='".$cart['subtotal']."' AND minweight<='$weight' AND maxweight>='$weight' ORDER BY rate");
			if ($rate) {
				$rate_value = $rate['rate'];
				if ($rate['rate_p'])
					$rate_value += $cart['subtotal'] * $rate['rate_p'] / 100;

				if ($rate['weight_rate'])
					$rate_value += $weight * $rate['weight_rate'];

				if ($rate['item_rate'])
					$rate_value += $items_count * $rate['item_rate'];

				$shipping_methods[$k]['rate'] = $rate_value;
			} else
				unset($shipping_methods[$k]);
		}

		if ($shipping_methods) {
			if (!$cart['shippingid'])
				$cart['shippingid'] = $shipping_methods[0]['shippingid'];

			$return['shipping_methods'] = $shipping_methods;

			if ($cart['shippingid'] != 'L') {
				$found = false;
				foreach ($shipping_methods as $v)
					if ($v['shippingid'] == $cart['shippingid']) {
						$cart['shipping_cost'] = $v['rate'];
						$found = true;
					}

				if (!$found) {
					$cart['shippingid'] = $shipping_methods[0]['shippingid'];
					$cart['shipping_cost'] = $shipping_methods[0]['rate'];
				}
			}
		} else
			$cart['shippingid'] = $cart['shipping_cost'] = 0;

		if ($cart['shippingid'] == 'L')
			$cart['shipping_cost'] = 0;
	} elseif ($cart['shippingid'] == 'L')
		$cart['shipping_cost'] = 0;
	else
		$cart['shippingid'] = $cart['shipping_cost'] = 0;

	$cart['total'] += $cart['shipping_cost'];
	$tax = $db->row("SELECT * FROM taxes WHERE active='Y'");
	if ($tax) {
		$tax_rate = $db->row("SELECT r.* FROM tax_rates r LEFT JOIN tax_rate_memberships m ON m.rateid=r.rateid WHERE (m.membershipid IS NULL OR m.membershipid='".$userinfo['membershipid']."') AND r.zoneid='$customer_zone' AND r.taxid='$tax[taxid]' ORDER BY r.rate_value");
		if ($tax_rate) {
			$cart['tax_details'] = $tax;
			if ($tax_rate['rate_type'] == '%') {
				$tax_value = $cart['subtotal'] * $tax_rate['rate_value'] / 100;
			} else
				$tax_value = $tax_rate['rate_value'];

			if ($tax_rate['shipping'] && $tax_rate['rate_type'] == '%')
				$tax_value += $cart['shipping_cost'] * $tax_rate['rate_value'] / 100;

			$cart['tax'] = $tax_value;
		} else
			$cart['tax'] = 0;
	} else {
		$cart['tax_details'] = array();
		$cart['tax'] = 0;
	}

	$cart['total'] += $cart['tax'];
	if ($cart['gift_card']) {
		$old_total = $cart['total'];
		$cart['total'] -= $cart['gc']['amount_left'];
		if ($cart['total'] < 0)
			$cart['total'] = 0;

		$cart['gc_discount'] = $old_total - $cart['total'];
	}

	$payment_methods = $db->all("SELECT * FROM payment_methods WHERE enabled=1 ORDER BY orderby");
	$return['payment_methods'] = $payment_methods;

	$cart['paymentid'] = $payment_methods[0]['paymentid'];

	$_SESSION['cart'] = $return['cart'] = $cart;
	return $return;
}