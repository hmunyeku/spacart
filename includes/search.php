<?php
if (!$search_condition['per_page'])
	$search_condition['per_page'] = 16;

$objects_per_page = $search_condition['per_page'];

if ($search_condition['featured']) {
	$products = $db->all("SELECT p.* FROM products p, featured_products fp WHERE fp.productid=p.productid AND fp.categoryid=".$search_condition['featured']." AND fp.enabled=1 AND p.status=1 ORDER BY fp.orderby LIMIT ".$objects_per_page);
} else {
	$conditions = array();
	$conditions[] = "p.status=1";
	if ($search_condition['substring']) {
		$search_substring = addslashes($search_condition['substring']);
		$conditions[] = "(p.name LIKE '%".$search_substring."%' OR p.descr LIKE '%".$search_substring."%' OR p.keywords LIKE '%".$search_substring."%')";
	}

	$from_tbls = '';
	$group_by = '';
	if ($search_condition['categoryid']) {
		$from_tbls .= ' category_products c, ';
		if ($categoryid)
			$conditions[] = "c.categoryid='".$categoryid."'";
		else
			$conditions[] = "c.categoryid='".$search_condition['categoryid']."'";

		$conditions[] = "c.productid=p.productid";
	}

	$group_by = ' GROUP BY p.productid ';
	$from_tbls .= 'products p ';
	if ($search_condition['brandid']) {
		$conditions[] = "p.brandid='".addslashes($search_condition['brandid'])."'";
	}

	$inner_join = '';
	if ($_GET['filter']) {
		$filter = $_GET['filter'];
		if ($filter['brandid'])
			$conditions[] = "p.brandid='".addslashes($filter['brandid'])."'";

		if ($filter['price']) {
			$tmp = explode('-', $filter['price']);
			if ($tmp['1'] == 'More')
				$tmp['1'] = 9999999999;

			$conditions[] = "p.price>='".addslashes($tmp['0'])."'";
			$conditions[] = "p.price<='".addslashes($tmp['1'])."'";
		}

		if ($filter['attr']) {
			$i = 0;
			foreach ($filter['attr'] as $k=>$v) {
				$i++;
				$k = str_replace('"', '&quot;', $k);
				$k = str_replace('&', '&amp;', addslashes($k));

				$values = array();
				foreach ($v as $v2) {
					$v2 = str_replace('"', '&quot;', $v2);
					$ids[] = str_replace('&', '&amp;', addslashes($v2));
				}

				$inner_join .= " INNER JOIN option_groups av_".$i." ON av_".$i.".productid=p.productid AND av_".$i.".name='".$k."'";
				$inner_join .= " INNER JOIN options ao_".$i." ON ao_".$i.".groupid=av_".$i.".groupid AND ao_".$i.".name IN ('".implode("', '", $ids)."') ";
			}

			$from_tbls .= $inner_join;
		}
	}

	if ($conditions)
		$where = " WHERE ".implode(' AND ', $conditions);

	$total_items = $db->field("SELECT COUNT(*) FROM (SELECT COUNT(p.productid) as cnt FROM ".$from_tbls.$where.$group_by.") as t");
	if ($total_items > 0) {
		$template['total_items'] = $total_items;
		require SITE_ROOT."/includes/navigation.php";
		if ($_GET['sort'] == 1) {
			$orderby = 'c.orderby'.($_GET['direction'] == 2 ? " DESC" : '').', p.name'.($_GET['direction'] == 2 ? " DESC" : '');
		} elseif ($_GET['sort'] == 2) {
			$orderby = 'p.name'.($_GET['direction'] == 2 ? " DESC" : '');
		} elseif ($_GET['sort'] == 3) {
			$orderby = 'p.price'.($_GET['direction'] == 2 ? " DESC" : '');
		} elseif ($_GET['sort'] == 4) {
			$orderby = 'p.sales_stats'.($_GET['direction'] == 1 ? " DESC" : '');
		} elseif ($_GET['sort'] == 5) {
			$orderby = 'p.views_stats'.($_GET['direction'] == 1 ? " DESC" : '');
		} elseif ($_GET['sort'] == 6) {
			$orderby = 'p.add_date'.($_GET['direction'] == 1 ? " DESC" : '');
		}

		if (!$orderby)
			$orderby = 'p.name';

		$products = $db->all("SELECT p.* FROM ".$from_tbls.$where.$group_by." ORDER BY ".$orderby." LIMIT ".$first_page.", ".$objects_per_page);
	} else
		$products = array();
}

foreach ($products as $k=>$v) {
	if ($v['photoid'])
		$products[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
}

if ($_GET['load_filter'] && !$search_condition['featured']) {
	if (!$products)
		exit('<center><br />'.lng('No products found').'</center>');

	$all_products = $db->all("SELECT p.* FROM ".$from_tbls.$where.$group_by." ORDER BY ".$orderby);
	$brands = array();
	$filter_prices = array(
		'0'		=> '50',
		'50'	=> '100',
		'100'	=> '500',
		'500'	=> '1000',
		'1000'	=> '5000',
		'5000'	=> ''
	);
	$prices = array();
	$options = array();
	foreach ($all_products as $k=>$v) {
		if ($v['brandid'])
			$brands[$v['brandid']]++;

		$option_groups = $db->all("SELECT * FROM option_groups WHERE productid='".$v['productid']."' ORDER BY orderby, name, fullname, groupid");
		if ($option_groups) {
			foreach ($option_groups as $v2) {
				$tmp = $db->all("SELECT * FROM options WHERE groupid='".$v2['groupid']."' ORDER BY orderby, name");
				if ($tmp) {
					foreach ($tmp as $v3) {
						if (!$options[$v2['name']][$v3['name']]) {
							$options[$v2['name']][$v3['name']] = array(
								'groupid'	=> $v2['groupid'],
								'optionid'	=> $v3['optionid'],
								'cnt'		=> 1
							);
						} else
							$options[$v2['name']][$v3['name']]['cnt']++;
					}
				}
			}
		}

		foreach ($filter_prices as $k2=>$v2) {
			if (!$v2)
				$v2 = 999999999999;

			if ($v['price'] >= $k2 && $v['price'] <= $v2) {
				if ($v2 == '999999999999')
					$v2 = 'More';

				$prices[$k2.'-'.$v2]++;
			}
		}

		$tmp = array();
		foreach ($filter_prices as $k2=>$v2) {
			if ($prices[$k2.'-'.$v2])
				$tmp[$k2.'-'.$v2] = $prices[$k2.'-'.$v2]++;
		}

		$prices = $tmp;
	}

	$template['options'] = $options;
	if ($brands) {
		$tmp = array();
		foreach ($brands as $k=>$v) {
			$brand = $db->row("SELECT * FROM brands WHERE brandid='".addslashes($k)."'");
			$tmp[] = array(
				'id'	=> $k,
				'name'	=> $brand['name'],
				'brand'	=> $brand,
				'cnt'	=> $v
			);
		}

		$brands = $tmp;
		function brands_sort($a, $b) {
	    	return strcmp($a["name"], $b["name"]);
		}

		usort($brands, "brands_sort");
	}

	$template['brands'] = $brands;
	$template['prices'] = $prices;
	$filter_url = str_replace('&load_filter=1', '', $_SERVER['REQUEST_URI']);
	if ($get['0'] == 'search') {
		$template['filter_url'] = '/search?filtered=1&q='.$search_condition['substring'];
		$template['reset_filter_url'] = '/search?q='.$search_condition['substring'];
	} elseif ($get['0'] == 'category') {
		$cleanurl = $db->field("SELECT cleanurl FROM categories WHERE categoryid='".$categoryid."'");
		if ($cleanurl) {
			$template['filter_url'] = '/'.$cleanurl.'?filtered=1&';
			$template['reset_filter_url'] = '/'.$cleanurl;
		} else {
			$template['filter_url'] = '/'.$categoryid.'?filtered=1&';
			$template['reset_filter_url'] = '/'.$categoryid;
		}
	} elseif ($get['0'] == 'brands') {
		if ($brand['cleanurl']) {
			$template['filter_url'] = '/brands/'.$brand['cleanurl'].'?filtered=1&';
			$template['reset_filter_url'] = '/brands/'.$brand['cleanurl'];
		} else {
			$template['filter_url'] = '/brands/'.$brand['brandid'].'?filtered=1&';
			$template['reset_filter_url'] = '/brands/'.$brand['brandid'];
		}
	}

	if ($filter) {
		if ($filter['brandid'])
			$filter['brand'] = $db->row("SELECT * FROM brands WHERE brandid='".addslashes($filter['brandid'])."'");

		$template['filter'] = $filter;
	}

	$html = get_template_contents('common/filter.php');
	exit($html);
}