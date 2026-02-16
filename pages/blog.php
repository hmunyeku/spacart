<?php
q_load('blog');
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);
	if (!empty($comment) && (!empty($login) || $config['Blog']['blog_guests'] == 'Y')) {
		$_SESSION['new_comment'] = array (
			'comment' => $comment,
			'new_name' => $new_name
		);

		$antibot_err = false;
		$rc_key = $config['General']['recaptcha_key'];
		$rc_skey = $config['General']['recaptcha_skey'];
		if (!$login && $rc_key && $rc_skey) {
			$postdata = http_build_query(
    			array(
        			'secret' => $rc_skey,
		        	'response' => $_POST['g-recaptcha-response']
	    		)
			);

			$opts = array('http' =>
		    	array(
	        		'method'  => 'POST',
		        	'header'  => 'Content-type: application/x-www-form-urlencoded',
    	    		'content' => $postdata
			    )
			);

			$context  = stream_context_create($opts);

			$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

			if (strstr($result, 'false') || !$_POST['g-recaptcha-response']) {
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Captcha is incorrect.')
				);

				$antibot_err = true;
			}
		}

		if (!$antibot_err && (!empty($login) || !empty($new_name))) {
			$blog = $db->row("SELECT * FROM blog WHERE blogid='".addslashes($blogid)."' AND active='Y'");
			if (!$blog)
				redirect('/');

			$_SESSION['new_comment'] = '';

			$comment = htmlspecialchars($comment);

			$data = array(
				'message'	=> $comment,
				'active'	=> 'Y',
				'date'		=> time(),
				'blogid'	=> $blogid,
       			'userid'	=> $login,
   				'name'		=> $new_name,
   				'ip'		=> $REMOTE_ADDR
			);

			$_SESSION['blog_comment'] = '';
			if ($config['Blog']['blog_moderate'] == 'Y')
				$data['active'] = 'N';

			$db->array2insert("blog_comments", $data);
			if ($config['Blog']['blog_moderate'] == 'Y') {
				if ($config['Blog']['blog_email'] == '1' || (!$login && $config['Blog']['blog_email'] == '2')) {					$subject = $company_name.': '.lng('Blog moderation').' #'.$blog['title'];
					$template['blog'] = $blog;
					$message = get_template_contents('mail/blog_comment.php');
					func_mail($config['Company']['company_name'], $config['Blog']['blog_moderator'], '', $subject, $message);
				}

				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> lng('Your comment has been added and awaiting for our moderation.')
				);
			} else {
				if ($config['Blog']['blog_email'] == '1' || (!$login && $config['Blog']['blog_email'] == '2')) {					$subject = $company_name.': '.lng('Blog moderation').' #'.$blog['title'];
					$template['blog'] = $blog;
					$message = get_template_contents('mail/blog_comment.php');
					func_mail($config['Company']['company_name'], $config['Blog']['blog_moderator'], '', $subject, $message);
				}

				$_SESSION['alerts'][] = array(
					'type'		=> 'i',
					'content'	=> lng('Your comment has been added.')
				);
			}
		} else {
			$_SESSION['blog_comment'] = $comment;

			redirect($_SERVER['REQUEST_URI'].'#leave_comment');
		}
	}

	redirect($_SERVER['REQUEST_URI']);
}

if (!empty($get['1'])) {
	$blog = $db->row("SELECT b.*, i.imageid, i.file, i.x, i.y, u.firstname, u.lastname FROM blog b LEFT JOIN users u ON b.author=u.id LEFT JOIN blog_images i ON b.blogid=i.blogid WHERE (b.blogid='".$get['1']."' OR b.cleanurl='".str_replace('.html', '', $get['1'])."') AND b.active='Y'");
	if (empty($blog)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('Blog not found')
		);

		redirect('/blog');
	}

	$template['head_title'] = $blog['title'].'. '.$template['head_title'];
	$template['bread_crumbs'][] = array('/blog', lng('Blog'));
	$template['bread_crumbs'][] = array('', $blog['title']);
	$blog["fulldescr"] = func_eol2br($blog["fulldescr"]);
	$blog['comments'] = $db->field("SELECT COUNT(*) FROM blog_comments WHERE blogid='".$blog['blogid']."' AND active='Y'");
	$template["blog"] = $blog;
	$total_items = $db->field("SELECT COUNT(*) FROM blog_comments WHERE blogid='".$blog['blogid']."' AND active='Y'");
	if ($total_items > 0) {
		$objects_per_page = $config['Blog']['comments_per_page'];
		require SITE_ROOT."/includes/navigation.php";

		$template["navigation_script"] = $current_location."/blog/".$get['2'].'?';

		$comments = $db->all("SELECT b.*, u.firstname, u.lastname FROM blog_comments b LEFT JOIN users u ON b.userid=u.id WHERE b.blogid='".$blog['blogid']."' AND b.active='Y' ORDER BY b.commentid DESC LIMIT $first_page, $objects_per_page");
		foreach ($comments as $k=>$v) {
			$comments[$k]['bb_message'] = func_blog_convert_string(func_eol2br($v['message']));
		}

		$template["comments"] = $comments;
	}

	$template['js'][] = 'str_replace';
	$template['js'][] = 'blog';
	$_SESSION['antibot_err'] = '';
} else {
	$template['head_title'] = lng('Our blog').'. '.$template['head_title'];
	$template['bread_crumbs'][] = array('', lng('Blog'));
	if (!empty($_GET['archive'])) {
		$tmp = explode('/', $_GET['archive']);
		$date_condition = " AND b.date>'".mktime(0,0,0,$tmp['0'],1,$tmp['1'])."' AND b.date<'".mktime(0,0,0,$tmp['0'],cal_days_in_month(CAL_GREGORIAN, $tmp['0'], $tmp['1']),$tmp['1'])."'";
	}

	$total_items = $db->field("SELECT COUNT(*) FROM blog b WHERE b.active='Y'$date_condition");
	if ($total_items > 0) {
		$objects_per_page = $config['Blog']['blogs_per_page'];
		require SITE_ROOT."/includes/navigation.php";

		$template["navigation_script"] = $current_location."/blog/?archive=".$_GET['archive'];

		$blogs = $db->all("SELECT b.*, i.imageid, i.file, i.x, i.y, u.firstname, u.lastname FROM blog b LEFT JOIN users u ON u.id=b.author LEFT JOIN blog_images i ON b.blogid=i.blogid WHERE b.active='Y'$date_condition ORDER BY b.blogid DESC LIMIT $first_page, $objects_per_page");
		foreach ($blogs as $k=>$v) {
			$blogs[$k]['comments'] = $db->field("SELECT COUNT(*) FROM blog_comments WHERE blogid='".$v['blogid']."' AND active='Y'");
			$blogs[$k]["descr"] = func_eol2br($v["descr"]);
		}

		$template["blogs"] = $blogs;
	}
}

$dates = $db->column("SELECT date FROM blog ORDER BY date DESC");
if (!empty($dates)) {
	$archive = array();
	$i = array();
	foreach ($dates as $v) {
		$i[date('M - Y', $v)]++;
		$archive[date('M - Y', $v)] = array(
				date('m/Y', $v),
				$i[date('M - Y', $v)]
		);
	}

	$template["blog_archive"] = $archive;
}

$template['css'][] = 'blog';
$template['js'][] = 'blog_bb';

$template['page'] = get_template_contents('blog/body.php');
