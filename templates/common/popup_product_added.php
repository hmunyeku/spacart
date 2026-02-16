<div class="product-added">
{if $product['variant_photo']}
<?php
	$image = $product['variant_photo'];
	$image['new_width'] = 234;
	$image['new_height'] = 200;
	include SITE_ROOT . '/includes/variant_image.php';
?>
{elseif $product['photo']}
<?php
	$image = $product['photo'];
	$image['new_width'] = 234;
	$image['new_height'] = 200;
	include SITE_ROOT . '/includes/image.php';
?>
{/if}
<b>{$product['name']}</b> {lng[added to cart]}
<br /><br />
<strong class="added_price">{lng[Price]}: <span>{price $product['price']}</span></strong>
{if $product_options}
<div class="added_options">
<b>{lng[Selected options]}:</b>
{foreach $product_options as $o}
{if $o['value']}
{$o['group']['name']}: {$o['value']<br />
{else}
{$o['group']['name']}: {$o['option']['name']}<br />
{/if}
{/foreach}
</div>
{/if}
</div>
<br /><br />
<table width="100%" class="product-added-table">
<tr>
 <td><button class="close_popup">{lng[Continue shopping]}</button></td>
 <td align="right">

<a href="/cart" class="main-button">{lng[View cart]}</a>
<a href="/checkout" class="main-button">{lng[Checkout]}</a>

 <button type="button" class="cart-link">{lng[View cart]}</button> <button type="button" class="checkout-link">{lng[Checkout]}</button>

 </td>
</tr>
</table>