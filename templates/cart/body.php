<h1>{lng[Your cart]}</h1>
{if $products}
<form action="/cart" id="cartform" method="POST">
<table width="100%" class="carttable">
<tbody>
<tr>
 <th width="50%" align="left" colspan="2">{lng[Product]}</th>
 <th width="20%">{lng[Price]}</th>
 <th>{lng[Quantity]}</th>
 <th width="20%">{lng[Total]}</th>
 <td width="10%"></td>
</tr>
{foreach $products as $v}
{php $url = $v['cleanurl'] ? $v['cleanurl'].'.html' : 'product/'.$v['productid'];}
	<tr>
	 <td class="image"><a href="{$current_location}/{$url}">
	{if $v['variant_photo']}
<?php
		$image = $v['variant_photo'];
		$image['new_width'] = 100;
		$image['new_height'] = 100;
		include SITE_ROOT . '/includes/variant_image.php';
?>
	{elseif $v['photo']}
<?php
		$image = $v['photo'];
		$image['new_width'] = 100;
		$image['new_height'] = 100;
		include SITE_ROOT . '/includes/image.php';
?>
	{/if}
	  </a>
<div class="cart4mobile">
<br />
	{if !$v['variant_photo'] && !$v['photo']}
	<br /><br />
	{/if}
<div class="cart-mobile-title-line">{include="cart/mobile_title.php"}</div></div>
	  </td>
	  <td>{include="cart/mobile_title.php"}</td>
	<td align="center" valign="middle">
{if $v['gift_card']}
	{price $v['amount']}
{else}
	{price $v['price']}
{/if}
	</td>
	<td align="center">
{if !$v['gift_card']}
<input type="text" size="4" data-max="{$v['avail']}" class="cart-quantity" name="quantity[{$v['cartid']}]" value="{$v['quantity']}" />
{/if}
	</td>
{php $product_subtotal = $v['price'] * $v['quantity']}
	<td align="center" nowrap>
{if !$v['gift_card']}
{price $product_subtotal}
{/if}
	</td>
	<td><a href="{$current_location}/cart/remove/{$v['cartid']}" class="remove-link">{lng[Delete]}</a></td>
	</tr>
{/foreach}
</tbody>
</table>
<br />
<hr />
<br />
<table width="100%">
<tr>
 <td><button<?php if ($is_ajax) echo ' type="button"'; ?> class="update-cart">{lng[Update]}</button> &nbsp; <button type="button" class="clear-cart main-button"<?php if (!$is_ajax) echo ' onclick="self.location=\'cart/clear\'"'; ?>>{lng[Clear cart]}</button></td>
 <td align="right" class="cart-line-height">
 {lng[Subtotal]}: {price $cart['subtotal']}<br />
{if $cart['coupon']}
{lng[Coupon discount]}({$cart['coupon']['coupon']}) <span class="remove_coupon">(x)</span>: {price $cart['coupon_discount']}</br>
{php $discounted_subtotal = $cart['subtotal'] - $cart['coupon_discount'];}
{lng[Discounted subtotal]}: {price $cart['discounted_subtotal']}</br>{* {price $discounted_subtotal} *}
{/if}
{lng[Shipping]}: {price $cart['shipping_cost']}<br />
{if $cart['tax_details']}
{lng[Tax]}({$cart['tax_details']['tax_name']}): {price $cart['tax']}<br />
{/if}
 <b>{lng[Total]}: {price $cart['total']}</b><br />
 <br /><button class="checkout-link" type="button"{if !$is_ajax} onclick="self.location='/checkout'"{/if}>{lng[Checkout]}</button></td>
</tr>
</table>
</form>
{else}<br /><br />
{lng[Cart is empty]}
{/if}