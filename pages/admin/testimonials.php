<?php
if ($_SERVER['REQUEST_METHOD'] == "POST") {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'delete' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v)
        	$db->query("DELETE FROM testimonials WHERE tid='$k'");
	} elseif ($mode == 'update') {
		foreach ($to_update as $k=>$v)
        	$db->array2update("testimonials", $v, "tid='$k'");
	} elseif ($mode == 'edit' && !empty($to_edit)) {
		$db->array2update("testimonials", $to_edit, "tid='".$get['2']."'");
	}

	redirect('/admin/testimonials'.($get['2'] ? '/'.$get['2'] : ''));
}

if ($get['2']) {
	$testimonial = $db->row("SELECT * FROM testimonials WHERE tid='".$get['2']."'");
	if ($testimonial['userid'])
		$testimonial['user'] = $db->row("SELECT * FROM users WHERE id='".$testimonial['userid']."'");

	$template['testimonial'] = $testimonial;
	$template['location'] .= ' &gt; <a href="'.$current_location.'/admin/testimonials">'.lng('Testimonials').'</a>';
	$template['location'] .= ' &gt; #'.$testimonial['tid'];
	$template['head_title'] = 'Testimonial #'.$testimonial['tid'].' :: '.$template['head_title'];
} else {
	$total_items = $db->field("SELECT COUNT(*) FROM testimonials");
	if ($total_items > 0) {
		$objects_per_page = 15;

        # Navigation code
        require SITE_ROOT . "/includes/navigation.php";
		$testimonials = $db->all("SELECT t.*, u.email FROM testimonials t LEFT JOIN users u ON u.id=t.userid ORDER BY t.status DESC, t.tid DESC LIMIT $first_page, $objects_per_page");
		$template['testimonials'] = $testimonials;
		$template['navigation_script'] = "/admin/testimonials?";
	}

	$template['location'] .= ' &gt; '.lng('Testimonials');
	$template['head_title'] = lng('Testimonials').' :: '.$template['head_title'];
}

$template['page'] = get_template_contents('admin/pages/testimonials.php');

$template['css'][] = 'admin_user';
$template['js'][] = 'states';