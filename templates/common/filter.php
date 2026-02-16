<div class="hidden filter-url">{$filter_url}</div>
{if $get['0'] != 'brands'}
{if $filter['brandid']}
<h4>{lng[Brands]}</h4>
<div class="selected-filter" data-id="{$filter['brandid']}" data-what="brand">{$filter['brand']['name']} (x)</div>
{elseif $brands}
<h4 class="pointer">{lng[Brands]}</h4>
<ul data-what="brand">
{foreach $brands as $b}
 <li data-id="{$b['id']}">{$b['name']} ({$b['cnt']})</li>
{/foreach}
</ul>
{/if}
{/if}

{if $filter['price']}
<h4>{lng[Price]}</h4>
<div class="selected-filter" data-id="{$filter['price']}" data-what="price">{$filter['price']} (x)</div>
{elseif $prices}
<h4 class="pointer">{lng[Price]}</h4>
<ul data-what="price">
{foreach $prices as $k=>$v}
 <li data-id="{$k}">{$k} ({$v})</li>
{/foreach}
</ul>
{/if}

{if $options}
{foreach $options as $c=>$o}
{if false && $filter['attr'][$c]}
<h4>{$c}</h4>
<div class="selected-filter" data-id="{php echo escape($c, 2);}" data-oid="{php echo escape($filter['attr'][$c], 2);}" data-what="attr">{$filter['attr'][$c]} (x)</div>
{else}
<h4 class="pointer{if $filter['attr'][$c]} opened{/if}">{$c}</h4>
<ul data-what="attr" groupid="{php echo func_filter_id($c);}" class="filter-attr filter-box-{php echo func_filter_id($c);}{if $filter['attr'][$c]} opened{/if}">
{foreach $o as $k=>$v}
 <li data-id="{php echo escape($c, 2);}" data-oid="{php echo escape($k, 2);}"><label><input type="checkbox"{foreach $filter['attr'][$c] as $selected}{if $selected == $k} checked{/if}{/foreach} />{$k} ({$v['cnt']})</label></li>
{/foreach}
</ul>
{/if}

{/foreach}
{/if}
{if $_GET['filter']}
<a href="javascript: void(0);" class="reset_filter">Reset filter</a>
<a href="{$reset_filter_url}" class="ajax_link reset_filter_url hidden"></a>
{/if}
{*
<h4>{lng[Price range]}</h4>
<div class="filter-price">
<input type="text" id="min_price" value="{php echo floor($min_price);}" readonly /> - <input type="text" id="max_price" value="{php echo ceil($max_price);}" readonly />
<div id="slider-range"></div>
</div>
*}