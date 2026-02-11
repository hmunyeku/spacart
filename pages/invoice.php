<?php
/**
 * SpaCart - Invoice/Order detail page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.order.php';
require_once SPACART_PATH.'/includes/func/func.user.php';

$orderId = !empty($get[1]) ? (int) $get[1] : 0;

if (!$orderId) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Commande non trouvée</p></div>';
    $page_title = 'Commande non trouvée';
    $breadcrumbs_html = '';
    return;
}

$fkSoc = 0;
if ($is_logged_in && $spacart_customer) {
    $fkSoc = (int) $spacart_customer->fk_soc;
}

$order = spacart_get_order_detail($orderId, $fkSoc);

if (!$order) {
    $page_html = '<div class="spacart-empty-state"><i class="material-icons large grey-text">error</i><p>Commande non trouvée ou accès non autorisé</p></div>';
    $page_title = 'Commande non trouvée';
    $breadcrumbs_html = '';
    return;
}

$page_title = 'Commande '.$order->ref.' - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Mon compte', 'url' => '#/profile'),
    array('label' => 'Commande '.$order->ref, 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'order' => $order,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/invoice/body.php', $tpl_vars);
