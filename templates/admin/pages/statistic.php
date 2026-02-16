<h1>{lng[Statistic]}</h1>
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