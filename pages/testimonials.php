<?php
/**
 * SpaCart - Testimonials page handler
 */
if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.blog.php';

$testimonials = spacart_get_testimonials();

$page_title = 'Témoignages - '.$spacart_config['title'];
$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Témoignages', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array('testimonials' => $testimonials, 'config' => $spacart_config);
$page_html = spacart_render(SPACART_TPL_PATH.'/testimonials/body.php', $tpl_vars);
