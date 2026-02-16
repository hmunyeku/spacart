<form method="POST" name="prform">
<input type="hidden" name="section" value="inventory">
{if $warehouses}
<table width="500" class="lines-table">
<tr>
 <th width="100%">{lng[Warehouse]}</th>
 <th>{lng[Avail]}</th>
</tr>
{foreach $warehouses as $w}
<tr>
 <td>#{$w['wcode']}, {$w['title']}<br />{$w['address']}</td>
 <td><input size="5" type="text" name="posted_data[{$w['wid']}]" value="{$w['avail']}"></td>
</tr>
{/foreach}
<tr>
 <td colspan="2"><br><button type="submit">{lng[Update]}</button></td>
</tr>
</table>
{else}
No <a href="/admin/warehouses">warehouses defined</a>.
{/if}
</form>