<table class="products-nav">
<tr>
{if $total_pages > 2}
 <td class="first">{include="common/navigation.php"}</td>
{/if}
{if $device == 'mobile'}
</tr>
<tr>
{/if}
 <td class="second sort-by">
{if $sort_by} {foreach $sort_by as $k=>$v}  {if $k == $_GET['sort']}
<a class="active direction-{if $_GET['direction'] == '1'}2{else}1{/if}" href="{$sort_by_script}sort={$k}&direction={if $_GET['direction'] == '1'}2{else}1{/if}">{php echo lng($v);}</a>
  {else}<a href="{$sort_by_script}sort={$k}&direction=1">{php echo lng($v);}</a>
  {/if} {/foreach}
{/if}
 </td>
</tr>
</table>

{if !isset($_GET['direction']) || $_GET['direction']}
 {php $direction = "&direction=0";}
{else}
 {php $direction = "&direction=1";}
{/if}
{include="common/products.php"}


{if $total_pages > 2}
<br />
<div class="bottom-pagination">
{include="common/navigation.php"}
</div>
<br />
{/if}