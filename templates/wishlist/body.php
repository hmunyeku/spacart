<h1>{lng[Wishlist]}</h1>
{if $wishlist}
<table width="100%">
<tr>
 <th width="70%" align="left" colspan="2">{lng[Product]}</th>
 <th width="20%">{lng[Price]}</th>
 <td width="10%"></td>
</tr>
{foreach $wishlist as $v}
{php $url = $v['product']['cleanurl'] ? $v['product']['cleanurl'].'.html' : 'product/'.$v['product']['productid'];}
	<tr>
	 <td class="image"><a href="{$current_location}/{$url}">
	{if $v['product']['photo']}
<?php
		$image = $v['product']['photo'];
		$image['new_width'] = 100;
		$image['new_height'] = 100;
		include SITE_ROOT . '/includes/image.php';
?>
	{/if}
	 </a></td>
	 <td><a href="{$current_location}/{$url}">{$v['product']['name']}</a></td>
	 <td align="center" valign="middle">{price $v['product']['price']}</td>
	 <td><a href="{$current_location}/wishlist/remove/{$v['wlid']}" class="remove-wl-link">{lng[Delete]}</a></td>
	</tr>
{/foreach}
</table>
<br />
<hr />
<br />
<button type="button" class="clear-wl"<?php if (!$is_ajax) echo ' onclick="self.location=\'wishlist/clear\'"'; ?>>{lng[Clear wishlist]}</button>
</form>
{else}<br />
{lng[Wishlist is empty]}
<br /><br />
{/if}