<?php

$template['location'] .= ' &gt; '.lng('Google sitemap');
$template['head_title'] = lng('Google sitemap').' :: '.$template['head_title'];

if ($get['2'] == 'generate') {
	ini_set('memory_limit', '524288000');
	set_time_limit(3600);
	session_write_close();
	$txt = '';
	$xml = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';

	$search_keywords = file(SITE_ROOT.'/search_keywords.txt');
	if ($search_keywords) {
		foreach ($search_keywords as $v) {
			$v = str_replace("\r\n", "", $v);
			$xml .= '<url><loc>'.$http_location.'/search?q='.$v.'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
			$txt .=  $http_location.'/search?q='.$v.'
';
		}
	}

	$categories = $db->all("SELECT categoryid, cleanurl FROM categories");
	foreach ($categories as $v) {
		if ($v['cleanurl']) {
			$xml .= '<url><loc>'.$http_location.'/'.$v['cleanurl'].'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
			$txt .=  $http_location.'/'.$v['cleanurl'].'
';
		} else {
			$xml .= '<url><loc>'.$http_location.'/category/'.$v['categoryid'].'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
			$txt .=  $http_location.'/category/'.$v['categoryid'].'
';
		}
	}

	$products = $db->all("SELECT productid , cleanurl FROM products");
	foreach ($products as $v) {
		if ($v['cleanurl']) {
			$xml .= '<url><loc>'.$http_location.'/'.$v['cleanurl'].'.html</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
			$txt .=  $http_location.'/'.$v['cleanurl'].'.html
';
		} else {
			$xml .= '<url><loc>'.$http_location.'/product/'.$v['productid'].'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
			$txt .=  $http_location.'/product/'.$v['productid'].'
';
	}
}

	$brands = $db->all("SELECT brandid, cleanurl FROM brands");
	foreach ($brands as $v) {
		$xml .= '<url><loc>'.$http_location.'/brands/'.($v['cleanurl'] ? $v['cleanurl'] : $v['brandid']).'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
		$txt .=  $http_location.'/brands/'.($v['cleanurl'] ? $v['cleanurl'] : $v['brandid']).'
';
	}

	$xml .= '<url><loc>'.$http_location.'/blog</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>1</priority></url>
';
	$txt .=  $http_location.'/blog
';

	$blog = $db->all("SELECT blogid, cleanurl FROM blog ORDER BY blogid");
	foreach ($blog as $v) {
		$xml .= '<url><loc>'.$http_location.'/blog/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['blogid']).'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>1</priority></url>
';
		$txt .=  $http_location.'/blog/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['blogid']).'
';
	}

	$xml .= '<url><loc>'.$http_location.'/news</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>1</priority></url>
';
	$txt .=  $http_location.'/news
';

	$news = $db->all("SELECT newsid, cleanurl FROM news ORDER BY newsid");
	foreach ($blog as $v) {
		$xml .= '<url><loc>'.$http_location.'/news/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['newsid']).'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>daily</changefreq><priority>1</priority></url>
';
		$txt .=  $http_location.'/news/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['newsid']).'
';
	}

	$pages = $db->all("SELECT pageid, cleanurl FROM pages");
	foreach ($pages as $v) {
		$xml .= '<url><loc>'.$http_location.'/page/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['pageid']).'</loc><lastmod>'.date('Y-m-d').'</lastmod><changefreq>weekly</changefreq><priority>1</priority></url>
';
		$txt .=  $http_location.'/page/'.($v['cleanurl'] ? $v['cleanurl'].'.html' : $v['pageid']).'
';
	}

	$xml .= '</urlset>';

	$fp = fopen(SITE_ROOT.'/sitemap.xml', 'w');
	fputs($fp, $xml);
	fclose($fp);

	$fp = fopen(SITE_ROOT.'/sitemap.txt', 'w');
	fputs($fp, $txt);
	fclose($fp);

	session_start();
	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> lng('Your sitemap successfully generated')
	);

	redirect('/admin/sitemap');
}

$template['page'] = get_template_contents('admin/pages/sitemap.php');
$template['css'][] = 'admin_sitemap';