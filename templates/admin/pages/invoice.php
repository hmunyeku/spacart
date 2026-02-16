<table width="1000">
<tr>
 <td width="50%">
<h1>Shipping info</h1>
<form method="POST">
<input type="text" name="tracking" placeholder="{lng[Tracking number]}" value="{php echo escape($order['tracking'], 2)}" />
<br >
<input type="text" name="tracking_url" placeholder="{lng[Tracking URL]}" value="{php echo escape($order['tracking_url'], 2)}" />
<br >
<button type="submit">{lng[Save]}</button>
</form>
 </td>
 <td>
<h1>{lng[Order]} #<?php echo $order['orderid']; ?> (	<select id="status">
<?php
		foreach ($order_statuses as $k=>$v) {
			echo '<option value="'.$k.'"'.($k == $order['status'] ? ' selected' : '').'>'.$v.'</option>';
		}
?>
</select>
<button onclick="javascript: self.location='/admin/invoice/<?php echo $order['orderid']; ?>/status/'+$('#status').val();">{lng[Save]}</button>
)</h1>
<small>{lng[Chagned status will be sent to customer]}</small>
<br /><br />
<div>{lng[Order date]}: <?php echo date($datetime_format, $order['date']); ?></div>
<div class="clear"></div>
{if $order['transaction_id']}
{lng[Transaction]} #: {$order['transaction_id']}
<br />
{if $order['payment_indent']}
Payment Indent: {$order['payment_indent']}
<br />
{/if}
<br />
{/if}
 </td>
</tr>
</table>
<br /><br />
{include="invoice/body.php"}