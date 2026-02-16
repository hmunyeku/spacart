<?php
q_load('news');
if ($_GET['nosearch']) {
	$_SESSION['news_substring'] = '';
	redirect('/news');
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);
	$_SESSION['news_substring'] = $substring;
	redirect('/news');
}

$template['head_title'] = lng('News').'. '.$template['head_title'];
$template['bread_crumbs'][] = array('/news', lng('News'));

if (!empty($get['1'])) {
	$news = $db->row("SELECT b.*, i.imageid, i.file, i.x, i.y, u.firstname, u.lastname FROM news b LEFT JOIN users u ON b.author=u.id LEFT JOIN news_images i ON b.newsid=i.newsid WHERE (b.newsid='".$get['1']."' OR b.cleanurl='".str_replace('.html', '', $get['1'])."') AND b.active='Y'");
	if (empty($news)) {
		$_SESSION['alerts'][] = array(
				'type' => 'e',
				'content' => lng('News not found')
		);

		redirect('/news');
	}

	$template['bread_crumbs'][] = array('', $news['title']);
	$db->query("UPDATE news SET counter='".($news['counter'] + 1)."' WHERE newsid='$news[newsid]'");
	$news["fulldescr"] = func_eol2br($news["fulldescr"]);
	$template["news"] = $news;
	$template['js'][] = 'news';
} else {
	if ($_SESSION['news_substring']) {
		$template['news_substring'] = $_SESSION['news_substring'];
		$substring_condition = " AND (b.descr LIKE '%".addslashes($_SESSION['news_substring'])."%' OR b.fulldescr LIKE '%".addslashes($_SESSION['news_substring'])."%' OR b.title LIKE '%".addslashes($_SESSION['news_substring'])."%')";
	}

	$total_items = $db->field("SELECT COUNT(*) FROM news b WHERE b.active='Y'$substring_condition");
	if ($total_items > 0) {
		$objects_per_page = $config['News']['newss_per_page'];
		require SITE_ROOT."/includes/navigation.php";

		$template["navigation_script"] = $current_location."/news/?";

		$newss = $db->all("SELECT b.*, i.imageid, i.file, i.x, i.y, u.firstname, u.lastname FROM news b LEFT JOIN users u ON u.id=b.author LEFT JOIN news_images i ON b.newsid=i.newsid WHERE b.active='Y'$substring_condition ORDER BY b.newsid DESC LIMIT $first_page, $objects_per_page");
		foreach ($newss as $k=>$v) {
			$newss[$k]["descr"] = strip_tags($v["descr"]);
		}

		$template["newss"] = $newss;
	}
}

$template['top_read'] = $db->all("SELECT * FROM news ORDER BY counter DESC LIMIT 5");

$template['page'] = get_template_contents('news/body.php');