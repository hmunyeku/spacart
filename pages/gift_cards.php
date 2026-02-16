<?php
if (!$login)
	redirect('/login');

q_load('blog');
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);

	redirect('/gift_cards');
}

$gift_cards = $db->all("SELECT * FROM gift_cards WHERE userid='$login' ORDER BY date DESC");
if (!empty($gift_cards)) {
	$template["gift_cards"] = $gift_cards;
}

$template['page'] = get_template_contents('gift_cards/body.php');