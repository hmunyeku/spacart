<?php
if ($_GET['mode'] == 'delete_image') {
	$image = $db->row("SELECT * FROM brand_images WHERE brandid='".$get['2']."'");
	$dir = SITE_ROOT . '/photos/brand/'.$get['2'].'/'.$image['imageid'];
	unlink($dir.'/'.$image['file']);
	rmdir($dir);
	$db->query("DELETE FROM brand_images WHERE brandid='".$get['2']."'");
	redirect("/admin/brands/".$get['2']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($get['2']) {
		if ($get['2'] == 'new')
			$get['2'] = $db->array2insert("brands", array('brandid'	=> 'NULL'));

		$to_update = array(
				'name' => $name,
				'descr' => $descr,
				'meta_title' => $meta_title,
				'meta_keywords' => $meta_keywords,
				'meta_descr' => $meta_descr,
				'active' => $active,
				'orderby' => $orderby ? $orderby : ($db->field("SELECT MAX(orderby) FROM brands") + 10)
		);

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Brand has been successfully saved'
		);

		$db->array2update("brands", $to_update, "brandid='".$get['2']."'");
	} elseif ($mode == "delete" && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$k = addslashes($k);
			$db->query("DELETE FROM brands WHERE brandid='".$k."'");
			$image = $db->row("SELECT * FROM brand_images WHERE brandid='".$k."'");
			if (!empty($image)) {
				$dir = SITE_ROOT.'/photos/brand/'.$k.'/'.$image['imageid'];
				unlink($dir.'/'.$image['file']);
				rmdir($dir);
				$db->query("DELETE FROM brand_images WHERE brandid='".$k."'");
			}
		}
	} elseif ($mode == "update" && !empty($to_update)) {
		foreach ($to_update as $k=>$v) {
			$v['active'] = $v['active'] ? $v['active'] : '';
			$db->array2update("brands", $v, "brandid='".addslashes($k)."'");
		}
	}

	if ($get['2'] && !empty($cleanurl)) {
		$cleanurl = trim($cleanurl);
		$cu_exist = $db->field("SELECT brandid FROM brands WHERE cleanurl='".addslashes($cleanurl)."' AND brandid<>'".$get['2']."'");
		if ($cu_exist)
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('This clean URL already exist for another brand.')
			);
		else {
			if (func_check_cleanurl($cleanurl))
				$db->query("UPDATE brands SET cleanurl='".addslashes($cleanurl)."' WHERE brandid='".$get['2']."'");
			else
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".".')
				);
		}
	}

	if (!empty($_FILES['userfile']['tmp_name'])) {
		$image = $db->row("SELECT * FROM brand_images WHERE brandid='".$get['2']."'");
		$dir = SITE_ROOT . '/photos/brand/'.$get['2'].'/'.$image['imageid'];
		unlink($dir.'/'.$image['file']);
		rmdir($dir);
		$db->query("DELETE FROM brand_images WHERE brandid='".$get['2']."'");

		$file = $_FILES['userfile'];
		$dir = SITE_ROOT . '/photos/brand/'.$get['2'];
		if (!is_dir($dir))
			mkdir($dir);

		$new_image = array(
			'brandid'	=> $get['2'],
			'file'		=> $file['name'],
			'size'		=> $file['size']
		);

		$imageid = $db->array2insert("brand_images", $new_image);
		$dir .= '/'.$imageid;
		if (!is_dir($dir))
			mkdir($dir);

		copy($file['tmp_name'], $dir.'/'.$file['name']);
		list($width, $height) = getimagesize($dir.'/'.$file['name']);

		$update = array(
			'x'	=> $width,
			'y'	=> $height
		);

		$db->array2update("brand_images", $update, "imageid='".$imageid."'");
	}

	if ($brandid)
		$get['2'] = $brandid;

	redirect("/admin/brands".(!empty($get['2']) ? "/".$get['2'] : ""));
}

if ($get['2'] == 'new')
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/brands">'.lng('Brands').'</a> &gt; '.lng('New brand');
elseif (!empty($get['2'])) {
	$brand = $db->row("SELECT * FROM brands WHERE brandid='".$get['2']."'");
	if (empty($brand)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('No brand found')
		);

		redirect("/admin/brands");
	}

	$brand['image'] = $db->row("SELECT * FROM brand_images WHERE brandid='".$get['2']."'");
	$template['brand'] = $brand;

	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/brands">'.lng('Brands').'</a> &gt; '.$brand['name'];
} else {
	$template['location'] .= ' &gt; '.lng('Brands');

	$total_items = $db->field("SELECT COUNT(*) FROM brands");
	if ($total_items > 0) {
		$objects_per_page = 30;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/brands/?";

		$brands = $db->all("SELECT * FROM brands ORDER BY orderby, name LIMIT $first_page, $objects_per_page");
		$template["brands"] = $brands;
	} else {
		redirect('/admin/brands/new');
	}
}

$template['head_title'] = lng('Brands').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/brands.php');

$template['css'][] = 'admin_brands';
$template['js'][] = 'admin_brands';