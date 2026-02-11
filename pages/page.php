<?php
/**
 * SpaCart - CMS Page handler
 */
if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.blog.php';

$slug = !empty($get[1]) ? $get[1] : '';

if (!$slug) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Page non trouvée</p></div>';
    $page_title = 'Page non trouvée';
    $breadcrumbs_html = '';
    return;
}

$cmsPage = spacart_get_page_by_slug($slug);

if (!$cmsPage) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Page non trouvée</p></div>';
    $page_title = 'Page non trouvée';
    $breadcrumbs_html = '';
    return;
}

$page_title = htmlspecialchars($cmsPage->meta_title ?: $cmsPage->title).' - '.$spacart_config['title'];
$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => $cmsPage->title, 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array('page' => $cmsPage, 'config' => $spacart_config);
$page_html = spacart_render(SPACART_TPL_PATH.'/page/body.php', $tpl_vars);
