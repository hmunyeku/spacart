{include="mail/header.php"}
{$email_header}
{lng[Hello]}
<br /><br />
<p>{lng[Someone interesting in your product]}</p>
Phone: {$phone}<br />
IP: {$user['REMOTE_ADDR']}<br />
Product: <a href="{$current_location}/{if $product['cleanurl']}{$product['cleanurl']}.html{else}product/{$product['productid']}{/if}">{$product['name']}</a>
<br /><br />
{$signature}
{include="mail/footer.php"}