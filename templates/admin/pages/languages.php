<form method="post" name="languagesform">
<input type="hidden" name="mode" value="update" />
{if $translate_mode}
<a href="/admin/language?exit_translate_mode=1" class="mdl-button">Exit "Translate" mode</a>
{else}
<a href="/admin/language?translate_mode=1" class="mdl-button">Enter "Translate" mode</a>
{/if}
&nbsp; (Make sure you do it in Development mode - see settings.php)
<br /><br />
{if $languages}
<a href="javascript: void(0);" onclick="javascript: check_all(document.languagesform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.languagesform, 'to_delete', false);">{lng[Uncheck all]}</a>
{/if}
<table cellpadding="3" cellspacing="1" width="600">
<tr>
	<th width="10">&nbsp;</th>
	<th width="15%">{lng[Code]}</th>
	<th width="10%">{lng[Name]}</th>
	<th width="20%">{lng[Active]}</th>
	<th width="20%">{lng[Pos]}</th>
	<th width="20%">{lng[Main]}</th>
	<th width="20%">{lng[Manage]}</th>
</tr>
{if $languages}
<?php
foreach ($languages as $b) {
	echo '<tr>
	<td><input type="checkbox" name="to_delete['.$b['id'].']" value="Y" /></td>
	<td align="center"><input type="text" size="20" name="to_update['.$b['id'].'][code]" value="'.$b['code'].'" /></td>
	<td align="center"><input type="text" size="20" name="to_update['.$b['id'].'][name]" value="'.$b['name'].'" /></td>
	<td align="center"><input type="checkbox" name="to_update['.$b['id'].'][active]" value="1"'.($b['active'] ? ' checked="checked"' : '').' /></td>
	<td align="center"><input type="text" size="5" name="to_update['.$b['id'].'][orderby]" value="'.$b['orderby'].'" /></td>
	<td align="center"><input type="radio" name="main_lang" value="'.$b['id'].'"'.($b['main'] ? ' checked="checked"' : '').' /></td>
	<td align="center"><a href="/admin/language/'.$b['code'].'">{lng[Manage]}</a></td>
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
	<td align="center"><input type="text" size="20" name="new_language[code]" value="" /></td>
	<td align="center"><input type="text" size="20" name="new_language[name]" value="" /></td>
	<td align="center"><input type="checkbox" name="new_language[active]" value="1" checked="checked" /></td>
	<td align="center"><input type="text" size="5" name="new_language[orderby]" value="" /></td>
	<td colspan="2"></td>
</tr>

<tr>
	<td colspan="7"><button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button></td>
</tr>

</table>
</form>
