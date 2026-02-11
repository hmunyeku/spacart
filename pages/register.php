<?php
/**
 * SpaCart - Register page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.user.php';

// Handle register POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $result = spacart_register_customer($_POST);
    spacart_json_response($result);
    exit;
}

$page_title = 'Inscription - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Inscription', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array('config' => $spacart_config);
$page_html = spacart_render(SPACART_TPL_PATH.'/register/body.php', $tpl_vars);
