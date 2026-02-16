<?php
q_load('news');

if ($_GET['mode'] == 'delete_image') {
	$image = $db->row("SELECT * FROM news_images WHERE newsid='".$get['2']."'");
	$dir = SITE_ROOT . '/photos/news/'.$get['2'].'/'.$image['imageid'];
	unlink($dir.'/'.$image['file']);
	rmdir($dir);
	$db->query("DELETE FROM news_images WHERE newsid='".$get['2']."'");
	redirect("/admin/news/".$get['2']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($get['2'] == "new" && !empty($title)) {
		$to_insert = array(
				'title' => $title,
				'fulldescr' => $fulldescr,
				'descr' => $descr,
				'meta_title' => $meta_title,
				'meta_keywords' => $meta_keywords,
				'meta_descr' => $meta_descr,
				'active' => $active,
				'date' => time(),
				'author' => $login
		);

		$newsid = $db->array2insert("news", $to_insert);
	} elseif (!empty($get['2'])) {
		$newsid = $get['2'];
		$to_update = array(
				'date'	=> time(),
				'title' => $title,
				'fulldescr' => $fulldescr,
				'descr' => $descr,
				'meta_title' => $meta_title,
				'meta_keywords' => $meta_keywords,
				'meta_descr' => $meta_descr,
				'active' => $active
		);

		$db->array2update("news", $to_update, "newsid='".$newsid."'");
	} elseif ($mode == "delete" && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$k = addslashes($k);
			$db->query("DELETE FROM news WHERE newsid='".$k."'");
			$db->query("DELETE FROM news_comments WHERE newsid='".$k."'");
			$image = $db->row("SELECT * FROM news_images WHERE newsid='".$k."'");
			if (!empty($image)) {
				$dir = SITE_ROOT.'/photos/news/'.$k.'/'.$image['imageid'];
				unlink($dir.'/'.$image['file']);
				rmdir($dir);
				$db->query("DELETE FROM news_images WHERE newsid='".$k."'");
			}
		}
	} elseif ($mode == "update" && !empty($to_update)) {
		foreach ($to_update as $k=>$v) {
			$v['active'] = $v['active'] ? $v['active'] : '';
			$db->array2update("news", $v, "newsid='".addslashes($k)."'");
		}
	}

	if ((($mode == "new" && !empty($title)) || !empty($newsid)) && !empty($cleanurl)) {
		$cleanurl = trim($cleanurl);
		$cu_exist = $db->field("SELECT newsid FROM news WHERE cleanurl='".addslashes($cleanurl)."' AND newsid<>'".$newsid."'");
		if ($cu_exist)
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('This clean URL already exist for another news.')
			);
		else {
			if (func_check_cleanurl($cleanurl))
				$db->query("UPDATE news SET cleanurl='".addslashes($cleanurl)."' WHERE newsid='".$newsid."'");
			else
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".". Also it should be without ".html".')
				);
		}
	}

	if (!empty($_FILES['userfile']['tmp_name'])) {
		$file = $_FILES['userfile'];
		$dir = SITE_ROOT . '/photos/news/'.$newsid;
		if (!is_dir($dir))
			mkdir($dir);

		$db->query("DELETE FROM news_images WHERE newsid='$newsid'");
		$new_image = array(
			'newsid'	=> $newsid,
			'file'		=> $file['name'],
			'size'		=> $file['size']
		);

		$imageid = $db->array2insert("news_images", $new_image);
		$dir .= '/'.$imageid;
		if (!is_dir($dir))
			mkdir($dir);

		copy($file['tmp_name'], $dir.'/'.$file['name']);
		list($width, $height) = getimagesize($dir.'/'.$file['name']);

		$update = array(
			'x'	=> $width,
			'y'	=> $height
		);

		$db->array2update("news_images", $update, "imageid='".$imageid."'");
	}

	if ($newsid)
		$get['2'] = $newsid;

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> 'News has been successfully saved'
	);

	redirect("/admin/news".(!empty($get['2']) ? "/".$get['2'] : ""));
}

if ($get['2'] == 'new')
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/news">'.lng('newss').'</a> &gt; '.lng('New news');
elseif (!empty($get['2'])) {
	$news = $db->row("SELECT * FROM news b LEFT JOIN users u ON b.author=u.id WHERE b.newsid='".$get['2']."'");
	if (empty($news)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('No news found')
		);

		redirect("/admin/news");
	}

	$news['image'] = $db->row("SELECT * FROM news_images WHERE newsid='".$get['2']."'");
	$template['news'] = $news;
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/news">'.lng('News').'</a> &gt; '.$news['title'];
} else {
	$template['location'] .= ' &gt; '.lng('News');

	if (!empty($_GET['author'])) {
		$author = addslashes($_GET['author']);
		$author_condition = " WHERE author='".$author."'";
	}

	$total_items = $db->field("SELECT COUNT(*) FROM news$author_condition");
	if ($total_items > 0) {
		$objects_per_page = $config['News']['newss_per_page_admin'];
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/news/?author=".$author;

		$newss = $db->all("SELECT * FROM news$author_condition ORDER BY newsid DESC LIMIT $first_page, $objects_per_page");
		foreach ($newss as $k=>$v) {
			$newss[$k]["descr"] = func_eol2br($v["descr"]);
		}

		$template["newss"] = $newss;
	} else {
		redirect('/admin/news/new');
	}
}

$template['head_title'] = lng('News').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/news.php');

$template['css'][] = 'admin_news';
$template['js'][] = 'admin_news';