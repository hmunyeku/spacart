<?php
/**
 * SpaCart - Login page handler
 */

if (!defined('SPACART_BOOT')) die('Access denied');

require_once SPACART_PATH.'/includes/func/func.user.php';

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    $result = spacart_login_customer($email, $password, $remember);
    spacart_json_response($result);
    exit;
}

$page_title = 'Connexion - '.$spacart_config['title'];

$bc_items = array(
    array('label' => 'Accueil', 'url' => '#/'),
    array('label' => 'Connexion', 'url' => '')
);
$breadcrumbs_html = spacart_breadcrumbs($bc_items);

$tpl_vars = array('config' => $spacart_config);
$page_html = spacart_render(SPACART_TPL_PATH.'/login/body.php', $tpl_vars);
