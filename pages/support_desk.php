<?php
if (!$login) {
	redirect('/ticket');
}

q_load('ticket');
if ($get['1'] == 'cancel') {
	$db->query("UPDATE users SET active_subscription=0 WHERE id='$login'");
	$_SESSION['alerts'][] = array(
		'type'		=> 'i',
		'content'	=> lng('Subscription cancelled')
	);

	redirect('/support_desk');
}

if ($userinfo['subscribed']) {
	$template['last_order'] = $db->row("SELECT * FROM subscriptions WHERE userid='$login' ORDER BY date DESC");
	$template['renew_date'] = $template['last_order']['date'] + 365 * 24 * 3600;
}

$template['can_post_ticket'] = func_can_post_ticket();
$template['head_title'] = lng('Support desk').'. '.$template['head_title'];
$template['bread_crumbs'][] = array('', lng('Support desk'));

$search_condition = " WHERE tickets.userid='$login'";

$left_join = " LEFT JOIN tickets_messages ON tickets_messages.ticketid=tickets.ticketid AND (tickets_messages.read='N' or tickets.read='N')";

$order_by = "tickets.read, mread, tickets.ticketid DESC";

$search_condition .= " AND tickets.status<>'S'";

# Search tickets
$total_items = count($db->all("SELECT COUNT(*) FROM tickets$left_join $search_condition GROUP BY tickets.ticketid"));
if ($total_items > 0) {
	$objects_per_page = $config['Tickets']['tickets_per_page'];

	require SITE_ROOT."/includes/navigation.php";
	$template["navigation_script"] = $current_location."/support_desk?";

	$tickets = $db->all("SELECT tickets.*, tickets_messages.read as mread FROM tickets$left_join $search_condition GROUP BY tickets.ticketid ORDER BY $order_by LIMIT $first_page, $objects_per_page");
	$template['tickets'] = $tickets;
}

$template['page'] = get_template_contents('support_desk/support_desk.php');
