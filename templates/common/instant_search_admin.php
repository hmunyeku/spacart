{if $_GET['where'] == '1'}
{if $products}
<table>
{foreach $products as $v}
<tr>
 <td class="photo"><a class="ajax_link" href="{$current_location}/admin/products/{$v['productid']}">
{if $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 40;
$image['class'] = 'product-image';
$image['id'] = 'pid-'.$v['productid'];
$image['new_height'] = 40;
$image['center'] = 1;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
</a>
 </td>
 <td class="res-name"><a class="ajax_link" href="{$current_location}/admin/products/{$v['productid']}">{$v['name']}</a>
<p>{price $v['price']}</p>
 </td>
</tr>
{/foreach}
</table>
{if $total_items > 30}
<div class="more-no-search">And more</div>
{/if}
{else}
<div class="more-no-search">No products found</div>
{/if}
{elseif $_GET['where'] == '2'}
{if $users}
<table>
{foreach $users as $v}
<tr>
 <td class="res-name"><a class="ajax_link" href="{$current_location}/admin/user/{$v['id']}">{$v['firstname']} {$v['lastname']} ({$v['email']})</a></td>
</tr>
{/foreach}
</table>
{if $total_items > 30}
<div class="more-no-search">And more</div>
{/if}
{else}
<div class="more-no-search">No customers found</div>
{/if}
{elseif $_GET['where'] == '3'}
{if $orders}
<table>
{foreach $orders as $v}
<tr>
 <td class="res-name"><a class="ajax_link" href="{$current_location}/admin/invoice/{$v['orderid']}">#{$v['orderid']}, {$v['firstname']} {$v['lastname']} ({$v['email']})</a></td>
</tr>
{/foreach}
</table>
{if $total_items > 30}
<div class="more-no-search">And more</div>
{/if}
{else}
<div class="more-no-search">No orders found</div>
{/if}
{/if}