{if $get['0'] == 'category'}
<br />
{else}
<div class="banners-container">
{/if}
<div id="banners">
<div class="banners-slider">
{foreach $banners as $k=>$v}
<div id="banner_{$k}">
{if $v['url']}
<a href="{php echo escape($v['url'], 2);}">
{/if}
<img src="{$current_location}/photos/banners/{$categoryid}/{$v['bannerid']}/{$v['file']}" alt="{php echo escape($v['alt'], 2);}" />
{if $v['url']}
</a>
{/if}
</div>
{/foreach}
</div>
{if count($banners) > 1}
<div id="banners_nav">
{for $i = 0; $i < count($banners); $i++}
<img src="{$current_location}/images/spacer.gif" alt="" id="g2b_{$i}"{if $i == 0} class="active"{/if} />
{/for}
</div>
{/if}
</div>
{if $get['0'] != 'category'}
</div>
<div class="clear"></div>
{/if}