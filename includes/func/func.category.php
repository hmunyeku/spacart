<?php
function func_cleanurl_exists($cleanurl, $categoryid) {
	global $db;

	if ($categoryid)
		$category_query = " AND categoryid<>".$categoryid;

	return $db->field("SELECT COUNT(categoryid) FROM categories WHERE cleanurl='$cleanurl'".$category_query);
}

function func_recalculate_subcount() {
	global $db;

	$categories = $db->all("SELECT categoryid FROM categories");
    foreach ($categories as $k=>$v) {
    	$subcategories = $db->field("SELECT COUNT(categoryid) FROM categories WHERE parentid='$v[categoryid]'");
    	$products = $db->field("SELECT COUNT(productid) FROM category_products WHERE categoryid='$v[categoryid]'");
    	$products_global = $products;
    	$parents = array($v['categoryid']);
		while ($parents && $sub = $db->all("SELECT categoryid FROM categories WHERE parentid IN (".implode(',', $parents).")")) {
			$parents = array();
			foreach ($sub as $v2) {
				$parents[] = $v2['categoryid'];
		    	$products_global += $db->field("SELECT COUNT(productid) FROM category_products WHERE categoryid='$v2[categoryid]'");
	    	}
    	}

		// products/subcategories counts are calculated dynamically by the VIEW - no update needed
   	}
}

function func_categories_tree($cat = 0, $orderby = "orderby, title") {
	global $db, $lng;

	if (ADMIN_AREA)
		$categories = $db->all("SELECT * FROM categories WHERE parentid='".$cat."' ORDER BY ".$orderby);
	else
		$categories = $db->all("SELECT * FROM categories WHERE parentid='".$cat."' AND enabled=1 ORDER BY ".$orderby);

	if (empty($categories))
		return;

	foreach ($categories as $k=>$v) {
		$categories[$k]['subcategories'] = func_categories_tree($v['categoryid'], $orderby);
	}

	return $categories;
}

function func_category_ids($cat = 0) {
	global $db, $lng;

	$categories = $db->all("SELECT categoryid FROM categories WHERE parentid='".$cat."'");
	if (empty($categories))
		return array();

	$ids = array();
	foreach ($categories as $k=>$v) {
		$ids[] = $v['categoryid'];
		$ids = array_merge(func_category_ids($v['categoryid']), $ids);
	}

	return $ids;
}

function categories_tree_html($tree, $selected, $depth = 0, $is_new = 0, $empty = 0, $multiple = 0) {
	global $get;
	if ($is_new) {
		if ($multiple)
			$html = '<select name="categories[]" multiple size="'.$multiple.'">';
		else
			$html = '<select name="categoryid">';

		if ($empty && !$multiple)
			$html .= "<option value=''>".lng('All')."</option>";
		elseif ($get['1'] == 'category')
			$html .= "<option value='0'>".lng('Root')."</option>";
	}

	if ($tree) {
		foreach ($tree as $k=>$v) {
			$html .= '<option value="'.$v['categoryid'].'"'.is_category_selected($v['categoryid'], $selected).'>';
			for ($i = 0; $i < $depth; $i++)
				$html .= '- &nbsp; ';

			$html .= $v['title'].'</option>';
			$html .= categories_tree_html($v['subcategories'], $selected, ($depth+1));
		}
	}

	if ($is_new)
		$html .= '</select>';

	return $html;
}

function is_category_selected($cat, $selected) {
	if (is_array($selected)) {
		$found = false;
		foreach ($selected as $v)
			if ($v == $cat || $v['categoryid'] == $cat) {
				$found = true;
				break;
			}

		if ($found)
			return ' selected';
	} elseif ($cat == $selected)
		return ' selected';
}

function func_delete_category($cat) {
	global $db;

	$tree = func_categories_tree($cat);
	if (!empty($tree)) {
		foreach ($tree as $v) {
			func_delete_category($v['categoryid']);
		}
	}

	$banners = $db->all("SELECT * FROM category_banners WHERE categoryid='$cat'");
	if (!empty($banners))
		foreach ($banners as $k=>$v) {
			unlink(SITE_ROOT.'/photos/banners/'.$cat.'/'.$v['bannerid'].'/'.$v['file']);
	 	}

	$db->query("DELETE FROM category_banners WHERE categoryid=".$cat);
	$db->query("DELETE FROM llx_categorie WHERE rowid=".$cat);
	$db->query("DELETE FROM category_products WHERE categoryid=".$cat);
	$icon = $db->row("SELECT file FROM category_icons WHERE categoryid=".$cat);
	if ($icon) {
		$db->query("DELETE FROM category_icons WHERE categoryid=".$cat);
		unlink(SITE_ROOT . '/photos/category/'.$cat.'/'.$icon['iconid'].'/'.$icon['file']);
	}
}