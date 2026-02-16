<?php
$category = $db->row("SELECT * FROM categories WHERE enabled=1 AND categoryid='".addslashes($categoryid)."'");
if (!$category)
	redirect('/');

$template['head_title'] = $category['title'].'. '.$template['head_title'];
$tmp = array('parentid'	=> $category['parentid']);
$cats = array();
while ($tmp['parentid'] != 0) {
	$tmp = $db->row("SELECT * FROM categories WHERE categoryid='".$tmp['parentid']."'");
	$cats[] = $tmp;
}

if ($cats) {
	krsort($cats);
	foreach ($cats as $v)
		$template['bread_crumbs'][] = array($v['cleanurl'] ? '/'.$v['cleanurl'] : '/'.$v['categoryid'], $v['title']);
}

$parentid = $tmp['categoryid'] ? $tmp['categoryid'] : $categoryid;
$template['parentid'] = $parentid;
q_load('category');
$template['categories_menu'] = func_categories_tree($parentid);

$template['bread_crumbs'][] = array('', $category['title']);
$template['category'] = $category;

$template['category_icon'] = $db->row("SELECT * FROM category_icons WHERE categoryid='".addslashes($categoryid)."'");

$subcategories = $db->all("SELECT * FROM categories WHERE enabled=1 AND parentid='".addslashes($categoryid)."' ORDER BY enabled DESC, orderby, title");
if ($subcategories) {
	foreach ($subcategories as $k=>$v)
		$subcategories[$k]['icon'] = $db->row("SELECT * FROM category_icons WHERE categoryid=".$v['categoryid']);

	$template['subcategories'] = $subcategories;
}

$search_condition = array();
$search_condition['per_page'] = '16';
$search_condition['featured'] = $categoryid;
$search_condition['sort'] = '';

include SITE_ROOT . '/includes/search.php';
$template['featured_products'] = $products;

$search_condition = array();
$search_condition['per_page'] = '16';
$search_condition['categoryid'] = $categoryid;
$search_condition['orderby'] = 'c.orderby, p.name';
if (!$_GET['sort']) {
	$_GET['sort'] = 1;
	$_GET['direction'] = 1;
	$is_sort = false;
} else
	$is_sort = true;

$search_condition['sort'] = $_GET['sort'];

include SITE_ROOT . '/includes/search.php';

$template['category_products'] = $products;

if ($category['cleanurl']) {
	$template["navigation_script"] = $current_location."/".$category['cleanurl']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].'&';
	$template["sort_by_script"] = $current_location."/".$category['cleanurl']."?";
} else {
	$template["navigation_script"] = $current_location."/".$categoryid."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].'&';
	$template["sort_by_script"] = $current_location."/".$categoryid."?";
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

	if ($category['cleanurl']) {
		$template["navigation_script"] = $current_location."/".$category['cleanurl']."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].$url_add.'&';
		$template["sort_by_script"] = $current_location."/".$category['cleanurl']."?".$url_add.'&';
	} else {
		$template["navigation_script"] = $current_location."/".$categoryid."?sort=".$_GET['sort'].'&direction='.$_GET['direction'].$url_add.'&';
		$template["sort_by_script"] = $current_location."/".$categoryid."?".$url_add.'&';
	}
}

$sort_by = array(
	'1'		=> 'Recommended',
	'2'		=> 'Name',
	'3'		=> 'Price',
	'4'		=> 'Bestsellers',
	'5'		=> 'Most viewed',
	'6'		=> 'Newest'
);

$template['sort_by'] = $sort_by;

$template['products'] = $products;
$template['per_row'] = 4;
$template['products_results_html'] = get_template_contents('common/products_results.php');
if ($is_ajax && ($_GET['page'] || $is_sort || $_GET['filtered'])) {
	exit($template['products_results_html']);
}

$banners = $db->all("SELECT * FROM category_banners WHERE categoryid='".addslashes($categoryid)."' ORDER BY pos, bannerid");
if ($banners) {
	foreach ($banners as $k=>$v) {
		$banners[$k]['image_url'] = $current_location.'/photos/banners/'.$v['categoryid'].'/'.$v['bannerid'].'/'.$v['file'];
	}

	$template['banners'] = $banners;
}

$template['page'] = get_template_contents('category/body.php');
