<?php
if ($get['2'] != 'recent') {?>
<form method="POST">
<input type="hidden" name="mode" value="search" />
<table cellspacing="1" cellpadding="3" class="orders_search">
<tr>
 <td align="right">{lng[Order #ID]}</td>
 <td><input type="text" name="orderid" size="5" value="<?php echo $search_orders['orderid']; ?>" /></td>
</tr>
<tr>
 <td align="right">{lng[Email]}</td>
 <td><input type="text" name="email" value="<?php echo $search_orders['email']; ?>" /></td>
</tr>
<tr>
 <td align="right">{lng[Customer name]}</td>
 <td><input type="text" name="customer" value="<?php echo $search_orders['customer']; ?>" /></td>
</tr>
<tr>
 <td align="right">{lng[Status]}</td>
 <td><select name="status"><option value="">{lng[All]}</option>
<?php
	foreach ($order_statuses as $k2=>$v2) {
		echo '<option value="'.$k2.'"'.($k2 == $search_orders['status'] ? ' selected' : '').'>'.$v2.'</option>';
	}
?>
</select>
 </td>
</tr>
<tr>
 <td valign="top" width="170" align="right">{lng[Search by date]}</td>
 <td>
<label><input type="radio" name="date_period" value="0"<?php if (!$search_orders['date_period']) echo ' checked="checked"'; ?> /> {lng[All dates]}</label><br />
<label><input type="radio" name="date_period" value="1"<?php if ($search_orders['date_period'] == '1') echo ' checked="checked"'; ?> /> {lng[Today]}</label><br />
<label><input type="radio" name="date_period" value="2"<?php if ($search_orders['date_period'] == '2') echo ' checked="checked"'; ?> /> {lng[This week]}</label><br />
<label><input type="radio" name="date_period" value="3"<?php if ($search_orders['date_period'] == '3') echo ' checked="checked"'; ?> /> {lng[This month]}</label><br />
<label><input type="radio" name="date_period" value="4"<?php if ($search_orders['date_period'] == '4') echo ' checked="checked"'; ?> /> {lng[Date period]}:</label><br />
<input type="text" id="date_from" name="date_from" value="<?php echo $search_orders['date_from']; ?>" size="7" /> - <input type="text" id="date_to" name="date_to" value="<?php echo $search_orders['date_to']; ?>" size="7" />
 </td>
</tr>
<tr>
 <td></td>
 <td><br /><button>{lng[Search]}</button> &nbsp; <a href="{$current_location}/admin/orders/reset">{lng[Reset filter]}</a></td>
</tr>
</table>
</form>
<h3>{lng[Search results]}</h3>
<?php
} else {
?>
<h3>{lng[Recent orders]}</h3>
<?php
}
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<?php
	echo '<br />';
}
?>

<form method="POST">
<?php
if ($orders) {
?>
<table cellspacing="1" cellpadding="14" width="700" class="lines-table">
<tr>
 <th>#</th>
 <th>{lng[Status]}</th>
 <th>{lng[Customer]}</th>
 <th>{lng[Date]}</th>
 <th>{lng[Total]}</th>
</tr>
<?php
	$total = 0;
	$total_paid = 0;
	foreach ($orders as $k=>$v) {		if ($v['status'] == '2' || $v['status'] == '3' || $v['status'] == '6')
			$total_paid += $v['total'];

		$total += $v['total'];

		echo '
		<tr>
			<td width="10"><a href="/admin/invoice/'.$v['orderid'].'">#'.$v['orderid'].'</a></td>
			<td width="100">
				<select name="status['.$v['orderid'].']">';
		foreach ($order_statuses as $k2=>$v2) {			echo '<option value="'.$k2.'"'.($k2 == $v['status'] ? ' selected' : '').'>'.$v2.'</option>';		}

		echo '</select>';
		if ($v['gift_card'])
			echo '<b>Paid with GC</b>';
#echo '<pre>';
#exit(print_R($v));

		echo '</td>
			<td nowrap><a href="/admin/user/'.$v['userid'].'">'.$v['firstname'].' '.$v['lastname'].' ('.$v['email'].')</a></td>
			<td width="130" nowrap><a href="/admin/invoice/'.$v['orderid'].'">'.date($datetime_format, $v['date']).'</a></td>
			<td align="right"><a href="/admin/invoice/'.$v['orderid'].'">$'.$v['total'].'</a></td>
		</tr>
		';
  	}

	echo '<tr><td colspan="5" align="right"><hr />{lng[Total]}: $'.$total.'</td></tr>';
	echo '<tr><td colspan="5" align="right"><b>{lng[Total Paid]}: $'.$total_paid.'</b></td></tr>';
	echo '</table>';
?>
<div class="fixed_save_button">
<button>{lng[Save]}</button>
</div>

<?php
} else echo 'No orders found.';
?>
</form>