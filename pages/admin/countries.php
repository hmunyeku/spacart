<?php
q_load('category');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'delete' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM states WHERE code='".addslashes($k)."' AND country_code='".$get[2]."'");
		}
	} else {
		foreach ($posted_data as $k=>$v)
			$db->array2update("states", $v, "code='".addslashes($k)."' AND country_code='".$get[2]."'");

		if (!empty($new_state['code'])) {
			$new_state['country_code'] = $get['2'];
			$db->array2insert("states", $new_state);
		}
	}

	redirect('/admin/countries'.($get['2'] ? '/'.$get['2'] : ''));
}

if ($get['2']) {
	$template['country'] = $db->row("SELECT * FROM countries WHERE code='".$get[2]."'");;
	$template['states'] = $db->all("SELECT * FROM states WHERE country_code='".$get[2]."'");
	$template['page'] = get_template_contents('admin/pages/states.php');
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/countries">'.lng('Countries').'</a> &gt; '.lng('States').' '.$template['country']['country'];
	$template['head_title'] = lng('States').' '.$country['country'].' :: '.$template['head_title'];
} else {
	$countries = $db->all("SELECT * FROM countries ORDER BY country");
	foreach ($countries as $k=>$v) {
		$countries[$k]['states'] = $db->field("SELECT COUNT(*) FROM states WHERE country_code='".$v['code']."'");
	}

	$template['countries'] = $countries;

	$template['location'] .= ' &gt; '.lng('Countries');
	$template['head_title'] = lng('Countries').' :: '.$template['head_title'];
	$template['page'] = get_template_contents('admin/pages/countries.php');
}

$template['css'][] = 'admin_countries';
$template['js'][] = 'admin_countries';
