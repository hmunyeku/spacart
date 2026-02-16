<?php
$type = 'N';
$location_title = lng('Newsletter');
$table = 'subscribers';
$template['subscribers'] = $subscribers = $db->field("SELECT COUNT(*) FROM ".$table."");

if ($get['2'] == 'export') {
	$array = array(
		0 => array('Email', 'Date')
	);

	$subscribers = $db->all("SELECT * FROM ".$table." ORDER BY date DESC");
	if ($subscribers)
		foreach ($subscribers as $v) {
			$array[] = array($v['email'], date($datetime_format, $v['date']));
		}

	$file = SITE_ROOT . '/var/tmp/subscribers.csv';
	$fp = fopen($file, 'w');
	foreach ($array as $v) {
		fputcsv($fp, $v);
	}

	fclose($fp);

	$size = filesize($file);
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename=subscribers.csv');
	header('Content-Transfer-Encoding: binary');
	header('Connection: Keep-Alive');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . $size);
	exit(file_get_contents($file));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $get['2']) {
	extract($_POST, EXTR_SKIP);
	if (!$id) {
		$to_insert = array(
			't' => $type,
			'date' => time()
		);

		$id = $db->array2insert("letters", $to_insert);
	}

	$to_update = array(
		'subject' => $subject,
		'message' => $message
	);

	$id = addslashes($id);
	$db->array2update("letters", $to_update, "id=$id");

	if ($mode == 'test') {
		if ($email1)
			func_mail('Test email', $email1, '', $subject, $message);

		if ($email2)
			func_mail('Test email', $email2, '', $subject, $message);

		if ($email3)
			func_mail('Test email', $email3, '', $subject, $message);

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Email has been sent')
		);
	} elseif ($mode == 'send' && $subscribers) {
		$list = $db->all("SELECT * FROM ".$table."");
		if ($list) {
			foreach ($list as $k=>$v) {
				$tmp = str_replace("{name}", $v['name'], $message);
				$tmp .= '<br /><br />If you no longer want to receive these emails you can <a href="'.$http_location.'/unsubscribe/'.$v['email'].'">unsubscribe here</a>';
				func_mail($v['title'].' '.$v['name'], $v['email'], '', $subject, $tmp);
			}
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Email has been sent')
		);
	}

	redirect("/admin/subscribtions/".$id);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($to_delete) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM letters WHERE id='$k'");
		}
	}

	redirect("/admin/subscribtions");
}

if ($get['2']) {
	if ($get['2'] == 'new') {
		$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/subscribtions">'.$location_title.'</a> &gt; '.lng('New letter');
	} else {
		$letter = $db->row("SELECT * FROM letters WHERE id='".$get['2']."'");
		$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/subscribtions">'.$location_title.'</a> &gt; '.$letter['subject'];
		$template['letter'] = $letter;
	}

	$template['page'] = get_template_contents('admin/pages/letter.php');
} else {
	$template['location'] .= ' &gt; '.$location_title;
	$total_items = $db->field("SELECT COUNT(*) FROM letters WHERE t='".$type."'");
	if ($total_items > 0) {
		$objects_per_page = 30;
		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/subscribtions?";

		$letters = $db->all("SELECT * FROM letters WHERE t='".$type."' ORDER BY id DESC LIMIT $first_page, $objects_per_page");

			$template["letters"] = $letters;
	} else {
		redirect('/admin/subscribtions/new');
	}

	$template['page'] = get_template_contents('admin/pages/letters.php');
}


$template['head_title'] = $location_title.' :: '.$template['head_title'];

$template['js'][] = 'newsletter';
$template['css'][] = 'newsletter';
