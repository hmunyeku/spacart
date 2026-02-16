<form method="post" name="roleform">
<input type="hidden" name="mode" value="{if $role}save{else}add{/if}" />
<?php
if ($get['2'] == 'new') {
?>
	<h3>{lng[New role]}</h3>
<?php
}
?>

<table cellpadding="3" cellspacing="1" width="90%" class="normal-table">

<tr>
 <td><b>{lng[Title]}</b></td>
 <td><input type="text" name="title" size="40" value="<?php echo escape($role['title'], 2); ?>" /></td>
</tr>

<tr>
 <td><b>{lng[Position]}</b></td>
 <td><input type="text" name="pos" size="10" value="<?php echo escape($role['pos'], 2); ?>" /></td>
</tr>

<tr>
 <td><b>{lng[Permissions]}</b></td>
 <td>
<select name="pages[]" multiple size="15">
<option value="root"{foreach $role['pages'] as $v}{if 'root' == $v} selected{/if}{/foreach}>{lng[Root admin]}</option>
{foreach $role_pages as $p}
<optgroup label="{$p['title']}">
{if $p['pages']}
{foreach $p['pages'] as $s}
<option value="{$s['id']}"{foreach $role['pages'] as $v}{if $s['id'] == $v} selected{/if}{/foreach}>{$s['title']}</option>
{/foreach}
{/if}
</optgroup>
{/foreach}
</select>
 </td>
</tr>

</table>
<br />
<div class="fixed_save_button">
<button type="submit"><?php if ($get['2'] == 'new') { ?>{lng[Add|escape]}<?php } else { ?>{lng[Save|escape]}<?php } ?></button>
</div>
</form>
