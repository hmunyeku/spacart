<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" class="area-a">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="/favicon.png" type="image/vnd.microsoft.icon" />
<title><?php echo $head_title; ?></title>

<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
{*
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
*}
<link rel="stylesheet" href="/materialize/material.orange-amber.min.css" />
<script defer src="/materialize/material.min.js"></script>
<script defer src="/files/chart.umd.js"></script>

<style type="text/css" media="all">
<?php
if (!empty($css)) {
	include 'includes/css.php';
}
?>
</style>
{if $login && $config['admin_theme_color'] && $userinfo['usertype'] == 'A'}
<style id="custom_style">
:root {
	--blue: #{$config['admin_theme_color']} !important;
}
</style>
{/if}

<script type="text/javascript">
var custId = 0;
var current_area = 'A',
	page = '<?php echo $get['1']; ?>',
	pageid = '',
	browser = <?php echo $browser; ?>,
	current_location = '<?php echo $current_location;?>',
	ajax_delimiter = '<?php echo $ajax_delimiter; ?>',
	currency_symbol = '{$config['General']['currency_symbol']}',
	weight_symbol = '{$config['General']['weight_symbol']}',
	payment_currency = '{$payment_currency}',
	is_ajax_page = {php echo $is_ajax_page;},
	facebook_api = '{$current_protocol}://connect.facebook.net/en-en/all.js',
	twitter_api = '{$current_protocol}://platform.twitter.com/widgets.js',
	w_prices = [],
	qadd = '',
	oid = 0,
	variants = [],
	groups = [],
	options = [],
	exceptions = [],
	w_prices = [],
	product_base,
	product_price,
	product_weight,
	product_price_ql,
	product_weight_ql,
	default_images,
	default_images_ql,
	product_avail = [];

{if !$login || $userinfo['usertype'] != 'A'}
var need_login = {if $_GET['mode'] == 'login'}1{else}0{/if};
{/if}

<?php
if ($userinfo['usertype'] != 'A') {
?>
var login_form = [];
login_form['email'] = "E-mail";
login_form['password'] = "Password";
<?php
}
?>
</script>
<script src="/ckeditor/ckeditor.js"></script>

<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="//code.jquery.com/ui/1.14.1/jquery-ui.min.js" integrity="sha256-AlTido85uXPlSyyaZNsjJXeCs07eSv3r43kyCVc8ChI=" crossorigin="anonymous"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css" type="text/css" />

<?php
if (!empty($js)) {
	include 'includes/js.php';
}
?>

<script src="/images/jquery.flot.js"></script>
<script src="/images/jquery.flot.time.js"></script>
</head>
<body class="admin-area{if !$login || $userinfo['usertype'] != 'A'} no-logged{/if}">
  <?php
	if ($alerts) {
		echo '<div class="alerts"><span class="close-alerts"><b>X</b> Close</span>';
		foreach ($alerts as $v) {
			if ($v['type'] == 'e') {
?>
<div class="error">{lng[Error]}:
<?php
				echo ' '.$v['content'].'</div>';
			} else {
				echo ' '.$v['content'].'<br>';
			}

			echo '<br>';
		}

		echo '</div>';
	}
  ?>
{if !$login || $userinfo['usertype'] != 'A'}
{include="admin/pages/login_new.php"}
{else}
	<div class="container">
		<div class="navigation-admin">
{if $login && $userinfo['usertype'] == 'A'}
			<ul>
				<li>
					<a href="/" class="logo-link no-ajax" target="_blank">
						<span class="icon"><img src="/images/logo_admin.png" alt="" /></span>
						<span class="title">SPA-Cart</span>
					</a>
				</li>
				<li>
					<a href="/admin">
						<span class="icon"><ion-icon name="home-outline"></ion-icon></span>
						<span class="title">{lng[Dashboard]}</span>
					</a>
				</li>
{if $allow_pages['pages_1']['allow']}
				<li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="bag-handle-outline"></ion-icon></span>
						<span class="title">{lng[Orders and discounts]}</span>
					</a>
<div>
{if in_array('orders', $allow_pages_ids) || $root_admin}
<a href="/admin/orders/recent">{lng[Recent orders]}</a>
{/if}
{if in_array('orders', $allow_pages_ids) || $root_admin}
<a href="/admin/orders">{lng[Search orders]}</a>
<a href="/admin/statistic">{lng[Statistic]}</a>
{/if}
{if in_array('coupons', $allow_pages_ids) || $root_admin}
<a href="/admin/coupons">{lng[Discount coupons]}</a>
{/if}
{if in_array('gift_cards', $allow_pages_ids) || $root_admin}
<a href="/admin/gift_cards">{lng[Gift Cards]}</a>
{/if}
</div>
 </li>
{/if}

{if $allow_pages['pages_2']['allow']}
				<li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="people-outline"></ion-icon></span>
						<span class="title">{lng[Users]}</span>
					</a>
<div>
{if in_array('users', $allow_pages_ids) || $root_admin}
<a href="/admin/users">{lng[Browse users]}</a>
{/if}
{if in_array('users', $allow_pages_ids) || $root_admin}
<a href="/admin/user/new">{lng[Create new user]}</a>
{/if}
{if in_array('memberships', $allow_pages_ids) || $root_admin}
<a href="/admin/memberships">{lng[Membership levels]}</a>
{/if}
{if $root_admin}
<a href="/admin/roles">{lng[Roles]}</a>
{/if}
</div>
				</li>
{/if}

{if $allow_pages['pages_3']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="apps-outline"></ion-icon></span>
						<span class="title">{lng[Catalog]}</span>
					</a>
<div>
{if in_array('products', $allow_pages_ids) || $root_admin}
<a href="/admin/products">{lng[Products]}</a>
{/if}
{if in_array('products', $allow_pages_ids) || $root_admin}
<a href="/admin/products/add">{lng[Add new product]}</a>
{/if}
{if in_array('reviews', $allow_pages_ids) || $root_admin}
<a href="/admin/reviews">{lng[Reviews]}</a>
{/if}
{if in_array('categories', $allow_pages_ids) || $root_admin}
<a href="/admin/categories">{lng[Categories]}</a>
{/if}
{if in_array('brands', $allow_pages_ids) || $root_admin}
<a href="/admin/brands">{lng[Brands]}</a>
{/if}
{if $warehouse_enabled}
<a href="/admin/warehouses">{lng[Warehouses]}</a>
{/if}
{if in_array('import', $allow_pages_ids) || $root_admin}
<a href="/admin/import">{lng[Import catalog]}</a>
{/if}
{if in_array('export', $allow_pages_ids) || $root_admin}
<a href="/admin/export">{lng[Export catalog]}</a>
{/if}
</div>
 </li>
{/if}

{if $allow_pages['pages_4']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="copy-outline"></ion-icon></span>
						<span class="title">{lng[Content]}</span>
					</a>
<div>
{if in_array('news', $allow_pages_ids) || $root_admin}
<a href="/admin/news">{lng[News]}</a>
{/if}
{if in_array('subscribtions', $allow_pages_ids) || $root_admin}
<a href="/admin/subscribtions">{lng[Newsletter]}</a>
{/if}
{if in_array('blog', $allow_pages_ids) || $root_admin}
<a href="/admin/blog">{lng[Blog]}</a>
{/if}
{if in_array('testimonials', $allow_pages_ids) || $root_admin}
<a href="/admin/testimonials">{lng[Testimonials]}</a>
{/if}
{if in_array('pages', $allow_pages_ids) || $root_admin}
<a href="/admin/pages">{lng[Static pages]}</a>
{/if}
{if in_array('homepage', $allow_pages_ids) || $root_admin}
<a href="/admin/homepage">{lng[Homepage]}</a>
{/if}
</div>
 </li>
{/if}
{if $allow_pages['pages_5']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="card-outline"></ion-icon></span>
						<span class="title">{lng[Shipping and payment]}</span>
					</a>
<div>
{if in_array('shipping', $allow_pages_ids) || $root_admin}
<a href="/admin/shipping">{lng[Shipping methods]}</a>
{/if}
{if in_array('shipping_charges', $allow_pages_ids) || $root_admin}
<a href="/admin/shipping_charges">{lng[Shipping charges]}</a>
{/if}
{if in_array('payment', $allow_pages_ids) || $root_admin}
<a href="/admin/payment">{lng[Payment methods]}</a>
{/if}
{if in_array('taxes', $allow_pages_ids) || $root_admin}
<a href="/admin/taxes">{lng[Taxes]}</a>
{/if}
{if in_array('zones', $allow_pages_ids) || $root_admin}
<a href="/admin/zones">{lng[Destination zones]}</a>
{/if}
{if in_array('countries', $allow_pages_ids) || $root_admin}
<a href="/admin/countries">{lng[Countries/States]}</a>
{/if}
</div>
 </li>
{/if}

{if $allow_pages['pages_6']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
						<span class="title">{lng[Configuration]}</span>
					</a>
<div>
{if in_array('configuration', $allow_pages_ids) || $root_admin}
<a href="/admin/configuration/General">{lng[General settings]}</a>
{/if}
{if in_array('configuration', $allow_pages_ids) || $root_admin}
<a href="/admin/configuration/Company">{lng[Company information]}</a>
{/if}
{if in_array('configuration', $allow_pages_ids) || $root_admin}
<a href="/admin/configuration/Blog">{lng[Blog settings]}</a>
{/if}
{if in_array('language', $allow_pages_ids) || $root_admin}
<a href="/admin/language">{lng[Languages]}</a>
{/if}
{if in_array('currencies', $allow_pages_ids) || $root_admin}
<a href="/admin/currencies">{lng[Currencies]}</a>
{/if}
</div>
 </li>
{/if}


{if $allow_pages['pages_7']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="people-circle-outline"></ion-icon></span>
						<span class="title">{lng[Support desk]}</span>
					</a>
<div>
{if in_array('support_desk', $allow_pages_ids) || $root_admin}
<a href="/admin/support_desk">{lng[Support desk]}</a>
{/if}
{if in_array('configuration', $allow_pages_ids) || $root_admin}
<a href="/admin/configuration/Tickets">{lng[Support desk settings]}</a>
{/if}
</div>
 </li>
{/if}
 </li>
{if $allow_pages['pages_8']['allow']}
 <li>
					<a href="javascript: void(0);" class="no-ajax">
						<span class="icon"><ion-icon name="search-circle-outline"></ion-icon></span>
						<span class="title">{lng[SEO]}</span>
					</a>
<div>
{if in_array('search_keywords', $allow_pages_ids) || $root_admin}
<a href="/admin/search_keywords">{lng[Search keywords]}</a>
{/if}
{if in_array('sitemap', $allow_pages_ids) || $root_admin}
<a href="/admin/sitemap">{lng[Google Sitemap]}</a>
{/if}
</div>
 </li>
{/if}
{else}
{/if}
			</ul>
		</div>

		<!-- main -->
		<div class="main">
{if $login}
			<div class="topbar">
{*
				<div class="toggle">
					<ion-icon name="menu-outline"></ion-icon>
				</div>
*}
				<div class="header-links">
<div id="quick_search_form">
<label>{lng[Search]}: </label>
<select>
<option value="1">{lng[Products]}</option>
<option value="2">{lng[Customers]}</option>
<option value="3">{lng[Orders]}</option>
</select>
<input type="text" placeholder="Start typing" class="custom-element" />
<div class="instant-search"></div>
</div>
<a class="no-ajax" href="{$current_location}" target="_blank">{lng[Open site]}</a>
<a class="ajax_link" href="{$current_location}/admin/user/{$login}">{lng[Profile]}</a>
<a href="{$current_location}/logout">{lng[Log out]}</a>
				</div>
			</div>
{/if}

			<div class="mainBox">
  <div class="content">
<div class="ajax_container">
{include="admin/ajax_container.php"}
</div>
  </div>

			</div>
		</div>
	</div>
{/if}
<div class="loading"><img src="{$current_location}/images/spacer.gif" alt="..."/></div>
<a class="goback-admin hidden ajax_link" href="#"></a>
<script>
function custom_elements() {
	if ($('body').hasClass('no-logged')) {
		return;
 }

	$('button:not(.custom-element)').each(function() {
		if ($(this).closest('#calendar').length)
			$(this).addClass('custom-element');
		else
			$(this).addClass('mdl-button mdl-button--colored mdl-button--raised mdl-js-button mdl-js-ripple-effect custom-element');
	});

	$('input[type="checkbox"]:not(.custom-element)').each(function() {
		var oT = $(this),
			p = oT.attr('placeholder'),
			id = oT.attr('id'),
			s_added = false;

		custId++;
		if (!id) {
			id = 'cusI'+custId;
			oT.attr('id', id);
		}

		if (!p) {
			if (oT.closest('.normal-table').length) {
				var tdFirst = oT.closest('tr').find('td:first');
				p = tdFirst.html();
				tdFirst.hide();
			} else {
				oT.addClass('custom-element');
				return;
			}
		}

		oT.addClass('custom-element');
	    oT.wrap('<label class="new-checkbox" for="'+id+'"></label>');
	    $('#'+id).after('<span class="mdl-checkbox__label"> &nbsp; '+p+'</span>');
	});

	$('textarea:not(.ckeditor), input[type="text"]:not(.custom-element), input[type="password"]:not(.custom-element)').each(function() {
		if ($(this).hasClass('custom-element'))
			return;

		var oT = $(this),
			p = oT.attr('placeholder'),
			id = oT.attr('id'),
			s_added = false;

		custId++;
		if (!id) {
			id = 'cusI'+custId;
			oT.attr('id', id);
		}

console.log(oT.attr('name'));

		if (!p) {
			if (oT.closest('.normal-table').length) {
				var tdFirst = oT.closest('tr').find('td:first');
				p = tdFirst.html();
				tdFirst.hide();
			} else {
				oT.addClass('custom-element');
				return;
			}
		}

					oT.addClass('mdl-textfield__input custom-element');
	    oT.wrap('<div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label" id="cusT-'+custId+'"></div>');
	    $('#cusT-'+custId).append('<label class="mdl-textfield__label" for="'+id+'">'+p+'</label>');
	    $('#cusT-'+custId).append('<span class="clear"></span>');
					var box = $('#cusT-'+custId);
					$('#cusT-'+custId+' .clear').click(function() {
						box.find('input').val('');
						box.find('textarea').val('');
						box.removeClass('is-dirty');
					});
	});

	$('.normal-table select:not(.custom-element)').each(function() {
		$(this).addClass('custom-element');
		var tdFirst = $(this).closest('tr').find('td:first'),
			p = tdFirst.html();

		tdFirst.hide();
		$(this).before('<div class="select-title">'+p+'</div>');
	});
}

function reinitialize_mdl() {
	$('.mdl-tooltip, .mdl-textfield, .mdl-radio, .mdl-checkbox, .mdl-spinner, .mdl-button, .md-button, .mdl-badge').each(function() {
		componentHandler.upgradeElement($(this).get(0));
	});
}

custom_elements();
$('body').show();
</script>
	<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
	<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
	<script>
		// MenuToggle
/*
		let toggle = document.querySelector('.toggle');
		let main = document.querySelector('.main');

		toggle.onclick = function(){
			navigation_admin.classList.toggle('active');
			main.classList.toggle('active');
		}
*/
		let navigation_admin = document.querySelector('.navigation-admin');

		// add hovered class in selected list item
		let list = document.querySelectorAll('.navigation-admin li');
		function activeLink(){
			list.forEach((item) =>
			item.classList.remove('hovered'));
			this.classList.add('hovered');
		}

		function deactiveLink(){
			list.forEach((item) =>
			item.classList.remove('hovered'));
		}

		function activeLinkClick(){
			list.forEach((item) =>
			item.classList.remove('clicked'));
			this.classList.add('clicked');
		}

		list.forEach((item) => 
		item.addEventListener('mouseover',activeLink));

		list.forEach((item) => 
		item.addEventListener('mouseout',deactiveLink));
{*
		list.forEach((item) => 
		item.addEventListener('click',activeLinkClick));
*}
  </script>

{if $login && $design_mode}
<?php
include SITE_ROOT.'/theme.php';
?>
{/if}

{if $login && $translate_mode}
{include="common/translate.php"}
{/if}

<script src="/images/kickout-ads.min.js"></script>
</body>
</html>