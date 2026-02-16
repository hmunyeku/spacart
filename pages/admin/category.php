<?php
q_load('category');

if ($get['3'] == 'delete') {
	$parentid = $db->field("SELECT parentid FROM categories WHERE rowid='".$get['2']."'");
	func_delete_category($get['2']);
	redirect('/admin/categories/'.$parentid);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'move') {
		if ($categoryid == $get['2'])
			$_SESSION['alerts'][] = array(
				'type'		=> 'i',
				'content'	=> 'You cannot move category to itself.'
			);
		else
			$db->query("UPDATE llx_categorie SET fk_parent='".$categoryid."' WHERE rowid='".$get['2']."'");

		redirect('/admin/category/'.$get['2']);
	} elseif ($mode == 'category_products') {
		foreach ($posted_data as $k=>$v) {
			$db->array2update("category_products", $v, "categoryid='".$get['2']."' AND productid='".$k."'");
		}

		redirect('/admin/category/'.$get['2'].'/products');
	} elseif ($get['3'] == 'banners') {
		$userfile = $_FILES['userfile'];
		if ($mode == "add" && !empty($userfile['name'])) {
			if (empty($new_pos))
				$new_pos = $db->field("SELECT MAX(pos) FROM category_banners WHERE rowid='".$get['2']."'")+10;

			$dir = SITE_ROOT . "/photos/banners/".$get['2'];
			if (!is_dir($dir))
				mkdir($dir);

			$new_banner = array(
				'categoryid'	=> $get['2'],
				'file'			=> $userfile['name'],
				'size'			=> $userfile['size'],
				'alt'			=> $new_alt,
				'url'			=> $new_url,
				'pos'			=> $new_pos
			);

			$bannerid = $db->array2insert("category_banners", $new_banner);
			$dir .= '/'.$bannerid;
			mkdir($dir);
			copy($userfile['tmp_name'], $dir.'/'.$userfile['name']);
			list($width, $height) = getimagesize($dir.'/'.$userfile['name']);

			$update = array(
				'x'	=> $width,
				'y'	=> $height
			);

			$db->array2update("category_banners", $update, "bannerid='".$bannerid."'");
		} elseif ($mode == "delete" and !empty($to_delete)) {
			foreach ($to_delete as $k=>$v) {
				$banner_file = $db->field("SELECT file FROM category_banners WHERE bannerid='$k'");
				unlink(SITE_ROOT.'/photos/banners/'.$get['2'].'/'.$k.'/'.$banner_file);
				$db->query("DELETE FROM category_banners WHERE bannerid='$k'");
		 	}
		} elseif ($mode == "update" && !empty($to_update)) {
			foreach ($to_update as $k=>$v) {
				$db->array2update("category_banners", $v, "bannerid='$k'");
			}
		}

		redirect('/admin/category/'.$get['2'].'/banners');
	}

	if (empty($title)) {
		$_SESSION['alerts'] = array(
			'type'		=> 'e',
			'content'	=> lng('Category title cannot be empty')
		);
		redirect('/admin/category/'.$get['2']);
	}

	# Check for cleanurl
	if ($cleanurl) {
		$cleanurl = trim($cleanurl);
		if (func_cleanurl_exists($cleanurl, $get['2'])) {
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Such clean URL already exist.')
			);

			$_SESSION['saved_data'] = $_POST;
			header('where_redirect: '.'/admin/category?parentid='.$parentid);
			redirect('/admin/category?parentid='.$parentid);
		} elseif (!func_check_cleanurl($cleanurl)) {
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".".')
			);

			$_SESSION['saved_data'] = $_POST;
			redirect('/admin/category?parentid='.$parentid);
		}
	}

	if ($get['2'])
		$categoryid = $get['2'];
	else
		$categoryid = $db->array2insert("categories", array('parentid'	=> $parentid, 'orderby' => $orderby ? $orderby : ($db->field("SELECT MAX(orderby) FROM categories WHERE parentid='$parentid'") + 10)));

	$category_array = array(
		'title'				=> $title,
		'description'		=> htmlspecialchars_decode($description),
		'meta_title'		=> $meta_title,
		'meta_keywords'		=> $meta_keywords,
		'meta_description'	=> $meta_description,
		'cleanurl'	=> $cleanurl,
		'orderby'	=> $orderby,
		'enabled'	=> $enabled
	);

	$db->array2update("categories", $category_array, "categoryid='".$categoryid."'");

	if ($delete_icon) {
		$icon = $db->row("SELECT * FROM category_icons WHERE rowid='".$categoryid."'");
		$dir = SITE_ROOT . '/photos/category/'.$categoryid.'/'.$icon['iconid'];
		unlink($dir.'/'.$icon['file']);
		rmdir($dir);
		$db->query("DELETE FROM category_icons WHERE rowid='".$categoryid."'");
	}

	if ($_FILES)
		foreach ($_FILES as $k=>$v) {
			if ($v['tmp_name']) {
				$icon = $db->row("SELECT * FROM category_icons WHERE rowid='".$categoryid."'");
				$dir = SITE_ROOT . '/photos/category/'.$categoryid.'/'.$icon['iconid'];
				unlink($dir.'/'.$icon['file']);
				rmdir($dir);
				$db->query("DELETE FROM category_icons WHERE rowid='".$categoryid."'");
				$dir = SITE_ROOT . '/photos/category/'.$categoryid;
				if (!is_dir($dir))
					mkdir($dir);

				$new_photo = array(
					'categoryid'	=> $categoryid,
					'file'			=> $v['name'],
					'size'			=> $v['size']
				);

				$iconid = $db->array2insert("category_icons", $new_photo);
				$dir .= '/'.$iconid;
				if (!is_dir($dir))
					mkdir($dir);

				copy($v['tmp_name'], $dir.'/'.$v['name']);
				list($width, $height) = getimagesize($dir.'/'.$v['name']);

				$update = array(
					'x'	=> $width,
					'y'	=> $height
				);

				$db->array2update("category_icons", $update, "iconid='".$iconid."'");
			}
		}

	func_recalculate_subcount();

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> 'Category has been successfully saved'
	);

	redirect('/admin/category/'.$categoryid);
}

if ($get['2'])
	$category = $db->row("SELECT * FROM categories WHERE rowid='".$get['2']."'");

$parentid = $_GET['parentid'] ? $_GET['parentid'] : $category['parentid'];
$category_location = '<a href="/admin/categories/" class="ajax_link">'.lng('Categories').'</a>';
$tmp = array();
while ($parent = $db->row("SELECT * FROM categories WHERE rowid='".$parentid."'")) {
	$parentid = $parent['parentid'];
	$tmp[] = ' &gt; <a href="/admin/categories/'.$parent['categoryid'].'" class="ajax_link">'.$parent['title'].'</a>';
}

if (!empty($tmp)) {
	krsort($tmp);
	$category_location .= implode('', $tmp);
}

if ($get['2']) {
	$template['category'] = $category;
	$template['category_icon'] = $db->row("SELECT * FROM category_icons WHERE rowid='".$get['2']."'");
	$template['location'] .= ' &gt; <a href="/admin/categories">'.lng('Categories').'</a> &gt; '.$category['title'];
	$template['head_title'] = lng('Category').' '.$category['title'].' :: '.$template['head_title'];
	$category_location .= ' &gt; <a href="/admin/categories/'.$category['categoryid'].'" class="ajax_link">'.$category['title'].'</a>';

	$tree = func_categories_tree(0, "title");
	$template['categories_tree'] = categories_tree_html($tree, $category['parentid'], 0, 1, 0);

	if ($get['3'] == 'products') {
		$category_location .= ' &gt; '.lng('Category products');
		$template['products'] = $db->all("SELECT p.*, c.orderby FROM products p, category_products c WHERE c.categoryid='".$category['categoryid']."' AND c.productid=p.productid GROUP BY p.productid ORDER BY c.orderby, p.name");
	} elseif ($get['3'] == 'banners') {
		$category_location .= ' &gt; '.lng('Category banners');
		$banners = $db->all("SELECT * FROM category_banners WHERE rowid='".$category['categoryid']."' ORDER BY pos, bannerid");
		if ($banners) {
			foreach ($banners as $k=>$v) {
   				$banners[$k]['image_url'] = $current_location.'/photos/banners/'.$v['categoryid'].'/'.$v['bannerid'].'/'.$v['file'];
			}

			$template['banners'] = $banners;
		}
	} else
		$category_location .= ' &gt; '.lng('Category details');
} else {
	$category_location .= ' &gt; Add new category';
	$template['category_location'] = $category_location;

	if ($_SESSION['saved_data']) {
		$template['new_category'] = 'Y';
		$template['category'] = $_SESSION['saved_data'];
	}

	$template['location'] .= ' &gt; <a href="/admin/categories">'.lng('Categories').'</a> &gt; '.lng('Add new category');
	$template['head_title'] = lng('Add new category').' :: '.$template['head_title'];
}

$template['category_location'] = $category_location;

if ($get['3'] == 'products')
	$template['page'] = get_template_contents('admin/pages/category_products.php');
else
	$template['page'] = get_template_contents('admin/pages/category.php');

$_SESSION['saved_data'] = array();

$template['css'][] = 'admin_category';
$template['js'][] = 'admin_category';
