{*
<label><input type="checkbox" id="wenabled"{if $config['warehouse_enabled']} checked{/if}> Enable warehouses</label>
<div class="warehouses-area{if !$config['warehouse_enabled']} hidden{/if}">
*}
<form method="post">
<input type="hidden" name="mode" value="update" />

{if $total_pages > 2}
{include="common/navigation.php"}
{/if}

<table cellpadding="3" cellspacing="1" width="800">
<tr>
	<th width="10">&nbsp;</th>
	<th width="20%">{lng[Warehouse code]}</th>
	<th width="20%">{lng[Title]}</th>
	<th width="35%" align="center">{lng[Warehouse address]}</th>
	<th width="35%" align="center">{lng[Warehouses description]}</th>
	<th width="5%" nowrap="nowrap" align="center">{lng[Pos]}</th>
	<th width="5%" nowrap="nowrap" align="center">{lng[Enabled]}</th>
</tr>

<?php
if ($warehouses) {
	foreach ($warehouses as $v) {
?>
<tr>
	<td><input type="checkbox" name="to_delete[{$v['wid']}]" /></td>
	<td><input type="text" size="20" name="posted_data[{$v['wid']}][wcode]" value="{php echo escape($v['wcode'], 2);}" /></td>
	<td><input type="text" size="20" name="posted_data[{$v['wid']}][title]" value="{php echo escape($v['title'], 2);}" /></td>
	<td align="center"><input type="text" size="40" name="posted_data[{$v['wid']}][address]" value="{php echo escape($v['address'], 2);}" /></td>
	<td align="center"><textarea cols="40" rows="10" name="posted_data[{$v['wid']}][descr]">{$v['descr']}</textarea></td>
	<td align="center"><input type="text" size="5" name="posted_data[{$v['wid']}][pos]" value="{$v['pos']}" /></td>
	<td align="center"><input type="checkbox" name="posted_data[{$v['wid']}][enabled]" value="1"{if $v['enabled']} checked{/if} /></td>
</tr>
<?php
	}
?>
<tr>
	<td colspan="7">
	<button>{lng[Update]}</button>
	<button type="button" onclick="submitForm(this, 'delete')">{lng[Delete selected]}</button>
	</td>
</tr>
<?php
} else {
?>

<tr>
	<td colspan="7" align="center"><br />{lng[No warehouses defined]}</td>
</tr>

<?php
}
?>

<tr>
	<td colspan="7"><br /><h3>{lng[Add new]}</h3></td>
</tr>

<tr>
	<td>&nbsp;</td>
	<td><input type="text" size="20" name="add_warehouse[wcode]" /></td>
	<td><input type="text" size="20" name="add_warehouse[title]" /></td>
	<td align="center"><input type="text" size="40" name="add_warehouse[address]" /></td>
	<td align="center"><textarea cols="40" rows="10" name="add_warehouse[descr]"></textarea></td>
	<td align="center"><input type="text" size="5" name="add_warehouse[pos]" value="{$new_pos}" /></td>
	<td align="center"><input type="checkbox" name="add_warehouse[enabled]" value="1" checked /></td>
</tr>
<tr>
	<td colspan="6"><br /><button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button></td>
</tr>
</table>

{if $total_pages > 2}
{include="common/navigation.php"}
{/if}

</form>
{*</div>*}