<?php
if (!empty($get['1'])) {
	$brand = $db->row("SELECT b.*, i.imageid, i.file, i.x, i.y FROM brands b LEFT JOIN brand_images i ON b.brandid=i.brandid WHERE (b.brandid='".$get['1']."' OR b.cleanurl='".$get['1']."') AND b.active='Y'");
	if (empty($brand)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('Brand not found')
		);

		redirect('/brands');
	}

	$template['head_title'] = $brand['name'].'. '.$template['head_title'];
	$template['bread_crumbs'][] = array('/brands', lng('Brands'));
	$template['bread_crumbs'][] = array('', $brand['name']);
	$search_condition = array();
	$search_condition['per_page'] = '16';
	$search_condition['brandid'] = $brand['brandid'];
	$search_condition['orderby'] = 'p.name';

	$sort_by = array(
		'2'		=> 'Name',
		'3'		=> 'Price',
		'4'		=> 'Bestsellers',
		'5'		=> 'Most viewed',
		'6'		=> 'Newest'
	);

	$template['sort_by'] = $sort_by;

	if (!$sort_by[$_GET['sort']]) {
		$_GET['sort'] = 2;
		$is_sort = false;
	} elseif (!$_GET['sort']) {
		$_GET['sort'] = 2;
		$_GET['direction'] = 1;
		$is_sort = false;
	} else
		$is_sort = true;

	$search_condition['sort'] = $_GET['sort'];

	include SITE_ROOT . '/includes/search.php';

	$template['category_products'] = $products;

	if ($brand['cleanurl']) {
		$template["navigation_script"] = $current_location."/brands/".$brand['cleanurl']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].'&';
		$template["sort_by_script"] = $current_location."/brands/".$brand['cleanurl']."?";
	} else {
		$template["navigation_script"] = $current_location."/brands/".$brand['brandid']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].'&';
		$template["sort_by_script"] = $current_location."/brands/".$brand['brandid']."?";
	}

if ($_GET['filter']) {
	$url_add = '';
	foreach ($_GET['filter'] as $k=>$v) {
		if (is_array($v)) {
			foreach ($v as $k2=>$v2) {
				if ($k == 'attr') {
					foreach ($v2 as $v3)
						$url_add .= '&filter['.$k.']['.$k2.'][]='.$v3;
				} else
					$url_add .= '&filter['.$k.']['.$k2.']='.$v2;
			}
		} else
			$url_add .= '&filter['.$k.']='.$v;
	}

	if ($brand['cleanurl']) {
		$template["navigation_script"] = $current_location."/brands/".$brand['cleanurl']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].$url_add.'&';
		$template["sort_by_script"] = $current_location."/brands/".$brand['cleanurl']."?".$url_add.'&';
	} else {
		$template["navigation_script"] = $current_location."/brands/".$brand['brandid']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].$url_add.'&';
		$template["sort_by_script"] = $current_location."/brands/".$brand['brandid']."?".$url_add.'&';
	}
}
	$template['products'] = $products;
	$template['per_row'] = 4;
	$template['products_results_html'] = get_template_contents('common/products_results.php');
	if ($is_ajax && ($_GET['page'] || $is_sort || $_GET['filtered'])) {
		exit($template['products_results_html']);
	}

	$template['brand_products'] = $products;
	$template['css'][] = 'products';
	$template['js'][] = 'products';
	$template['css'][] = 'popup';
	$template['js'][] = 'popup';
	$template['js'][] = 'jquery.zoom.min';

	$brand["descr"] = func_eol2br($brand["descr"]);
	$template["brand"] = $brand;
} else {
	$template['head_title'] = lng('Brands').'. '.$template['head_title'];
	$template['bread_crumbs'][] = array('', lng('Brands'));
	if (!empty($_GET['archive'])) {
		$tmp = explode('/', $_GET['archive']);
		$date_condition = " AND b.date>'".mktime(0,0,0,$tmp['0'],1,$tmp['1'])."' AND b.date<'".mktime(0,0,0,$tmp['0'],cal_days_in_month(CAL_GREGORIAN, $tmp['0'], $tmp['1']),$tmp['1'])."'";
	}

	$total_items = $db->field("SELECT COUNT(*) FROM brands b WHERE active='Y'$date_condition");
	if ($total_items > 0) {
		$objects_per_page = 10;
		require SITE_ROOT."/includes/navigation.php";

		$template["navigation_script"] = $current_location."/brands/?";

		$brands = $db->all("SELECT b.*, i.imageid, i.file, i.x, i.y FROM brands b LEFT JOIN brand_images i ON b.brandid=i.brandid WHERE b.active='Y'$date_condition ORDER BY b.orderby, b.name LIMIT $first_page, $objects_per_page");
		$template["brands"] = $brands;
	}
}

$template['css'][] = 'brands';
$template['page'] = get_template_contents('brands/body.php');
