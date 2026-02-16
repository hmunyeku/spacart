{if $brand}
<div class="brand_page">
{if $brand['imageid']}
<?php
		$image = $brand;
		$image['new_width'] = 200;
		$image['new_height'] = 400;
		include SITE_ROOT . '/includes/brand_image.php';
?>
{/if}
<h1>{$brand['name']}</h1>
<br />
<div class="descr">{$brand['descr']}</div>
</div>
{if $brand_products}
{php $tag_id = "products"; $products = $brand_products;}
<div class="clear"></div>
<h3>{lng[Products]}</h3>
<div class="products-results">
{$products_results_html}
</div>
{/if}
{elseif $brands}
{if $total_pages > 2}
<div align="right">{include="common/navigation.php"}</div>
<br />
{/if}
<div class="brands">
{foreach $brands as $b}
{php $url = $current_location.'/brands/'.($b['cleanurl'] ? $b['cleanurl'] : $b['brandid']);}
<div class="brand">
{if $b['imageid']}
<a href="{$url}">
<?php
$image = $b;
$image['new_width'] = 205;
$image['new_height'] = 205;
$image['valign'] = true;
$image['center'] = true;
include SITE_ROOT . '/includes/brand_image.php';
?>
{/if}
</a>
<a href="{$url}">{$b['name']}</a>
<br />
</div>
{/foreach}
</div>
{if $total_pages > 2}
<div align="right">{include="common/navigation.php"}</div>
<br />
{/if}
{else}
<br />
<center>{lng[No brands found]}</center>
{/if}
