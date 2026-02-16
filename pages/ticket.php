<?php
if ($get['1'] == 'status') {
	$db->query("UPDATE tickets SET status='".addslashes($get['3'])."' WHERE ticketid='".addslashes($get['2'])."' AND userid='$login'");
	exit;
}

if ($get['1'] == 'attachment') {
	$get[2] = addslashes($get[2]);
	$file = $db->row("SELECT * FROM tickets_attachments WHERE attachid='$get[2]'");
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=\"".$file['file_name']."\"");
	readfile(SITE_ROOT . '/files/tickets/' . $file['ticketid'] . '/' . $file['messageid'] . '/' . $file['file_name'], true);
	exit;
}

q_load('ticket');

if (!isset($_SESSION['mytickets']))
	$_SESSION['mytickets'] = array();

$template['ticket_cats'] = $ticket_cats = func_ticket_cats();
$get['1'] = addslashes($get['1']);

$template['head_title'] = lng('Support Desk').'. '.$template['head_title'];
$template['bread_crumbs'][] = array('/support_desk', lng('Support Desk'));

if ($get['1']) {
	if ($_GET['authkey']) {
		$ticket = $db->row("SELECT * FROM tickets WHERE authkey='".addslashes($_GET['authkey'])."'");
		if ($ticket)
			$_SESSION['mytickets'][] = $ticket['ticketid'];
	}

	if (!$login && empty($_SESSION['mytickets'])) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('You need to be logged in to access this page')
		);

		redirect('/');
	}

	if ($_SESSION['mytickets']) {
		$ticket = $db->row("SELECT * FROM tickets WHERE ticketid='".$get['1']."' AND ticketid IN ('".implode("','", $_SESSION['mytickets'])."')");
		if (!$ticket && !$login) {
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('You need to be logged in to access this page')
			);

			redirect('/');
		}
	} else
		$ticket = $db->row("SELECT * FROM tickets WHERE ticketid='".$get['1']."' AND userid='$login'");

	if (!$ticket) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('You have no access to this ticket')
		);

		redirect("/support_desk");
	}

	$ticketid = $ticket['ticketid'];
	$tmp = $db->all("SELECT * FROM tickets_attachments WHERE messageid='' AND ticketid='$ticket[ticketid]'");
	if (!empty($tmp))
		$ticket['attachments'] = $tmp;

	$ticket['fields'] = unserialize($ticket['fields']);
	$template['bread_crumbs'][] = array('', lng('Ticket').' #'.$ticket['ticketid']);
} else
	$template['bread_crumbs'][] = array('', lng('New ticket'));

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	// SEC-CRIT-6: CSRF token verification
	if (!spacart_csrf_verify()) {
		$_SESSION["alerts"][] = array(
			"type"		=> "e",
			"content"	=> lng("Session expired. Please try again.")
		);
		redirect("/ticket");
	}

	extract($_GET, EXTR_SKIP);
	extract($_POST, EXTR_SKIP);
	$ticketid = $ticket['ticketid'];
	if (!$ticket) {
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
				$_SESSION['predefined_ticket'] = $_POST;
				$_SESSION['alerts'][] = array(
					'type'		=> 'e',
					'content'	=> lng('Captcha is incorrect.')
				);

				redirect('/ticket'.($productid ? '?productid='.$productid : ''));
			}
		}

		if (!$subject || !$message || (!$email && !$login)) {
			$_SESSION['predefined_ticket'] = $_POST;
			$_SESSION['alerts'][] = array(
				'type'		=> 'e',
				'content'	=> lng('Please, enter all fields.')
			);

			redirect('/ticket'.($productid ? '?productid='.$productid : ''));
		}

		if ($ticket_field)
			foreach ($ticket_field as $k=>$v)
				$ticket_field[$k] = str_replace("\n", "<br />", $v);

		$to_insert = array(
			'authkey'	=> md5(time().($userinfo['email'] ? $userinfo['email'] : $email)),
			     'type' => ($productid ? 'P' : 'C'),
			     'productid'	=> $productid,
			     'priority' => $priority,
			     'userid' => $login,
			     'email' => ($userinfo['email'] ? $userinfo['email'] : $email),
			     'subject' => $subject,
			     'message' => trim($message),
			     'status' => 'O',
			     'admin_read' => 'N',
			     'read' => 'Y',
			     'date' => time(),
			     'cat1'		=> $ticket_cat_1,
			     'cat2'		=> $ticket_cat_2,
			     'cat3'		=> $ticket_cat_3,
			     'cat4'		=> $ticket_cat_4,
			     'cat5'		=> $ticket_cat_5,
			     'fields'	=> serialize($ticket_field),
		);

		$attachments = $_FILES['attachments'];
		if (!$login)
			$_SESSION['ticket_email'] = $email;

		$ticketid = func_create_ticket($to_insert, $attachments, true);
		$_SESSION['mytickets'][] = $ticketid;
		$_SESSION['predefined_ticket'] = '';
	} elseif ($mode == 'create_new') {
		if (empty($message)) {
			$_SESSION['predefined_ticket'] = $_POST;
			$_SESSION['alerts'][] = array(
				       'type' => 'e',
				       'content' => lng('Please enter ticket message')
			);

			redirect("ticket/$ticketid#create_new");
		}

		if ($config['Tickets']['tickets_close'] == 'Y')
			$db->query("UPDATE tickets SET status='O' WHERE ticketid='$ticketid'");

		$attachments = $_FILES['attachments'];
		$to_insert = array(
			     'admin_read' => 'N',
			     'read' => 'Y',
			     'message' => trim($message),
			     'userid' => $login,
			     'email' => $userinfo['email'],
			     'date' => time(),
			     'ticketid' => $ticketid
		);

		$messageid = func_create_message($to_insert, $attachments, '123');

		$arg = "#m".$messageid;
		$_SESSION['predefined_ticket'] = '';
	}

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> lng('Ticket created. Please, wait our answer to your email.')
	);

	redirect("/ticket/".$ticketid);
}

if (!empty($ticketid)) {
	$template['ticket'] = $ticket;

	$total_items = $db->field("SELECT COUNT(*) FROM tickets_messages WHERE ticketid='$ticketid'");
	if ($total_items > 0) {
		$objects_per_page = $config['Tickets']['tickets_messages_per_page'];

		require SITE_ROOT."/includes/navigation.php";

		$messages = $db->all("SELECT users.lastname, users.firstname, tickets_messages.* FROM tickets_messages LEFT JOIN users ON tickets_messages.userid=users.id WHERE tickets_messages.ticketid='$ticketid' GROUP BY tickets_messages.messageid ORDER BY tickets_messages.messageid DESC LIMIT $first_page, $objects_per_page");
		if (!empty($messages)) {
			foreach ($messages as $k=>$v) {
				$tmp = $db->all("SELECT * FROM tickets_attachments WHERE messageid='$v[messageid]' AND ticketid='$v[ticketid]'");
				if (!empty($tmp))
					$messages[$k]['attachments'] = $tmp;
			}
		}

		$template['messages'] = $messages;

		$template['navigation_script'] = "/ticket/$ticketid";
	}

	$db->query("UPDATE tickets_messages SET `read`='Y' WHERE ticketid='$ticketid'");
	$db->query("UPDATE tickets SET `read`='Y' WHERE ticketid='$ticketid'");
}

if ($_GET['productid']) {
	$product = $db->row("SELECT * FROM products WHERE productid='".addslashes($_GET['productid'])."'");
	if (!$_SESSION['predefined_ticket']['subject']) {
		$_SESSION['predefined_ticket']['subject'] = 'Product #'.$product['sku'];
	}

	$template['product'] = $product;
}

$template["predefined"] = $_SESSION['predefined_ticket'];

$template['page'] = get_template_contents('support_desk/ticket.php');
$template['js'][] = 'ticket';
$template['css'][] = 'ticket';
