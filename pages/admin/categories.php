<?php
q_load('category');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($action == 'featured_products') {
	    if ($mode == 'update') {
	        if (is_array($posted_data)) {
    	        foreach ($posted_data as $productid => $v) {
        	        $query_data = array(
            	        'enabled' => (!empty($v['enabled']) ? 1 : 0),
                	    'orderby' => intval($v['orderby']),
	                );
    	            $db->array2update("featured_products", $query_data, "productid='".addslashes($productid)."' AND categoryid='".$get['2']."'");
        	    }
	        }
    	} elseif ($mode == 'delete' && !empty($to_delete)) {
			foreach ($to_delete as $productid => $v)
				$db->query("DELETE FROM featured_products WHERE productid='".addslashes($productid)."' AND categoryid='$get[2]'");
	    } elseif ($mode == 'add' && intval($newproductid) > 0) {
    	    $newenabled = (!empty($newenabled) ? 1 : 0);
        	if (empty($neworderby))
            	$neworderby = $db->field("SELECT MAX(orderby) FROM featured_products WHERE categoryid='$get[2]'") + 10;

	        $db->query("REPLACE INTO featured_products (productid, orderby, enabled, categoryid) VALUES ('".addslashes($newproductid)."','".addslashes($neworderby)."','".addslashes($newenabled)."', '$get[2]')");
	    }

		redirect('/admin/categories'.($get['2'] ? '/'.$get['2'] : '').'#featured');
	} else {
		if ($mode == 'delete' && $cat)
			func_delete_category($cat);
		else {
			foreach ($posted_data as $k=>$v) {
				if (!$v['enabled'])
					$v['enabled'] = 0;

				$db->array2update("categories", $v, "categoryid='".addslashes($k)."'");
			}
		}

		redirect('/admin/categories'.($get['2'] ? '/'.$get['2'] : ''));
	}
}

if ($get['2']) {
	$parent_query = " WHERE parentid='".$get['2']."'";
	$category = $db->row("SELECT * FROM categories WHERE categoryid='".$get['2']."'");
	$template['location'] .= ' &gt; '.$category['title'];
	$template['head_title'] = lng('Category').' '.$category['title'].' :: '.$template['head_title'];
	$parentid = $category['parentid'];
	$category_location = '<a href="/admin/categories/" class="ajax_link">'.lng('Categories').'</a>';
	$tmp = array();
	while ($parent = $db->row("SELECT * FROM categories WHERE categoryid='".$parentid."'")) {
		$parentid = $parent['parentid'];
		$tmp[] = ' &gt; <a href="/admin/categories/'.$parent['categoryid'].'" class="ajax_link">'.$parent['title'].'</a>';
	}

	if (!empty($tmp)) {
		krsort($tmp);
		$category_location .= implode('', $tmp);
	}

	$category_location .= ' &gt; '.$category['title'];
	$template['category_location'] = $category_location;
	$template['category'] = $category;
} else {
	$parent_query = " WHERE parentid='0'";
	$template['location'] .= ' &gt; '.lng('Categories');
	$template['head_title'] = lng('Categories').' :: '.$template['head_title'];
}

if ($get['2'])
	$cat = $get['2'];
else
	$cat = 0;

$template['featured_products'] = $db->all("SELECT * FROM products p, featured_products fp WHERE fp.productid=p.productid AND fp.categoryid='".$cat."' ORDER BY fp.orderby");

$categories = $db->all("SELECT * FROM categories ".$parent_query." ORDER BY enabled DESC, orderby, title");
if ($categories)
	$template['categories'] = $categories;

$template['page'] = get_template_contents('admin/pages/categories.php');

$template['css'][] = 'admin_categories';
$template['js'][] = 'admin_categories';
$template['js'][] = 'admin_popup_product';

$template['css'][] = 'admin_popup';
