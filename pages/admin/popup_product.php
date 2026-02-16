<?php
if ($get['2']) {
	$products = $db->all("SELECT p.productid, p.name FROM products p, category_products c WHERE c.categoryid='".$get['2']."' AND c.productid=p.productid GROUP BY p.productid ORDER BY c.orderby, p.name");
	if ($products) {
		echo '<select multiple size="20">';
		foreach ($products as $k=>$v) {
			echo '<option value="'.$v['productid'].'">'.$v['name'].'</option>';
		}

		exit('<select>');
	} else
		exit(lng('No products under this category'));
} else {
	q_load('category');
	$tree = func_categories_tree(0, 'title');
	$template['categories_tree'] = categories_tree_html($tree, 0, 0, 1, 0, 20);
	exit(get_template_contents('admin/common/popup_product.php').'-');
}