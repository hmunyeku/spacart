<?php
extract($_POST, EXTR_SKIP);
extract($_GET, EXTR_SKIP);
$zoneid = $get['2'];
function func_add_element($zoneid, $field_type, $zone_elements) {
	global $db;

	$db->query("DELETE FROM zone_element WHERE zoneid='".$zoneid."' AND field_type='".$field_type."'");
	if (!empty($zone_elements) && is_array($zone_elements))
		foreach ($zone_elements as $k=>$v) {
			$v = trim($v);
			if (empty($v))
				continue;

			$to_insert = array(
				'zoneid'		=> $zoneid,
				'field'			=> $v,
				'field_type'	=> $field_type
			);

			$db->array2insert("zone_element", $to_insert);
		}
}

function sort_elements($a, $b) {
	static $sort_order;

	$sort_order = array_flip(array('C','S','T','Z','A'));
	if ($sort_order[$a['element_type']] > $sort_order[$b['element_type']])
		return 1;
	else
		return 0;
}

$zoneid = intval($zoneid);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($mode == 'details') {
		$zone_name = trim($zone_name);
		if (!empty($zone_name)) {
			if ($get['2'] == 'add') {
				$to_insert = array(
					'zone_name'		=> $zone_name
				);

				$zoneid = $db->array2insert("zones", $to_insert);
				$get['2'] = $zoneid;
			} else {
				$db->query("UPDATE zones SET zone_name='".addslashes($zone_name)."' WHERE zoneid='".$get['2']."'");
			}


			$zone_countries = explode(";", $zone_countries_store);
			$zone_states = explode(";", $zone_states_store);
			if ($zone_states)
				foreach($zone_states as $v)
					if (preg_match('!^(.+)_!S',$v, $m)) {
						if (!in_array($m[1], $zone_countries))
							$zone_countries[] = $m[1];
					}

			$db->query("DELETE FROM zone_element WHERE zoneid='$zoneid'");

			func_add_element($zoneid, 'C', $zone_countries);
			func_add_element($zoneid, 'S', $zone_states);
			func_add_element($zoneid, 'T', explode("\n", $zone_cities));
			func_add_element($zoneid, 'Z', explode("\n", $zone_zipcodes));
		} else {
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Zone name must be entered')
			);
		}

		redirect("/admin/zones/".$get['2']);
	}

	if ($mode == 'delete' && is_array($to_delete))
		foreach ($to_delete as $k=>$v) {
				$db->query("DELETE FROM zones WHERE zoneid='".$k."'");
				$db->query("DELETE FROM zone_element WHERE zoneid='".$k."'");
				$db->query("DELETE FROM shipping_rates WHERE zoneid='".$k."'");
				$db->query("DELETE FROM tax_rates WHERE zoneid='".$k."'");
			}

	if ($mode == 'clone') {
		$zone_data = $db->row("SELECT * FROM zones WHERE zoneid='".$zoneid."'");
		if (!empty($zone_data)) {
			unset($zone_data['zoneid']);
			$zone_data['zone_name'] = $zone_data['zone_name']." (clone)";
			$new_zoneid = $db->array2insert("zones", $zone_data);
			$zone_elements = $db->all("SELECT * FROM zone_element WHERE zoneid='".$zoneid."'");
			if (is_array($zone_elements))
				foreach ($zone_elements as $k=>$v) {
					$to_insert = array(
						'zoneid'		=> $new_zoneid,
						'field'			=> $v['field'],
						'field_type'	=> $v['field_type']
					);

					$db->array2insert("zone_element", $to_insert);
				}
		}

		redirect('/admin/zones/'.$new_zoneid);
	}

	redirect('/admin/zones');
}

$template['head_title'] = lng('Zones').' :: '.$template['head_title'];
if ($get['2'] == 'add' or !empty($get['2'])) {
	if ($get['2'] != 'add') {
		$zone = $db->row("SELECT * FROM zones WHERE zoneid='".$get['2']."'");
		if (empty($zone))
			redirect('/admin/zones');

		$template['zone'] = $zone;
		$zoneid = $get['2'];
	} else
		$zoneid = "";

	$zone_countries = $db->all("SELECT c.code, c.country FROM zone_element e, countries c WHERE e.field_type='C' AND e.field=c.code AND c.active='Y' AND e.zoneid='".$zoneid."' ORDER BY c.country");
	$template['zone_countries'] = $zone_countries;
	$rest_countries = $db->all("SELECT c.code, c.region, c.country, e.zoneid FROM countries c LEFT JOIN zone_element e ON e.field_type='C' AND e.field=c.code AND e.zoneid='".$zoneid."' WHERE c.active='Y' AND zoneid IS NULL ORDER BY country");
	$template['rest_countries'] = $rest_countries;
	$rest_zones = array();
	if ($rest_countries)
		foreach($rest_countries as $v)
			$rest_zones[$v['region']][] = $v['code'];

	$template['rest_zones'] = $rest_zones;

	$zone_states = $db->all("SELECT s.* FROM states s, zone_element e WHERE e.field_type='S' AND e.field=CONCAT(s.country_code,'_',s.code) AND e.zoneid='".$zoneid."' ORDER BY s.country_code, s.state");
	$rest_states = $db->all("SELECT s.*, e.zoneid FROM countries c, states s LEFT JOIN zone_element e ON e.field_type='S' AND e.field=CONCAT(s.country_code,'_',s.code) AND e.zoneid='".$zoneid."' WHERE c.code=s.country_code AND c.active='Y' AND zoneid IS NULL ORDER BY s.country_code, s.state");
	$_distinct_countries = $db->all("SELECT DISTINCT country_code, c.country FROM states s, countries c WHERE c.code=s.country_code");

	$state_country = array();
	if (is_array($_distinct_countries))
		foreach ($_distinct_countries as $k=>$v)
			$state_country[$v['country_code']] = $v['country'];

	if (is_array($zone_states))
		foreach ($zone_states as $k=>$v)
			$zone_states[$k]['country'] = $state_country[$v['country_code']];

	if (is_array($rest_states))
		foreach ($rest_states as $k=>$v)
			$rest_states[$k]['country'] = $state_country[$v['country_code']];

	$template['zone_states'] = $zone_states;
	$template['rest_states'] = $rest_states;

	$zone_elements = $db->all("SELECT * FROM zone_element WHERE zoneid='".$get[2]."' AND field_type IN ('T','Z','A')");
	$template['zone_elements'] = $zone_elements;

	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/zones">'.lng('Detination zones').'</a>';
	$template['location'] .= ' &gt; '.lng('Zone details');
	$template['page'] = get_template_contents('admin/pages/zone_edit.php');
} else {
	$zones = $db->all("SELECT * FROM zones ORDER BY zone_name");
	$template['location'] .= ' &gt; '.lng('Detination zones');

	$template['zones'] = $zones;
	$template['page'] = get_template_contents('admin/pages/zones.php');
}

$template['css'][] = 'admin_zones';
$template['js'][] = 'admin_zones';