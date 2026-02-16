<form method="post">
<input type="hidden" name="mode" value="update" />

<a href="/admin/roles/new" class="ajax_link">{lng[New role]}</a><br />

<table cellpadding="3" cellspacing="1" width="600" class="lines-table">
<?php
if ($roles) {
?>
<tr>
	<th width="10">&nbsp;</th>
	<th width="80%">{lng[Title]}</th>
	<th width="15%" align="center">{lng[Pos]}</th>
</tr>

<?php
	foreach ($roles as $v) {
?>
<tr>
	<td width="10"><input type="checkbox" name="to_delete[]" value="<?php echo $v['roleid']; ?>" /></td>
	<td><a href="/admin/roles/{$v['roleid']}" class="ajax_link">{$v['title']}</a></td>
	<td align="center"><input type="text" size="5" name="posted_data[<?php echo $v['roleid']; ?>][pos]" value="<?php echo $v['pos']; ?>" /></td>
</tr>
<?php
	}
?>
<?php
} else {
?>

<tr>
	<td colspan="3" align="center"><br />{lng[No Roles defined]}</td>
</tr>

<?php
}
?>

</table>

{if $roles}
<div class="fixed_save_button">
	<button>{lng[Update]}</button>
	<button type="button" onclick="submitForm(this, 'delete')">{lng[Delete selected]}</button>
</div>
{/if}
</form>