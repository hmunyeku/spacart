<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);
	if (is_numeric($amount)) {
		$db->query("INSERT INTO gift_cards SET gcid='".addslashes(func_giftcert_generate())."', amount='".$amount."', amount_left='".$amount."', date='".time()."', status='Y'");
	}

	if ($to_delete)
		foreach ($to_delete as $k=>$v)
			$db->query("DELETE FROM gift_cards WHERE gcid='".addslashes($k)."'");

	redirect('/admin/gift_cards');
}

$gift_cards = $db->all("SELECT * FROM gift_cards ORDER BY date DESC");
if (!empty($gift_cards)) {
	foreach ($gift_cards as $k=>$v) {
		if ($v['userid'])
			$gift_cards[$k]['user'] = $db->row("SELECT * FROM users WHERE id='".$v['userid']."'");
	}

	$template["gift_cards"] = $gift_cards;
}

$template['page'] = get_template_contents('admin/pages/gift_cards.php');