<?php
q_load('category');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($get['2'] == 'banners') {
		$userfile = $_FILES['userfile'];
		if ($mode == "add" && !empty($userfile['name'])) {
			if (empty($new_pos))
				$new_pos = $db->field("SELECT MAX(pos) FROM category_banners WHERE categoryid=0")+10;

			$dir = SITE_ROOT . "/photos/banners/0";
			if (!is_dir($dir))
				mkdir($dir);

			$new_banner = array(
				'categoryid'	=> 0,
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
				$k = addslashes($k);
				$banner_file = $db->field("SELECT file FROM category_banners WHERE bannerid='$k'");
				unlink(SITE_ROOT.'/photos/banners/0/'.$k.'/'.$banner_file);
				$db->query("DELETE FROM category_banners WHERE bannerid='$k'");
	 		}
		} elseif ($mode == "update" && !empty($to_update)) {
			foreach ($to_update as $k=>$v) {
				$k = addslashes($k);
				$db->array2update("category_banners", $v, "bannerid='$k'");
			}
		}

		redirect('/admin/homepage/banners');
	} else {
		$db->query("REPLACE INTO languages SET lng='".$lng."', word='Site title', translation='".addslashes($site_title)."'");
		$db->query("REPLACE INTO languages SET lng='".$lng."', word='Site description', translation='".addslashes(htmlspecialchars_decode($site_description))."'");
		$db->query("REPLACE INTO languages SET lng='".$lng."', word='Homepage meta title', translation='".addslashes($meta_title)."'");
		$db->query("REPLACE INTO languages SET lng='".$lng."', word='Homepage meta keywords', translation='".addslashes($meta_keywords)."'");
		$db->query("REPLACE INTO languages SET lng='".$lng."', word='Homepage meta description', translation='".addslashes($meta_description)."'");
	
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Homepage has been successfully saved'
		);

		redirect('/admin/homepage');
	}
}

if ($get['2'] == 'banners') {
	$banners = $db->all("SELECT * FROM category_banners WHERE categoryid=0 ORDER BY pos, bannerid");
	if ($banners) {
		foreach ($banners as $k=>$v) {
			$banners[$k]['image_url'] = $current_location.'/photos/banners/'.$v['categoryid'].'/'.$v['bannerid'].'/'.$v['file'];
		}

		$template['banners'] = $banners;
	}
}

$template['location'] .= ' &gt; '.lng('Homepage');
$template['head_title'] = lng('Homepage').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/homepage.php');

$template['css'][] = 'admin_category';
$template['js'][] = 'admin_category';
