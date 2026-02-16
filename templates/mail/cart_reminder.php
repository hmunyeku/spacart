{include="mail/header.php"}
{lng[Hello]}{if $user['firstname']} {$user['firstname']}{/if},<br /><br />
<p>{lng[Recently you left products in your cart on our site]} <a href="{$current_location}?{$link_add}">{$config['Company']['company_name']}</a>.</p>

<table>
{foreach $cart['products'] as $p}
{php $url = $current_location.($p['cleanurl'] ? '/'.$p['cleanurl'].'.html' : '/product/'.$p['productid']);}
<tr>
 <td valign="top">
{if $p['photo']}
<?php
$image = $p['photo'];
$image['new_width'] = 100;
$image['new_height'] = 100;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
 </td>
 <td valign="top"><a href="{$url}?{$link_add}">{$p['name']}</a></td>
</tr>
{/foreach}
</table>
<br />
<a href="{$current_location}?{$link_add}">View your cart</a><br />

<br /><br />
{$signature}
{include="mail/footer.php"}