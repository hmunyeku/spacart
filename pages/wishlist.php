<?php
/**
 * SpaCart - Wishlist page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.user.php';
require_once SPACART_PATH.'/includes/func/func.product.php';

if (!$is_logged_in || !$spacart_customer) {
    $page_html = '<script>window.location.hash="#/login";</script>';
    $page_title = 'Connexion requise';
    $breadcrumbs_html = '';
    return;
}

$wishlist = spacart_get_wishlist($spacart_customer->rowid);

$page_title = 'Mes favoris - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Mes favoris', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'wishlist' => $wishlist,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/wishlist/body.php', $tpl_vars);
