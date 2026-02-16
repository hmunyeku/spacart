{if !$per_row}
{php $per_row = 3;}
{/if}
{php $tmp = array(); $i = $j = 0;}
{foreach $products as $k=>$v}
 {php $i++; $tmp[$j][] = $v;}
 {if $i == $per_row}
  {php $i = 0; $j++;}
 {/if}
{/foreach}
{php $products = $tmp;}
<table{if $tag_id} id="{$tag_id}"{/if} class="products per-row-{$per_row}">
{foreach $products as $row}
<tr class="photos">
{foreach $row as $k=>$v}
<td><div><a class="ajax_link" title="Move product to cart to add it" href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}.html{else}product/{$v['productid']}{/if}">
{if $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 234;
$image['class'] = 'product-image';
$image['id'] = 'pid-'.$v['productid'];
$image['new_height'] = 400;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
</a>
<span class="move2cart">{lng[Move me to cart]}</span>
</div></td>
{/foreach}
{if $k == 0}
 <td colspan="3" width="100%"></td>
{elseif $k == 1}
 <td colspan="2" width="100%"></td>
{elseif $k == 2 && $per_row > 3}
 <td width="100%"></td>
{/if}
</tr>
<tr>
{foreach $row as $k=>$v}
 <td><a class="ajax_link" href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}.html{else}product/{$v['productid']}{/if}">{$v['name']}</a></td>
{/foreach}
{if $k == 0}
 <td colspan="3" width="100%"></td>
{elseif $k == 1}
 <td colspan="2" width="100%"></td>
{elseif $k == 2 && $per_row > 3}
 <td width="100%"></td>
{/if}
</tr>
<tr class="price-row">
{foreach $row as $k=>$v}
 <td>{price $v['price']}{if $v['list_price']} <span>(<s>{price $v['list_price']}</s>){/if}</span></td>
{/foreach}
{if $k == 0}
 <td colspan="3" width="100%"></td>
{elseif $k == 1}
 <td colspan="2" width="100%"></td>
{elseif $k == 2 && $per_row > 3}
 <td width="100%"></td>
{/if}
</tr>

<tr>
{foreach $row as $k=>$v}
<td>
<a class="main-button ajax_link" href="{$current_location}/{if $v['cleanurl']}{$v['cleanurl']}.html{else}product/{$v['productid']}{/if}">{lng[View]}</a>
{if $v['avail'] > 0}
<button id="pid{$v['productid']}">{lng[Add to cart]}</button>
{else}
<div class="out-of-stock">
<span>{price $v['price']}{if $v['list_price']} (<s>{price $v['list_price']}</s>){/if}</span>
Out of stock
</div>
{/if}
</td>
{/foreach}
{if $k == 0}
 <td colspan="3" width="100%"></td>
{elseif $k == 1}
 <td colspan="2" width="100%"></td>
{elseif $k == 2 && $per_row > 3}
 <td width="100%"></td>
{/if}
</tr>
<tr>
 <td colspan="4">&nbsp;</td>
</tr>
{/foreach}
</table>