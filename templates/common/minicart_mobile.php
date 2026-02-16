{if $cart['products']}
<a href="{$current_location}/cart" class="ajax_link"><img class="cart-icon" src="/images/cart.png" alt="" /> {lng[Cart]} ({php echo count($cart['products']);})</a>
{else}
<img src="/images/cart.png" class="cart-icon" alt="" />
<span>{lng[Cart]}</span>
{/if}
