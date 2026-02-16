{include="mail/header.php"}
{lng[lbl_reply_above_here]}<br /><br />

{$email_header}

{lng[Hello]},

{if $product}
<p>Recently you asked a question about product {$product['sku']}<br />
<a href="{$current_location}/{if $product['cleanurl']}{$product['cleanurl']}.html{else}product/{$product['productid']}{/if}">{$product['name']}</a>
</p>
{/if}

<p />A new message on your ticket #{$ticket['ticketid']}

{*{if $ticket['userid']}*}
<p />{lng[lbl_full_ticket]}<br />
<a href="{$http_location}/ticket/{$ticket['ticketid']}?authkey={$ticket['authkey']}">{$http_location}/ticket/{$ticket['ticketid']}?authkey={$ticket['authkey']}</a>
{*{/if}*}

<p />{lng[lbl_ticket_subject]}: {$ticket['subject']}

<hr size='1' />

{if $product}
{lng[Our answer]}:
{else}
{lng[Replied Message]}:
{/if}

<p />{$message['message']}

<br /><br />

{$signature}
{include="mail/footer.php"}