<?php
q_load('product');
$product = func_select_product($get['1']);
if (!$product)
	redirect('/');

$db->query("INSERT INTO products_stats (productid, views_stats) VALUES ('$product[productid]', 1) ON DUPLICATE KEY UPDATE views_stats=views_stats+1");
$template['photos'] = $db->all("SELECT * FROM products_photos WHERE productid=".$get['1']." ORDER BY pos, photoid DESC");
$template['product'] = $product;

exit(get_template_contents('common/popup_product.php'));
