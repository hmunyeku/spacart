{include="mail/header.php"}
{lng[Hello]},<br /><br />
<p>{$name} {lng[recommended you this product]}</p>
{php $url = $current_location.($product['cleanurl'] ? '/'.$product['cleanurl'].'.html' : '/product/'.$product['productid']);}
<a href="{$url}">{$product['name']}</a><br />
{$url}
{if $message}
<hr />
{php echo func_eol2br($message);}
{/if}
<br /><br />
{$signature}
{include="mail/footer.php"}