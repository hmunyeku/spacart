<form method="post">
<input type="hidden" name="mode" value="update" />

{if $total_pages > 2}
{include="common/navigation.php"}
{/if}

<table cellpadding="3" cellspacing="1" width="900">

<tr>
	<th width="10">&nbsp;</th>
	<th width="20%">{lng[Coupon code]}</th>
	<th width="35%" align="center">{lng[Coupon discount]}</th>
	<th colspan="2" width="30%" align="center">{lng[Times to be used]}</th>
	<th width="15%" nowrap="nowrap" align="center">{lng[Status]}</th>
</tr>

<?php
if ($coupons) {
	foreach ($coupons as $v) {
?>
<tr>
	<td><input type="checkbox" name="to_delete[<?php echo $v['coupon']; ?>]" /></td>
	<td><?php echo $v['coupon']; ?></td>
	<td align="center">
<input type="text" size="5" name="posted_data[<?php echo $v['coupon']; ?>][discount]" value="<?php echo $v['discount']; ?>" />
<select name="posted_data[<?php echo $v['coupon']; ?>][discount_type]">
<option value="P">%</option>
<option value="A"<?php if ($v['discount_type'] == 'A') echo ' selected'; ?>>$</option>
</select>
	</td>
	<td align="center" nowrap><input type="text" size="2" name="posted_data[<?php echo $v['coupon']; ?>][times]" value="<?php echo $v['times']; ?>" /> / <?php echo $v['times_used']; ?></td>
	<td align="center" nowrap><label><input type="checkbox" name="posted_data[<?php echo $v['coupon']; ?>][per_customer]" value="1"<?php if ($v['per_customer']) echo ' checked'; ?> /> {lng[per customer]}</label></td>
	<td align="center">
<select name="posted_data[<?php echo $v['coupon']; ?>][status]">
<option value="Y">{lng[Active]}</option>
<option value="N"<?php if ($v['status'] == 'N') echo ' selected'; ?>>{lng[Disabled]}</option>
</select>
	</td>
</tr>
<?php
	}
?>
<tr>
	<td colspan="6">
	<button>{lng[Update]}</button>
	<button type="button" onclick="submitForm(this, 'delete')">{lng[Delete selected]}</button>
	</td>
</tr>
<?php
} else {
?>

<tr>
	<td colspan="6" align="center"><br />{lng[No discount coupons defined]}</td>
</tr>

<?php
}
?>

<tr>
	<td colspan="6"><br /><h3>{lng[Add new]}</h3></td>
</tr>

<tr>
	<td>&nbsp;</td>
	<td><input type="text" size="20" name="add_coupon[coupon]" /></td>
	<td align="center">
<input type="text" size="5" name="add_coupon[discount]" />
<select name="add_coupon[discount_type]">
<option value="P">%</option>
<option value="A">$</option>
</select>
	</td>
	<td align="center"><input type="text" size="5" name="add_coupon[times]" /></td>
	<td align="center"><label><input type="checkbox" name="add_coupon[per_customer]" value="1" /> {lng[per customer]}</label></td>
	<td align="center">
<select name="add_coupon[status]">
<option value="Y">{lng[Active]}</option>
<option value="N">{lng[Disabled]}</option>
</select>
	</td>
</tr>
<tr>
	<td colspan="6"><br /><button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button></td>
</tr>
</table>

{if $total_pages > 2}
{include="common/navigation.php"}
{/if}

</form>