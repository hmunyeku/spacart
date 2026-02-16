<div id="banners"><ul style="width: {php echo count($banners) * 730;}px;">
{foreach $banners as $k=>$v}
<li id="banner_{$k}">
{if $v['url']}
<a href="{php echo escape($v['url'], 2);}">
{/if}
<img src="{$current_location}/photos/banners/{$categoryid}/{$v['bannerid']}/{$v['file']}" alt="{php echo escape($v['alt'], 2);}" />
{if $v['url']}
</a>
{/if}
</li>
{/foreach}
</ul>
<div id="banners_nav">
{for $i = 0; $i < count($banners); $i++}
<img src="{$current_location}/images/spacer.gif" alt="" id="g2b_{$i}"{if $i == 0} class="active"{/if} />
{/for}
</div>
</div>
<div class="responsive-banners">
<img src="/images/banners/1.jpg" alt="" class="res-ban-1" />
<img src="/images/banners/2.jpg" alt="" class="res-ban-2" />
</div>
<div class="clear"></div>