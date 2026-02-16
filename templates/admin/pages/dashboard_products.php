<table cellspacing="0">
{foreach $products as $v}
<tr>
 <td class="photo"><a target="_blank" href="/admin/products/{$v['productid']}">
{if $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 50;
$image['class'] = 'product-image';
$image['id'] = 'pid-'.$v['productid'];
$image['new_height'] = 50;
$image['center'] = 1;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
</a>
 </td>
 <td class="name"><a target="_blank" href="/admin/products/{$v['productid']}">{$v['name']}</a><span>{if $type == 'B'} ({$v['sales_stats']} sales){elseif $type == 'M'} ({$v['views_stats']} views){/if}</span></td>
</tr>
{/foreach}
</table>