<?php
if ($userinfo['usertype'] != 'A' && $get['1'] != 'login') {
	$_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
	redirect('admin/login');
}

$role_pages = array(
	'pages_1'	=> array(
		'title'	=> 'Orders and discounts',
		'pages'	=> array(
			array(
				'title'	=> 'Orders',
				'id'	=> 'orders'
			),
			array(
				'title'	=> 'Coupons',
				'id'	=> 'coupons'
			),
			array(
				'title'	=> 'Gift Cards',
				'id'	=> 'gift_cards'
			),
		)
	),
	'pages_2'	=> array(
		'title'	=> 'Users',
		'pages'	=> array(
			array(
				'title'	=> 'Users',
				'id'	=> 'users'
			),
			array(
				'title'	=> 'Membership levels',
				'id'	=> 'memberships'
			),
		)
	),
	'pages_3'	=> array(
		'title'	=> 'Catalog',
		'pages'	=> array(
			array(
				'title'	=> 'Products',
				'id'	=> 'products'
			),
			array(
				'title'	=> 'Reviews',
				'id'	=> 'reviews'
			),
			array(
				'title'	=> 'Categories',
				'id'	=> 'categories'
			),
			array(
				'title'	=> 'Brands',
				'id'	=> 'brands'
			),
			array(
				'title'	=> 'Import',
				'id'	=> 'import'
			),
			array(
				'title'	=> 'Export',
				'id'	=> 'export'
			),
		)
	),
	'pages_4'	=> array(
		'title'	=> 'Content',
		'pages'	=> array(
			array(
				'title'	=> 'News',
				'id'	=> 'news'
			),
			array(
				'title'	=> 'Blog',
				'id'	=> 'blog'
			),
			array(
				'title'	=> 'Subscribtions',
				'id'	=> 'subscribtions'
			),
			array(
				'title'	=> 'Testimonials',
				'id'	=> 'testimonials'
			),
			array(
				'title'	=> 'Static Pages',
				'id'	=> 'pages'
			),
			array(
				'title'	=> 'Homepage',
				'id'	=> 'homepage'
			),
		)
	),
	'pages_5'	=> array(
		'title'	=> 'Shipping and payment',
		'pages'	=> array(
			array(
				'title'	=> 'Shipping methods',
				'id'	=> 'shipping'
			),
			array(
				'title'	=> 'Shipping charges',
				'id'	=> 'shipping_charges'
			),
			array(
				'title'	=> 'Payment methods',
				'id'	=> 'payment'
			),
			array(
				'title'	=> 'Taxes',
				'id'	=> 'taxes'
			),
			array(
				'title'	=> 'Destination zones',
				'id'	=> 'zones'
			),
			array(
				'title'	=> 'Countries/States',
				'id'	=> 'countries'
			),
		)
	),
	'pages_6'	=> array(
		'title'	=> 'Configuration',
		'pages'	=> array(
			array(
				'title'	=> 'General/Company/Blog/Support desk',
				'id'	=> 'configuration'
			),
			array(
				'title'	=> 'Languages',
				'id'	=> 'languages'
			),
			array(
				'title'	=> 'Currencies',
				'id'	=> 'currencies'
			),
		)
	),
	'pages_7'	=> array(
		'title'	=> 'Support desk',
		'pages'	=> array(
			array(
				'title'	=> 'Support desk',
				'id'	=> 'support_Desk'
			),
		)
	),
	'pages_8'	=> array(
		'title'	=> 'SEO',
		'pages'	=> array(
			array(
				'title'	=> 'Search keywords',
				'id'	=> 'search_keywords'
			),
			array(
				'title'	=> 'Google sitemap',
				'id'	=> 'sitemap'
			),
		)
	),
);

$template['role_pages'] = $role_pages;
$check_role_page = $get['1'];
if ($check_role_page == 'user')
	$check_role_page = 'users';

if ($check_role_page == 'ticket')
	$check_role_page = 'support_desk';

$allowed_role_pages = array(
	'profile', 'dashboard'
);

if (!$check_role_page)
	$check_role_page = 'dashboard';

$root_roles = $db->all("SELECT * FROM roles WHERE pages LIKE '%|root|%' AND roleid='".$userinfo['roleid']."'");
if ($root_roles) {
	$template['root_admin'] = $root_admin = 1;
}

$allow_user_modify = $db->all("SELECT * FROM roles WHERE pages LIKE '%|users|%' AND roleid='".$userinfo['roleid']."'");
if ($allow_user_modify) {
	$template['allow_user_modify'] = $allow_user_modify = 1;
}

$role = $db->row("SELECT * FROM roles WHERE roleid='".$userinfo['roleid']."'");
if (($role) && $check_role_page && $userinfo['usertype'] == 'A') {
	$tmp = explode('|', $role['pages']);
	$pages = array();
	foreach ($tmp as $v) {
		if ($v == '|' || !$v)
			continue;

		$pages[] = $v;
	}

	if ($get['1'] == 'invoice' && in_array('orders', $pages))
		$pages[] = 'invoice';

	if (!$root_admin && !in_array($check_role_page, $pages) && !in_array($check_role_page, $allowed_role_pages) && !($get['1'] == 'user' && $get['2'] == $login)) {
		$_SESSION['alerts'][] = array(
			'type'		=> 'e',
			'content'	=> 'You don\'t have access to this page'
		);

		redirect('/admin');
	}

	foreach ($role_pages as $k=>$v) {
		$found = false;
		foreach ($v['pages'] as $p) {
			if (in_array($p['id'], $pages))
				$found = true;
		}

		if ($found || $root_admin)
			$role_pages[$k]['allow'] = 1;
	}

	$template['allow_pages'] = $role_pages;
	$template['allow_pages_ids'] = $pages;
}

$template['head_title'] = lng('Administration');
$template['location'] = '<a href="/admin">'.lng('Administration').'</a>';
if (empty($get['1']))
	$script = SITE_ROOT . '/pages/admin/dashboard.php';
else
	$script = SITE_ROOT . '/pages/admin/'.$get['1'].'.php';

if (file_exists($script))
	include $script;
else
	redirect('/');

$template['css'][] = 'admin';
if ($login && $userinfo['usertype'] == 'A') {
	$template['css'][] = 'admin_new';
} else {
	$template['css'][] = 'admin_login';
}

$template['js'][] = 'admin';
