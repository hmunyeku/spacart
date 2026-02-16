<div id="dcart"><img src="{$current_location}/images/dcart.png" alt="" /><br />{lng[Move product here]}</div>

<h1>{$category['title']}</h1>
{if $banners}{php $categoryid = $category['categoryid'];}{include="common/banners.php"}
<div class="category-banners-descr">
{$category['description'];}
</div>
<div class="clear"></div>
{else}
<table class="category-details">
<tr>
{if $category_icon}
	<td>
<?php
$image = $category_icon;
$image['new_width'] = 500;
$image['new_height'] = 300;
include SITE_ROOT . '/includes/icon.php';
?>
</td>
{/if}

<td width="100%">{php echo $category['description'] ? '<p>'.$category['description'].'</p>' : '';}</td></tr></table>
{/if}

{if $subcategories}
<br />
<h3></h3>
<br /><br />
<h2>{lng[Subcategories]}</h2>
<div id='subcategories' class="responsive-sub">
{foreach $subcategories as $v}
<div class="res-sub-item">
<div class="photo">
{php $url = $v['cleanurl'] ? $v['cleanurl'] : $v['categoryid'];}
{if $v['icon']}
<a href="{$current_location}/{$url}">
<?php
$image = $v['icon'];
$image['new_width'] = 234;
$image['new_height'] = 200;
include SITE_ROOT . '/includes/icon.php';
?>
</a>
{/if}
</div>
<div class="res-name"><a href="{$current_location}/{$url}">{$v['title']}</a></div>
</div>
{/foreach}
</div>
<div class="clear"></div>
{/if}

{if $featured_products}
{php $tag_id = "featured_products"; $products = $featured_products; $per_row = 4; $sort_by = '';}
<h3>{lng[Featured products]}</h3>
{include="common/products.php"}
<br />
{/if}

{if $category_products}
{php $tag_id = "products"; $products = $category_products; $per_row = 4;}
<h3>{lng[Products]}</h3>
<div class="products-results">
{$products_results_html}
</div>
{elseif !$subcategories}<br />
<center>{lng[No products in this category]}</center>{/if}
