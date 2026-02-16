<?php
q_load('category', 'product');

if (empty($section))
	$section = $get['3'];

if ($_GET['remove_photo']) {
	$photo = $db->row("SELECT * FROM products_photos WHERE photoid='".addslashes($_GET['remove_photo'])."'");
	$dir = SITE_ROOT . '/photos/product/'.$get['2'].'/'.$photo['photoid'];
	unlink($dir.'/'.$photo['file']);
	rmdir($dir);
	$db->query("DELETE FROM products_photos WHERE photoid='".addslashes($_GET['remove_photo'])."'");
	func_optimize_photo($get['2']);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($variant_wh) {
		foreach ($variant_wh as $vid=>$w) {
			$db->query("DELETE FROM product_inventory WHERE variantid='$vid' AND productid='$get[2]'");
			foreach ($w as $wid=>$avail) {
				$db->query("INSERT INTO product_inventory SET wid='$wid', avail='$avail', variantid='$vid', productid='$get[2]'");
			}
		}

		exit;
	}

	if ($mode == 'delete_products') {
		if ($_POST['to_delete']) {
			foreach ($_POST['to_delete'] as $id=>$v) {
				func_delete_product($id);
			}

			$_SESSION['alerts'][] = array(
				'type'		=> 'i',
				'content'	=> 'Your products has been deleted successfully!'
			);
		} else
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> 'You need to select products to delete.'
			);

		func_recalculate_subcount();
		redirect('/admin/products');
	}

	if (empty($section)) {
		if ($get['2']) {
			if ($_POST['mode'] == 'clone') {
				$productid = addslashes($get['2']);
				$product = $db->row("SELECT * FROM products WHERE productid='".$productid."'");

				unset($product['productid']);
				$product['sku'] = $product['sku'].' (CLONE)';
				$product['cleanurl'] = $product['cleanurl'].'-CLONE';
				$product['name'] = $product['name'].'-CLONE';
				$new_productid = $db->array2insert("products", $product);
				$category_products = $db->all("SELECT * FROM category_products WHERE productid='".$productid."'");
				if ($category_products)
					foreach ($category_products as $cp) {
						$cp['productid'] = $new_productid;
						$db->array2insert("category_products", $cp);
					}

				$related_products = $db->all("SELECT * FROM related_products WHERE productid1='".$productid."'");
				if ($related_products)
					foreach ($related_products as $rp) {
						$rp['productid1'] = $new_productid;
						$db->array2insert("related_products", $rp);
					}

				$images = $db->all("SELECT * FROM products_photos WHERE productid='".$productid."'");
				if ($images) {
					$dir = SITE_ROOT . '/photos/product/';
					foreach ($images as $i) {
						$new_image = $i;
						unset($new_image['photoid']);
						$new_image['productid'] = $new_productid;
						$old_path = $dir.'/'.$i['productid'].'/'.$i['photoid'].'/'.$i['file'];
						$new_path = $dir.'/'.$new_productid;
						if (!is_dir($new_path))
							mkdir($new_path);

						$new_imageid = $db->array2insert("products_photos", $new_image);
						$new_path = $dir.'/'.$new_productid.'/'.$new_imageid;
						if (!is_dir($new_path))
							mkdir($new_path);

						copy($old_path, $new_path.'/'.$i['file']);
					}
				}

				$old_options = array();
				$groups = $db->all("SELECT * FROM option_groups WHERE productid='".$productid."'");
				if ($groups) {
					foreach ($groups as $g) {
						$new_group = $g;
						unset($new_group['groupid']);
						$new_group['productid'] = $new_productid;
						$new_groupid = $db->array2insert("option_groups", $new_group);
						$options = $db->all("SELECT * FROM options WHERE groupid='".$g['groupid']."'");
						foreach ($options as $o) {
							$new_option = $o;
							$new_option['groupid'] = $new_groupid;
							unset($new_option['optionid']);
							$old_options[$o['optionid']] = $db->array2insert("options", $new_option);
						}
					}
				}

				$variants = $db->all("SELECT * FROM variants WHERE productid='".$productid."'");
				$old_variants = array();
				if ($variants) {
					foreach ($variants as $k=>$v) {
						$new_var = $v;
						unset($new_var['variantid']);
						$new_var['productid'] = $new_productid;
						$new_variantid = $db->array2insert("variants", $new_var);
						$old_variants[$v['variantid']] = $new_variantid;

						$items = $db->all("SELECT * FROM variant_items WHERE variantid='".$v['variantid']."'");
						foreach ($items as $i) {
							$i['variantid'] = $new_variantid;
							$i['optionid'] = $old_options[$i['optionid']];
							$db->array2insert("variant_items", $i);
						}

						$variant_images = $db->all("SELECT * FROM variant_images WHERE variantid='".$v['variantid']."'");
						if ($variant_images) {
							$dir = SITE_ROOT . '/photos/variant/';
							foreach ($variant_images as $i) {
								$new_image = $i;
								unset($new_image['imageid']);
								$new_image['variantid'] = $new_variantid;
								$old_path = $dir.'/'.$i['variantid'].'/'.$i['imageid'].'/'.$i['file'];
								$new_path = $dir.'/'.$new_variantid;
								if (!is_dir($new_path))
									mkdir($new_path);

								$new_imageid = $db->array2insert("variant_images", $new_image);
								$new_path = $dir.'/'.$new_variantid.'/'.$new_imageid;
								if (!is_dir($new_path))
									mkdir($new_path);

								copy($old_path, $new_path.'/'.$i['file']);
							}
						}
					}
				}

				$prices = $db->all("SELECT * FROM wholesale_prices WHERE productid='".$productid."'");
				if ($prices) {
					foreach ($prices as $p) {
						$new_price = $p;
						unset($new_price['priceid']);
						$new_price['productid'] = $new_productid;
						$new_price['variantid'] = $old_variants[$new_price['variantid']];
						$db->array2insert("wholesale_prices", $new_price);
					}
				}

				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> 'Product has been successfully cloned'
				);
				redirect('/admin/products/'.$new_productid);
			} elseif ($_POST['mode'] == 'delete') {
				func_delete_product($get['2']);
				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> 'Your product has been deleted successfully!'
				);

            	func_recalculate_subcount();
				redirect('/admin/products');
			}

			if ($get['2'] == 'add') {
				$new_product = array(
					'add_date'	=> time()
				);

				$productid = $db->array2insert('products', $new_product);
			} else
				$productid = $get['2'];

			$sku = func_process_sku($sku, $productid);
			$update = array(
				'sku'				=> $sku,
				'brandid'			=> $brandid,
				'name'				=> $name,
				'descr'				=> htmlspecialchars_decode($descr),
				'keywords'			=> $keywords,
				'price'				=> $price,
				'list_price'		=> $list_price,
				'weight'			=> $weight,
				'avail'				=> $avail,
				'status'			=> $status,
				'meta_description'	=> $meta_description,
				'meta_keywords'		=> $meta_keywords,
				'title_tag'			=> $title_tag
			);

			if ($cleanurl) {
				$cleanurl = trim($cleanurl);
				$cu_exist = $db->field("SELECT productid FROM products WHERE cleanurl='".addslashes($cleanurl)."' AND productid<>'".$productid."'");
				if ($cu_exist)
					$_SESSION['alerts'][] = array(
						'type'		=> 'e',
						'content'	=> lng('This clean URL already exist for another product.')
					);
				else {
					if (func_check_cleanurl($cleanurl))
						$update['cleanurl'] = $cleanurl;
					else
						$_SESSION['alerts'][] = array(
							'type'		=> 'e',
							'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".". Also it should be without ".html".')
						);
				}
			} else
				$update['cleanurl'] = '';

			$db->array2update('products', $update, "productid='".$productid."'");
			$db->query("DELETE FROM category_products WHERE productid='".$productid."'");
			$db->query("INSERT INTO category_products SET main='Y', productid='".$productid."', categoryid='".addslashes($categoryid)."'");
			if (!empty($categories))
				foreach ($categories as $v)
					if ($v != $categoryid)
						$db->query("INSERT INTO category_products SET main='', productid='".$productid."', categoryid='".addslashes($v)."'");

			func_recalculate_subcount();

			$_SESSION['alerts'][] = array(
				'type'		=> 'i',
				'content'	=> 'Product has been successfully saved'
			);

			redirect('/admin/products/'.$productid);
		} else {
			if ($mode == 'search') {
				foreach ($_POST as $k=>$v) {
					if (!is_array($v))
						$_POST[$k] = addslashes($v);
				}

				$_SESSION['search_products'] = $_POST;
			} else {
				foreach ($posted_data as $k=>$v)
					$db->array2update('products', $v, "productid='".$k."'");
			}

			redirect('/admin/products');
		}
	} elseif ($section == 'images') {
		if ($_FILES) {
			$images = array();
			foreach ($_FILES as $k=>$v) {
				if ($v['tmp_name']) {
					$v['name'] = str_replace("'", "", $v['name']);
					$v['name'] = str_replace('"', '', $v['name']);

					$dir = SITE_ROOT . '/photos/product/'.$get['2'];
					if (!is_dir($dir))
						mkdir($dir);

					$new_photo = array(
						'productid'	=> $get['2'],
						'file'		=> $v['name'],
						'size'		=> $v['size'],
						'pos'		=> $db->field("SELECT MAX(pos) FROM products_photos WHERE productid='".$get['2']."'") + 10
					);

					$photoid = $db->array2insert("products_photos", $new_photo);
					$dir .= '/'.$photoid;
					if (!is_dir($dir))
						mkdir($dir);

					copy($v['tmp_name'], $dir.'/'.$v['name']);
					list($width, $height) = getimagesize($dir.'/'.$v['name']);

					$update = array(
						'x'	=> $width,
						'y'	=> $height
					);

					$db->array2update("products_photos", $update, "photoid='".$photoid."'");
					$update['photoid'] = $photoid;
					$images[] = array_merge($new_photo, $update);
				}
			}

			func_optimize_photo($get['2']);
			if (!empty($images)) {
        		ob_start();
				foreach ($images as $image) {
					echo '<div data-id="'.$image['photoid'].'"><img src="/images/close.gif" class="remove" alt="" />';
					$image['new_width'] = 100;
					$image['new_height'] = 100;
					$image['center'] = 1;
					$image['is_admin'] = 1;

					include 'includes/image.php';
					echo '</div>';
				}

		        $tmp = ob_get_clean();
				exit(str_replace("\n", "", $tmp));
			}
        }

		if ($to_delete_photos)
			foreach ($to_delete_photos as $k=>$v) {
				$photo = $db->row("SELECT * FROM products_photos WHERE photoid='".addslashes($k)."'");
				if ($photo) {
					$dir = SITE_ROOT . '/photos/product/'.$get['2'].'/'.$photo['photoid'];
					unlink($dir.'/'.$photo['file']);
					rmdir($dir);
					$db->query("DELETE FROM products_photos WHERE photoid='".$photo['photoid']."'");
				}
			}

		if ($_POST['s']) {
			$sort = explode(',', $_POST['s']);
			foreach ($sort as $k=>$v) {
				$v = addslashes($v);
				if ($v)
					$db->query("UPDATE products_photos SET pos='".addslashes($k)."' WHERE photoid='".$v."'");
			}

			func_optimize_photo($get['2']);
			exit;
		}

		redirect('/admin/products/'.$get['2'].'/images');
	} elseif ($section == 'related') {
		if ($mode == 'update') {
			if (!empty($newproductid)) {
				$newproductid = addslashes($newproductid);
				if ($db->field("SELECT COUNT(*) FROM related_products WHERE productid1='".$get[2]."' AND productid2='".$newproductid."'"))
					$_SESSION['alerts'][] = array(
						'type'		=> 'e',
						'content'	=> lng('Such related productid already added to this product.')
					);
				elseif ($newproductid == $get['2'])
					$_SESSION['alerts'][] = array(
						'type'		=> 'e',
						'content'	=> lng('You cannot define the same product as related product.')
					);
				else {
					$orderby = $db->field("SELECT MAX(orderby) FROM related_products WHERE productid1='".$get[2]."'") + 10;
					$db->query("INSERT INTO related_products SET productid1='".$get[2]."', productid2='".$newproductid."', orderby='".$orderby."'");
				}
			}

			if (!empty($posted_data))
				foreach ($posted_data as $k=>$v)
					$db->array2update("related_products", $v, "productid1='".$get[2]."' AND productid2='".addslashes($k)."'");
		} elseif (!empty($to_delete)) {
			foreach ($to_delete as $k=>$v) {
				$db->query("DELETE FROM related_products WHERE productid1='".$get[2]."' AND productid2='".addslashes($k)."'");
			}
		}

		redirect('/admin/products/'.$get['2'].'/related');
	} elseif ($section == 'options') {
		if ($get['4']) {
			$old_group_variant = $db->field("SELECT variant FROM option_groups WHERE groupid='".$get['4']."'");
			$new_group_variant = $old_group_variant;
			if ($mode == 'update') {
				if ($posted_data['variant'] == 1 && $posted_data['type'] == 't') {
					$posted_data['type'] = 'g';
					$posted_data['view_type'] = 's';
					$_SESSION['alerts'][] = array(
						'type'		=> 'e',
						'content'	=> lng('Such type not not allowed for variant option. Options group/Select box has been chosen.')
					);
				}

				if ($get['4'] == 'add') {
					$posted_data['productid'] = $get['2'];
					$posted_data['orderby'] = $db->field("SELECT MAX(orderby) FROM option_groups WHERE productid='".$get['2']."'") + 10;
					$get['4'] = $db->array2insert("option_groups", $posted_data);
				} else {
					$new_group_variant = $posted_data['variant'];
					if (!$posted_data['variant'])
						$posted_data['variant'] = '';

					if (!$posted_data['enabled'])
						$posted_data['enabled'] = '';

					$db->array2update("option_groups", $posted_data, "groupid='".$get['4']."'");
				}

				if (!empty($new_option)) {
					foreach ($new_option as $k=>$v) {
						if (!empty($v['name'])) {
							if (!$v['enabled'])
								$v['enabled'] = '';

							$v['groupid'] = $get['4'];
							if (empty($v['orderby']))
								$v['orderby'] = $db->field("SELECT MAX(orderby) FROM options WHERE groupid='".$get['4']."'") + 10;

							$db->array2insert("options", $v);
						}
					}
				}

				if (!empty($options_data)) {
					foreach ($options_data as $k=>$v) {
						if (!$v['enabled'])
							$v['enabled'] = '';

						$db->array2update("options", $v, "optionid='".addslashes($k)."'");
					}
				}
			} elseif ($mode == 'delete' && !empty($to_delete)) {
				foreach ($to_delete as $k=>$v) {
					$k = addslashes($k);
					$db->query("DELETE FROM options WHERE optionid='".$k."'");
					$db->query("DELETE FROM options_ex WHERE optionid='".$k."'");
				}
			}

			redirect('/admin/products/'.$get['2'].'/options/'.$get['4']);
		} else {
			if ($mode == 'update') {
				foreach ($posted_data as $k=>$v) {
					$v['variant'] = $v['variant'];
					$v['enabled'] = $v['enabled'];
					$db->array2update("option_groups", $v, "groupid='".$k."'");
				}
			} elseif ($mode == 'delete' && !empty($to_delete)) {
				foreach ($to_delete as $k=>$v) {
					$k = addslashes($k);
					$db->query("DELETE FROM option_groups WHERE groupid='".$k."'");
					$options = $db->all("SELECT optionid FROM options WHERE groupid='".$k."'");
					$db->query("DELETE FROM options WHERE groupid='".$k."'");
					if ($options) {
						$ids = array();
						foreach ($options as $v2)
							$ids[] = addslashes($v2['optionid']);

						$db->query("DELETE FROM options_ex WHERE optionid IN (".implode(',', $ids).")");

						$variants = $db->all("SELECT DISTINCT(variantid) FROM variant_items WHERE optionid IN (".implode(',', $ids).")");
						if ($variants) {
							foreach ($variants as $v2) {
								func_delete_variant($v2['variantid']);
							}
						}
					}
				}
			} elseif ($mode == 'add_exception') {
				$new = array(
					'productid'		=> $get['2'],
					'exceptionid'	=> $db->field("SELECT MAX(exceptionid) FROM options_ex") + 1
				);

				foreach ($new_exception as $g=>$o) {
					$new['optionid'] = $o;
					$db->array2insert('options_ex', $new);
				}
			} elseif ($mode == 'delete_ex' && !empty($to_delete)) {
				foreach ($to_delete as $k=>$v)
					$db->query("DELETE FROM options_ex WHERE exceptionid='".addslashes($k)."'");
			}
		}

		redirect('/admin/products/'.$get['2'].'/options');
	} elseif ($section == 'variants') {
		if ($mode == 'filter') {
			$filter = '';
			foreach ($variants_filter as $k=>$v)
				if (!empty($v))
					$filter .= $k.'='.$v.';';

			if (empty($filter))
				redirect('/admin/products/'.$get['2'].'/variants');
			else
				redirect('/admin/products/'.$get['2'].'/variants?filter='.$filter);
		} elseif ($mode == 'reset_filter')
			redirect('/admin/products/'.$get['2'].'/variants');
		elseif ($mode == 'add' && !empty($new_variants)) {
			$variants = array();
			$data = $db->all("SELECT * FROM variants WHERE productid='".$get['2']."'");
			if ($data)
				foreach ($data as $v) {
					$variants[$v['variantid']] = array();
					$options = $db->all("SELECT optionid FROM variant_items WHERE variantid='".$v['variantid']."'");
					if ($options)
						foreach ($options as $i)
							$variants[$v['variantid']][] = $i['optionid'];
				}

			$product = $db->row("SELECT * FROM products WHERE productid='".$get['2']."'");

			$_var = array(
				'sku'			=> $product['sku'],
				'title'			=> $product['name'],
				'productid'		=> $product['productid'],
				'price'			=> $product['price'],
				'weight'		=> $product['weight'],
				'avail'			=> 1000
			);

			$all = func_all_variants($new_variants);
			$v_added = false;
			$v_exists = false;
			foreach ($all as $o) {
				$exist = false;
				foreach ($variants as $v) {
					if (count($v) == count($o)) {
						$found = true;
						foreach ($o as $i) {
							$found2 = false;
							foreach ($v as $i2) {
								if ($i2 == $i) {
									$found2 = true;
									break;
								}
							}

							if (!$found2) {
								$found = false;
								break;
							}
						}

						if ($found) {
							$exist = true;
							break;
						} else {
							$exist = false;
						}
					}
				}

				if ($exist) {
					$v_exists = true;
				} else {
					$v_added = true;
					# Get unique SKU
					if (strlen($_var['sku']) > 25) {
						$_var['sku'] = "SKU".$get['2']."v";
					}

					$sku = $_var['sku'];
					$cnt = 0;
					while (func_sku_exist($sku)) {
						$sku = $_var['sku'].++$cnt;
					}

					$_var['sku'] = $sku;
					$variantid = $db->array2insert("variants", $_var);

					foreach ($o as $oid)
						$db->query("INSERT INTO variant_items (variantid, optionid) VALUES ('".$variantid."', '".$oid."')");
				}
			}

			if ($v_exists)
				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> lng('Some variants has not been added as already exists.')
				);

			if ($v_added)
				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> lng('Variants has been added.')
				);
		} elseif ($mode == 'update') {
			foreach ($posted_data as $k=>$v) {
				$k = addslashes($k);
				$v['sku'] = func_process_sku($v['sku'], 0, $k);

				$db->array2update('variants', $v, "variantid='".$k."'");

				if (!empty($wprices[$k]))
					foreach ($wprices[$k] as $k2=>$v2) {
						$k2 = addslashes($k2);
						if ($v2['removed'] == 'Y') {
							$db->query("DELETE FROM wholesale_prices WHERE priceid='".$k2."'");
						} else {
							$db->query("UPDATE wholesale_prices SET price='".addslashes($v2['price'])."', membershipid='".addslashes($v2['membershipid'])."', quantity='".addslashes($v2['quantity'])."' WHERE priceid='".$k2."'");
						}
					}

				if (!empty($new_wprice))
					foreach ($new_wprice[$k] as $k2=>$v2) {
						if (empty($v2['price']) || $v2['price'] == "0.00")
							continue;

						$new_wp = array(
							'productid'			=> $get['2'],
							'variantid'			=> $k,
							'membershipid'		=> $v2['membershipid'],
							'price'				=> $v2['price'],
							'quantity'			=> $v2['quantity']
						);

						$db->array2insert("wholesale_prices", $new_wp);
					}
			}
		} elseif ($mode == 'delete' && !empty($to_delete)) {
			foreach ($to_delete as $k=>$v)
				func_delete_variant($k);
		}

		redirect('/admin/products/'.$get['2'].'/variants');
	} elseif ($section == 'variant_images') {
		if ($mode == 'upload' && !empty($new_group)) {
			$groups = $new_group;
			$tmp = $db->all("SELECT * FROM variants WHERE productid='".$get['2']."'");
			$variants = array();
			foreach ($tmp as $tmp2) {
				$tmp3 = $db->all("SELECT o.name, o.optionid, og.name as group_name, og.groupid FROM variant_items as vi INNER JOIN options AS o ON vi.optionid=o.optionid INNER JOIN option_groups AS og ON og.groupid=o.groupid WHERE vi.variantid='".$tmp2['variantid']."' ORDER BY o.orderby, o.name");
				if ($tmp3) {
					foreach ($tmp3 as $tmp4) {
						$variants[$tmp2['variantid']][$tmp4['groupid']] = $tmp4['optionid'];
					}
				}
			}

			$ids = array();
			foreach ($variants as $vid=>$g) {
				$found = true;
				foreach ($g as $k=>$v) {
					$found2 = false;
					foreach ($groups[$k] as $v2) {
						if ($v2 == $v) {
							$found2 = true;
							break;
						}
					}

					if ($found2 && $found) {
						$found = true;
					} elseif(!empty($groups[$k])) {
						$found = false;
						break;
					}
				}

				if ($found) {
					$ids[] = $vid;
				}
			}

			$files = $_FILES['userfile'];
			foreach ($files['name'] as $k=>$v) {
				if (!empty($v)) {
					foreach ($ids as $vid) {
						$new_image = array(
							'file'		=> $v,
							'size'		=> $files['size'][$k],
							'variantid'	=> $vid,
							'pos'		=> $db->field("SELECT MAX(pos) FROM variant_images WHERE variantid='".$vid."'") + 10
						);

						$imageid = $db->array2insert("variant_images", $new_image);
						$dir = SITE_ROOT . '/photos/variant/' . $vid;
						if (!is_dir($dir))
							mkdir($dir);

						$dir .= '/' . $imageid;
						if (!is_dir($dir))
							mkdir($dir);

						copy($files['tmp_name'][$k], $dir . '/' . $v);
						list($x, $y) = getimagesize($files['tmp_name'][$k]);
						$db->query("UPDATE variant_images SET x='".$x."', y='".$y."' WHERE imageid='".$imageid."'");
					}
				}
			}

		} elseif ($mode == 'update') {
			foreach ($posted_data as $k=>$v)
				$db->array2update('variant_images', $v, "imageid='".addslashes($k)."'");
		} elseif ($mode == 'delete' && !empty($to_delete)) {
			foreach ($to_delete as $k=>$v)
				func_delete_variant_image($k);
		}

		redirect('/admin/products/'.$get['2'].'/variant_images');
	} elseif ($section == 'inventory') {
		$db->query("DELETE FROM product_inventory WHERE productid='$get[2]' AND variantid=0");
		foreach ($posted_data as $k=>$v) {
			$db->query("INSERT INTO product_inventory SET productid='$get[2]', wid='$k', avail='$v'");
		}

		redirect('/admin/products/'.$get['2'].'/inventory');
	} elseif ($section == 'wholesale') {
		if ($mode == 'update') {
			if (!empty($new_wp))
				foreach ($new_wp as $v) {
					if (!empty($v['price'])) {
						$v['productid'] = $get['2'];
						$db->array2insert('wholesale_prices', $v);
					}
				}

			if (!empty($posted_data))
				foreach ($posted_data as $k=>$v)
					$db->array2update('wholesale_prices', $v, "priceid='".addslashes($k)."'");
		} elseif ($mode == 'delete' && !empty($to_delete))
			foreach ($to_delete as $k=>$v) {
				$db->query("DELETE FROM wholesale_prices WHERE priceid='".addslashes($k)."'");
			}

		redirect('/admin/products/'.$get['2'].'/wholesale');
	}
}

	if ($get['2']) {
		if ($get['2'] != 'add') {
			$product = $db->row("SELECT p.*, c.categoryid FROM products p LEFT JOIN category_products c ON c.productid=p.productid AND c.main='Y' WHERE p.productid='".$get['2']."'");
			$template['product'] = $product;
			$category_products = $db->all("SELECT * FROM category_products WHERE productid='".$get['2']."' AND main<>'Y'");
			$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/products">'.lng('Products').'</a> &gt; <a href="'.$current_location.'/admin/products/'.$product['productid'].'">'.$product['name'].'</a>';
			if (empty($get['3'])) {
				$template['location'] .= '&gt; '.lng('Product details');
			} elseif ($section == 'inventory') {
				$template['location'] .= '&gt; '.lng('Warehouses');
				$warehouses = $db->all("SELECT w.*, i.avail FROM warehouses w LEFT JOIN product_inventory i ON i.variantid=0 AND w.wid=i.wid AND i.productid='$get[2]' WHERE w.enabled=1 ORDER BY w.pos");
				if ($warehouses) {
					$template["warehouses"] = $warehouses;
				}
			} elseif ($get['3'] == 'images') {
				$template['location'] .= '&gt; '.lng('Images');
				$template['photos'] = $db->all("SELECT * FROM products_photos WHERE productid='".$get['2']."' ORDER BY pos, photoid DESC");
				$template['js'][] = 'jquery.ui.sortable';
				$template['js'][] = 'admin_product_images';
				$template['css'][] = 'admin_product_images';
			} elseif ($get['3'] == 'related') {
				$template['location'] .= '&gt; '.lng('Related products');
				$template['related_products'] = $db->all("SELECT p.productid, p.name, r.orderby FROM related_products r, products p WHERE r.productid2=p.productid AND r.productid1='".$get[2]."' ORDER BY r.orderby");
				$template['js'][] = 'admin_popup_product';
				$template['css'][] = 'admin_popup';
			} elseif ($get['3'] == 'options') {
				if ($get['4']) {
					$template['location'] .= '&gt; <a href="'.$current_location.'/admin/products/'.$product['productid'].'/options">'.lng('Products options').'</a>';
					if ($get['4'] != 'add') {
						$template['option_group'] = $db->row("SELECT * FROM option_groups WHERE groupid='".$get['4']."'");
						$template['options'] = $db->all("SELECT * FROM options WHERE groupid='".$get['4']."' ORDER BY orderby, name");
						$template['location'] .= '&gt; '.$template['option_group']['name'];
					} else
						$template['location'] .= '&gt; '.lng('Add option group');

					$template['js'][] = 'admin_product_options';
					$template['js'][] = 'admin_duplicate_row';
				} else {
					$template['location'] .= '&gt; '.lng('Products options');
					$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$get['2']."' ORDER BY orderby, name, fullname, groupid");
					if (!empty($option_groups)) {
						foreach ($option_groups as $k=>$v) {
							$option_groups[$k]['options'] = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."'");
						}

						$template['option_groups'] = $option_groups;
					}

					$tmp = $db->all("SELECT * FROM options_ex WHERE productid='".$get['2']."'");
					if ($tmp) {
						$options_ex = array();
						foreach ($tmp as $v)
							$options_ex[$v['exceptionid']][] = $v['optionid'];

						$template['options_ex'] = $options_ex;
					}
				}
			} elseif ($get['3'] == 'variants') {
				$template['location'] .= '&gt; '.lng('Products variants');
				$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$get['2']."' AND variant=1 ORDER BY orderby, name, fullname, groupid");
				$filter = array();
				if (!empty($option_groups)) {
					if (!empty($_GET['filter'])) {
						$tmp = explode(';', $_GET['filter']);
						foreach ($tmp as $v) {
							$tmp2 = explode('=', $v);
							$filter[$tmp2[0]] = $tmp2[1];
						}
					}

					foreach ($option_groups as $k=>$v) {
						$options = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, name");
						if ($options) {
							foreach ($options as $k2=>$v2) {
								if ($filter[$v['groupid']] == $v2['optionid'])
									$options[$k2]['selected'] = 'Y';
							}

							$option_groups[$k]['options'] = $options;
						}
					}

					$template['option_groups'] = $option_groups;
				} else
					redirect('/admin/products/'.$get['2']);

				if (!empty($filter))
					$template['filter'] = $filter;

				$tmp = $db->all("SELECT groupid, name FROM option_groups WHERE productid='".$get['2']."' AND variant=1 ORDER BY orderby, name");
				$groups = array();
				foreach ($tmp as $v) {
					$groups[] = array(
						'groupid'		=> $v['groupid'],
						'name'			=> $v['name'],
						'options'		=> $db->all("SELECT optionid, name FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, name")
					);
				}

				$tmp = $db->all("SELECT * FROM variants WHERE productid='".$get['2']."' ORDER BY variantid");
				$variants = array();
				foreach ($tmp as $tmp2) {
					$tmp2['options'] = $db->all("SELECT og.name, o.optionid, og.groupid, og.name FROM variant_items as vi INNER JOIN options AS o ON vi.optionid=o.optionid INNER JOIN option_groups AS og ON og.groupid=o.groupid WHERE vi.variantid='".$tmp2['variantid']."' ORDER BY og.orderby, o.orderby, o.name");
					$tmp2['wholesale'] = $db->all("SELECT * FROM wholesale_prices WHERE variantid='".$tmp2['variantid']."' ORDER BY membershipid, quantity");
					$tmp2['warehouses'] = $db->all("SELECT w.*, i.avail FROM warehouses w LEFT JOIN product_inventory i ON w.wid=i.wid AND i.productid='".$get['2']."' AND i.variantid='".$tmp2['variantid']."' WHERE w.enabled=1 ORDER BY w.pos");
					if (is_array($filter) && !empty($filter)) {
						$found = true;
						foreach ($filter as $k=>$v) {
							$found2 = false;
							foreach ($tmp2['options'] as $o) {
								if ($o['groupid'] == $k && $o['optionid'] == $v) {
									$found2 = true;
									break;
								}
							}

							if (!$found2) {
								$found = false;
								break;
							} elseif ($found2) {
								$found = true;
								break;
							}
						}

						if ($found)
							$variants[] = $tmp2;
					} else
						$variants[] = $tmp2;
				}

				if ($variants) {
					$template["memberships"] = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");
					$template['variants'] = $variants;
				}

				$template['css'][] = 'admin_product_variants';
				$template['js'][] = 'admin_product_variants';
				$template['js'][] = 'admin_duplicate_row';
			} elseif ($get['3'] == 'variant_images') {
				$template['location'] .= '&gt; '.lng('Products variants images');
				$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$get['2']."' AND variant=1 ORDER BY orderby, name, fullname, groupid");
				if (!empty($option_groups)) {
					foreach ($option_groups as $k=>$v) {
						$option_groups[$k]['options'] = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, name");
					}

					$template['option_groups'] = $option_groups;
				} else
					redirect('/admin/products/'.$get['2']);

				$variants = $db->all("SELECT * FROM variants WHERE productid='".$get['2']."' ORDER BY variantid");
				if ($variants) {
					foreach ($variants as $k=>$v) {
						$variants[$k]['options'] = $db->all("SELECT o.name, g.name as group_name FROM variant_items i, options o, option_groups g WHERE i.variantid='".$v['variantid']."' AND i.optionid=o.optionid AND g.groupid=o.groupid ORDER BY o.orderby, o.name");

						$images = $db->all("SELECT * FROM variant_images WHERE variantid='".$v['variantid']."'");
						if ($images) {
							$template['has_images'] = 'Y';
							$variants[$k]['images'] = $images;
						}
					}

					if ($template['has_images'] == 'Y')
						$template['variants'] = $variants;
				}

				$template['css'][] = 'admin_product_variant_images';
				$template['js'][] = 'admin_product_variant_images';
			} elseif ($get['3'] == 'wholesale') {
				$template["wholesale"] = $db->all("SELECT * FROM wholesale_prices WHERE productid='".$get['2']."' AND variantid='0' ORDER BY membershipid, quantity");
				$template["memberships"] = $db->all("SELECT m.*, COUNT(u.id) as users FROM memberships m LEFT JOIN users u ON u.membershipid = m.membershipid GROUP BY m.membershipid ORDER BY m.orderby, m.membership");
				$template['js'][] = 'admin_duplicate_row';
			}

			$template['head_title'] = $product['name'].' :: '.$template['head_title'];
			# Product tabs
			$html = '<ul class="admin-tabs">';
			$url = $current_location.'/admin/products/'.$get['2'];
			$html .= '<li'.(empty($get['3']) ? ' class="active"' : '' ). '><a href="'.$url.'">'.lng('Product details').'</a></li>';

			if ($warehouse_enabled)
				$html .= '<li'.(($get['3'] == 'inventory') ? ' class="active"' : '' ). '><a href="'.$url.'/inventory">'.lng('Warehouses inventory').'</a></li>';

			$html .= '<li'.(($get['3'] == 'images') ? ' class="active"' : '' ). '><a class="no-ajax" href="'.$url.'/images">'.lng('Images').'</a></li>';

			$active_modules['Product_Options'] = $active_modules['Related_Products'] = true;
			if ($active_modules['Product_Options']) {
				$html .= '<li'.(($get['3'] == 'options') ? ' class="active"' : '' ). '><a href="'.$url.'/options">'.lng('Options').'</a></li>';
				$is_variants = $db->field("SELECT COUNT(groupid) FROM option_groups WHERE productid='".$get['2']."' AND variant=1");
				if ($is_variants) {
					$html .= '<li'.(($get['3'] == 'variants') ? ' class="active"' : '' ). '><a href="'.$url.'/variants">'.lng('Variants').'</a></li>';
					$has_variants = $db->field("SELECT COUNT(variantid) FROM variants WHERE productid='".$get['2']."'");
					if ($has_variants)
						$html .= '<li'.(($get['3'] == 'variant_images') ? ' class="active"' : '' ). '><a class="no-ajax" href="'.$url.'/variant_images">'.lng('Variants Images').'</a></li>';
				}
			}

			$html .= '<li'.(($get['3'] == 'wholesale') ? ' class="active"' : '' ). '><a href="'.$url.'/wholesale">'.lng('Wholesale pricing').'</a></li>';

			if ($active_modules['Wholesale'])
				$html .= '<li'.(($get['3'] == 'wholesale') ? ' class="active"' : '' ). '><a href="'.$url.'/wholesale">'.lng('Wholesale').'</a></li>';

			if ($active_modules['Related_Products'])
				$html .= '<li'.(($get['3'] == 'related') ? ' class="active"' : '' ). '><a href="'.$url.'/related">'.lng('Related products').'</a></li>';

			if ($active_modules['Product_Reviews'])
				$html .= '<li'.(($get['3'] == 'reviews') ? ' class="active"' : '' ). '><a href="'.$url.'/reviews">'.lng('Reviews').'</a></li>';

			$html .= '</ul>';

			$template['product_tabs'] = $html;
		} else
			$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/products">'.lng('Products').'</a> &gt; '.lng('Add product');

		if (empty($get['3'])) {
			$tree = func_categories_tree(0, "title");
			$template['categories_tree'] = categories_tree_html($tree, $product['categoryid'], 0, 1, 0);
			$template['categories_tree_m'] = categories_tree_html($tree, $category_products, 0, 1, 0, 10);
		}

		$template['brands'] = $db->all("SELECT brandid, name FROM brands ORDER BY orderby, name");
		$template['page'] = get_template_contents('admin/pages/product.php');
	} else {
		$tree = func_categories_tree(0, "title");
		$template['categories_tree'] = categories_tree_html($tree, $search_products['categoryid'], 0, 1, 1);
		$template['brands'] = $db->all("SELECT brandid, name FROM brands ORDER BY orderby, name");
		$where = array();

		if (!empty($search_products['substring']))
			$where[] = "(p.name LIKE '%".$search_products['substring']."%' OR p.descr LIKE '%".$search_products['substring']."%' OR p.keywords LIKE '%".$search_products['substring']."%')";

		if ($search_products['categoryid']) {
			$category_table = ', category_products c';
			$category_query = 'c.productid=p.productid AND ';
			if (!empty($search_products['in_subcategories'])) {
				$ids = func_category_ids($search_products['categoryid']);
				$ids[] = $search_products['categoryid'];
				$where[] = "c.categoryid IN ('".implode("','", $ids)."')";
			} else {
				$where[] = "c.categoryid='".$search_products['categoryid']."'";

				if (!empty($search_products['main_category']) && empty($search_products['additional_category']))
					$where[] = "c.main='Y'";
				elseif (empty($search_products['main_category']) && !empty($search_products['additional_category']))
					$where[] = "c.main=''";
			}
		} else {
			$category_table = '';
			$category_query = '';
		}

		if (!empty($search_products['sku']))
			$where[] = "p.sku LIKE '%".$search_products['sku']."%'";

		if (!empty($search_products['productid']))
			$where[] = "p.productid='".$search_products['productid']."'";

		if (!empty($search_products['price_min']))
			$where[] = "p.price>='".$search_products['price_min']."'";

		if (!empty($search_products['price_max']))
			$where[] = "p.price<='".$search_products['price_max']."'";

		if (!empty($search_products['list_price_min']))
			$where[] = "p.list_price>='".$search_products['list_price_min']."'";

		if (!empty($search_products['list_price_max']))
			$where[] = "p.list_price<='".$search_products['list_price_max']."'";

		if (!empty($search_products['avail_min']))
			$where[] = "p.avail>='".$search_products['avail_min']."'";

		if (!empty($search_products['avail_max']))
			$where[] = "p.avail<='".$search_products['avail_max']."'";

		if (!empty($search_products['weight_min']))
			$where[] = "p.weight>='".$search_products['weight_min']."'";

		if (!empty($search_products['weight_max']))
			$where[] = "p.weight<='".$search_products['weight_max']."'";

		if (!empty($search_products['status']))
			$where[] = "p.status='".$search_products['status']."'";

		if (!empty($search_products['brandid']))
			$where[] = "p.brandid='".$search_products['brandid']."'";

		if (empty($where))
			$search_condition = '';
		else
			$search_condition = " WHERE ".$category_query.implode(" AND ", $where);

		$total_items = $db->field("SELECT COUNT(*) FROM (SELECT COUNT(p.productid) as cnt FROM products p".$category_table.$search_condition." GROUP BY p.productid) as t");
		if ($total_items > 0) {
			$objects_per_page = 50;
    	    # Navigation code
	        require SITE_ROOT . "/includes/navigation.php";
			$orderby = $_GET['sort'] ? 'p.'.str_replace("'", "", $_GET['sort']) : 'p.name';
			if ($_GET['direction'] == 1)
				$orderby .= " DESC";

			$template['products'] = $db->all("SELECT p.* FROM products p".$category_table.$search_condition." GROUP BY p.productid ORDER BY p.status DESC, ".$orderby." LIMIT $first_page, $objects_per_page");
			$template['navigation_script'] = "/admin/products?sort=".$_GET['sort'].'&direction='.$_GET['direction'];
    	}

		$template['location'] .= ' &gt; '.lng('Products');
		$template['head_title'] = lng('Products').' :: '.$template['head_title'];
		$template['page'] = get_template_contents('admin/pages/products.php');
	}

$template['css'][] = 'admin_products';
$template['js'][] = 'admin_products';