<div class="page-container">
<div class="withleftmenu">
<div class="left_filter">
<h2>{lng[Narrow selection]}</h2>
<div id="left_filter">
<div class="cssload-container"><div class="cssload-speeding-wheel"></div></div>

</div>
</div>

<div class="main-container">
<div class="filter_switcher"><svg><use xlink:href="/images/sprite.svg?1#filter_opener"></use></svg></div>
<div id="bread_crumbs_container">{$bread_crumbs_html}</div>

<div class="content" align="left">
{if false && !$no_left_menu}
{$left_menu;}
{/if}
	<div id="center"{if true || $no_left_menu == 'Y'} class="no_left_menu"{/if}>
{$page}

{if $recently && $get['0'] != 'checkout'}
{if $get['0'] != 'product' && $get['0'] != 'home'}
</div></div>
{/if}
</div>
</div>
<div class="clear"></div>
</div>
<br /><br />
<div id="home-tabs">
<ul class="home-tabs">
 <li class="tab-1 active" data-tab="1">{lng[Recently viewed]}</li>
</ul>
</div>

{* Start page container *}
<div class="page-container page-container-2">
<div class="content">
	<div id="center" class="no_left_menu">

 {php $tag_id = "featured_products"; $products = $recently; $per_row = 4;}
<div id="tab-7">
<div class="carousel-pr" id="carousel-5">
  <div class="controls">
    <div class="button-left">
      <div class="icon">
        <span></span>
      </div>
    </div>
    <div class="button-right">
      <div class="icon">
        <span></span>
      </div>
    </div>
  </div>
  <div class="carousel-wrapper">
    <div class="content-pr">
 {include="common/products.php"}
     </div>
  </div>
</div>

</div>
{/if}

	</div>
</div>

<div class="clear"></div>
</div>
</div>
</div>
</div>
{if $alerts}
 <div class="alerts"><span onclick="javascript: $('.alerts').slideUp();" class="close_alert"><b></b></span>
 {foreach $alerts as $v}
  {if $v['type'] == 'e'}<div class="error">Error: {$v['content']}</div>{else}{$v['content']}<br>{/if}<br>
 {/foreach}
 </div>
{/if}
<div class="clear"></div>
