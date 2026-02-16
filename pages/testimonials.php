<?php
if ($get['1'] == 'new') {
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$_SESSION['new_testimonial'] = $_POST;

		$rc_key = $config['General']['recaptcha_key'];
		$rc_skey = $config['General']['recaptcha_skey'];
		if ($rc_key && $rc_skey) {
			$postdata = http_build_query(
    			array(
	        		'secret' => $rc_skey,
		        	'response' => $_POST['g-recaptcha-response']
    			)
			);

			$opts = array('http' =>
			    array(
        			'method'  => 'POST',
		    	    'header'  => 'Content-type: application/x-www-form-urlencoded',
        			'content' => $postdata
			    )
			);

			$context  = stream_context_create($opts);
			$result = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
			if (strstr($result, 'false') || !$_POST['g-recaptcha-response']) {
				$_SESSION['saved_contact'] = $_POST;
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Captcha is incorrect.')
				);

				redirect('/testimonials/new');
			}
		}

		extract($_POST, EXTR_SKIP);
		$_SESSION['new_testimonial'] = array();
		$to_insert = array(
			"status" => 'P',
			'name' => $name,
			'message' => $message,
			'userid' => $login,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'date' => time(),
			'url' => $url
		);

		$db->array2insert("testimonials", $to_insert);
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('Thank you for adding your testimonial. Now it will be waiting for our approval')
		);

		$message = get_template_contents('mail/testimonial.php');
		$subject = $config['Company']['company_name'].': '.lng('New testimonial');
		func_mail($config['Company']['company_name'], $config['Company']['support_department'], '', $subject, $message);

		redirect('/testimonials');
	}

	if (!$_SESSION['new_testimonial']) {
		$_SESSION['new_testimonial'] = array(
			'name'	=> $userinfo['firstname'].' '.$userinfo['lastname']
		);
	}

	$template['preentered'] = $_SESSION['new_testimonial'];
	$template['bread_crumbs'][] = array('/testimonials', "Testimonials");
	$template['bread_crumbs'][] = array('', "New testimonial");
	$template['head_title'] = lng('New testimonial').'. '.$template['head_title'];
	$template['page'] = get_template_contents('testimonials/new.php');
} else {
	$total_items = $db->field("SELECT COUNT(*) FROM testimonials WHERE status='A'");
	if ($total_items > 0) {
		$objects_per_page = 15;

        # Navigation code
        require SITE_ROOT . "/includes/navigation.php";
		$testimonials = $db->all("SELECT * FROM testimonials WHERE status='A' ORDER BY status DESC, tid DESC LIMIT $first_page, $objects_per_page");
		foreach ($testimonials as $k=>$t) {
			$t['message'] = func_eol2br($t['message']);
			if ($t['url'] && !strstr($t['url'], 'http'))
				$t['url'] = 'http://'.$t['url'];

			$testimonials[$k] = $t;
		}

		$template['testimonials'] = $testimonials;
		$template['navigation_script'] = "/testimonials?";
	}

	$template['bread_crumbs'][] = array('', "Testimonials");
	$template['head_title'] = lng('Testimonials').'. '.$template['head_title'];
	$template['page'] = get_template_contents('testimonials/list.php');
}