<?php
if (empty($get['2']))
	redirect('/admin/configuration/General');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	foreach ($config[$get['2']] as $k=>$v) {
		if (!$_POST[$k])
			$_POST[$k] = '';
	}

	if ($posted_data['state']) {
		if ($get['2'] == 'Company' && $posted_data['state'])
			$_POST['location_state'] = $posted_data['state'];
		elseif ($posted_data['state'])
			$_POST['default_state'] = $posted_data['state'];
	}

	foreach ($_POST as $k=>$v) {
		if (is_array($v))
			continue;

		$db->query("UPDATE config SET value='".addslashes($v)."' WHERE name='".addslashes($k)."'");
	}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Settings have been successfully saved'
		);

	redirect("/admin/configuration".(!empty($get['2']) ? "/".$get['2'] : ""));
}

$conf = $db->all("SELECT * FROM config WHERE category='".$get['2']."' ORDER BY orderby");
if ($conf) {
	foreach ($conf as $k=>$v) {
		if ($v['type'] == 'selector') {
			$tmp = explode("\n", $v['variants']);
			$tmp2 = array();
			$found = false;
			foreach ($tmp as $l) {
				if (strstr($l, ':'))
					$found = true;

				if ($found) {
					$tmp3 = explode(':', $l);
					if ($tmp3['1'])
						$tmp2[] = array($tmp3[0], $tmp3[1]);
				} else
					$tmp2[] = array($l, $l);
			}

			$conf[$k]['variants'] = $tmp2;
		}
	}

	$template['conf'] = $conf;
}

$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/configuration">'.lng('Configuration').'</a> &gt; '.$get['2'];
$template['head_title'] = lng('Configuration').' :: '.$template['head_title'];

$template['countries'] = $countries;
$template['page'] = get_template_contents('admin/pages/configuration.php');

$template['css'][] = 'admin_configuration';
$template['js'][] = 'admin_configuration';
$template['js'][] = 'states';