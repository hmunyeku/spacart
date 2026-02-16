<?php
extract($_GET, EXTR_SKIP);
extract($_POST, EXTR_SKIP);

q_load('ticket');

$get['2'] = addslashes($get['2']);

$tmp = func_ticket_cats();
if ($tmp) {
	$ticket_cats = array();
	foreach ($tmp as $k=>$v) {
		$cat = $v['cat1'];
		if ($v['cat2'])
			$cat .= ' &gt; '.$v['cat2'];

		if ($v['cat3'])
			$cat .= ' &gt; '.$v['cat3'];

		if ($v['cat4'])
			$cat .= ' &gt; '.$v['cat4'];

		if ($v['cat5'])
			$cat .= ' &gt; '.$v['cat5'];

		$ticket_cats[$v['cat1'].'|||'.$v['cat2'].'|||'.$v['cat3'].'|||'.$v['cat4'].'|||'.$v['cat5']] = $cat;
	}

	$template['ticket_cats'] = $ticket_cats;
}

	$ticket = $db->row("SELECT * FROM tickets WHERE ticketid='".$get['2']."'");
	if (!$ticket) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> lng('No such ticket')
		);

		redirect("/admin/support_desk");
	}

	$ticketid = $ticket['ticketid'];
	$ticket['customer'] = $db->row("SELECT * FROM users WHERE id='".$ticket['userid']."'");
	$tmp = $db->all("SELECT * FROM tickets_attachments WHERE messageid='' AND ticketid='$ticket[ticketid]'");
	if (!empty($tmp))
		$ticket['attachments'] = $tmp;

	$ticket['fields'] = unserialize($ticket['fields']);
	$template['ticket'] = $ticket;

if ($mode == 'delete_message') {
	$db->query("DELETE FROM tickets_messages WHERE messageid='$messageid'");
	$attachments = $db->all("SELECT * FROM tickets_attachments WHERE messageid='$messageid'");
	if (!empty($attachments)) {
		foreach ($attachments as $k=>$v) {
			unlink(SITE_ROOT.'/'.$v['file_path']);
		}

		$db->query("DELETE FROM tickets_attachments WHERE messageid='$messageid'");
	}

	redirect("/admin/ticket/$ticketid#messages");
}

if ($mode == "clone_message") {
	$message = $db->row("SELECT * FROM tickets_messages WHERE messageid='$messageid'");
	$message['ticketid'] = $_GET['to'];
	unset($message['messageid']);

	$new_messageid = $db->array2insert("tickets_messages", $message);

	$attachments = $db->all("SELECT * FROM tickets_attachments WHERE messageid='$messageid'");
	if (!empty($attachments)) {
		$file_path = SITE_ROOT . "/files/tickets/".$ticketid."/".$messageid;
		foreach ($attachments as $k=>$v) {
			$v['messageid'] = $new_messageid;
			$v['ticketid'] = $to;
			$new_file_path = SITE_ROOT . "/files/tickets/".$to."/".$new_messageid."/".$new_messageid."_".$v['file_name'];
			if (!is_dir(SITE_ROOT . "/files/tickets/$to"))
				mkdir(SITE_ROOT . "/files/tickets/$to");

			if (!is_dir(SITE_ROOT . "/files/tickets/$to/$new_messageid"))
				mkdir(SITE_ROOT . "/files/tickets/$to/$new_messageid");

			copy($file_path.'/'.$v['file_name'], $new_file_path);
			unset($v['attachid']);
			$v['file_path'] = $new_file_path;
			$db->array2insert("tickets_attachments", $v);
		}
	}

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> 'Message has been successfully cloned'
	);

	redirect("/admin/ticket/".$ticketid);
}

if ($mode == "move") {
	$db->query("DELETE FROM tickets WHERE ticketid='$ticketid'");

	$messages = $db->all("SELECT * FROM tickets_messages WHERE ticketid='$ticketid' ORDER BY messageid");
	foreach ($messages as $k=>$v) {
		$insert = $v;
		unset($insert['messageid']);
		$insert['ticketid'] = $to;
		$messageid = $db->array2insert("tickets_messages", $insert);
		$attachments = $db->all("SELECT * FROM tickets_attachments WHERE messageid='$v[messageid]'");
		if (!empty($attachments)) {
			$file_path = SITE_ROOT . "/files/tickets/".$ticketid."/".$v['messageid'];
			foreach ($attachments as $k=>$v) {
				$v['messageid'] = $messageid;
				$v['ticketid'] = $to;
				$new_file_path = SITE_ROOT . "/files/tickets/".$to."/".$messageid."/".$messageid."_".$v['file_name'];
				if (!is_dir(SITE_ROOT . "/files/tickets/$to"))
					mkdir(SITE_ROOT . "/files/tickets/$to");

				if (!is_dir(SITE_ROOT . "/files/tickets/$to/$new_messageid"))
					mkdir(SITE_ROOT . "/files/tickets/$to/$messageid");

				copy($file_path.'/'.$v['file_name'], $new_file_path);
				unset($v['attachid']);
				$v['file_path'] = $new_file_path;
				$db->array2insert("tickets_attachments", $v);
			}
		}
	}

	$db->query("DELETE FROM tickets_messages WHERE ticketid='$ticketid'");

	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> 'Ticket has been successfully moved'
	);

	redirect("/admin/ticket/".$to);
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	if (empty($ticketid)) {
		exit;
		$attachments = $_FILES['attachments'];
		$to_insert = array(
					     'type' => $ticket['type'],
					     'priority' => $ticket['priority'],
					     'email' => $ticket['customer_email'],
					     'subject' => $ticket['subject'],
					     'message' => $ticket['message'],
					     'status' => $ticket['status'],
					     'admin_read' => 'Y',
					     'date' => time(),
					     'open_date' => $open_date
		);

		if (!empty($ticket['login']) && count($ticket['login']) > 1) {
			$ticketids = array();
			foreach ($ticket['login'] as $k=>$v) {
				$to_insert['login'] = $v;
				$ticketids[] = func_create_ticket($to_insert, $attachments);
			}

			$top_message['content'] = 'New tickets has been successfully created.<br />';
			foreach ($ticketids as $k=>$v) {
				$top_message['content'] .= '<a href="ticket.php?ticketid='.$v.'">#'.$v.'</a><br />';
			}

			redirect("/admin/support_desk");
		} else {
			$to_insert['login'] = $customer_login;
			$ticketid = func_create_ticket($to_insert, $attachments);
		}
	} elseif ($mode == 'delete_messages') {
		$db->query("DELETE FROM tickets_messages WHERE ticketid='$ticketid'");
	} elseif ($mode == 'delete_messages' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM tickets_messages WHERE messageid='$k'");
		}
		$arg = "#messages";
	} elseif ($mode == 'update_message' && $messageid) {
		$db->query("UPDATE tickets_messages SET message='".addslashes($message)."' WHERE messageid='$messageid'");
		$top_message['content'] = 'Message has been successfully updated.';
		$arg = "#messages";
	} elseif ($mode == 'add_message' && !empty($message)) {
		if ($ticket['status'] == 'O' && $config['Tickets']['tickets_close'] == 'Y')
			$db->query("UPDATE tickets SET status='C' WHERE ticketid='$ticketid'");

		$attachments = $_FILES['attachments'];
		$to_insert = array(
			     'admin_read' => 'Y',
			     'message' => $message,
			     'userid' => $login,
			     'date' => time(),
			     'ticketid' => $ticketid,
			     'email'	=> $userinfo['email']
		);

		$messageid = func_create_message($to_insert, $attachments);

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'New message has been successfully created.'
		);

		$arg = "#messages";
	} elseif ($mode == 'delete') {
		$db->query("DELETE FROM tickets WHERE ticketid='$ticketid'");
		$db->query("DELETE FROM tickets_messages WHERE ticketid='$ticketid'");

		$attachments = $db->all("SELECT * FROM tickets_attachments WHERE ticketid='$ticketid'");
		if (!empty($attachments)) {
			foreach ($attachments as $k=>$v) {
				unlink(SITE_ROOT.'/'.$v['file_path']);
			}

			$db->query("DELETE FROM tickets_attachments WHERE ticketid='$ticketid'");
		}

		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> lng('lbl_ticket_deleted')
		);

		redirect('/admin/support_desk');
	} elseif (!empty($ticketid)) {
		$cats = explode('|||', $ticket_category);
		$ticket = $_POST['ticket'];
		$to_update = array(
		     'type' => $ticket['type'],
		     'subject' => $ticket['subject'],
		     'message' => $ticket['message'],
		     'status' => $ticket['status'],
		     'priority' => $ticket['priority'],
		     'notes'		=> $ticket['notes'],
		     'open_date'	=> $open_date,
		     'cat1'			=> $cats[0],
		     'cat2'			=> $cats[1],
		     'cat3'			=> $cats[2],
		     'cat4'			=> $cats[3],
		     'cat5'			=> $cats[4],
		);

		$db->array2update("tickets", $to_update, "ticketid='$ticketid'");
		$_SESSION['alerts'][] = array(
			'type'		=> 'i',
			'content'	=> 'Ticket has been successfully updated.'
		);

		$arg = '#';
	}

	redirect('/admin/ticket/'.$ticketid.$arg);
	exit;
}

if (!empty($ticketid)) {
	$total_items = $db->field("SELECT COUNT(*) FROM tickets_messages WHERE ticketid='$ticketid'");
	if ($total_items > 0) {
		$objects_per_page = $config['Tickets']['tickets_messages_per_page_admin'];
		require SITE_ROOT."/includes/navigation.php";
		$messages = $db->all("SELECT users.lastname, users.firstname, tickets_messages.* FROM tickets_messages LEFT JOIN users ON tickets_messages.userid=users.id WHERE tickets_messages.ticketid='$ticketid' GROUP BY tickets_messages.messageid ORDER BY tickets_messages.messageid DESC LIMIT $first_page, $objects_per_page");

		foreach ($messages as $k=>$v) {
			$tmp = $db->all("SELECT * FROM tickets_attachments WHERE messageid='$v[messageid]' AND ticketid='$v[ticketid]'");
			if (!empty($tmp))
				$messages[$k]['attachments'] = $tmp;
		}

		$template['messages'] = $messages;
		$template['navigation_script'] = $current_location."/admin/ticket/".$ticketid.'?';
	}

	$db->query("UPDATE tickets_messages SET `admin_read`='Y' WHERE ticketid='$ticketid'");
	$db->query("UPDATE tickets SET `admin_read`='Y' WHERE ticketid='$ticketid'");

	if ($ticket['productid']) {
		$product = $db->row("SELECT * FROM products WHERE productid='".addslashes($ticket['productid'])."'");
		$template['product'] = $product;
	}
}

$tickets = $db->all("SELECT * FROM tickets WHERE ticketid<>'$ticketid' AND userid='$ticket[userid]' ORDER BY date DESC");
$template['tickets'] = $tickets;

$template['location'] .= ' &gt; <a href="/admin/support_desk?mode=search">'.lng('Support desk').'</a> &gt; '.lng('Ticket');
$template['head_title'] = lng('Support desk').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/ticket.php');
