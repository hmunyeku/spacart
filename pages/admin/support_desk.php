<?php
q_load('ticket');
if ($get['2'] == 'reset') {
	$_SESSION['search_sd'] = array();
	redirect('/admin/support_desk');
}

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

if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_POST['mode'])) {
	extract($_POST, EXTR_SKIP);
	if ($mode == 'update') {
		foreach ($statuses as $k=>$v) {
			$db->query("UPDATE tickets SET status='$v' WHERE ticketid='$k'");
		}
	} elseif ($mode == 'delete' && !empty($to_delete)) {
		foreach ($to_delete as $k=>$v) {
			$db->query("DELETE FROM tickets WHERE ticketid='$k'");
			$db->query("DELETE FROM tickets_attachments WHERE ticketid='$k'");
			$db->query("DELETE FROM tickets_messages WHERE ticketid='$k'");
			$db->query("DELETE FROM tickets_emails WHERE ticketid='$k'");
		}
	}

	redirect("/admin/support_desk/?mode=search".$pagestr);
}

if ($_SERVER['REQUEST_METHOD'] == "POST" && $get['2'] == 'search') {
	$_SESSION['search_sd'] = $_POST['posted_data'];
	$tmp = explode('|||', $_SESSION['search_sd']['ticket_category']);
	$_SESSION['search_sd']['cat1'] = $tmp[0];
	$_SESSION['search_sd']['cat2'] = $tmp[1];
	$_SESSION['search_sd']['cat3'] = $tmp[2];
	$_SESSION['search_sd']['cat4'] = $tmp[3];
	$_SESSION['search_sd']['cat5'] = $tmp[4];
	redirect("/admin/support_desk/?mode=search");
}

if ($_GET['mode'] == "search") {
	extract($_GET, EXTR_SKIP);
	$search_data = $_SESSION['search_sd'];
	$search_data["sort_field"] = 'date';
	$search_data["sort_direction"] = '1';

	$data = array();

	if (!empty($page) && $search_data["page"] != intval($page)) {
		# Store the current page number in the session
		$search_data["page"] = $page;
	}

	if (is_array($search_data)) {
		$data = $search_data;
		foreach ($data as $k=>$v)
			if (!is_array($v) && !is_numeric($v))
				$data[$k] = addslashes($v);
	}

	$condition = array();
	#
	# Search by date condition
	#
	if (!empty($data["date_period"]) && $data['date_period'] != 'A') {
		if ($data["date_period"] == "C") {
			$tmp = explode('/', $search_data['date_from']);
			$start_date = mktime(0,0,0,$tmp['0'],$tmp['1'],$tmp['2']);

			$tmp = explode('/', $search_data['date_to']);
			if ($tmp['0'])
				$end_date = mktime(23,59,59,$tmp['0'],$tmp['1'],$tmp['2']);
		}
		else {
			# ...orders within this month
			$end_date = time();
			if ($data["date_period"] == "M") {
				$start_date = mktime(0,0,0,date("n",$end_date),1,date("Y",$end_date));
			}
			elseif ($data["date_period"] == "D") {
				$start_date = mktime(0,0,0,date("n",$end_date),date("j",$end_date),date("Y",$end_date));
			}
			elseif ($data["date_period"] == "W") {
				$first_weekday = $end_date - (date("w",$end_date) * 86400);
				$start_date = mktime(0,0,0,date("n",$first_weekday),date("j",$first_weekday),date("Y",$first_weekday));
			}

			$end_date = time();
		}

		$condition[] = "tickets.date>='".($start_date)."'";
		$condition[] = "tickets.date<='".($end_date)."'";
	}

	if ($data['cat1'])
		$condition[] = "tickets.cat1='".addslashes($data['cat1'])."'";

	if ($data['cat2'])
		$condition[] = "tickets.cat2='".addslashes($data['cat2'])."'";

	if ($data['cat3'])
		$condition[] = "tickets.cat3='".addslashes($data['cat3'])."'";

	if ($data['cat4'])
		$condition[] = "tickets.cat4='".addslashes($data['cat4'])."'";

	if ($data['cat5'])
		$condition[] = "tickets.cat5='".addslashes($data['cat5'])."'";

	if ($data['ticketid1'])
		$condition[] = "tickets.ticketid='$data[ticketid1]'";

	if ($data['subject'])
		$condition[] = "tickets.subject like '%".$data['subject']."%'";

	if ($data['email'])
		$condition[] = "tickets.email='$data[email]'";

	if ($data['status'])
		$condition[] = "tickets.status='$data[status]'";

	if ($data['priority'])
		$condition[] = "tickets.priority='$data[priority]'";

	if ($data['message'] || $data['unread'] == 'Y')
		$left_join = " LEFT JOIN tickets_messages ON tickets.ticketid=tickets_messages.ticketid";

	if ($data['message'])
		$condition[] = "(tickets.message like '%".addslashes($data['message'])."%' OR tickets_messages.message like '%".addslashes($data['message'])."%')";

	if ($data['unread'] == 'Y')
		$condition[] = "(tickets_messages.admin_read='N' or tickets.admin_read='N')";

	if (!empty($condition))
		$search_condition = " WHERE ".implode(" AND ", $condition);

	$order_by = "tickets.ticketid DESC";
	if (!empty($data["sort_field"])) {
		# Sort the search results...

		$direction = ($data["sort_direction"] ? "DESC" : "ASC");
		$order_by = "tickets.".$data['sort_field']." $direction";
	}

# Search tickets
	$total_items = count($db->all("SELECT COUNT(*) FROM tickets$left_join $search_condition GROUP BY tickets.ticketid"));
	if ($total_items > 0) {
		$objects_per_page = $config['Tickets']['tickets_per_page_admin'];

		require SITE_ROOT."/includes/navigation.php";
		$template["navigation_script"] = $current_location."/admin/support_desk?mode=search&";

		$tickets = $db->all("SELECT tickets.* FROM tickets$left_join $search_condition GROUP BY tickets.ticketid ORDER BY $order_by LIMIT $first_page, $objects_per_page");
	} else {
		$_SESSION['alerts'][] = array(
			'content' => lng('lbl_no_tickets_found')
		);

		redirect("/admin/support_desk");
	}

	if (!empty($tickets)) {
		foreach ($tickets as $k=>$v) {
			$messages = $db->all("SELECT * FROM tickets_messages WHERE ticketid='$v[ticketid]' ORDER BY messageid DESC");

			$count = count($messages);
			$tickets[$k]['count'] = $count;

			$tmp = $messages['0'];
			$tickets[$k]['customer'] = $db->row("SELECT * FROM users WHERE id='$v[userid]'");
		}

		$template['tickets'] = $tickets;
	}
}

$template['search_prefilled'] = $_SESSION['search_sd'];

$template['location'] .= ' &gt; '.lng('Support desk');
$template['head_title'] = lng('Support desk').' :: '.$template['head_title'];

$template['page'] = get_template_contents('admin/pages/support_desk.php');
