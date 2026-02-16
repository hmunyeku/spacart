<?php
q_load('blog');

if (!empty($_GET['declineid'])) {
	$db->query("UPDATE blog_comments SET active='N' WHERE commentid='".addslashes($_GET['declineid'])."'");
	redirect("/admin/blog/".$get['2']);
} elseif (!empty($_GET['approveid'])) {
	$db->query("UPDATE blog_comments SET active='Y' WHERE commentid='".addslashes($_GET['approveid'])."'");
	redirect("/admin/blog/".$get['2']);
} elseif (!empty($_GET['deleteid'])) {
	$db->query("DELETE FROM blog_comments WHERE commentid='".addslashes($_GET['deleteid'])."'");
	redirect("/admin/blog/".$get['2']);
} elseif ($_GET['mode'] == 'delete_image') {
	$image = $db->row("SELECT * FROM blog_images WHERE blogid='".$get['2']."'");
	$dir = SITE_ROOT . '/photos/blog/'.$get['2'].'/'.$image['imageid'];
	unlink($dir.'/'.$image['file']);
	rmdir($dir);
	$db->query("DELETE FROM blog_images WHERE blogid='".$get['2']."'");
	redirect("/admin/blog/".$get['2']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if (!empty($comment)) {
		$comment = htmlspecialchars($comment);
		$data = array(
				'message' => $comment,
				'active' => 'Y',
				'date' => time()
		);

		if (!empty($commentid))
			$db->array2update("blog_comments", $data, "commentid='".addslashes($commentid)."'");
		else {
			$data['blogid'] = $get['2'];
			$data['userid'] = $login;
      		$data['ip'] = $_SERVER['REMOTE_ADDR'];
       		$db->array2insert("blog_comments", $data);
		}
	} elseif ($get['2'] == "new" && !empty($title)) {
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

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Blog has been successfully added'
		);

		$blogid = $db->array2insert("blog", $to_insert);
	} elseif (!empty($get['2'])) {
		$blogid = $get['2'];
		$to_update = array(
				'title' => $title,
				'fulldescr' => $fulldescr,
				'descr' => $descr,
				'meta_title' => $meta_title,
				'meta_keywords' => $meta_keywords,
				'meta_descr' => $meta_descr,
				'active' => $active
		);

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Blog has been successfully saved'
		);

		$db->array2update("blog", $to_update, "blogid='".$blogid."'");
	} elseif ($mode == "delete" && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$k = addslashes($k);
			$db->query("DELETE FROM blog WHERE blogid='".$k."'");
			$db->query("DELETE FROM blog_comments WHERE blogid='".$k."'");
			$image = $db->row("SELECT * FROM blog_images WHERE blogid='".$k."'");
			if (!empty($image)) {
				$dir = SITE_ROOT.'/photos/blog/'.$k.'/'.$image['imageid'];
				unlink($dir.'/'.$image['file']);
				rmdir($dir);
				$db->query("DELETE FROM blog_images WHERE blogid='".$k."'");
			}
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Blog has been successfully deleted'
		);
	} elseif ($mode == "update" && !empty($to_update)) {
		foreach ($to_update as $k=>$v) {
			$v['active'] = $v['active'] ? $v['active'] : '';
			$db->array2update("blog", $v, "blogid='".addslashes($k)."'");
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Blogs have been successfully updated'
		);
	}

	if ((($mode == "new" && !empty($title)) || !empty($blogid)) && !empty($cleanurl)) {
		$cleanurl = trim($cleanurl);
		$cu_exist = $db->field("SELECT blogid FROM blog WHERE cleanurl='".addslashes($cleanurl)."' AND blogid<>'".$blogid."'");
		if ($cu_exist)
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('This clean URL already exist for another blog.')
			);
		else {
			if (func_check_cleanurl($cleanurl))
				$db->query("UPDATE blog SET cleanurl='".addslashes($cleanurl)."' WHERE blogid='".$blogid."'");
			else
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".". Also it should be without ".html".')
				);
		}
	}

	if (!empty($_FILES['userfile']['tmp_name'])) {
		$image = $db->row("SELECT * FROM blog_images WHERE blogid='".$blogid."'");
		$dir = SITE_ROOT . '/photos/blog/'.$blogid.'/'.$image['imageid'];
		unlink($dir.'/'.$image['file']);
		rmdir($dir);
		$db->query("DELETE FROM blog_images WHERE blogid='".$blogid."'");

		$file = $_FILES['userfile'];
		$dir = SITE_ROOT . '/photos/blog/'.$blogid;
		if (!is_dir($dir))
			mkdir($dir);

		$new_image = array(
			'blogid'	=> $blogid,
			'file'		=> $file['name'],
			'size'		=> $file['size']
		);

		$imageid = $db->array2insert("blog_images", $new_image);
		$dir .= '/'.$imageid;
		if (!is_dir($dir))
			mkdir($dir);

		copy($file['tmp_name'], $dir.'/'.$file['name']);
		list($width, $height) = getimagesize($dir.'/'.$file['name']);

		$update = array(
			'x'	=> $width,
			'y'	=> $height
		);

		$db->array2update("blog_images", $update, "imageid='".$imageid."'");
	}

	if ($blogid)
		$get['2'] = $blogid;

	redirect("/admin/blog".(!empty($get['2']) ? "/".$get['2'] : ""));
}

if ($get['2'] == 'new')
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/blog">'.lng('Blogs').'</a> &gt; '.lng('New blog');
elseif (!empty($get['2'])) {
	$blog = $db->row("SELECT * FROM blog b LEFT JOIN users u ON b.author=u.id WHERE b.blogid='".$get['2']."'");
	if (empty($blog)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('No blog found')
		);

		redirect("/admin/blog");
	}

	$blog['image'] = $db->row("SELECT * FROM blog_images WHERE blogid='".$get['2']."'");
	$template['blog'] = $blog;
	$total_items = $db->field("SELECT COUNT(*) FROM blog_comments WHERE blogid='".$get['2']."'");
	if ($total_items > 0) {
		$objects_per_page = $config['Blog']['comments_per_page_admin'];
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/blog/".$get['2'].'?';

		$comments = $db->all("SELECT * FROM blog_comments b LEFT JOIN users u ON b.userid=u.id WHERE b.blogid='".$get['2']."' ORDER BY b.commentid DESC LIMIT $first_page, $objects_per_page");
		foreach ($comments as $k=>$v) {
			$comments[$k]['bb_message'] = func_blog_convert_string(func_eol2br($v['message']));
		}

		$template["comments"] = $comments;
	}

	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/blog">'.lng('Blogs').'</a> &gt; '.$blog['title'];
	$template['js'][] = 'blog_bb';
	$template['js'][] = 'str_replace';
} else {
	$template['location'] .= ' &gt; '.lng('Blogs');

	if (!empty($_GET['author'])) {
		$author = addslashes($_GET['author']);
		$author_condition = " WHERE author='".$author."'";
	}

	$total_items = $db->field("SELECT COUNT(*) FROM blog$author_condition");
	if ($total_items > 0) {
		$objects_per_page = $config['Blog']['blogs_per_page_admin'];
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/blog/?author=".$author;

		$blogs = $db->all("SELECT * FROM blog$author_condition ORDER BY blogid DESC LIMIT $first_page, $objects_per_page");
		foreach ($blogs as $k=>$v) {
			$blogs[$k]["descr"] = func_eol2br($v["descr"]);
			$blogs[$k]['comments'] = $db->field("SELECT COUNT(*) FROM blog_comments WHERE blogid='".$v['blogid']."'");
		}

		$template["blogs"] = $blogs;
	} else {
		redirect('/admin/blog/new');
	}

	$tmp = $db->all("SELECT * FROM blog b LEFT JOIN users u ON b.author=u.id ORDER BY u.firstname");
	if (!empty($tmp)) {
		$authors = array();
		$i = array();
		foreach ($tmp as $k=>$v) {
			$i[$v['id']]++;
			$authors[$v['id']] = array(
						$i[$v['id']],
						$v['firstname'].' '.$v['lastname']
			);
		}

		$template["authors"] = $authors;
	}
}

$template['head_title'] = lng('Blogs').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/blog.php');

$template['css'][] = 'admin_blog';
$template['js'][] = 'admin_blog';