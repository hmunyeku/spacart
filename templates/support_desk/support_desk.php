<h1>{lng[Support Desk]}</h1>
<div class="support_desk">
<br />

<div{if !$can_post_ticket} class="hidden1"{/if} id="post_ticket" align="right"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored" onclick="self.location='/ticket';">{lng[New ticket]}</button></div>

{if $tickets}

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

<table cellpadding="10" cellspacing="0" class="support-desk-table">

<tr>
        <th align='center'>#</th>
        <th align="left">&nbsp;{lng[Subject]}</a></th>
        <th width="150" align='center'>{lng[Status]}</th>
        <th width="150" align='center'>{lng[Date]}</th>
        <th width="150" align='center'>{lng[Actions]}</th>
</tr>

{foreach $tickets as $k=>$i}

<tr class="ticket-link{if $k % 2} tl-second{/if}">
        <td width='10'><a href="/ticket/{$i['ticketid']}">{$i['ticketid']}</a>&nbsp;</td>
        <td>&nbsp;<a href="/ticket/{$i['ticketid']}">{$i['subject']}</a>{if $i['read'] == "N" || $i['mread'] == "N"} <font color="#00AA00">({lng[new messages]})</font>{/if}</td>
        <td id="ticket-status-{$i['ticketid']}">{php $mode = "static"; $status=$i['status'];}{include="common/ticket_status.php"}</td>
        <td width='150' align='center'>{php echo date($datetime_format, $i['date']);}</td>
        <td align="right" id="ticket-status-links-{$i['ticketid']}">
        {if $i['status'] != '3' && $i['status'] != 'C'}
    	    &nbsp; <a class="close-ticket" href="javascript: void(0);" onclick="javascript: if (confirmed || confirm('{lng[Are you sure?|escape]}', $(this))) close_ticket('{$i['ticketid']}', 1);"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Close]}</button></a>
{*	        &nbsp; <a class="close-ticket" href="javascript: void(0);" onclick="javascript: if (confirmed || confirm('{lng[Are you sure?|escape]}', $(this))) close_ticket('{$i['ticketid']}', 2);"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Cancel]}</button></a>*}
        {/if}
        </td>
</tr>
{/foreach}

</table>

<br />

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

{else}
{lng[lbl_no_tickets]}
{/if}

{*
{if $last_order}
<br /><hr /><br />
You are a current subscriber. Your subscription is valid until {php echo date($date_format, $renew_date);}
{if $userinfo['active_subscription']}
<br /><br />
<button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored" type="button" onclick="javascript: if (confirmed || confirm('{lng[Are you sure?|escape]}', $(this))) self.location='/support_desk/cancel';">{lng[Cancel subscription]}</button> - Your subscription still will be active, but you will not be charged at the end date.
{/if}
<br /><br />

{if !$can_post_ticket && !$userinfo['active_subscription']}
<button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored" onclick="self.location='/subscribe';">{lng[Subscribe again]}</button>
{/if}

{/if}
*}
</div>