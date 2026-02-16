<?php
if ($_GET['translate_mode']) {
	$_SESSION['translate_mode'] = 1;
	redirect('/admin/language');
}

if ($_GET['exit_translate_mode']) {
	$_SESSION['translate_mode'] = '';
	redirect('/admin/language');
}

if ($_GET['mode'] == 'export') {
	$lng = $get['2'];
	$labels = $db->all("SELECT * FROM languages WHERE lng='".$lng."' ORDER BY id");
	$array = array();
	$array[] = array("Label", "Translation", "On English");
	foreach ($labels as $k=>$v) {
		$label = $db->field("SELECT translation FROM languages WHERE lng='en' AND word='".addslashes($v['word'])."'");
		$array[] = array($v['word'], $v['translation'], $label);
	}

	$fp = fopen(SITE_ROOT . '/var/tmp/lng', 'w+');
	foreach ($array as $fields) {
	    fputcsv($fp, $fields);
	}

	fclose($fp);

	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename="language_labels_'.$lng.'.csv"');
	header('Pragma: no-cache');
	readfile(SITE_ROOT . '/var/tmp/lng');

	exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'add') {
		$db->array2insert("languages_codes", $new_language);
	} elseif ($mode == "delete" && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM languages_codes WHERE id='".addslashes($k)."'");
		}
	} elseif ($mode == "update" && !empty($to_update)) {
		foreach ($to_update as $k=>$v) {
			$v['active'] = $v['active'] ? $v['active'] : '';
			$db->array2update("languages_codes", $v, "id='".addslashes($k)."'");
		}

		if ($main_lang) {
			$db->query("UPDATE languages_codes SET main='0'");
			$db->query("UPDATE languages_codes SET main='1' WHERE id='".addslashes($main_lang)."'");
		}
	} elseif ($_FILES['file'] && $get['2']) {
		session_write_close();
		$lng = $get['2'];
		copy($_FILES['file']['tmp_name'], SITE_ROOT . '/var/tmp/lng_import.csv');
		$fp = fopen(SITE_ROOT . '/var/tmp/lng_import.csv', 'r');
	    while ($data = fgetcsv($fp, 10000, ",")) {
			if ($data['0'] && $data['1']) {
				$tmp = $db->field("SELECT translation FROM languages WHERE lng='".$lng."' AND word='".addslashes($data['0'])."'");
				if ($tmp) {
					$db->query("UPDATE languages SET translation='".addslashes($data['1'])."' WHERE lng='".$lng."' AND word='".addslashes($data['0'])."'");
				} else
					$db->query("INSERT INTO languages SET lng='".$lng."', word='".addslashes($data['0'])."', translation='".addslashes($data['1'])."'");
			}
	    }

	    fclose($fp);
		session_start();

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Language labels imported successful')
		);
	}

	redirect('/admin/language/'.$get['2']);
}

$template['location'] .= ' &gt; <a href="/admin/language">'.lng('Languages').'</a>';
$template['head_title'] = lng('Languages').' :: '.$template['head_title'];
if ($get['2']) {
	$language_data = $db->row("SELECT * FROM languages_codes WHERE code='".addslashes($get['2'])."'");
	if (!$language_data)
		redirect('/admin/language');

	$template["language_data"] = $language_data;
	$template['location'] .= ' &gt; '.$language_data['name'];
	$template['page'] = get_template_contents('admin/pages/language.php');
} else {
	$languages = $db->all("SELECT * FROM languages_codes ORDER BY active, orderby, code");
	$template["languages"] = $languages;
	$template['page'] = get_template_contents('admin/pages/languages.php');
}