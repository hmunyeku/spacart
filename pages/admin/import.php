<?php
$dir = SITE_ROOT . '/var/import_export';
if (!is_dir($dir))
	mkdir($dir);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if (empty($_FILES['file']['tmp_name'])) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('Please, select file to import')
		);

		redirect('/admin/import');
	}

	q_load('product', 'category', 'brands');
	doImport($_FILES['file']['tmp_name'], $delimiter);
	exit;
}

$regex = <<<'END'
/
  (
    (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                      # ...one or more times
  )
| ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
| ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
/x
END;
function utf8replacer($captures) {
  if ($captures[1] != "") {
    // Valid byte sequence. Return unmodified.
    return $captures[1];
  }
  elseif ($captures[2] != "") {
    // Invalid byte of the form 10xxxxxx.
    // Encode as 11000010 10xxxxxx.
    return "\xC2".$captures[2];
  }
  else {
    // Invalid byte of the form 11xxxxxx.
    // Encode as 11000011 10xxxxxx.
    return "\xC3".chr(ord($captures[3])-64);
  }
}

function doImport($file, $delimiter) {
	global $db, $config, $_SESSION, $export_dir, $http_location;
	set_time_limit(86400);
	ini_set('memory_limit', '1024M');

	echo '<pre>Import is in progress. Please, wait...<br />';
	ob_flush();
	flush();
	$array = csv_file_to_array($file, true, 9999999, $delimiter);

	foreach ($array as $k=>$v) {
		$array[$k] = str_replace('<line-break>', "\r\n", $v);
	}

	$import = array();
	$current = '';
	$categories = array();
	$brands = array();
	$warehouses = array();
	$products = array();
	$products_options_groups = array();
	$products_options = array();
	$variants = array();
	$variant_items = array();
	$wholesale = array();
	$related = array();
	$reviews = array();
	foreach ($array as $k=>$v) {
		foreach ($v as $k2=>$v2) {
			$v[$k2] = addslashes($v2);
		}

		if ($v['0'] == '[CATEGORIES]') {
			echo '<br />Importing categories...';
			$current = 'categories';
		} elseif ($v['0'] == '[CATEGORIES BANNERS]') {
			echo '<br />Importing categories banners...';
			$current = 'cat_banners';
		} elseif ($v['0'] == '[BRANDS]') {
			echo '<br />Importing brands...';
			$current = 'brands';
		} elseif ($v['0'] == '[BRANDS BANNERS]') {
			echo '<br />Importing brands banners...';
			$current = 'brands_banners';
		} elseif ($v['0'] == '[WAREHOUSES]') {
			echo '<br />Importing warehouses...';
			$current = 'warehouses';
		} elseif ($v['0'] == '[PRODUCTS]') {
			echo '<br />Importing products...';
			$current = 'products';
		} elseif ($v['0'] == '[PRODUCTS INVENTORY]') {
			echo '<br />Importing products inventory...';
			$current = 'inventory';
		} elseif ($v['0'] == '[VARIANTS INVENTORY]') {
			echo '<br />Importing variants inventory...';
			$current = 'variant_inventory';
		} elseif ($v['0'] == '[PRODUCTS IMAGES]') {
			echo '<br />Importing products images...';
			$current = 'images';
		} elseif ($v['0'] == '[PRODUCTS OPTIONS]') {
			echo '<br />Importing products options...';
			$current = 'products_options';
		} elseif ($v['0'] == '[OPTIONS]') {
			$current = 'options';
		} elseif ($v['0'] == '[VARIANTS]') {
			echo '<br />Importing variants...';
			$current = 'variants';
		} elseif ($v['0'] == '[VARIANTS IMAGES]') {
			echo '<br />Importing variants images...';
			$current = 'variants_images';
		} elseif ($v['0'] == '[WHOLESALE PRICES]') {
			echo '<br />Importing wholesale prices...';
			$current = 'wholesale';
		} elseif ($v['0'] == '[PRODUCT ATTACHMENTS]') {
			echo '<br />Importing attachments...';
			$current = 'attachments';
		} elseif ($v['0'] == '[PRODUCTS FAQ]') {
			echo '<br />Importing faq...';
			$current = 'faq';
		} elseif ($v['0'] == '[RELATED PRODUCTS]') {
			echo '<br />Importing related products...';
			$current = 'related';
		} elseif ($v['0'] == '[PRODUCTS REVIEWS]') {
			echo '<br />Importing products reviews...';
			$current = 'reviews';
		} elseif (empty($v) || count($v) == 1) {
			continue;
		}

		global $regex;
		if ($current == 'categories') {
			if (is_numeric($v['0'])) {
				if (empty($categories)) {
					$tmp = $db->all("SELECT * FROM categories ORDER BY categoryid");
					foreach ($tmp as $c) {
						$categories[$c['categoryid']] = $c;
					}
				}

				$tmp = explode(" / ", $v['6']);
				$path = array();
				$parentid = 0;
				foreach ($tmp as $c) {
					if ($c)
						foreach ($categories as $id=>$cat)
							if ($cat['title'] == $c && $c != $v['1']) {
								$path[] = $cat;
								$parentid = $id;
							}
				}

				if (!empty($categories[$v['0']])) {
					$parent_query = "";
					if ($parentid)
						$parent_query = ", parentid='$parentid'";

					$db->query("UPDATE llx_categorie SET visible='".$v['7']."'".$parent_query.", label='".$v['1']."', description='".$v['2']."', meta_keywords='".$v['3']."', meta_description='".$v['4']."', meta_title='".$v['5']."', cleanurl='".$v['8']."', orderby='".$v['9']."' WHERE categoryid='".$v['0']."'");
					$categoryid = $v['0'];
				} else {
					$categoryid = 0;
				}

				if ($categoryid && $v['10']) {
					$icon = file_get_contents(stripslashes($v['10']));
					if ($icon) {
						$image = $db->row("SELECT * FROM category_icons WHERE categoryid='".$categoryid."'");
						if ($image) {
							$dir = SITE_ROOT . '/photos/category/'.$categoryid.'/'.$image['iconid'];
							unlink($dir.'/'.$image['file']);
							rmdir($dir);
							$db->query("DELETE FROM category_icons WHERE categoryid='".$categoryid."'");
						}

						$dir = SITE_ROOT . '/photos/category/'.$categoryid;
						if (!is_dir($dir))
							mkdir($dir);

						$name = basename($v['10']);
						$new_image = array(
							'categoryid'=> $categoryid,
							'file'		=> $name,
							'size'		=> $file['size']
						);

						$imageid = $db->array2insert("category_icons", $new_image);
						$dir .= '/'.$imageid;
						if (!is_dir($dir))
							mkdir($dir);

						$fp = fopen( $dir.'/'.$name, 'w');
						fputs($fp, $icon);
						fclose($fp);

						list($width, $height) = getimagesize($dir.'/'.$name);
						$size = filesize($dir.'/'.$name);
						$update = array(
							'x'		=> $width,
							'y'		=> $height,
							'size'	=> $size
						);

						$db->array2update("category_icons", $update, "iconid='".$imageid."'");
					}
				}
			}
		}

		if ($current == 'cat_banners' && $v['5']) {
				if (is_url($v['5'])) {
					$image = file_get_contents(stripslashes($v['5']));
					$name = $v['5'];
					$v['5'] = SITE_ROOT.'/var/tmp/'.md5(time());
					$fp = fopen($v['5'], 'w');
					fputs($fp, $image);
					fclose($fp);
				} else {
					$name = $v['5'];
					$v['5'] = SITE_ROOT.'/var/import_export/categories_banners/'.$v['5'];
				}

				if (!file_exists($v['5']) && !is_dir($v['5']))
					continue;

				if (!$banners_removed[$v['1']]) {
					$banners = $db->row("SELECT * FROM category_banners WHERE categoryid='".$v['1']."'");
					if ($banners) {
						foreach ($banners as $b) {
							$dir = SITE_ROOT . '/photos/banner/'.$b['categoryid'].'/'.$b['bannerid'];
							unlink($dir.'/'.$b['file']);
							rmdir($dir);
						}

						$db->query("DELETE FROM category_banners WHERE categoryid='$v[1]'");
					}

					$banners_removed[$v['1']] = 1;
				}

				$dir = SITE_ROOT . '/photos/banners/'.$v[1];
				if (!is_dir($dir))
					mkdir($dir);

				$name = basename($v['5']);
				$new_image = array(
					'categoryid'=> $v[1],
					'file'		=> $name,
					'size'		=> $file['size'],
					'pos'		=> $v[4]
				);

				if ($v['0']) {
					$imageid = $new_image['bannerid'] = $v['0'];
					$db->array2insert("category_banners", $new_image);
				} else
					$imageid = $db->array2insert("category_banners", $new_image);

				$dir .= '/'.$imageid;
				if (!is_dir($dir))
					mkdir($dir);

				copy($v['5'], $dir.'/'.$name);
				list($width, $height) = getimagesize($dir.'/'.$name);
				$size = filesize($dir.'/'.$name);
				$update = array(
					'x'		=> $width,
					'y'		=> $height,
					'size'	=> $size
				);

				$db->array2update("category_banners", $update, "bannerid='".$imageid."'");
		}

		if ($current == 'brands') {
#			$array[] = array('!BRAND_ID', '!NAME', '!DESCRIPTION', '!META_KEYWORDS', '!META_DESCRIPTION', '!META_TITLE', '!ACTIVE', '!CLEAN_URL', '!POSITION', '!ICON_URL');
			if ((is_numeric($v['0']) || empty($v['0'])) && !empty($v['1'])) {
				if (empty($brands)) {
					$tmp = $db->all("SELECT * FROM brands ORDER BY brandid");
					foreach ($tmp as $b) {
						$brands[$b['brandid']] = $b;
					}
				}

				if (!empty($brands[$v['0']])) {
					$db->query("UPDATE brands SET name='".$v['1']."', descr='".$v['2']."', meta_title='".$v['3']."', meta_keywords='".$v['4']."', meta_descr='".$v['5']."', active='".$v['6']."', cleanurl='".$v['7']."', orderby='".$v['8']."' WHERE brandid='".$v['0']."'");
				} else {
					$db->query("INSERT INTO brands SET brandid='".$v['0']."', name='".$v['1']."', descr='".$v['2']."', meta_title='".$v['3']."', meta_keywords='".$v['4']."', meta_descr='".$v['5']."', active='".$v['6']."', cleanurl='".$v['7']."', orderby='".$v['8']."'");
				}

				if ($v['9']) {
					$icon = file_get_contents(stripslashes($v['9']));
					if ($icon) {
						$image = $db->row("SELECT * FROM brand_images WHERE brandid='".$v['0']."'");
						if ($image) {
							$dir = SITE_ROOT . '/photos/brand/'.$v['0'].'/'.$image['imageid'];
							unlink($dir.'/'.$image['file']);
							rmdir($dir);
							$db->query("DELETE FROM brand_images WHERE brandid='".$v['0']."'");
						}

						$dir = SITE_ROOT . '/photos/brand/'.$v['0'];
						if (!is_dir($dir))
							mkdir($dir);

						$name = basename($v['9']);
						$new_image = array(
							'brandid'	=> $v['0'],
							'file'		=> $name,
							'size'		=> $file['size']
						);

						$imageid = $db->array2insert("brand_images", $new_image);
						$dir .= '/'.$imageid;
						if (!is_dir($dir))
							mkdir($dir);

						$fp = fopen( $dir.'/'.$name, 'w');
						fputs($fp, $icon);
						fclose($fp);

						list($width, $height) = getimagesize($dir.'/'.$name);
						$size = filesize($dir.'/'.$name);
						$update = array(
							'x'		=> $width,
							'y'		=> $height,
							'size'	=> $size
						);

						$db->array2update("brand_images", $update, "imageid='".$imageid."'");
					}
				}
			}
		}

		if ($current == 'warehouses') {
#			$array[] = array('!CODE', '!TITLE', '!ADDRESS', '!DESCRIPTION', '!ENABLED', '!POS');
			if ($v['0'] == '!CODE' || $v['0'] == '[WAREHOUSES]')
				continue;

			if (!empty($v['0'])) {
				if (empty($warehouses)) {
					$tmp = $db->all("SELECT * FROM warehouses ORDER BY wid");
					foreach ($tmp as $w) {
						$warehouses[$w['wcode']] = $w;
					}
				}
				$warehouse_array = array(
					'wcode'		=> $v['0'],
					'title'		=> $v['1'],
					'address'	=> $v['2'],
					'descr'		=> $v['3'],
					'enabled'	=> $v['4'],
					'pos'		=> $v['5']
				);

				if (!empty($warehouses[$v['0']])) {
					$db->array2update("warehouses", $warehouse_array, "wid='".$warehouses[$v['0']]['wid']."'");
				} else {
					$db->array2insert("warehouses", $warehouse_array);
				}
			}
		}

		if ($current == 'products') {
/*
            [0] => !PRODUCT_ID
            [1] => !SKU
            [2] => !MAIN_CATEGORY
            [3] => !ADDITIONAL_CATEGORIES
            [4] => !BRAND_ID
            [5] => !NAME
            [6] => !DESCRIPTION
            [7] => !META_KEYWORDS
            [8] => !META_DESCRIPTION
            [9] => !META_TITLE
            [10] => !PRICE
            [11] => !LIST_PRICE
            [12] => !WEIGHT
            [13] => !AMOUNT
            [14] => !STATUS
            [15] => !CLEAN_URL
            [16] => !KEYWORDS
*/

					if ((is_numeric($v['0']) || empty($v['0'])) && !empty($v['1'])) {
						if (empty($products)) {
							$tmp = $db->all("SELECT * FROM products ORDER BY productid");
							foreach ($tmp as $p) {
								$products[$p['productid']] = $p;
							}
						}

						$sku = func_process_sku($v['1'], $v['0']);
						$v[5] = htmlspecialchars($v[5]);
						if ($products[$v['0']]) {
							$db->query("UPDATE llx_product SET label='$v[4]', name='$v[5]', descr='".$v['6']."', avail='".$v['13']."', title_tag='".$v['9']."', meta_keywords='".$v['7']."' , meta_description='".$v['8']."', keywords='".$v['16']."', price='".$v['10']."', sku='".$sku."', status='".$v['14']."', weight='".$v['12']."', list_price='".$v['11']."', cleanurl='".$v['15']."' WHERE productid='".$v['0']."'");
							$db->query("DELETE FROM category_products WHERE productid='$v[0]' AND main='Y'");
							$db->query("REPLACE INTO category_products SET productid='$v[0]', categoryid='$v[2]', main='Y'");

							$tmp = explode(',', $v['3']);
							if ($tmp['0']) {
								$db->query("DELETE FROM category_products WHERE productid='$v[0]' AND main=''");
								foreach ($tmp as $c)
									$db->query("REPLACE INTO category_products SET productid='$v[0]', categoryid='$c', main=''");
							}
						} else {
							if ($db->field("SELECT productid FROM products WHERE productid='".$v[0]."'")) {
								$pid = $db->field("SELECT MAX(productid) as pid FROM products") + 1;
								$_SESSION['alerts'][] = array(
									'type'		=> 'e',
									'content'	=> 'Product #ID <b>'.$v['0'].'</b> exists and changed to <b>'.$pid.'</b>'
								);
							} else
								$pid = $v['0'];

							$db->query("INSERT INTO llx_product SET rowid='".$pid."', add_date='".time()."', brandid='$v[4]', name='$v[5]', descr='".$v['6']."', avail='".$v['13']."', title_tag='".$v['9']."', meta_keywords='".$v['7']."' , meta_description='".$v['8']."', keywords='".$v['16']."', price='".$v['10']."', sku='".$sku."', status='".$v['14']."', weight='".$v['12']."', list_price='".$v['11']."', cleanurl='".$v['15']."'");
							$db->query("REPLACE INTO category_products SET productid='$pid', categoryid='$v[2]', main='Y'");

							$tmp = explode(',', $v['3']);
							if ($tmp['0']) {
								foreach ($tmp as $c)
									$db->query("REPLACE INTO category_products SET productid='$pid', categoryid='$c', main=''");
							}
						}
					}
				}

		if ($current == 'inventory') {
#			$array[] = array('[PRODUCTS INVENTORY]');
#			$array[] = array('!WAREHOUSE_CODE', '!PRODUCT_ID', '!PRODUCT_SKU', '!AVAIL');
			if ($v['0'] == '!WAREHOUSE_CODE' || $v['0'] == '[PRODUCTS INVENTORY]')
				continue;

			if (!empty($v['0'])) {
				if (!$v['1'])
					$v['1'] = $db->field("SELECT productid FROM products WHERE sku='".addslashes($v['2'])."'");

				if (!$inventory_removed[$v['1']]) {
					$db->query("DELETE FROM product_inventory WHERE productid='".$v['1']."' AND variantid=0");
					$inventory_removed[$v['1']] = 1;
				}

				$wid = $db->field("SELECT wid FROM warehouses WHERE wcode='".addslashes($v['0'])."'");
				$to_insert = array(
					'wid'		=> $wid,
					'productid'	=> $v['1'],
					'avail'		=> $v['3']
				);

				$db->array2insert("product_inventory", $to_insert);
			}
		}

		if ($current == 'variant_inventory') {
#			$array[] = array('[VARIANTS INVENTORY]');
#			$array[] = array('!WAREHOUSE_CODE', '!VARIANT_ID', '!VARIANT_SKU', '!AVAIL');
			if ($v['0'] == '!WAREHOUSE_CODE' || $v['0'] == '[VARIANTS INVENTORY]')
				continue;

			if (!empty($v['0'])) {
				if (!$v['1'])
					$v['1'] = $db->field("SELECT variantid FROM variants WHERE sku='".addslashes($v['2'])."'");

				$productid = $db->field("SELECT productid FROM variants WHERE variantid='".addslashes($v['1'])."'");
				if (!$variant_inventory_removed[$v['1']]) {
					$db->query("DELETE FROM product_inventory WHERE productid='".$productid."' AND variantid='".$v['1']."'");
					$variant_inventory_removed[$v['1']] = 1;
				}

				$wid = $db->field("SELECT wid FROM warehouses WHERE wcode='".addslashes($v['0'])."'");
				$to_insert = array(
					'wid'		=> $wid,
					'productid'	=> $productid,
					'variantid'	=> $v['1'],
					'avail'		=> $v['3']
				);

				$db->array2insert("product_inventory", $to_insert);
			}
		}

		if ($current == 'images' && $v['4']) {
/*
            [0] => !ID
            [1] => !PRODUCT_ID
            [2] => !ALT
            [3] => !POSITION
            [4] => !FILE
*/
				if (is_url($v['4'])) {
					$image = file_get_contents(stripslashes($v['4']));
					$name = $v['4'];
					$v['4'] = SITE_ROOT.'/var/tmp/'.md5(time());
					$fp = fopen($v['4'], 'w');
					fputs($fp, $image);
					fclose($fp);
				} else {
					$name = $v['4'];
					$v['4'] = SITE_ROOT.'/var/import_export/products_images/'.$v['4'];
				}

				if (!file_exists($v['4']) && !is_dir($v['4']))
					continue;

				if (!$images_removed[$v['1']]) {
					$images = $db->row("SELECT * FROM products_photos WHERE productid='".$v['1']."'");
					if ($images) {
						foreach ($images as $b) {
							$dir = SITE_ROOT . '/photos/product/'.$b['productid'].'/'.$b['photoid'];
							unlink($dir.'/'.$b['file']);
							rmdir($dir);
						}

						$db->query("DELETE FROM products_photos WHERE productid='".$v['1']."'");
					}

					$images_removed[$v['1']] = 1;
				}

				$dir = SITE_ROOT . '/photos/product/'.$v[1];
				if (!is_dir($dir))
					mkdir($dir);

				$name = basename($v['4']);
				$new_image = array(
					'productid'=> $v[1],
					'alt'		=> $v['2'],
					'file'		=> $name,
					'size'		=> $file['size'],
					'pos'		=> $v[3]
				);

				if ($v['0']) {
					$imageid = $new_image['photoid'] = $v['0'];
					$db->array2insert("products_photos", $new_image);
				} else
					$imageid = $db->array2insert("products_photos", $new_image);

				$dir .= '/'.$imageid;
				if (!is_dir($dir))
					mkdir($dir);

				copy($v['4'], $dir.'/'.$name);
				list($width, $height) = getimagesize($dir.'/'.$name);
				$size = filesize($dir.'/'.$name);
				$update = array(
					'x'		=> $width,
					'y'		=> $height,
					'size'	=> $size
				);

				$db->array2update("products_photos", $update, "photoid='".$imageid."'");
		}

				if ($current == 'products_options') {
/*
            [0] => !GROUP_ID
            [1] => !PRODUCT_ID
            [2] => !NAME
            [3] => !FULL_NAME
            [4] => !TYPE
            [5] => !VIEW_TYPE
            [6] => !POSITION
            [7] => !ENABLED
            [8] => !IS_VARIANT
            [9] => !OPTION_ID
            [10] => !NAME
            [11] => !PRICE_MODIFIER
            [12] => !PRICE_MODIFIER_TYPE
            [13] => !WEIGHT_MODIFIER
            [14] => !WEIGHT_MODIFIER_TYPE
            [15] => !POSITION
            [16] => !ENABLED
*/
					if ((is_numeric($v['0']) || empty($v['0'])) && !empty($v['1'])) {
						if (empty($v['0'])) {
							$group = $db->row("SELECT * FROM option_groups WHERE groupid='$groupid'");
							if (empty($group))
								continue;

							if (empty($products_options)) {
								$tmp = $db->all("SELECT * FROM options ORDER BY orderby, optionid");
								foreach ($tmp as $o) {
									$products_options[$o['optionid']] = $o;
								}
							}

							if (!empty($products_options[$v['9']])) {
								$db->query("UPDATE options SET groupid='$groupid', name='$v[10]', orderby='".$v['15']."', enabled='".$v['16']."', price_modifier='$v[11]', price_modifier_type='$v[12]', weight_modifier='$v[13]', weight_modifier_type='$v[14]' WHERE optionid='".$v['9']."'");
							} else {
								$db->query("INSERT INTO options SET optionid='".$v['9']."', groupid='$groupid', name='$v[10]', orderby='".$v['15']."', enabled='".$v['16']."', price_modifier='$v[11]', price_modifier_type='$v[12]', weight_modifier='$v[13]', weight_modifier_type='$v[14]'");
							}
						} else {
							$product = $db->row("SELECT * FROM products WHERE productid='$v[1]' OR sku='$v[1]'");
							if (!$product) {
								$groupid = 0;
								continue;
							}

							if (empty($products_options_groups)) {
								$tmp = $db->all("SELECT * FROM option_groups ORDER BY groupid");
								foreach ($tmp as $g) {
									$products_options_groups[$g['groupid']] = $g;
								}
							}

							if (!empty($products_options_groups[$v['0']])) {
								$db->query("UPDATE option_groups SET productid='$product[productid]', orderby='".$v['6']."', type='".$v['4']."', view_type='".$v['5']."', enabled='".$v['7']."', variant='".$v['8']."', name='".$v['2']."', fullname='".$v['3']."' WHERE groupid='".$v['0']."'");
								$groupid = $v['0'];
							} else {
								$groupid = $v['0'];
								$db->query("INSERT INTO option_groups SET groupid='".$groupid."', productid='$product[productid]', orderby='".$v['6']."', type='".$v['4']."', view_type='".$v['5']."', enabled='".$v['7']."', variant='".$v['8']."', name='".$v['2']."', fullname='".$v['3']."'");
							}
						}
					}
				}

				if ($current == 'variants') {
/*
            [0] => !VARIANT_ID
            [1] => !PRODUCT_ID
            [2] => !TITLE
            [3] => !AMOUNT
            [4] => !PRICE
            [5] => !WEIGHT
            [6] => !SKU
            [7] => !IS_DEFAULT
            [8] => !OPTIONS_GROUP
            [9] => !OPTION_ID
            [10] => !OPTION_NAME
*/
					if ((is_numeric($v['0']) || empty($v['0'])) && !empty($v['1'])) {
						if (empty($v['0'])) {
							$variant = $db->row("SELECT * FROM variants WHERE variantid='$variantid'");
							if (!$variant) {
								$variantid = 0;
								continue;
							}

							if (empty($products_options)) {
								$tmp = $db->all("SELECT * FROM options ORDER BY orderby, optionid");
								foreach ($tmp as $o) {
									$products_options[$o['optionid']] = $o;
								}
							}

							$db->query("DELETE FROM variant_items WHERE variantid='$variantid' AND optionid='$v[9]'");
							$db->query("INSERT INTO variant_items SET variantid='$variantid', optionid='$v[9]'");
						} else {
							$product = $db->row("SELECT * FROM products WHERE productid='$v[1]' OR sku='$v[1]'");
							if (!$product) {
								$variantid = 0;
								continue;
							}

							if (empty($variants)) {
								$tmp = $db->all("SELECT * FROM variants ORDER BY variantid");
								foreach ($tmp as $g) {
									$variants[$g['variantid']] = $g;
								}
							}

							$sku = func_process_sku($v['1'], 0, $v['0']);
							if (!empty($variants[$v['0']])) {
								$db->query("UPDATE variants SET productid='$product[productid]', avail='".$v['3']."', price='".$v['4']."', weight='".$v['5']."', sku='".$sku."', label='".$v['2']."' WHERE variantid='".$v['0']."'");
								$variantid = $v['0'];
							} else {
								$variantid = $v['0'];
								$db->query("INSERT INTO variants SET variantid='$variantid', productid='$product[productid]', avail='".$v['3']."', price='".$v['4']."', weight='".$v['5']."', sku='".$sku."', label='".$v['2']."' WHERE variantid='".$v['0']."'");
							}
						}
					}
				}

		if ($current == 'variants_images' && $v['4']) {
/*
      [0] => !ID
            [1] => !VARIANT_ID
            [2] => !ALT
            [3] => !POSITION
            [4] => !FILE
*/
				if (is_url($v['4'])) {
					$image = file_get_contents(stripslashes($v['4']));
					$name = $v['4'];
					$v['4'] = SITE_ROOT.'/var/tmp/'.md5(time());
					$fp = fopen($v['4'], 'w');
					fputs($fp, $image);
					fclose($fp);
				} else {
					$name = $v['4'];
					$v['4'] = SITE_ROOT.'/var/import_export/variants_images/'.$v['4'];
				}

				if (!file_exists($v['4']) && !is_dir($v['4']))
					continue;

				if (!$v_images_removed[$v['1']]) {
					$images = $db->row("SELECT * FROM variant_images WHERE variantid='".$v['1']."'");
					if ($images) {
						foreach ($images as $b) {
							if ($b['variantid']) {
								$dir = SITE_ROOT . '/photos/variant/'.$b['variantid'].'/'.$b['imageid'];
								unlink($dir.'/'.$b['file']);
								rmdir($dir);
							}
						}

						$db->query("DELETE FROM variant_images WHERE variantid='".$v['1']."'");
					}

					$v_images_removed[$v['1']] = 1;
				}

				$dir = SITE_ROOT . '/photos/variant/'.$v[1];
				if (!is_dir($dir))
					mkdir($dir);

				$name = basename($v['4']);
				$new_image = array(
					'variantid'=> $v[1],
					'file'		=> $name,
					'alt'		=> $v['2'],
					'size'		=> $file['size'],
					'pos'		=> $v[3]
				);

				if ($v['0']) {
					$imageid = $new_image['imageid'] = $v['0'];
					$db->array2insert("variant_images", $new_image);
				} else
					$imageid = $db->array2insert("variant_images", $new_image);

				$dir .= '/'.$imageid;
				if (!is_dir($dir))
					mkdir($dir);

				copy($v['4'], $dir.'/'.$name);
				list($width, $height) = getimagesize($dir.'/'.$name);
				$size = filesize($dir.'/'.$name);
				$update = array(
					'x'		=> $width,
					'y'		=> $height,
					'size'	=> $size
				);

				$db->array2update("variant_images", $update, "imageid='".$imageid."'");
		}

				if ($current == 'wholesale') {
/*
            [0] => !ID
            [1] => !MEMBERSHIP_ID
            [2] => !PRODUCT_ID
            [3] => !VARIANT_ID
            [4] => !PRICE
            [5] => !QUANTITY
*/
					if ($v['2'] && $v['3']) {
						$product = $db->row("SELECT * FROM products WHERE productid='$v[2]' OR sku='$v[2]'");
						if (!$product)
							continue;

						$variant = $db->row("SELECT * FROM variants WHERE variantid='$v[3]' OR sku='$v[3]'");
						if (!$variant)
							continue;

						if (!$wholesale_removed[$product['productid'].'_'.$variant['variantid']]) {
							$wholesale_removed[$product['productid'].'_'.$variant['variantid']] = 1;
							$db->query("DELETE FROM wholesale_prices WHERE productid='$product[productid]' AND variantid='$variant[variantid]'");
						}

						$db->query("INSERT INTO wholesale_prices SET productid='$product[productid]', variantid='$variant[variantid]', membershipid='$v[1]', price='$v[4]', quantity='$v[5]'");
					} elseif ($v['2']) {
						$product = $db->row("SELECT * FROM products WHERE productid='$v[2]' OR sku='$v[2]'");
						if (!$product && $v[2]) {
							continue;
						}

						$variant['variantid'] = 0;

						if (!$wholesale_removed[$product['productid'].'_'.$variant['variantid']]) {
							$wholesale_removed[$product['productid'].'_'.$variant['variantid']] = 1;
							$db->query("DELETE FROM wholesale_prices WHERE productid='$product[productid]' AND variantid='$variant[variantid]'");
						}

						$db->query("INSERT INTO wholesale_prices SET productid='$product[productid]', variantid='$variant[variantid]', membershipid='$v[1]', price='$v[4]', quantity='$v[5]'");
					}

				}

				if ($current == 'related') {
					$product1 = $db->field("SELECT productid FROM products WHERE productid='$v[0]' OR sku='$v[0]'");
					if (!$product1)
						continue;

					$product2 = $db->field("SELECT productid FROM products WHERE productid='$v[1]' OR sku='$v[1]'");
					if (!$product2)
						continue;

					if (!$related_removed[$product1]) {
						$related_removed[$product1] = 1;
						$db->query("DELETE FROM related_products WHERE productid1='$product1'");
					}


					$db->query("INSERT INTO related_products SET productid1='$product1', productid2='$product2', orderby='$v[2]'");
				}

				if ($current == 'reviews') {
					if ((is_numeric($v['0']) || empty($v['0'])) && !empty($v['1'])) {
						if (empty($reviews)) {
							$tmp = $connection->executeQuery("SELECT * FROM xlite_customer_reviews ORDER BY id")->fetchAll();
							foreach ($tmp as $r) {
								$reviews[$r['id']] = $r;
							}
						}

						if (!empty($reviews[$v['0']])) {
							$connection->executeQuery("UPDATE xlite_customer_reviews SET product_id='".$v['1']."', name='".$v['2']."', email='".$v['3']."', review='".str_replace("<EOL>", "\n", $v['4'])."', enabled='".$v['8']."', rate='".$v['5']."', ip='".$v['6']."', profile_id='".$v['7']."' WHERE id='".$v['0']."'");
						} else {
							$connection->executeQuery("INSERT INTO xlite_customer_reviews SET product_id='".$v['1']."', name='".$v['2']."', email='".$v['3']."', review='".str_replace("<EOL>", "\n", $v['4'])."', enabled='".$v['8']."', rate='".$v['5']."', ip='".$v['6']."'");
						}
					}
				}

           		if ($k % 10 == 0) {
   	        		echo '.';
            		ob_flush();
            		flush();
           		}
	}

	echo '<br /><br />Recalculating the data...<br />';
	$products = $db->all("SELECT productid FROM products ORDER BY productid");
	foreach ($products as $k=>$v) {
		func_optimize_photo($v['productid']);
	}

	func_recalculate_subcount();

	echo '<br />Data imported<br /><br /><br /><a href="'.$http_location.'/admin/import">Go back</a>';

	if ($_SESSION['alerts']) {
		echo '<br /><br />Notifications<br />';
		foreach ($_SESSION['alerts'] as $alert) {
			echo $alert['content'].'<br />';
		}
	}

	$_SESSION['alerts'] = '';
}

/*
	Categories
		Banners
	Brands
	Products
		Customer reviews
		Images
		Product Options
			Product Variants
			Variant Images
		Wholesale pricing
		Related items
*/

function csv_file_to_array($input_file_name, $include_header_in_output = TRUE, $length = 1000, $delimeter = ',', $enclosure = '"', $escape = '\\')
    {
        // NOTE: this attempts to properly recognize line endings when reading files from Mac; has small performance penalty
        ini_set('auto_detect_line_endings', TRUE);

        $csv_array = array();

        // Warnings are supressed, but that's OK since the code handles such warnings
        if (($handle = fopen($input_file_name, "r")) !== FALSE)
        {
            $row_counter      = 0;

            // Iterate over the CSV entries
            while (($row = fgetcsv($handle, $length, $delimeter, $enclosure, $escape)) !== FALSE)
            {
                if ($row_counter === 0 && $include_header_in_output === TRUE)
                {
                    // This is the first row in the CSV and it should be included in the output
                    $csv_array[] = csv_entry_to_array($row);
                }
                else if ($row_counter > 0)
                {
                    // This is a row in the CSV that needs to be stored in the return array
                    $csv_array[] = csv_entry_to_array($row);
                }

                $row_counter++;
            }

            // Close file handler
            fclose($handle);
        }
        else
        {
            // Input file: some error occured
            return array();
        }

        return $csv_array;
    }

function csv_entry_to_array(array $row)
    {
        $column_count = count($row);
        $csv_column_values = array();

        // Loop through the columns of the current CSV entry and store its value
        for ($column_index = 0; $column_index < $column_count; $column_index++)
        {
            // Store the value of the current CSV entry
            $csv_column_values[] = $row[$column_index];
        }

        // Return
        return $csv_column_values;
    }


$template['head_title'] = lng('Import catalog').' :: '.$template['head_title'];
$template['location'] .= ' &gt; '.lng('Import catalog');

$template['page'] = get_template_contents('admin/pages/import.php');

$template['css'][] = 'admin_import';
$template['js'][] = 'admin_import';
