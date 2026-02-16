<div class="padding-page gift_cards">
<h3>{lng[Add a new gift card]}</h3>
<form method="POST">
<input type="text" name="amount" placeholder="Enter Gift Card amount $" />
<button>{lng[Create]}</button>
</form>

{if $gift_cards}
<form method="POST">
<h3>{lng[Earlier purchased gift cards]}</h3>
<table cellspacing="1" class="lines-table">
<tr>
 <th></th>
 <th>{lng[Card number]}</th>
 <th>{lng[Amount]}</th>
 <th>{lng[Amount left]}</th>
 <th>{lng[User purchased]}</th>
</tr>
{foreach $gift_cards as $k=>$v}
<tr>
 <td><input type="checkbox" name="to_delete[{$v['gcid']}]" value="1" /></td>
 <td>{$v['gcid']}</td>
 <td>{price $v['amount']}</td>
 <td>{price $v['amount_left']}</td>
 <td>
{if $v['user']}
<a href="{$current_location}/admin/user/{$v['userid']}" target="_blank">{$v['user']['firstname']} {$v['user']['lastname']} ({$v['user']['email']})</a>
{/if}
 </td>
</tr>
{/foreach}
</table>
<button type="button" onclick="javascript: if (confirmed || confirm('', $(this))) submitForm(this, 'delete');">{lng[Delete selected]}</button>
</form>
{/if}
