<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($get['2']) {
		if ($get['2'] == 'new')
			$pageid = $db->array2insert("pages", array("orderby" => $db->field("SELECT MAX(orderby) FROM pages") + 10));
		else
			$pageid = $get['2'];

		$to_update = array(
			'title'				=> $title,
			'content'			=> $content,
			'meta_title'		=> $meta_title,
			'meta_keywords'		=> $meta_keywords,
			'meta_description'	=> $meta_description
		);

		if ($cleanurl) {
			$cleanurl = trim($cleanurl);
			$cu_exist = $db->field("SELECT pageid FROM pages WHERE cleanurl='".addslashes($cleanurl)."' AND pageid<>'".$pageid."'");
			if ($cu_exist)
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('This clean URL already exist for another page.')
				);
			else {
				if (func_check_cleanurl($cleanurl))
					$to_update['cleanurl'] = $cleanurl;
				else
					$_SESSION['alerts'][] = array(
						'type'		=> 'e',
						'content'	=> lng('Clean URL should contain only letters and numbers and be not more 250 characters length. Allowed symbols: "-", "_", ".". Also it should be without ".html".')
					);
			}
		} else
			$to_update['cleanurl'] = '';

		$db->array2update("pages", $to_update, "pageid='".$pageid."'");

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Page has been successfully saved'
		);

		redirect('/admin/pages/'.$pageid);
	} elseif ($mode == 'delete' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v)
			$db->query("DELETE FROM pages WHERE pageid='".addslashes($k)."'");
	} else {
		foreach ($to_update as $k=>$v)
			$db->array2update("pages", $v, "pageid='".addslashes($k)."'");
	}

	redirect('/admin/pages');
}

if ($get['2']) {
	$page = $db->row("SELECT * FROM pages WHERE pageid='".$get['2']."'");
	$template['page'] = $page;
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/pages">'.lng('Pages').'</a> &gt; '.$page['title'];
} else {
	$pages = $db->all("SELECT * FROM pages ORDER BY orderby, pageid");
	$template['pages'] = $pages;
	$template['location'] .= ' &gt; '.lng('Pages');
}

$template['head_title'] = lng('Pages').' :: '.$template['head_title'];
$template['page'] = get_template_contents('admin/pages/pages.php');
