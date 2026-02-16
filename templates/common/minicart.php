<svg><use xlink:href="/images/sprite.svg#cart"></use></svg>
{if $cart['products']}
<span class="hide4mobile">{lng[Products]}: </span>{php echo count($cart['products']);}
<div class="cart-links">
<a href="{$current_location}/cart" class="cart-link">{lng[View cart]}</a><span class="hide4mobile"><a href="{$current_location}/checkout" class="checkout-link">{lng[Checkout]}</a></span>
</div>
{else}
<span>{lng[Cart is empty]}</span>
{/if}