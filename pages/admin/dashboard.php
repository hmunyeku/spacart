<?php
$products = $db->all("SELECT p.* FROM products p, featured_products fp WHERE fp.productid=p.productid AND fp.categoryid=0 AND fp.enabled=1 AND p.status=1 ORDER BY fp.orderby");
foreach ($products as $k=>$v) {
	if ($v['photoid'])
		$products[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
}

$template['featured_products'] = $products;

$bestsellers = $db->all("SELECT * FROM products WHERE status=1 AND sales_stats>0 ORDER BY sales_stats DESC LIMIT 8");
foreach ($bestsellers as $k=>$v) {
	if ($v['photoid'])
		$bestsellers[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
}

$template['bestsellers'] = $bestsellers;

$most_viewed = $db->all("SELECT * FROM products WHERE status=1 AND views_stats>0 ORDER BY views_stats DESC LIMIT 8");
foreach ($most_viewed as $k=>$v) {
	if ($v['photoid'])
		$most_viewed[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
}

$template['most_viewed'] = $most_viewed;

$new_arrivals = $db->all("SELECT * FROM products WHERE status=1 ORDER BY add_date DESC LIMIT 8");
foreach ($new_arrivals as $k=>$v) {
	if ($v['photoid'])
		$new_arrivals[$k]['photo'] = $db->row("SELECT * FROM products_photos WHERE photoid='".$v['photoid']."'");
}

$template['new_arrivals'] = $new_arrivals;

$template['location'] .= ' &gt; '.lng('Dashboard');
$template['head_title'] = lng('Dashboard').' :: '.$template['head_title'];

$template['products'] = $db->field("SELECT COUNT(*) FROM products");
$template['orders_count'] = $db->field("SELECT COUNT(*) FROM orders");
$template['users'] = $db->field("SELECT COUNT(*) FROM users");
$template['categories'] = $db->field("SELECT COUNT(*) FROM categories");

$template['blogs'] = $db->field("SELECT COUNT(*) FROM blog");
$template['news'] = $db->field("SELECT COUNT(*) FROM news");
$template['static_pages'] = $db->field("SELECT COUNT(*) FROM pages");
$template['testimonials'] = $db->field("SELECT COUNT(*) FROM testimonials");
$template['subscribers'] = $db->field("SELECT COUNT(*) FROM subscribers");
$template['memberships'] = $db->field("SELECT COUNT(*) FROM memberships");
$template['coupons'] = $db->field("SELECT COUNT(*) FROM coupons");
$template['gift_cards'] = $db->field("SELECT COUNT(*) FROM gift_cards");

$tmp = $db->all("SELECT * FROM orders WHERE date>'".strtotime(date('M').' 1,'.date('Y'))."' ORDER BY orderid");
if ($tmp) {
	$month_totals = array();
	for ($i = 1; $i < 31; $i++) {
		$tmp2 = mktime(0,0,0,date('m'),$i,date('Y'));
		$month_totals[$tmp2] = 0;
	}

	foreach ($tmp as $k=>$v) {
		$tmp2 = mktime(0,0,0,date('m', $v['date']),date('d', $v['date']),date('Y', $v['date']));
		$month_totals[$tmp2] += $v['total'];
	}

	if (count($month_totals) > 1)
		$template['month_totals'] = $month_totals;
}

$tmp = $db->all("SELECT * FROM orders WHERE date>'".
				strtotime((
						   intval(date('M'))-1
						   ).' 1,'.date('Y'))
				."' AND date<'".strtotime(date('M').' 1,'.date('Y'))."' ORDER BY orderid");
if ($tmp) {
	$month_totals = array();
	for ($i = 1; $i < 31; $i++) {
		$tmp2 = mktime(0,0,0,date('m')-1,$i,date('Y'));
		$month_totals[$tmp2] = 0;
	}

	foreach ($tmp as $k=>$v) {
		$tmp2 = mktime(0,0,0,date('m', $v['date']),date('d', $v['date']),date('Y', $v['date']));
		$month_totals[$tmp2] += $v['total'];
	}

	if (count($month_totals) > 1)
		$template['month_totals_2'] = $month_totals;
}

$orders = $db->all("SELECT * FROM orders ORDER BY orderid");
if ($orders) {
	foreach ($orders as $k=>$v) {
		$order = $v;
		$hour = date('H', $v['date']);
		if ($hour > 16) {
			$start_date = $end_date = date('Y-m-d', $v['date'] + 30000);
			if ($v['status'] == '2') {
				if ($v['date'] + 142800 < time())
					$order['icon'] = 2;
				else if ($v['date'] + 56400 < time())
					$order['icon'] = 1;
			}
		} else {
			$start_date = $end_date = date('Y-m-d', $v['date']);
			if ($v['status'] == '2') {
				if (date('d', $v['date']) < (date('d') - 1))
					$order['icon'] = 2;
				else if (date('d', $v['date']) < date('d'))
					$order['icon'] = 1;
			}
		}

		$order['start_date'] = $start_date;
		$order['end_date'] = $end_date;

		$orders[$k] = $order;
	}

	$template['orders'] = $orders;
}

$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$template['orders_today'] = $db->field("SELECT COUNT(orderid) FROM orders WHERE date>=".$today);
$template['total_today'] = $db->field("SELECT SUM(total) FROM orders WHERE date>=".$today);
$template['total_today_paid'] = $db->field("SELECT SUM(total) FROM orders WHERE status IN ('2', '3', '6') AND date>=".$today);

$first_weekday = time() - date("w") * 86400;
$week = mktime(0, 0, 0, date("n", $first_weekday), date("j", $first_weekday), date("Y", $first_weekday));
$template['orders_week'] = $db->field("SELECT COUNT(orderid) FROM orders WHERE date>=".$week);
$template['total_week'] = $db->field("SELECT SUM(total) FROM orders WHERE date>=".$week);
$template['total_week_paid'] = $db->field("SELECT SUM(total) FROM orders WHERE status IN ('2', '3', '6') AND date>=".$week);

$month = mktime(0, 0, 0, date("n"), 1, date("Y"));
$template['orders_month'] = $db->field("SELECT COUNT(orderid) FROM orders WHERE date>=".$month);
$template['total_month'] = $db->field("SELECT SUM(total) FROM orders WHERE date>=".$month);
$template['total_month_paid'] = $db->field("SELECT SUM(total) FROM orders WHERE status IN ('2', '3', '6') AND date>=".$month);

$year = mktime(0, 0, 0, 1, 1, date("Y"));
$template['orders_year'] = $db->field("SELECT COUNT(orderid) FROM orders WHERE date>=".$year);
$template['total_year'] = $db->field("SELECT SUM(total) FROM orders WHERE date>=".$year);
$template['total_year_paid'] = $db->field("SELECT SUM(total) FROM orders WHERE status IN ('2', '3', '6') AND date>=".$year);

$template['orders_all'] = $db->field("SELECT COUNT(orderid) FROM orders");
$template['total_all'] = $db->field("SELECT SUM(total) FROM orders");
$template['total_all_paid'] = $db->field("SELECT SUM(total) FROM orders WHERE status IN ('2', '3', '6')");

$template['page'] = get_template_contents('admin/pages/dashboard.php');
$template['css'][] = 'admin_dashboard';
$template['js'][] = 'admin_dashboard';