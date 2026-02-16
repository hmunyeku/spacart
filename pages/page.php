<?php
// Strip .html suffix for backward compatibility with old URL format
$slug = str_replace('.html', '', $get['1']);
$safe_slug = $db->mysqli->real_escape_string($slug);

// First try Dolibarr backoffice table (llx_spacart_page)
$page = $db->row("SELECT rowid, title, content, meta_title, meta_description, slug, status FROM llx_spacart_page WHERE (rowid = '".intval($get['1'])."' OR slug = '".$safe_slug."') AND status = 1 AND entity = 1");

// Fallback to legacy pages table for backward compatibility
if (!$page) {
    $page = $db->row("SELECT * FROM pages WHERE pageid='".intval($get['1'])."' OR cleanurl='".$safe_slug."'");
}

if ($page) {
    $meta = !empty($page['meta_title']) ? $page['meta_title'] : $page['title'];
    $template['head_title'] = $meta.'. '.$template['head_title'];
    if (!empty($page['meta_description'])) {
        $template['meta_description'] = $page['meta_description'];
    }
    $template['static_page'] = $page;
} else {
    redirect('/');
}

$template['page'] = get_template_contents('static_pages/body.php');
$template['css'][] = 'static';
