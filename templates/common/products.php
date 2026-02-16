<div class="responsive-columns products"{if $tag_id} id="{$tag_id}"{/if}>
{foreach $products as $v}
<div class="res-item">
<div class="res-zoom">
{*
<div data-productid="{$v['productid']}" class="quick-look main-button">{lng[Quicklook]}</div>
*}
</div>
<div class="photo">
{if $v['list_price'] > $v['price']}
<span class="discount">
{php}
$percent = (($v['list_price'] - $v['price'])*100) / $v['list_price'];
echo '-'.round($percent).'%';
{/php}
</span>
{/if}

<a class="save2wl" onclick="javascript: return {if $login}add_wishlist({$v['productid']});{else}login_popup('W');{/if}" href="javascript: void(0);"><svg><use xlink:href="/images/sprite.svg#favorite"></use></svg></a>

<a class="ajax_link"{*{if $device != 'mobile'} title="Move product to cart to add it"{/if}*} href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}.html{else}product/{$v['productid']}{/if}">
{if $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 234;
$image['class'] = 'product-image';
$image['id'] = 'pid-'.$v['productid'];
$image['new_height'] = 200;
$image['center'] = 1;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
</a>
{*
{if $device != 'mobile'}
<span class="move2cart">{lng[Move me to cart]}</span>
{/if}
*}
</div>
 <div class="res-name"><a class="ajax_link" href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}.html{else}product/{$v['productid']}{/if}">{$v['name']}</a></div>
 <div class="res-price">
 {price $v['price']}
{if $v['list_price']}<s>{price $v['list_price']}</s>{/if}
 </div>
 <div class="res-rating">
 <div class="rating-votes"><div style="width: {php func_average_rating($v)}%;"></div></div>
 </div>
 <div class="res-buttons">
{if $v['avail'] > 0}
<button id="pid{$v['productid']}">{lng[Add to cart]}<span><svg><use xlink:href="/images/sprite.svg#cart"></use></svg></span></button>
{else}
<div class="out-of-stock">
Out of stock
</div>
{/if}
{*
<br />
<a class="main-button" onclick="javascript: return {if $login}add_wishlist({$v['productid']});{else}login_popup();{/if}" href="javascript: void(0);">{lng[Add to wishlist]}</a>
*}
 </div>
</div>
{/foreach}
</div>
<div class="clear"></div>