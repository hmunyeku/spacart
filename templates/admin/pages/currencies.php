<form method="post" name="currenciesform">
<input type="hidden" name="mode" value="update" />
{if $currencies}
<a href="javascript: void(0);" onclick="javascript: check_all(document.currenciesform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.currenciesform, 'to_delete', false);">{lng[Uncheck all]}</a>
{/if}
<table cellpadding="3" cellspacing="1" width="600">
<tr>
	<th width="10">&nbsp;</th>
	<th width="15%">{lng[Currency code]}</th>
	<th width="10%">{lng[Rate]}</th>
	<th width="15%">{lng[Symbol]}</th>
	<th width="20%">{lng[Active]}</th>
	<th width="20%">{lng[Pos]}</th>
	<th width="20%">{lng[Main]}</th>
</tr>
{if $currencies}
<?php
foreach ($currencies as $b) {
	echo '<tr>
	<td><input type="checkbox" name="to_delete['.$b['id'].']" value="Y" /></td>
	<td align="center"><input type="text" size="20" name="to_update['.$b['id'].'][code]" value="'.$b['code'].'" /></td>
	<td align="center"><input type="text" size="20" name="to_update['.$b['id'].'][rate]" value="'.$b['rate'].'" /></td>
	<td align="center"><input type="text" size="20" name="to_update['.$b['id'].'][symbol]" value="'.$b['symbol'].'" /></td>
	<td align="center"><input type="checkbox" name="to_update['.$b['id'].'][active]" value="1"'.($b['active'] ? ' checked="checked"' : '').' /></td>
	<td align="center"><input type="text" size="5" name="to_update['.$b['id'].'][orderby]" value="'.$b['orderby'].'" /></td>
	<td align="center"><input type="radio" name="main_currency" value="'.$b['id'].'"'.($b['main'] ? ' checked="checked"' : '').' /></td>
</tr>';
}
?>

<tr>
	<td colspan="7">
<button type="button" onclick="javascript: submitForm(this, 'update');">{lng[Update]}</button> &nbsp;
<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
	</td>
</tr>
{/if}

<tr>
	<td colspan="7"><h3>{lng[Add new]}</h3></td>
</tr>
<tr>
	<td></td>
	<td align="center"><input type="text" size="20" name="new_currency[code]" value="" /></td>
	<td align="center"><input type="text" size="20" name="new_currency[rate]" value="" /></td>
	<td align="center"><input type="text" size="20" name="new_currency[symbol]" value="" /></td>
	<td align="center"><input type="checkbox" name="new_currency[active]" value="1" checked="checked" /></td>
	<td align="center"><input type="text" size="5" name="new_currency[orderby]" value="" /></td>
	<td></td>
</tr>

<tr>
	<td colspan="7"><button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button></td>
</tr>

</table>
</form>
