<?php
exit('API disabled');
$key = '556677';
if ($_GET['key'] != $key) {
	exit('Access denied');
}

q_load('product', 'category');
if ($_GET['mode'] == 'categories') {
	$tree = func_categories_tree(0, "orderby");
	echo '<pre>';
	echo "Category path,Category #ID\n";
	foreach ($tree as $k=>$v) {
		echo $v['title'].','.$v['categoryid']."\n";
		if ($v['subcategories'])
			foreach ($v['subcategories'] as $v2) {
				echo $v['title'].'/'.$v2['title'].','.$v2['categoryid']."\n";
				if ($v2['subcategories'])
					foreach ($v2['subcategories'] as $v3) {
						echo $v['title'].'/'.$v2['title'].'/'.$v3['title'].','.$v3['categoryid']."\n";
						if ($v3['subcategories'])
							foreach ($v3['subcategories'] as $v4) {
								echo $v['title'].'/'.$v2['title'].'/'.$v3['title'].'/'.$v4['title'].','.$v4['categoryid']."\n";
							}
					}
			}
	}

	exit;
}

if ($_GET['mode'] == 'brands') {
	$tree = $db->all("SELECT * FROM brands ORDER BY orderby");
	echo '<pre>';
	echo "Brand,Brand #ID\n";
	foreach ($tree as $k=>$v) {
		echo $v['name'].','.$v['brandid']."\n";
	}

	exit;
}

if ($_GET['mode'] == 'warehouses') {
	$tree = $db->all("SELECT * FROM warehouses ORDER BY pos");
	echo '<pre>';
	echo "Warehouse,Code\n";
	foreach ($tree as $k=>$v) {
		echo $v['title'].','.$v['wcode']."\n";
	}

	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'product') {
		$product = func_select_product($productid);
		$inventory = $db->all("SELECT w.wcode as code, i.avail FROM product_inventory i, warehouses w WHERE i.productid='$productid' AND i.wid=w.wid AND variantid=0");
		$product['inventory'] = $inventory;
		$photos = $db->all("SELECT * FROM products_photos WHERE productid='".$productid."' ORDER BY pos, photoid DESC");
		$product['photos'] = $photos;
		$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$productid."' ORDER BY orderby, name, fullname, groupid");
		if (!empty($option_groups)) {
			foreach ($option_groups as $k=>$v) {
				$option_groups[$k]['options'] = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, name");
			}

			$product['option_groups'] = $option_groups;
			$tmp = $db->all("SELECT * FROM variants WHERE productid='".$productid."' ORDER BY variantid");
			if (!empty($tmp)) {
				$variants = array();
				foreach ($tmp as $tmp2) {
					$tmp2['options'] = $db->all("SELECT og.name, o.optionid, og.groupid, og.name FROM variant_items as vi INNER JOIN options AS o ON vi.optionid=o.optionid INNER JOIN option_groups AS og ON og.groupid=o.groupid WHERE vi.variantid='".$tmp2['variantid']."' ORDER BY og.orderby, o.orderby, o.name");
					$inventory = $db->all("SELECT w.wcode as code, i.avail FROM product_inventory i, warehouses w WHERE i.productid='$productid' AND i.wid=w.wid AND variantid='".$tmp2['variantid']."'");
					$tmp2['inventory'] = $inventory;
					$variants[] = $tmp2;
				}

				$product['variants'] = $variants;
			}
		}

		$categories = $db->column("SELECT categoryid FROM category_products WHERE main<>'Y' AND productid='".$v['productid']."'");
		$ids = array();
		if (!empty($categories)) {
			foreach ($categories as $c) {
				$ids[] = $c['categoryid'];
			}

			$product['additional_categories'] = implode(',', $ids);
		}

		echo '<pre>';
		exit(print_R($product));
	} elseif ($mode == 'manage_product') {
		$array = json_decode($_POST['data'], true);
# Uncomment to display error
/*
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
        break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            echo ' - Unknown error';
        break;
    }
*/
		$exists = $db->row("SELECT * FROM variants WHERE sku='".addslashes($array['sku'])."'");
		if ($exists) {
			$result = array(
				'status'	=> 'E',
				'message'	=> 'Variant with such SKU exists',
				'variantid'	=> $exists
			);

			exit(json_encode($result));
		}

		$exists = $db->field("SELECT productid FROM products WHERE sku='".addslashes($array['sku'])."'");

		$fields = array(
				'sku',
				'brandid',
				'name',
				'cleanurl',
				'descr',
				'list_price',
				'price',
				'weight',
				'avail',
				'status',
				'meta_description',
				'meta_keywords',
				'title_tag'
			);

		$product_array = array();
		foreach ($array as $k=>$v) {
			if (in_array($k, $fields))
				$product_array[$k] = $v;
		}

		if (!$product_array) {
			$result = array(
				'status'	=> 'E',
				'message'	=> 'Product array is empty'
			);

			exit(json_encode($result));
		}

		if ($exists) {
			$db->array2update("products", $product_array, "productid='".$exists."'");
			$productid = $exists;
			$api_message = "Product #ID".$exists." has that SKU and was updated";
		} else {
			$product_array['add_date'] = time();
			$productid = $db->array2insert('products', $product_array);
			$api_message = 'Product inserted. New ID is '.$productid;
		}

		$db->query("DELETE FROM category_products WHERE productid='".$productid."' AND main='Y'");
		$db->query("REPLACE INTO category_products SET productid='".$productid."', categoryid='".addslashes($array['main_categoryid'])."', main='Y'");
		$tmp = explode(',', $array['add_categories']);
		if ($tmp['0']) {
			$db->query("DELETE FROM category_products WHERE productid='$productid' AND main=''");
			foreach ($tmp as $c)
				$db->query("REPLACE INTO category_products SET productid='$productid', categoryid='$c', main=''");
		}

		if ($array['inventory'] || $array['inventory'] == 'D') {
			$db->query("DELETE FROM product_inventory WHERE productid='".$productid."' AND variantid=0");
			if (is_array($array['inventory'])) {
				foreach ($array['inventory'] as $w) {
					$wid = $db->field("SELECT wid FROM warehouses WHERE wcode='".addslashes($w['code'])."'");
					$db->query("INSERT INTO product_inventory SET productid='".$productid."', wid='".$wid."', avail='".$w['avail']."'");
				}
			}
		}

		if ($array['photos'] || $array['photos'] == 'D') {
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

			if (is_array($array['photos'])) {
				$photos_dir = SITE_ROOT . '/photos/product/'.$productid;
				if (!is_dir($photos_dir))
					mkdir($photos_dir);

				foreach ($array['photos'] as $pos=>$photo_url) {
					$image = file_get_contents(stripslashes($photo_url));
					$tmp_name = SITE_ROOT.'/var/tmp/'.md5(time());
					$fp = fopen($tmp_name, 'w');
					fputs($fp, $image);
					fclose($fp);
					if (!is_dir($dir))
						mkdir($dir);

					$name = basename($photo_url);
					$new_image = array(
						'productid'=> $productid,
						'file'		=> $name,
						'pos'		=> $pos
					);

					$imageid = $db->array2insert("products_photos", $new_image);
					$dir = $photos_dir.'/'.$imageid;
					if (!is_dir($dir))
						mkdir($dir);

					copy($tmp_name, $dir.'/'.$name);
					list($width, $height) = getimagesize($dir.'/'.$name);
					$size = filesize($dir.'/'.$name);
					$update = array(
						'x'		=> $width,
						'y'		=> $height,
						'size'	=> $size
					);

					$db->array2update("products_photos", $update, "photoid=".$imageid);
					unlink($tmp_name);
				}
			}
		}

		func_optimize_photo($productid);

		if ($array['options'] || $array['options'] == 'D') {
			$groups = $db->all("SELECT groupid FROM option_groups WHERE productid='".$productid."'");
			if ($groups) {
				$ids = array();
				foreach ($groups as $v)
					$ids[] = $v['groupid'];

				$db->query("DELETE FROM options WHERE groupid IN (".implode(',', $ids).")");
				$db->query("DELETE FROM option_groups WHERE productid='".$productid."'");
			}

			if (is_array($array['options'])) {
				foreach ($array['options'] as $group) {
					$new_group = array(
						'productid'	=> $productid,
						'name'		=> $group['name'],
						'fullname'	=> $group['fullname'],
						'type'		=> $group['type'],
						'view_type'	=> $group['view_type'],
						'enabled'	=> $group['enabled'],
						'variant'	=> $group['variant'],
					);

					$groupid = $db->array2insert("option_groups", $new_group);
					if ($group['options']) {
						foreach ($group['options'] as $option) {
							$new_option = array(
								'groupid'	=> $groupid,
								'name'		=> $option['name'],
								'enabled'	=> $option['enabled'],
								'price_modifier'		=> $option['price_modifier'],
								'price_modifier_type'	=> $option['price_modifier_type'],
								'weight_modifier'		=> $option['weight_modifier'],
								'weight_modifier_type'	=> $option['weight_modifier_type'],
							);

							$db->array2insert("options", $new_option);
						}
					}
				}
			}
		}

		if ($array['variants'] || $array['variants'] == 'D') {
			$variants = $db->all("SELECT variantid FROM variants WHERE productid='".$productid."'");
			if ($variants) {
				$ids = array();
				foreach ($variants as $v)
					$ids[] = $v['variantid'];

				$db->query("DELETE FROM variant_items WHERE variantid IN (".implode(',', $ids).")");
				$db->query("DELETE FROM variants WHERE productid='".$productid."'");
			}

			if (is_array($array['variants'])) {
				foreach ($array['variants'] as $variant) {
					$new_variant = array(
						'productid'	=> $productid,
						'avail'		=> $variant['avail'],
						'price'		=> $variant['price'],
						'weight'	=> $variant['weight'],
						'sku'		=> $variant['sku'],
						'title'		=> $variant['title'],
						'def'		=> $variant['def'],
					);

					$sku_exists = $db->row("SELECT * FROM variants WHERE sku='".addslashes($variant['sku'])."'");
					if ($sku_exists) {
						$result = array(
							'status'	=> 'E',
							'message'	=> 'Product imported without variants - variant with SKU '.$variant['sku'].' exists'
						);

						exit(json_encode($result));
					}

					$sku_exists = $db->row("SELECT * FROM products WHERE sku='".addslashes($variant['sku'])."'");
					if ($sku_exists) {
						$result = array(
							'status'	=> 'E',
							'message'	=> 'Product imported without variants - product with variant SKU '.$variant['sku'].' exists'
						);

						exit(json_encode($result));
					}

					$variantid = $db->array2insert("variants", $new_variant);
					if ($variant['options']) {
						foreach ($variant['options'] as $option) {
							$group = $db->row("SELECT * FROM option_groups WHERE productid='$productid' AND name='".addslashes($option['group'])."'");
							if (!$group) {
								$result = array(
									'status'	=> 'E',
									'message'	=> 'Variant error: group '.$option['group'].' does not exist'
								);

								exit(json_encode($result));
							}

							if ($group) {
								$group_option = $db->row("SELECT * FROM options WHERE groupid='$group[groupid]' AND name='".addslashes($option['option'])."'");
								if (!$group_option) {
								exit("SELECT * FROM options WHERE groupid='$group[groupid]' AND name='".addslashes($option['option'])."'");
									$result = array(
										'status'	=> 'E',
										'message'	=> 'Variant error: option '.$option['option'].' from group '.$option['group'].' does not exist'
									);

									exit(json_encode($result));
								}

								$new_option = array(
									'variantid'	=> $variantid,
									'optionid'	=> $group_option['optionid']
								);

								$db->array2insert("variant_items", $new_option);
							}
						}
					}

					if ($variant['inventory'] || $variant['inventory'] == 'D') {
						$db->query("DELETE FROM product_inventory WHERE productid='".$productid."' AND variantid='".$variantid."'");
						if (is_array($variant['inventory'])) {
							foreach ($variant['inventory'] as $w) {
								$wid = $db->field("SELECT wid FROM warehouses WHERE wcode='".addslashes($w['code'])."'");
								$db->query("INSERT INTO product_inventory SET productid='".$productid."', variantid='".$variantid."', wid='".$wid."', avail='".$w['avail']."'");
							}
						}
					}
				}
			}
		}

		$result = array(
			'status'	=> 'S',
			'message'	=> $api_message,
			'productid'	=> $productid
		);

		exit(json_encode($result));
	}
}

$example_add_product = array(
	'sku'			=> 'new_sku',
	'brandid'		=> 2,
	'name'			=> 'New product',
	'cleanurl'		=> 'new-product',
	'descr'			=> 'New product description',
	'list_price'	=> '104.99',
	'price'			=> '95.99',
	'weight'		=> '10',
	'avail'			=> '1000',
    'inventory'		=> array(
		array(
 			'code' => 'FirstW',
    		'avail' => 18
		),
		array(
 			'code' => 'ABC',
    		'avail' => 14
		),
	),
	'status'		=> '1',
	'meta_description'	=> 'Meta description',
	'meta_keywords'		=> 'Meta keywords',
	'title_tag'			=> 'Title tag',
	'main_categoryid'	=> '20',
	'add_categories' 	=> '19,21,22',
	'photos'		=> array(
		'http://simpleproductphotography.com/wp-content/uploads/2016/06/huf-converse-product-red-skidgrip-1.jpg',
		'https://community.uservoice.com/wp-content/uploads/iterative-product-development-800x533.jpg',
		'http://www.mamiyaleaf.com/assets/slider/product/product_slider_heinz_baumann.jpg'
	),
	'options'		=> array(
		array(
			'name'		=> 'Color',
			'fullname'	=> 'Color',
			'type'		=> 'g',
			'view_type'	=> 'p',
			'enabled'	=> 1,
			'variant'	=> 1,
			'options'	=> array(
				array(
					'name'				=> 'Red',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
				array(
					'name'				=> 'Green',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
				array(
					'name'				=> 'Blue',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
			),
		),
		array(
			'name'		=> 'Size',
			'fullname'	=> 'Size',
			'type'		=> 'g',
			'view_type'	=> 'p',
			'enabled'	=> 1,
			'variant'	=> 1,
			'options'	=> array(
				array(
					'name'				=> 'Small',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
				array(
					'name'				=> 'Medium',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
				array(
					'name'				=> 'Big',
					'enabled'			=> 1,
					'price_modifier'	=> 0,
					'price_modifier_type'	=> '%',
					'weight_modifier'	=> 0,
					'weight_modifier_type'	=> '%',
				),
			),
		),
	),
	'variants'		=> array(
		array(
			'avail'		=> 1000,
			'price'		=> 10,
			'weight'	=> 5,
			'sku'		=> 'new_sku_v1',
			'title'		=> 'Red/Small New product',
			'def'		=> 1,
			'options'	=> array(
				array(
					'group'		=> 'Color',
					'option'	=> 'Red'
				),
				array(
					'group'		=> 'Size',
					'option'	=> 'Small'
				),
			),
		    'inventory'		=> array(
				array(
 					'code' => 'FirstW',
		    		'avail' => 25
				),
				array(
 					'code' => 'ABC',
		    		'avail' => 35
				),
			),
		),
		array(
			'avail'		=> 1000,
			'price'		=> 10,
			'weight'	=> 5,
			'sku'		=> 'new_sku_v2',
			'title'		=> 'Red/Big New product',
			'def'		=> 0,
			'options'	=> array(
				array(
					'group'		=> 'Color',
					'option'	=> 'Red'
				),
				array(
					'group'		=> 'Size',
					'option'	=> 'Big'
				),
			),
		    'inventory'		=> array(
				array(
 					'code' => 'FirstW',
		    		'avail' => 250
				),
				array(
 					'code' => 'ABC',
		    		'avail' => 350
				),
			),
		),
		array(
			'avail'		=> 1000,
			'price'		=> 10,
			'weight'	=> 5,
			'sku'		=> 'new_sku_v3',
			'title'		=> 'Green/Medium New product',
			'def'		=> 0,
			'options'	=> array(
				array(
					'group'		=> 'Color',
					'option'	=> 'Green'
				),
				array(
					'group'		=> 'Size',
					'option'	=> 'Medium'
				),
			),
		    'inventory'		=> array(
				array(
 					'code' => 'FirstW',
		    		'avail' => 10
				),
				array(
 					'code' => 'ABC',
		    		'avail' => 15
				),
			),
		),
	),
);

$example_add_product_json = json_encode($example_add_product);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:g="http://base.google.com/ns/1.0" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://ogp.me/ns/fb#">
<head>
<meta charset="utf-8" />
</head>
<body>
<a href="/api?key=<?php echo htmlspecialchars($_GET['key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>&mode=categories">Categories</a>
&nbsp;
<a href="/api?key=<?php echo htmlspecialchars($_GET['key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>&mode=brands">Brands</a>
&nbsp;
<a href="/api?key=<?php echo htmlspecialchars($_GET['key'] ?? '', ENT_QUOTES, 'UTF-8'); ?>&mode=warehouses">Warehouses</a>
<br /><br />
<form method="POST" name="apiform" />
Mode: <input type="text" name="mode" value="product" readonly /><br />
Product #ID: <input type="text" name="productid" value="104" />
<br /><br />
<button type="button" onclick="document.apiform.mode.value='product'; document.apiform.submit()">View prodcut</button>
<br /><br />
<textarea name="data" cols="80" placeholder="JSON" rows="20"><?php echo $example_add_product_json;?></textarea>
<br /><br />
<button type="button" onclick="document.apiform.mode.value='manage_product'; document.apiform.submit()">Add product</button>
</form>
</body>
</html>
<?php
echo '<pre><b>JSON decoded example</b><hr />';
echo print_R($example_add_product);
exit;
?>