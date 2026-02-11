<?php
/**
 * SpaCart - Profile page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.user.php';

if (!$is_logged_in || !$spacart_customer) {
    $page_html = '<script>window.location.hash="#/login";</script>';
    $page_title = 'Connexion requise';
    $breadcrumbs_html = '';
    return;
}

// Handle profile update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = spacart_update_profile($spacart_customer->rowid, $_POST);
    spacart_json_response($result);
    exit;
}

$customer = $spacart_customer;
$addresses = spacart_get_customer_addresses($customer->rowid);
$orders = spacart_get_customer_orders($customer->fk_soc, 10);

$page_title = 'Mon compte - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Mon compte', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array(
    'customer' => $customer,
    'addresses' => $addresses,
    'orders' => $orders,
    'config' => $spacart_config
);

$page_html = spacart_render(SPACART_TPL_PATH.'/profile/body.php', $tpl_vars);
