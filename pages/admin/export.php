<?php
$export_dir = SITE_ROOT . '/var/import_export';
if (!is_dir($export_dir))
	mkdir($export_dir);

if ($get['2'] == 'download') {
	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="export.csv"');
	$path = SITE_ROOT . '/var/import_export/export.csv';

	$data = file_get_contents($path);
	echo $data;
	exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if (empty($import)) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('Please, select options to export')
		);

		redirect('/admin/export');
	}

	session_write_close();
	doExport($import);
	exit;
}

function doExport($import) {
	global $db, $config, $_SESSION, $export_dir, $http_location, $_POST;
	$export_product = explode(',', $_POST['export_product']);
	$pids = array();
	if ($export_product)
		foreach ($export_product as $v) {
			if ($v)
				$pids[] = $v;
		}

	set_time_limit(86400);
	ini_set('memory_limit', '1024M');

	echo '<pre>File is currently generating. Please, wait...<br />';
	ob_flush();
	flush();

	$array = array();
	if ($import['categories']) {
		echo 'Categories';
		$array[] = array('[CATEGORIES]');
		$array[] = array('!CATEGORY_ID', '!NAME', '!DESCRIPTION', '!META_KEYWORDS', '!META_DESCRIPTION', '!META_TITLE', '!CATEGORY_PATH', '!ENABLED', '!CLEAN_URL', '!POSITION', '!ICON_URL');
		$results = $db->all("SELECT * FROM categories ORDER BY categoryid");
		if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$path = $v['title'];
            		if (!empty($v['parentid'])) {
            			$parent = $db->row("SELECT * FROM categories WHERE categoryid='".$v['parentid']."'");
            			$path = $parent['title'].' / '.$path;
	            		if (!empty($parent['parentid'])) {
	            			$parent2 = $db->row("SELECT * FROM categories WHERE categoryid='".$parent['parentid']."'");
	            			$path = $parent2['title'].' / '.$path;
	        	    		if (!empty($parent2['parentid'])) {
	    	        			$parent3 = $db->row("SELECT * FROM categories WHERE categoryid='".$parent2['parentid']."'");
		            			$path = $parent3['title'].' / '.$path;
	            			}
            			}
            		}

					$tmp = str_replace("\r\n", "<line-break>", $v['description']);
					$icon_url = '';
					$icon = $db->row("SELECT * FROM category_icons WHERE categoryid='$v[categoryid]'");
					if ($icon) {
						$icon_url = $http_location.'/photos/category/'.$icon['categoryid'].'/'.$icon['iconid'].'/'.$icon['file'];
					}

					$v['meta_description'] = str_replace("\r\n", "<line-break>", $v['meta_description']);
					$v['meta_keywords'] = str_replace("\r\n", "<line-break>", $v['meta_keywords']);
            		$array[] = array($v['categoryid'], $v['title'], $tmp, $v['meta_keywords'], $v['meta_description'], $v['meta_title'], $path, $v['enabled'], $v['cleanurl'], $v['orderby'], $icon_url);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			echo '<br />';
			$array[] = array('');
		}

		if ($import['cat_banners']) {
			if (!is_dir($export_dir."/categories_banners"))
				mkdir($export_dir."/categories_banners");

			foreach (glob($export_dir.'/categories_banners/*') as $file)
				unlink($file);

			echo 'Categories banners<br />';
			$array[] = array('[CATEGORIES BANNERS]');
			if (extension_loaded('zip'))
				$array[] = array('Images packed here '.$export_dir.'/categories_banners.zip');
			else
				$array[] = array('Images packed here '.$export_dir.'/categories_banners.tgz');

			$array[] = array('!ID', '!CATEGORY_ID', '!ALT', '!LINK', '!POSITION', '!FILE');
			$results = $db->all("SELECT * FROM category_banners ORDER BY bannerid");
            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$path = SITE_ROOT.'/photos/banners/'.$v['categoryid'].'/'.$v['bannerid'].'/'.$v['file'];
            		$new_path = $export_dir."/categories_banners/".$v['bannerid'].'_'.$v['file'];
            		copy($path, $new_path);
            		$array[] = array($v['bannerid'], $v['categoryid'], $v['alt'], $v['url'], $v['pos'], $v['bannerid'].'_'.$v['file']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			if (extension_loaded('zip')) {
				$zip = new ZipArchive();
				$zip_name = $export_dir."/categories_banners.zip";
				unlink($zip_name);
				$zip->open($zip_name, ZIPARCHIVE::CREATE);
				foreach (glob($export_dir.'/categories_banners/*') as $file) {
					$zip->addFile($file, basename($file));
				}

				$zip->close();
			} else
				exec("tar -czf ".$export_dir."/categories_banners.tgz ".$export_dir."/category_banners; rm -rf ".$export_dir."/category_banners");

			$array[] = array('');
			echo '<br />';
		}


		if ($import['brands']) {
			echo 'Brands';
			$array[] = array('[BRANDS]');
			$array[] = array('!BRAND_ID', '!NAME', '!DESCRIPTION', '!META_KEYWORDS', '!META_DESCRIPTION', '!META_TITLE', '!ACTIVE', '!CLEAN_URL', '!POSITION', '!ICON_URL');
			$results = $db->all("SELECT * FROM brands ORDER BY brandid");
            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
					$tmp = str_replace("\r\n", "<line-break>", $v['descr']);
					$icon_url = '';
					$icon = $db->row("SELECT * FROM brand_images WHERE brandid='$v[brandid]'");
					if ($icon) {
						$icon_url = $http_location.'/photos/brand/'.$icon['brandid'].'/'.$icon['imageid'].'/'.$icon['file'];
					}

					$v['meta_description'] = str_replace("\r\n", "<line-break>", $v['meta_description']);
					$v['meta_keywords'] = str_replace("\r\n", "<line-break>", $v['meta_keywords']);
	           		$array[] = array($v['brandid'], $v['name'], $tmp, $v['meta_keywords'], $v['meta_descr'], $v['meta_title'], $v['active'], $v['cleanurl'], $v['orderby'], $icon_url);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['warehouses']) {

			echo 'Warehouses';
			$array[] = array('[WAREHOUSES]');
			$array[] = array('!CODE', '!TITLE', '!ADDRESS', '!DESCRIPTION', '!ENABLED', '!POS');
			$results = $db->all("SELECT * FROM warehouses ORDER BY pos");
            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
					$tmp = str_replace("\r\n", "<line-break>", $v['descr']);
	           		$array[] = array($v['wcode'], $v['title'], $v['address'], $tmp, $v['enabled'], $v['pos']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['products']) {
			echo 'Products';
			$array[] = array('[PRODUCTS]');
			$array[] = array('!PRODUCT_ID', '!SKU', '!MAIN_CATEGORY', '!ADDITIONAL_CATEGORIES', '!BRAND_ID', '!NAME', '!DESCRIPTION', '!META_KEYWORDS', '!META_DESCRIPTION', '!META_TITLE', '!PRICE', '!LIST_PRICE', '!WEIGHT', '!IN_STOCK', '!STATUS', '!CLEAN_URL', '!KEYWORDS');
			if ($pids)
				$results = $db->all("SELECT * FROM products WHERE productid IN (".implode(',', $pids).") ORDER BY productid");
			else
				$results = $db->all("SELECT * FROM products ORDER BY productid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
					$main_category = $db->field("SELECT categoryid FROM category_products WHERE main='Y' AND productid='".$v['productid']."'");
					$categories = $db->column("SELECT categoryid FROM category_products WHERE productid='".$v['productid']."'");
					$ids = array();
					if (!empty($categories)) {
						foreach ($categories as $c) {
							$ids[] = $c;
						}
					}

					$tmp = str_replace("\r\n", "<line-break>", $v['descr']);
					$v['meta_description'] = str_replace("\r\n", "<line-break>", $v['meta_description']);
					$v['meta_keywords'] = str_replace("\r\n", "<line-break>", $v['meta_keywords']);
					$v['name'] = htmlspecialchars_decode($v['name']);
            		$array[] = array($v['productid'], $v['sku'], $main_category, implode(",", $ids), $v['brandid'], $v['name'], $tmp, $v['meta_keywords'], $v['meta_description'], $v['title_tag'], $v['price'], $v['list_price'], $v['weight'], $v['avail'], $v['status'], $v['cleanurl'], $v['keywords']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['inventory']) {
			echo 'Products inventory';
			$array[] = array('[PRODUCTS INVENTORY]');
			$array[] = array('!WAREHOUSE_CODE', '!PRODUCT_ID', '!PRODUCT_SKU', '!AVAIL');
			$results = $db->all("SELECT w.wcode, i.*, p.sku FROM warehouses w, product_inventory i, products p WHERE p.productid=i.productid AND w.wid=i.wid AND w.enabled=1 AND i.variantid=0 ORDER BY i.productid, w.pos");
            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$array[] = array($v['wcode'], $v['productid'], $v['sku'], $v['avail']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
	           	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['variant_inventory']) {
			echo 'Variants inventory';
			$array[] = array('[VARIANTS INVENTORY]');
			$array[] = array('!WAREHOUSE_CODE', '!VARIANT_ID', '!VARIANT_SKU', '!AVAIL');
			$results = $db->all("SELECT w.wcode, i.*, v.sku FROM warehouses w, product_inventory i, products p, variants v WHERE v.variantid=i.variantid AND p.productid=i.productid AND w.wid=i.wid AND w.enabled=1 ORDER BY i.variantid, w.pos");
            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$array[] = array($v['wcode'], $v['variantid'], $v['sku'], $v['avail']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
	           	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['images']) {
			if (!is_dir($export_dir."/products_images"))
				mkdir($export_dir."/products_images");

			foreach (glob($export_dir.'/products_images/*') as $file)
				unlink($file);

			echo 'Products images';
			$array[] = array('[PRODUCTS IMAGES]');
			if (extension_loaded('zip'))
				$array[] = array('Images packed here '.$export_dir.'/products_images.zip');
			else
				$array[] = array('Images packed here '.$export_dir.'/products_images.tgz');

			$array[] = array('!ID', '!PRODUCT_ID', '!ALT', '!POSITION', '!FILE');
			if ($pids)
				$results = $db->all("SELECT * FROM products_photos WHERE productid IN (".implode(',', $pids).") ORDER BY photoid");
			else
				$results = $db->all("SELECT * FROM products_photos ORDER BY photoid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$path = SITE_ROOT.'/photos/product/'.$v['productid'].'/'.$v['photoid'].'/'.$v['file'];
            		$new_path = $export_dir."/products_images/".$v['photoid'].'_'.$v['file'];
            		copy($path, $new_path);
            		$array[] = array($v['photoid'], $v['productid'], $v['alt'], $v['pos'], $v['photoid'].'_'.$v['file']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
	           	}
            }

			if (extension_loaded('zip')) {
				$zip = new ZipArchive();
				$zip_name = $export_dir."/products_images.zip";
				unlink($zip_name);
				$zip->open($zip_name, ZIPARCHIVE::CREATE);
				foreach (glob($export_dir.'/products_images/*') as $file) {
					$zip->addFile($file, basename($file));
				}

				$zip->close();
			} else
				exec("tar -czf ".$export_dir."/products_images.tgz ".$export_dir."/products_images; rm -rf ".$export_dir."/products_images");

			$array[] = array('');
			echo '<br />';
		}

		if ($import['options']) {
			echo 'Products options';
			$array[] = array('[PRODUCTS OPTIONS]');
			$array[] = array('!GROUP_ID', '!PRODUCT_ID', '!NAME', '!FULL_NAME', '!TYPE', '!VIEW_TYPE', '!POSITION', '!ENABLED', '!IS_VARIANT', '!OPTION_ID', '!NAME', '!PRICE_MODIFIER', '!PRICE_MODIFIER_TYPE', '!WEIGHT_MODIFIER', '!WEIGHT_MODIFIER_TYPE', '!POSITION', '!ENABLED');
			if ($pids)
				$results = $db->all("SELECT * FROM option_groups WHERE productid IN (".implode(',', $pids).") ORDER BY groupid");
			else
				$results = $db->all("SELECT * FROM option_groups ORDER BY groupid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
	           		$array[] = array($v['groupid'], $v['productid'], $v['name'], $v['fullname'], $v['type'], $v['view_type'], $v['orderby'], $v['enabled'], $v['variant']);
					$options = $db->all("SELECT * FROM options WHERE groupid='".$v['groupid']."' ORDER BY orderby, optionid");
		            if (!empty($options)) {
            			foreach ($options as $o) {
	           				$array[] = array('', '', '', '', '', '', '', '', '', $o['optionid'], $o['name'], $o['price_modifier'], $o['price_modifier_type'], $o['weight_modifier'], $o['weight_modifier_type'], $o['orderby'], $o['enabled']);
    	        		}
	        	    }

            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['variants']) {
			echo 'Variants';
			$array[] = array('[VARIANTS]');
			$array[] = array('!VARIANT_ID', '!PRODUCT_ID', '!TITLE', '!IN_STOCK', '!PRICE', '!WEIGHT', '!SKU', '!IS_DEFAULT', '!OPTIONS_GROUP', '!OPTION_ID', '!OPTION_NAME');
			if ($pids)
				$results = $db->all("SELECT * FROM variants WHERE productid IN (".implode(',', $pids).") ORDER BY variantid");
			else
				$results = $db->all("SELECT * FROM variants ORDER BY variantid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
	           		$array[] = array($v['variantid'], $v['productid'], $v['title'], $v['avail'], $v['price'], $v['weight'], $v['sku'], $v['def']);
					$options = $db->all("SELECT o.optionid, o.name, g.name as group_name FROM variant_items i, options o, option_groups g WHERE g.groupid=o.groupid AND i.variantid='".$v['variantid']."' AND i.optionid=o.optionid ORDER BY g.orderby, o.orderby");
					if (!empty($options)) {
						foreach ($options as $o) {
							$array[] = array('', '', '', '', '', '', '', '', '', '', $o['group_name'], $o['optionid'], $o['name']);
						}
					}

            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['variant_images']) {
			if (!is_dir($export_dir."/variants_images"))
				mkdir($export_dir."/variants_images");

			foreach (glob($export_dir.'/variants_images/*') as $file)
				unlink($file);

			echo 'Variants images';
			$array[] = array('[VARIANTS IMAGES]');
			if (extension_loaded('zip'))
				$array[] = array('Images packed here '.$export_dir.'/variants_images.zip');
			else
				$array[] = array('Images packed here '.$export_dir.'/variants_images.tgz');

			$array[] = array('!ID', '!VARIANT_ID', '!ALT', '!POSITION', '!FILE');
			if ($pids) {
				$results = $db->all("SELECT vi.* FROM variant_images vi, variants v WHERE v.variantid=vi.variantid AND v.productid IN (".implode(',', $pids).") ORDER BY vi.imageid");
			} else
				$results = $db->all("SELECT * FROM variant_images ORDER BY imageid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$path = SITE_ROOT.'/photos/variant/'.$v['variantid'].'/'.$v['imageid'].'/'.$v['file'];
            		$new_path = $export_dir."/variants_images/".$v['variantid'].'_'.$v['file'];
            		copy($path, $new_path);
            		$array[] = array($v['imageid'], $v['variantid'], $v['alt'], $v['pos'], $v['variantid'].'_'.$v['file']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			if (extension_loaded('zip')) {
				$zip = new ZipArchive();
				$zip_name = $export_dir."/variants_images.zip";
				unlink($zip_name);
				$zip->open($zip_name, ZIPARCHIVE::CREATE);
				foreach (glob($export_dir.'/variants_images/*') as $file) {
					$zip->addFile($file, basename($file));
				}

				$zip->close();
			} else
				exec("tar -czf ".$export_dir."/variants_images.tgz ".$export_dir."/variants_images; rm -rf ".$export_dir."/variants_images");

			$array[] = array('');
			echo '<br />';
		}

		if ($import['wholesale']) {
			echo 'Wholesale prices';
			$array[] = array('[WHOLESALE PRICES]');
			$array[] = array('!ID', '!MEMBERSHIP_ID', '!PRODUCT_ID', '!VARIANT_ID', '!PRICE', '!QUANTITY');
			if ($pids)
				$results = $db->all("SELECT * FROM wholesale_prices WHERE productid IN (".implode(',', $pids).") ORDER BY priceid");
			else
				$results = $db->all("SELECT * FROM wholesale_prices ORDER BY priceid");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
	           		$array[] = array($v['priceid'], $v['membershipid'], $v['productid'], $v['variantid'], $v['price'], $v['quantity']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['related']) {
			echo 'Related products';
			$array[] = array('[RELATED PRODUCTS]');
			$array[] = array('!PRODUCT_SKU', '!RELATED_PRODUCT_SKU', '!POSITION');
			if ($pids)
				$results = $db->all("SELECT * FROM related_products WHERE productid1 IN (".implode(',', $pids).") ORDER BY productid1");
			else
				$results = $db->all("SELECT * FROM related_products ORDER BY productid1");

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$parent_sku = $db->field("SELECT sku FROM products WHERE productid='$v[productid1]'");
            		$related_sku = $db->field("SELECT sku FROM products WHERE productid='$v[productid2]'");
	           		$array[] = array($parent_sku, $related_sku, $v['orderby']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		if ($import['reviews']) {
			echo 'Products reviews';
			$array[] = array('[PRODUCTS REVIEWS]');
			$array[] = array('!ID', '!PRODUCT_ID', '!NAME', '!EMAIL', '!REVIEW', '!RATE VALUE', '!IP', '!PROFILE ID', '!ENABLED');
			if (empty($products_ids)) {
				$results = $connection->executeQuery("SELECT * FROM xlite_customer_reviews ORDER BY id")->fetchAll();
			} else {
				$results = $connection->executeQuery("SELECT * FROM xlite_customer_reviews WHERE product_id IN ('".implode("', '", $products_ids)."') ORDER BY id")->fetchAll();
			}

            if (!empty($results)) {
            	foreach ($results as $k=>$v) {
            		$tmp = preg_replace("/\n/Ss", "<line-break>", $v['review']);
            		$tmp = preg_replace("/\r/Ss", "", $tmp);
	           		$array[] = array($v['id'], $v['product_id'], $v['name'], $v['email'], $tmp, $v['rate'], $v['ip'], $v['profile_id'], $v['enabled']);
            		if ($k % 10 == 0) {
    	        		echo '.';
	            		ob_flush(); flush();
            		}
            	}
            }

			$array[] = array('');
			echo '<br />';
		}

		$fp = fopen($export_dir.'/export.csv', 'w');
		foreach ($array as $v) {
			if (is_array($v)) {
				fputcsv($fp, $v);
			}
		}

		fclose($fp);
		echo '<br />Data exported<br /><br /><a href="'.$http_location.'/admin/export/download" target="_blank">Download</a><br /><br /><a href="'.$http_location.'/admin/export">Go back</a>';
		exit;
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

$template['head_title'] = lng('Export catalog').' :: '.$template['head_title'];
$template['location'] .= ' &gt; '.lng('Export catalog');

$template['page'] = get_template_contents('admin/pages/export.php');

$template['css'][] = 'admin_import';
$template['js'][] = 'admin_import';