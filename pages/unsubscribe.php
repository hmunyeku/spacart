<?php
$db->query("DELETE FROM subscribers WHERE email='".addslashes($get['1'])."'");
$_SESSION['alerts'][] = array(
	'type'	=> 'i',
	'content'	=> lng('You have been unsubscribed')
);

redirect('/');