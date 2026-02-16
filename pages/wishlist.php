<?php
if (!$login)
	redirect('/');

if ($_GET['add']) {
	if (!$db->field("SELECT wlid FROM wishlist WHERE productid='".addslashes($_GET['add'])."' AND userid='".$login."'")) {
		$insert = array(
			'userid'	=> $login,
			'productid'	=> $_GET['add'],
			'date'		=> time()
		);

		$db->array2insert('wishlist', $insert);
	}

	exit;
}

if ($get['1'] == 'remove') {
	$db->query("DELETE FROM wishlist WHERE wlid='".addslashes($get['2'])."' AND userid='".$login."'");
	if ($is_ajax) {
		exit;
	} else
		redirect('/wishlist');
} elseif ($get['1'] == 'clear') {
	$db->query("DELETE FROM wishlist WHERE userid='".$login."'");
	if ($is_ajax) {
		exit;
	} else
		redirect('/wishlist');
}

$wishlist = $db->all("SELECT * FROM wishlist WHERE userid='".$login."' ORDER BY date DESC");
if ($wishlist) {
	foreach ($wishlist as $k=>$v) {
		$wishlist[$k]['product'] = $db->row("SELECT * FROM products WHERE productid='".$v['productid']."' AND status IN ('1', '3')");
		if ($wishlist[$k]['product']) {
			$wishlist[$k]['product']['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$wishlist[$k]['product']['photoid']."'");
		} else {
			$db->query("DELETE FROM wishlist WHERE productid='".$v['productid']."' AND userid='$login'");
			unset($wishlist[$k]);
		}
	}

	$template['wishlist'] = $wishlist;
}

$template['page'] = get_template_contents('wishlist/body.php');

if ($is_ajax) {
	$result = array($template['page'], $page_title, $template['bread_crumbs_html'], $get['0'], $template['parentid']);
	exit(json_encode($result));
}
