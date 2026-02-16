{if $v['gift_card']}
<b>{lng[Gift Card]}</b><br />
{lng[You will see the Gift Card key phrase on paid invoice]}
{else}
	  <a href="{$current_location}/{$url}">{$v['name']}</a>
{/if}
{if $v['weight'] && $v['weight'] != '0.00'}
<br /><small>{lng[Weight]}: {weight $v['weight']}</small>
{/if}
{if $v['product_options']}
<hr />
<b>{lng[Selected options]}:</b><br />
<table width="100%">
{foreach $v['product_options'] as $o}
<tr>
	<td width="100" valign="top">{$o['name']}:</td>
	<td>{$o['option']['name']}</td>
</tr>
{/foreach}
</table>
{/if}