<form action="{$current_location}/admin/countries/<?php echo $country['code']; ?>" method="post" name="states_form">
<input type="hidden" name="mode" value="" />

<a href="javascript: void(0);" onclick="javascript: check_all(document.states_form, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.states_form, 'to_delete', false);">{lng[Uncheck all]}</a>
<table cellpadding="2" cellspacing="1" class="states lines-table">

<tr>
  <th>&nbsp;</th>
  <th>{lng[Code]}</th>
  <th>{lng[States]}</th>
</tr>

<?php
if ($states) {
	foreach ($states as $v) {
		echo '<tr>
  <td width="1%" align="center"><input type="checkbox" name="to_delete['.$v['code'].']" /></td>
  <td width="5%" align="center"><input type="text" name="posted_data['.$v['code'].'][code]" size="10" value="'.escape($v['code']).'" /></td>
  <td align="center"><input type="text" name="posted_data['.$v['code'].'][state]" size="35" value="'.escape($v['state']).'" /></td>
</tr>';
	}
} else {
	echo "<tr><td colspan='3' align='center'>";
?>
{lng[No states defined in this country]}
<?php
 echo "</td></tr>";
}
?>

<tr>
 <td colspan="3"><h3>{lng[Add new]}</h3></td>
</tr>

<tr>
  <td>&nbsp;</td>
  <td width="5%" align="center"><input type="text" name="new_state[code]" size="10" /></td>
  <td align="center"><input type="text" name="new_state[state]" size="35" /></td>
</tr>

</table>

<div class="fixed_save_button">
<button type="submit">{lng[Update]}</button>
&nbsp;
<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
</div>

</form>