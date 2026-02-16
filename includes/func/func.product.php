<?php
function func_select_product($productid, $variantid = false, $amount = 0) {
	global $db, $userinfo;
	global $warehouse_enabled;
	$product = $db->row("SELECT p.*, c.categoryid FROM products p, category_products c WHERE p.productid='".addslashes($productid)."' AND p.productid=c.productid AND c.main='Y' AND p.status<>2");
	if ($product['brandid']) {
		$product['brand'] = $db->row("SELECT * FROM brands WHERE brandid='".$product['brandid']."'");
		$product['brand_image'] = $db->row("SELECT * FROM brand_images WHERE brandid='".$product['brandid']."'");
	}

	if ($variantid) {
		$variant = $db->row("SELECT * FROM variants WHERE variantid='".addslashes($variantid)."'");
		$product = array_merge($product, $variant);
		$product['name'] = $product['title'];
		# Check for wholesale pricing
		$tmp = $db->field("SELECT price FROM wholesale_prices WHERE membershipid IN (0, '".$userinfo['membershipid']."') AND variantid='".$variantid."' AND quantity<='".$amount."' ORDER BY quantity DESC");
		if ($tmp)
			$product['price'] = $tmp;

		if ($warehouse_enabled) {
			$product['avail'] = $db->field("SELECT SUM(avail) FROM product_inventory WHERE productid='$productid' AND variantid='".addslashes($variantid)."'");
			if (!$product['avail'])
				$product['avail'] = 0;

			if ($variant['avail_block']) {
				$product['avail'] -= $variant['avail_block'];
				$product['avail_block'] = $variant['avail_block'];
			}
		}

		if ($amount > $product['avail'])
			$product['quantity'] = $product['avail'];

		$product['variant_photo'] = $db->row("SELECT * FROM variant_images WHERE variantid='".addslashes($variantid)."' ORDER BY imageid");
	} else {
		if ($warehouse_enabled) {
			$has_variants = $db->field("SELECT COUNT(variantid) FROM variants WHERE productid='$productid'");
			if ($has_variants)
				$product['avail'] = $db->field("SELECT SUM(avail) FROM product_inventory WHERE productid='$productid' AND variantid<>0");
			else
				$product['avail'] = $db->field("SELECT SUM(avail) FROM product_inventory WHERE productid='$productid' AND variantid=0");

			if (!$product['avail'])
				$product['avail'] = 0;

			if ($product['avail_block'])
				$product['avail'] -= $product['avail_block'];
		}

		# Check for wholesale pricing
		$tmp = $db->field("SELECT price FROM wholesale_prices WHERE membershipid IN (0, '".$userinfo['membershipid']."') AND productid='".addslashes($productid)."' AND variantid=0 AND quantity<='".$amount."' ORDER BY quantity DESC");
		if ($tmp)
			$product['price'] = $tmp;

		if ($amount > $product['avail'])
			$product['quantity'] = $product['avail'];
	}

	if (empty($product['variant_photo']) && $product['photoid'])
		$product['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$product['photoid']."'");

	return $product;
}

function func_all_variants($ars) {
	$result = array();
	$ars = array_values($ars);
	$sizeIn = sizeof($ars);
	$size = $sizeIn > 0 ? 1 : 0;
	foreach ($ars as $ar)
		$size = $size * sizeof($ar);

	for ($i = 0; $i < $size; $i++) {
		$result[$i] = array();
		for ($j = 0; $j < $sizeIn; $j++)
			array_push($result[$i], current($ars[$j]));

		for ($j = ($sizeIn -1); $j >= 0; $j--) {
			if (next($ars[$j]))
				break;
			elseif (isset ($ars[$j]))
				reset($ars[$j]);
		}
	}

	return $result;
}


function func_sku_exist($sku) {
	global $db;

	if ($db->field("SELECT COUNT(*) FROM products WHERE sku='".$sku."'"))
		return true;

	if ($db->field("SELECT COUNT(*) FROM variants WHERE sku='".$sku."'"))
		return true;
}

function func_delete_product($productid) {
	global $db;

	$product = $db->row("SELECT * FROM products WHERE productid='".$productid."'");
	if (!$product)
		return;

	$db->query("UPDATE llx_product SET tosell=0, tobuy=0 WHERE rowid='".$productid."'");
	$db->query("DELETE FROM wishlist WHERE productid='".$productid."'");
	$db->query("DELETE FROM wholesale_prices WHERE productid='".$productid."'");
	$variants = $db->all("SELECT * FROM variants WHERE productid='".$productid."'");
	if ($variants) {
		foreach ($variants as $v)
			func_delete_variant($v['variantid']);
	}

	$images = $db->all("SELECT * FROM products_photos WHERE productid='".$productid."'");
	if ($images) {
		$dir = SITE_ROOT . '/photos/product/'.$productid;
		foreach ($images as $v) {
			$dir2 = $dir . '/' . $v['photoid'];
			unlink($dir2 . '/' . $v['file']);
			rmdir($dir2);
		}

		rmdir($dir);
		$db->query("DELETE FROM products_photos WHERE productid='".$productid."'");
	}

	$options = $db->all("SELECT * FROM option_groups WHERE productid='".$productid."'");
	if ($options) {
		foreach ($options as $v) {
			$db->query("DELETE FROM options WHERE groupid='".$v['groupid']."'");
		}

		$db->query("DELETE FROM option_groups WHERE productid='".$productid."'");
	}

	$db->query("DELETE FROM featured_products WHERE productid='".$productid."'");
	$db->query("DELETE FROM category_products WHERE productid='".$productid."'");
}

function func_delete_variant($variantid) {
	global $db;

	$vid = addslashes($variantid);
	$db->query("DELETE FROM variants WHERE variantid='".$vid."'");
	$db->query("DELETE FROM variant_items WHERE variantid='".$vid."'");
	$db->query("DELETE FROM wholesale_prices WHERE variantid='".$vid."'");
	$images = $db->all("SELECT * FROM variant_images WHERE variantid='".$vid."'");
	if ($images) {
		$dir = SITE_ROOT . '/photos/variant/'.$variantid;
		foreach ($images as $k=>$v) {
			$dir2 = $dir . '/' . $v['imageid'];
			unlink($dir2 . '/' . $v['file']);
			rmdir($dir2);
		}

		rmdir($dir);
		$db->query("DELETE FROM variant_images WHERE variantid='".$vid."'");
	}
}

function func_delete_variant_image($imageid) {
	global $db;

	$imgid = addslashes($imageid);
	$image = $db->row("SELECT * FROM variant_images WHERE imageid='".$imgid."'");
	if ($image) {
		$dir = SITE_ROOT . '/photos/variant/'.$image['variantid'] . '/' . $image['imageid'];
		unlink($dir . '/' . $image['file']);
		rmdir($dir);
		$db->query("DELETE FROM variant_images WHERE imageid='".$imgid."'");
	}
}

function func_get_variantid($options, $productid = false) {
    global $db;
    if (empty($options) || !is_array($options))
        return false;

    $ids = array_map('intval', array_keys($options));
    $ids = array_map('addslashes', $ids);
    $vids = $db->all("SELECT groupid FROM option_groups WHERE view_type NOT IN ('p', 's', 'r') AND groupid IN ('" . implode("','", $ids) . "')");
    if (!empty($vids))
        foreach ($vids as $v)
            unset($options[$v['groupid']]);

    if (empty($options))
        return false;

    if ($productid === false) {
        $ids = array_map('intval', array_keys($options));
	    $ids = array_map('addslashes', $ids);
        $productid = $db->field("SELECT productid FROM option_groups WHERE groupid IN ('" . implode("','", $ids) . "')");
    }

    $cnt = $db->field("SELECT COUNT(DISTINCT g.groupid) FROM option_groups g, options o WHERE g.variant=1 AND g.enabled=1 AND g.productid='".$productid."' AND g.groupid = o.groupid AND o.enabled=1");
    if ($cnt != count($options))
        return false;

    $options = array_map('intval', $options);
    $options = array_map('addslashes', $options);

    return $db->field("SELECT variantid, COUNT(variantid) as cnt FROM variant_items WHERE variant_items.optionid IN ('".implode("','", $options)."') GROUP BY variantid HAVING cnt=".$cnt."");
}

function func_get_default_options($productid, $amount, $membershipid = 0) {
	global $db, $_orderby, $config;

	# Get product options
	$groups = $db->all("SELECT g.groupid, g.view_type FROM option_groups g LEFT JOIN options o ON g.groupid=o.groupid AND o.enabled=1 WHERE o.enabled=1 AND g.productid='".$productid."' AND (o.groupid IS NOT NULL OR g.view_type IN ('t', 'i')) GROUP BY g.groupid ORDER BY g.orderby");
	if (empty($groups))
		return true;

	$tmp = array();
	foreach ($groups as $k=>$v)
		$tmp[$v['groupid']][] = $v;

	$groups = $tmp;
	$_product_options = array();
	$_orderby = array_keys($groups);
	$_orderby = array_flip($_orderby);
	# Get default variant
	$variant_counter = $db->field("SELECT COUNT(DISTINCT g.groupid) FROM option_groups g, options o, variant_items v WHERE g.groupid=o.groupid AND o.enabled=1 AND g.enabled=1 AND g.productid='".$productid."' AND g.variant=1 AND v.optionid=o.optionid");
	if ($variant_counter > 0) {

		$avail_where = "";
		if ($config["General"]["unlimited_products"] == "N")
			$avail_where = "AND avail >= ".$amount;

		# Detect default variant
		$def_variantid = $db->field("SELECT variantid FROM variants WHERE productid='".$productid."' AND def=1 ".$avail_where);
		if (!empty($def_variantid)) {
			$tmp = $db->all("SELECT o.groupid, o.optionid FROM options o, variant_items v WHERE v.variantid='".$def_variantid."' AND v.optionid=o.optionid");
			if (count($tmp) != $variant_counter)
				return false;

			$_product_options = array();
			foreach ($tmp as $v)
				$_product_options[$v['groupid']] = $v['optionid'];

			# Check exceptions
			$tmp = $db->all("SELECT exceptionid, COUNT(optionid) as cnt FROM options_ex WHERE optionid IN ('".implode("','", $_product_options)."') GROUP BY exceptionid");
			if (!empty($tmp)) {
				$exceptions = array();
				foreach ($tmp as $v)
					$exceptions[$v['exceptionid']] = $v['cnt'];

				# Get exceptions counters
				$tmp = $db->all("SELECT exceptionid, COUNT(optionid) as cnt FROM options_ex WHERE exceptionid IN ('".implode("','", array_keys($exceptions))."') GROUP BY exceptionid");
				$exception_counters = array();
				foreach ($tmp as $v)
					$exception_counters[$v['exceptionid']] = $v['cnt'];

				foreach ($exceptions as $eid => $cnt) {
					if ($exception_counters[$eid] == $cnt) {
						$_product_options = array();
						break;

					}
				}

				if (!empty($_product_options)) {
					$exceptions = $db->hash("SELECT o.groupid, COUNT(e.exceptionid) as cnt FROM options_ex e, options o WHERE e.optionid=o.optionid AND e.exceptionid IN ('".implode("','", array_keys($exceptions))."') AND e.optionid NOT IN ('".implode("','", $_product_options)."') GROUP BY o.groupid", "groupid", false, true);
					if (!empty($exceptions)) {
						$class_counters = $db->hash("SELECT groupid, COUNT(*) FROM options WHERE groupid IN ('".implode("','", array_keys($exceptions))."') AND enabled=1 GROUP BY groupid", "groupid", false, true);
						foreach ($exceptions as $cid => $cnt) {
							if (isset($classes[$cid]) && isset($class_counters[$cid]) && $class_counters[$cid] == $cnt) {
								$_product_options = array();
								break;
							}
						}
					}
				}

				unset($exceptions, $exception_counters);
			}

			# Unset variant-type classes
			if (!empty($_product_options)) {
				foreach ($_product_options as $cid => $oid)
					if (isset($classes[$cid]))
						unset($classes[$cid]);

				if (func_check_product_options($productid, $_product_options))
					return $_product_options;
			}
		}
	}

	# Get class options
	$options = $db->all("SELECT groupid, optionid FROM options WHERE groupid IN ('".implode("','", array_keys($groups))."') AND enabled=1 ORDER BY orderby");
	if (empty($options))
		return false;

	$tmp = array();
	foreach ($options as $k=>$v) {
		$tmp[$v['groupid']][] = $v['optionid'];
	}

	$options = $tmp;
	$_flag = false;
	foreach ($groups as $k => $class) {
		if ($class['view_type'] == 't' || $class['view_type'] == 'i') {
			$_product_options[$k] = '';
			unset($groups[$k]);
			continue;
		}

		$groups[$k]['cnt'] = $_flag ? 0 : -1;
		$_flag = true;
		if (isset($options[$k]))
			$groups[$k]['options'] = array_values($options[$k]);
		else
			unset($groups[$k]);
	}

	if (empty($groups)) {
		if (empty($_product_options))
			return false;

		uksort($_product_options, "func_get_default_options_callback");
		return $_product_options;
	}

	while(!$is_add) {
		$product_options = $_product_options;
		$is_add = true;
		foreach ($groups as $k=>$g) {
			if ($is_add) {
				if (count($g['options'])-1 <= $g['cnt'])
					$g['cnt'] = 0;
				else {
					$is_add = false;
					$g['cnt']++;
				}
			}

			$product_options[$k] = $g['options'][$g['cnt']];
			$groups[$k]['cnt'] = $g['cnt'];
		}

		if (func_check_product_options($productid, $product_options)) {
			$variantid = func_get_variantid($product_options, $productid);

            # Check variant quantity in stock
            if (
				empty($variantid) ||
                ($config["General"]["unlimited_products"] == "Y") ||
                $db->field("SELECT avail FROM variants WHERE variantid='".$variantid."'") >= $amount
            )
                break;
		}
	}

	if (empty($product_options))
		return false;

	uksort($product_options, "func_get_default_options_callback");
	return $product_options;
}

function func_get_default_options_callback($a, $b) {
	global $_orderby;

	$a = $_orderby[$a];
	$b = $_orderby[$b];
	if ($a == $b)
		return 0;

	return $a > $b ? 1 : -1;
}

function func_check_product_options($productid, $options) {
	global $db;

	if (empty($options) || !is_array($options))
		return false;

	$ids = array_map("intval", array_keys($options));
	$ids = array_map("addslashes", $ids);
	$textids = $db->column("SELECT groupid FROM option_groups WHERE groupid IN ('".implode("','", $ids)."') AND view_type IN ('t', 'i')", "groupid");
	$where = array();
	$oids = array();
	foreach ($options as $gid=>$oid) {
		$gid = intval($gid);
		if (empty($gid))
			return false;

		$oid = addslashes($oid);
		if (!is_numeric($oid) || empty($oid))
			$where[] = "g.groupid='".$gid."' AND o.optionid IS NULL AND g.view_type IN ('t', 'i')";
		else {
			$where[] = "g.groupid='".$gid."' AND (o.optionid='".$oid."' OR (o.optionid IS NULL AND g.view_type IN ('t', 'i')))";
			if (empty($textids) || !in_array($gid, $textids))
				$oids[] = $oid;
		}
	}

	$groups = $db->all("SELECT g.groupid, g.view_type FROM option_groups g LEFT JOIN options o ON g.groupid=o.groupid AND o.enabled=1 WHERE g.enabled=1 AND g.productid='".$productid."' AND ((".implode(") OR (", $where).")) GROUP BY g.groupid");
	if (count($groups) != count($options))
		return false;

	$counter = $db->field("SELECT COUNT(DISTINCT g.groupid) FROM option_groups g, options o WHERE g.productid='".$productid."' AND g.enabled=1 AND g.groupid=o.groupid AND o.enabled=1");
	$oids_counter = count($oids);
	$oids = implode("','", $oids);
	if ($counter == $oids_counter)
		return !$db->field("SELECT COUNT(*) as cnt_orig, SUM(IF(e2.optionid IS NULL, 0, 1)) as cnt_ex FROM options_ex e1 LEFT JOIN options_ex e2 ON e1.optionid = e2.optionid AND e2.optionid IN ('".$oids."') GROUP BY e1.exceptionid HAVING cnt_orig = cnt_ex");
	else {
		$exceptions = $db->all("SELECT exceptionid, COUNT(optionid) FROM options_ex WHERE optionid IN ('".$oids."') GROUP BY exceptionid");
		if (empty($exceptions))
			return true;

		$ids = array();
		foreach ($exceptions as $v)
			$ids[] = $v['exceptionid'];

		$tmp = $db->all("SELECT exceptionid, COUNT(optionid) as cnt FROM options_ex WHERE exceptionid IN ('".implode("','", array_keys($exceptions))."') GROUP BY exceptionid");
		$exception_counters = array();
		foreach ($tmp as $v)
			$exception_counters[$v['exceptionid']] = $v['cnt'];

		foreach ($exceptions as $eid=>$cnt)
			if ($exception_counters[$eid] == $cnt)
				return false;

		# Check partly options data
		$exceptions = $db->hash("SELECT o.groupid, COUNT(e.exceptionid) FROM options_ex e, options o, option_groups g WHERE e.optionid=o.optionid AND e.exceptionid IN ('".implode("','", array_keys($exceptions))."') AND e.optionid NOT IN ('".$oids."') AND o.enabled=1 AND g.enabled=1 AND o.groupid=g.groupid GROUP BY o.groupid", "groupid", false, true);
		if (empty($exceptions))
			return true;

		$class_counters = $db->hash("SELECT groupid, COUNT(*) FROM options WHERE groupid IN ('".implode("','", array_keys($exceptions))."') AND avail = 'Y' GROUP BY groupid", "groupid", false, true);
		foreach ($exceptions as $gid => $cnt)
			if (isset($class_counters[$gid]) && $class_counters[$gid] == $cnt)
				return false;

		return true;
	}
}

function func_process_sku($sku, $productid = 0, $variantid = 0) {
	global $db, $_SESSION;

	if (is_numeric($sku)) {
		$old_sku = $sku;
		$was_numeric = '1';
		if ($productid)
			$sku = 'SKU'.$productid;
		else
			$sku = 'SKU'.$variantid.'v';
	}

	$sku_exists = $db->field("SELECT productid FROM products WHERE sku='".addslashes($sku)."' AND productid<>'$productid'");
	if (!$sku_exists) {
		$sku_exists = $db->field("SELECT variantid FROM variants WHERE sku='".addslashes($sku)."' AND variantid<>'$variantid'");
		$variantid = $variantid;
	}

	if ($sku_exists) {
		if (!$was_numeric) {
			$old_sku = $sku;
			if ($variantid)
				$sku = 'SKU'.$variantid.'v';
			else
				$sku = 'SKU'.$productid;
		}

		$sku_exists = $db->field("SELECT productid FROM products WHERE sku='".addslashes($sku)."' AND productid<>'$productid'");
		if (!$sku_exists)
			$sku_exists = $db->field("SELECT variantid FROM variants WHERE sku='".addslashes($sku)."' AND variantid<>'$variantid'");

		if ($sku_exists) {
			$sku .= time();
		}

		if ($was_numeric)
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> 'SKU <b>'.$old_sku.'</b> cannot be numeric and changed to <b>'.$sku.'</b>'
			);
		else
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> 'SKU <b>'.$old_sku.'</b> exists and changed to <b>'.$sku.'</b>'
			);
	}

	return $sku;
}