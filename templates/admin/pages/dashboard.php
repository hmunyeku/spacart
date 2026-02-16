<table class="db-stats-1" cellspacing="30">
<tr>
 <td class="ajax_link td-1" href='/admin/products'><span>{$products}</span><h2>{lng[Products]}</h2></td>
 <td class="ajax_link td-2" href='/admin/orders'><span>{$orders_count}</span><h2>{lng[Orders]}</h2></td>
 <td class="ajax_link td-3" href='/admin/users'><span>{$users}</span><h2>{lng[Customers]}</h2></td>
 <td class="ajax_link td-4" href='/admin/categories'><span>{$categories}</span><h2>{lng[Categories]}</h2></td>
</tr>
</table>

<table class="db-stats-2" cellspacing="30">
<tr>
 <td class="ajax_link td-1" href='/admin/news'><span>{$news}</span><h2>{lng[News]}</h2></td>
 <td class="ajax_link td-2" href='/admin/subscribtions'><span>{$subscribers}</span><h2>{lng[Subscribers]}</h2></td>
 <td class="ajax_link td-3" href='/admin/blog'><span>{$blogs}</span><h2>{lng[Blogs]}</h2></td>
 <td class="ajax_link td-4" href='/admin/pages'><span>{$static_pages}</span><h2>{lng[Pages]}</h2></td>
</tr>
<tr>
 <td class="ajax_link td-5" href='/admin/testimonials'><span>{$testimonials}</span><h2>{lng[Testimonials]}</h2></td>
 <td class="ajax_link td-6" href='/admin/gift_cards'><span>{$gift_cards}</span><h2>{lng[Gift cards]}</h2></td>
 <td class="ajax_link td-7" href='/admin/coupons'><span>{$coupons}</span><h2>{lng[Coupons]}</h2></td>
 <td class="ajax_link td-8" href='/admin/memberships'><span>{$memberships}</span><h2>{lng[Memberships]}</h2></td>
</tr>
</table>

{*
<div class="agency_income_chart hidden">{php echo json_encode($totals_chart)}</div>

<div class="chart-container-agency chart-container--line">
<canvas id="chart-line"></canvas>
</div>
*}

<table class="db-stats-3" cellspacing="30">
<tr>
 <td>
<h3>{lng[Featured products]}<a class="float-right" target="_blank" href="/admin/categories">({lng[Manage]})</a></h3>
<?php
$products = $featured_products;
$type = 'F';
?>
{include="admin/pages/dashboard_products.php"}
 </td>
 <td>
<h3>{lng[New arrivals]}</h3>
<?php
$products = $new_arrivals;
$type = 'N';
?>
{include="admin/pages/dashboard_products.php"}
 </td>
 <td>
<h3>{lng[Bestsellers]}</h3>
<?php
$products = $bestsellers;
$type = 'B';
?>
{include="admin/pages/dashboard_products.php"}
 </td>
 <td>
<h3>{lng[Most viewed]}</h3>
<?php
$products = $most_viewed;
$type = 'M';
?>
{include="admin/pages/dashboard_products.php"}
 </td>
</tr>
</table>

{*
<table class="dashboard-statistic">
<tr>
 <td class="first">
<h3>{lng[Today's statistic]}</h3>
<table cellspacing="1">
<tr>
    <th>{lng[Today's orders]}</th>
	<th>{lng[Today's total]}</th>
	<th>{lng[Today's paid]}</th>
</tr>
<tr>
    <td><?php echo $orders_today ? $orders_today : 0; ?></td>
    <td>{price $total_today}</td>
    <td>{price $total_today_paid}</td>
</tr>
</table>
 </td>
 <td class="second">
<h3>{lng[Week's statistic]}</h3>
<table cellspacing="1">
<tr>
    <th>{lng[Week's orders]}</th>
	<th>{lng[Week's total]}</th>
	<th>{lng[Week's paid]}</th>
</tr>
<tr>
    <td><?php echo $orders_week ? $orders_week : 0; ?></td>
    <td>{price $total_week}</td>
    <td>{price $total_week_paid}</td>
</tr>
</table>
 </td>
</tr>
<tr>
 <td class="first">
<h3>{lng[Month's statistic]}</h3>
<table cellspacing="1">
<tr>
    <th>{lng[Month's orders]}</th>
	<th>{lng[Month's total]}</th>
	<th>{lng[Month's paid]}</th>
</tr>
<tr>
    <td><?php echo $orders_month ? $orders_month : 0; ?></td>
    <td>{price $total_month}</td>
    <td>{price $total_month_paid}</td>
</tr>
</table>
 </td>
 <td class="second">
<h3>{lng[Year's statistic]}</h3>
<table cellspacing="1">
<tr>
    <th>{lng[Year's orders]}</th>
	<th>{lng[Year's total]}</th>
	<th>{lng[Year's paid]}</th>
</tr>
<tr>
    <td><?php echo $orders_year ? $orders_year : 0; ?></td>
    <td>{price $total_year}</td>
    <td>{price $total_year_paid}</td>
</tr>
</table>
 </td>
</tr>
<tr>
 <td class="first">
<h3>{lng[General statistic]}</h3>
<table cellspacing="1">
<tr>
    <th>{lng[Total orders]}</th>
	<th>{lng[Orders total]}</th>
	<th>{lng[Total paid]}</th>
</tr>
<tr>
    <td><?php echo $orders_all ? $orders_all : 0; ?></td>
    <td>{price $total_all}</td>
    <td>{price $total_all_paid}</td>
</tr>
</table>
 </td>
 <td></td>
</tr>

</table>
*}

{*
<table class="dashboard">
<tr>
 <td class="first">
<h3>Bestsellers</h3>
<ul>
<?php
foreach ($bestsellers as $v) {
		echo '<li><a href="/admin/products/'.$v['productid'].'">'.$v['name'].'</a> ('.$v['sales_stats'].' sales)</li>';
	}
?>
</ul>
 </td>
 <td class="second">
<h3>Most viewed</h3>
<ul>
<?php
foreach ($most_viewed as $v) {
		echo '<li><a href="/admin/products/'.$v['productid'].'">'.$v['name'].'</a> ('.$v['views_stats'].' views)</li>';
	}
?>
</ul>
 </td>
</tr>
</table>
*}