{if $mode == "static"}
{if $type == "C"}{lng[Contact us]}{elseif $type == "P"}{lng[Product]}{elseif $type == "T"}{lng[Ticket topic]}{elseif $type == "M"}{lng[Ticket mailbox]}{/if}
{else}
<select name="{$name}" {$extra}>
{if $empty == "Y"}
<option value=""{if $type == ""} selected{/if}>{lng[All]}</option>
{/if}
{*{if $config['Tickets']['tickets_contactus'] == "Y"}*}
<option value="C"{if $type == "C"} selected{/if}>{lng[Contact us]}</option>
{*{/if}*}
{*{if $config.Tickets.tickets_product == "Y"}*}
<option value="P"{if $type == "P"} selected{/if}>{lng[Product]}</option>
{*{/if}*}
{*
<option value="T"{if $type == "T"} selected{/if}>{$lng.lbl_ticket_topic}</option>
<option value="M"{if $type == "M"} selected{/if}>{$lng.lbl_ticket_mailbox}</option>
*}
</select>
{/if}
