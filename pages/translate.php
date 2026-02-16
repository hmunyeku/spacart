<?php
if (!$login || $userinfo['usertype'] != 'A' || !$translate_mode)
	redirect('/');

$_GET['lbl'] = str_replace('&amp;', '&', $_GET['lbl']);
$db->query("UPDATE languages SET translation='".addslashes($_GET['translate'])."' WHERE lng='".$lng."' AND word='".addslashes($_GET['lbl'])."'");
exit;
