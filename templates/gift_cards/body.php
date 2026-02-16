<div class="padding-page gift_cards">
<h1>{lng[Gift Cards]}</h1>

<h3>{lng[Purchase a new gift card]}</h3>
<input type="text" name="gift_card" id="gift_card" placeholder="{lng[Enter Gift Card amount $]}" />
<button>{lng[Add to cart]}</button>
{if $gift_cards}

<h3>{lng[Earlier purchased gift cards]}</h3>
<table>
<tr>
 <th>{lng[Card number]}</th>
 <th>{lng[Amount]}</th>
 <th>{lng[Amount left]}</th>
</tr>
{foreach $gift_cards as $k=>$v}
<tr>
 <td>{$v['gcid']}</td>
 <td>{price $v['amount']}</td>
 <td>{price $v['amount_left']}</td>
</tr>
{/foreach}
</table>
{/if}
