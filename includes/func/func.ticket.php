<?php
// Security: include upload validation functions (SEC-CRIT-4)
if (!function_exists('spacart_validate_upload')) {
    require_once dirname(__FILE__) . '/func.security.php';
}

#
# Generate ticket #id
#
function func_generate_ticketid() {
	global $db;

	$tmp = strtoupper(substr(md5(time()), 0, 7));
	$i = 0;
	while ($db->field("SELECT ticketid FROM tickets WHERE ticketid='$tmp'")) {
		$tmp = strtoupper(substr(md5(time().$i), 0, 7));
		$i++;
	}

	return $tmp;
}

#
# Create a new ticket
#
function func_create_ticket($ticket, $attachments, $to_admin=false, $crontab='') {
	global $db, $config, $_SERVER;

	$ticketid = func_generate_ticketid();
	$ticket['ticketid'] = $ticketid;
	$db->array2insert("tickets", $ticket);

	$message = array(
		   'message' => $ticket['message'],
		   'email' => $ticket['email'],
		   'userid' => $ticket['userid'],
		   'date' => $ticket['date'],
		   'read' => $ticket['read'],
		   'admin_read' => $ticket['admin_read'],
		   'ticketid' => $ticketid,
		   'ip' => $_SERVER['REMOTE_ADDR']
	);

	$messageid = func_create_message($message, $attachments, 'ticket', $crontab);

	if ($to_admin) {
		func_send_new_ticket($ticketid, $to_admin, $crontab);
	} else {
		func_send_new_ticket($ticketid, $to_admin, $crontab);
	}


	// Sync ticket to Dolibarr ERP
	if (function_exists('spacart_sync_ticket_to_dolibarr')) {
		spacart_sync_ticket_to_dolibarr($ticket);
	}
	return $ticketid;
}

#
# Create a new message
#
function func_create_message($message, $attachments, $to_admin=false, $crontab='') {
	global $db, $config;

	$messageid = $db->array2insert("tickets_messages", $message);
	$ticketid = $message['ticketid'];

	mkdir(SITE_ROOT.'/files/tickets/'.$ticketid);
	if (!empty($attachments)) {
		if ($to_admin == 'ticket') {
			$attach_messageid = 0;
		} else
			$attach_messageid = $messageid;

		if ($crontab) {
			foreach ($attachments as $file) {
				// SEC-CRIT-4: Validate file extension before saving
				$_ext = strtolower(pathinfo($file['FileName'], PATHINFO_EXTENSION));
				$_blocked = array('php','php3','php4','php5','php7','phtml','pht','phps','phar','cgi','pl','py','sh','bat','exe','com','dll','asp','aspx','jsp','htaccess','shtml');
				if (in_array($_ext, $_blocked)) {
					continue; // Skip dangerous files
				}
				$file_path = SITE_ROOT.'/files/tickets/'.$ticketid.'/'.$attach_messageid;
				if (!is_dir($file_path))
					mkdir($file_path);

				// Sanitize filename
				$safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['FileName']);
				$file_path .= '/'.$safe_filename;
				$fp = fopen($file_path, 'w');
				fputs($fp, $file['Data']);
				fclose($fp);
				$db->query("INSERT INTO tickets_attachments SET date='".time()."', ticketid='$ticketid', messageid='$attach_messageid', file_name='".addslashes($safe_filename)."', file_path='".addslashes($file_path)."'");
			}
		} else {
			$ticket_path = SITE_ROOT.'/files/tickets/'.$ticketid;
			if (!is_dir($ticket_path))
				mkdir($ticket_path);

			foreach ($attachments['tmp_name'] as $k=>$v) {
				if (file_exists($v)) {
					// SEC-CRIT-4: Validate uploaded file
					$_validation = spacart_validate_upload_multi($attachments, $k);
					if (!$_validation['valid']) {
						continue; // Skip invalid files
					}

					$new_path = $ticket_path."/".$attach_messageid;
					if (!is_dir($new_path))
						mkdir($new_path);

					$file_name = $_validation['safe_name'];
					$new_path .= '/'.$file_name;
					copy($v, $new_path);
					$db->query("INSERT INTO tickets_attachments SET ticketid='$ticketid', messageid='$attach_messageid', date='".time()."', file_path='".addslashes($new_path)."', file_name='".addslashes($file_name)."'");
				}
			}
		}
	}

	if ($config['Tickets']['tickets_send_email'] == '5') return $messageid;

	if ($to_admin == 'ticket') {
	} elseif ($to_admin) {
		func_send_new_message($messageid, $to_admin, $crontab);
	} else {
		func_send_new_message($messageid, $to_admin, $crontab);
	}


	// Sync ticket message to Dolibarr ERP (only for replies, not initial ticket message)
	if ($to_admin !== 'ticket' && function_exists('spacart_sync_ticket_message')) {
		spacart_sync_ticket_message($message['ticketid'], $message);
	}
	return $messageid;
}

#
# Send new ticket email notification
#
function func_send_new_ticket($ticketid, $to_admin=false, $crontab='') {
	global $db, $config, $xcart_dir, $template, $login;

	$ticket = $db->row("SELECT * FROM tickets WHERE ticketid='$ticketid'");
	$ticket['message'] = func_eol2br($ticket['message']);

	$template['ticket'] = $ticket;

	$userinfo = $db->row("SELECT * FROM users WHERE id='$ticket[userid]'");
	$template['userinfo'] = $userinfo;
	$email = $ticket["email"];

	$tmp = $db->all("SELECT * FROM tickets_attachments WHERE ticketid='$ticketid' AND messageid=''");
	$attachments = array();
	if (!empty($tmp)) {
		foreach ($tmp as $k=>$v) {
			$file_path = SITE_ROOT . '/files/tickets/' . $ticketid . '/' . $v['messageid'] . '/';
			$attachments[] = array(
					 'name' => $v['file_name'],
					 'type' => 'application/force-download',
					 'data' => file_get_contents($file_path . $v['file_name']),
					 'file_path'	=> $file_path . $v['file_name']
			);
		}
	}

	if ($to_admin) {
		if ($config['Tickets']['tickets_email'])
			$from = $config['Tickets']['tickets_email'];
		else
			$from = $config["Company"]["support_department"];

		if ($config['Tickets']['tickets_to_email']) {
			$to = $config['Tickets']['tickets_to_email'];
		} else {
			$to = $config["Company"]["support_department"];
		}

		$to = $config["Company"]["support_department"];
		$log_email = array(
		     "ticketid" => $ticketid,
		     "date" => time(),
		     "from" => $email,
		     "to" => $to,
		     'crontab' => $crontab,
		     "type" => "New ticket"
		);

		$db->array2insert("tickets_emails", $log_email);

		$message = get_template_contents('mail/ticket_posted_admin.php');
		$subject = $config['Company']['company_name'].': New ticket #'.$ticket['ticketid'];
		$from = $from;
		func_mail($config['Company']['company_name'], $to, $from, $subject, $message, '', $attachments);
	} else {
		if (!empty($config['Tickets']['tickets_email_'.$ticket['type']])) {
			$from = $config['Tickets']['tickets_email_'.$ticket['type']];
		} else {
			$from = $config["Company"]["support_department"];
		}

		$log_email = array(
		     "ticketid" => $ticketid,
		     "date" => time(),
		     "from" => $from,
		     'crontab' => $crontab,
		     "to" => $email,
		     "type" => "New ticket"
		);

		$db->array2insert("tickets_emails", $log_email);
		exit;
		$message = get_template_contents('mail/ticket_posted.php');
		$subject = $config['Company']['company_name'].': New ticket #'.$ticket['ticketid'];
		func_mail($config['Company']['company_name'], $to, '', $subject, $message, '', $attachments);
	}
}

#
# Send new message email notification
#
function func_send_new_message($messageid, $to_admin=false, $crontab='') {
	global $db, $config, $template;

	$message = $db->row("SELECT * FROM tickets_messages WHERE messageid='$messageid'");
	$ticket = $db->row("SELECT * FROM tickets WHERE ticketid='$message[ticketid]'");
	$message['message'] = func_eol2br($message['message']);
	$template['ticket'] = $ticket;
	$template['message'] = $message;
	if ($ticket['productid']) {
		$product = $db->row("SELECT * FROM products WHERE productid='".addslashes($ticket['productid'])."'");
		$template['product'] = $product;
	}

	$userinfo = $db->row("SELECT * FROM users WHERE id='$ticket[userid]'");
	$template['userinfo'] = $userinfo;
	$email = $ticket["email"];

	$tmp = $db->all("SELECT * FROM tickets_attachments WHERE ticketid='$ticket[ticketid]' AND messageid='$messageid'");
	$attachments = array();
	if (!empty($tmp)) {
		foreach ($tmp as $k=>$v) {
			$file_path = SITE_ROOT . '/files/tickets/' . $ticket['ticketid'] . '/' . $v['messageid'] . '/';
			$attachments[] = array(
					 'name' => $v['file_name'],
					 'type' => 'application/force-download',
					 'data' => file_get_contents($file_path . $v['file_name']),
					 'file_path'	=> $file_path . $v['file_name']
			);
		}
	}

	if ($to_admin) {
		if ($config['Tickets']['tickets_email']) {
			$from = $config['Tickets']['tickets_email'];
		} else {
			$from = $config["Company"]["support_department"];
		}

		if ($config['Tickets']['tickets_to_email']) {
			$to = $config['Tickets']['tickets_to_email'];
		} else {
			$to = $config["Company"]["support_department"];
		}

		$to = $config["Company"]["support_department"];
		$log_email = array(
		     "ticketid" => $ticket['ticketid'],
		     "messageid" => $messageid,
		     "date" => time(),
		     "from" => $from,
		     "to" => $to,
		     'crontab' => $crontab,
		     "type" => "New message"
		);

		$db->array2insert("tickets_emails", $log_email);
		$message = get_template_contents('mail/ticket_message_posted_admin.php');
		$subject = $config['Company']['company_name'].': New message on ticket [#'.$ticket['ticketid'].']';
		$from = $from;
		func_mail($config['Company']['company_name'], $to, $from, $subject, $message, '', $attachments);
	} else {
		if ($config['Tickets']['tickets_email'])
			$from = $config['Tickets']['tickets_email'];
		else
			$from = $config["Company"]["support_department"];

		$log_email = array(
		     "ticketid" => $ticket['ticketid'],
		     "messageid" => $messageid,
		     "date" => time(),
		     "from" => $from,
		     "to" => $email,
		     'crontab' => $crontab,
		     "type" => "New message"
		);

		$db->array2insert("tickets_emails", $log_email);

		$message = get_template_contents('mail/ticket_message_posted.php');
		if ($ticket['productid']) {
			$subject = $config['Company']['company_name'].': Product #'.$product['sku'].' [#'.$ticket['ticketid'].']';
		} else
			$subject = $config['Company']['company_name'].': New message on ticket [#'.$ticket['ticketid'].']';

		func_mail($userinfo['firstname'].' '.$userinfo['lastname'], $email, $from, $subject, $message, '', $attachments);
	}
}

function func_ticket_cats() {
	return false;
	global $db;

	$cats = $db->all("SELECT * FROM tickets_cats");
	if (!$cats)
		return;

	foreach ($cats as $k=>$v) {
		$v = array_map('addslashes', $v);
		$fields = $db->all("SELECT * FROM tickets_cats_fields WHERE cat1='".$v['cat1']."' AND cat2='".$v['cat2']."' AND cat3='".$v['cat3']."' AND cat4='".$v['cat4']."' AND cat5='".$v['cat5']."'");
		if ($fields)
			foreach ($fields as $f)
				$cats[$k]['fields'][] = $f;
	}

	return $cats;
}

function func_can_post_ticket() {
	global $db, $login;

	return !$db->field("SELECT COUNT(*) FROM tickets WHERE status IN ('O', '1', '2') AND userid='$login'");
}