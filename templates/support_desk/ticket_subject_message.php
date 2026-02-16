{if $ticket}
<h1>{lng[Ticket]} #{$ticket['ticketid']} (<span id="ticket-status-{$ticket['ticketid']}">{php $mode = "static"; $status=$ticket['status'];}{include="common/ticket_status.php"}</span>)</h1>
{else}
<h1>{lng[New ticket]}</h1>
{/if}

{if !$ticket}

<script>
{foreach $ticket_cats as $k=>$c}
ticket_cats[{$k}] = ["{php echo escape($c['cat1'], 2);}", "{php echo escape($c['cat2'], 2);}", "{php echo escape($c['cat3'], 2);}", "{php echo escape($c['cat4'], 2);}", "{php echo escape($c['cat5'], 2);}", []];
 {if $c['fields']}
  {foreach $c['fields'] as $k2=>$f}
ticket_cats[{$k}][5][{$k2}] = ["{php echo escape($f['field'], 2);}", "{$f['required']}"];
  {/foreach}
 {/if}
{/foreach}
</script>

<form method="POST" name="ticketform" enctype="multipart/form-data">
<input type="hidden" name="mode" value="">

<div class="ticket-div">
<table cellspacing="1" cellpadding="2" width="" class="tickettable">
<tr>
 <td valign="top" style="width: 50%;">
 <table width="100%">
<tr>
 <td valign="top" class='data-name' align="right"></td>
 <td valign="top" class="star">*</td>
 <td><label class="drop-down">{lng[Select ticket category]}:</label><div id="ticket_category">Loading...</div></td>
</tr>

<tr>
 <td class='data-name' align="right"></td>
 <td>&nbsp;</td>
{php $name="priority"; $value=$predefined['priority'];}
 <td><label class="drop-down">{lng[lbl_ticket_priority]}:</label><br />{include="common/ticket_priority.php"}</td>
</tr>
 </table>
 </td>
 <td valign="top">
 <table width="100%">
<tr>
 <td class='data-name' align="right"></td>
 <td class="star" style="padding-top: 30px;">*</td>
 <td>
  <div class="mdl-textfield mdl-js-textfield">
 <input class="mdl-textfield__input" type="text" name="subject" id="ticket_subject" style="width: 300px;" value="{php echo escape($predefined['subject'], 2);}" onkeyup="javascript: if(this.value && document.getElementById('error_mes').style.display == 'block') document.getElementById('error_mes').style.display='none';">
    <label class="mdl-textfield__label" for="ticket_subject">Subject</label>
  </div>
 </td>
</tr>

<tr>
 <td class='data-name' valign="top" align="right"></td>
 <td class="star" style="padding-top: 30px;">*</td>
 <td>
  <div class="mdl-textfield mdl-js-textfield">
 <textarea class="mdl-textfield__input" name="message" id="ticket_message" style="width: 300px; height: 100px;" onkeyup="javascript: if(this.value && document.getElementById('error_mes').style.display == 'block') document.getElementById('error_mes').style.display='none';">{$predefined['message']}</textarea>
    <label class="mdl-textfield__label" for="ticket_message">Message</label>
  </div>

 </td>
</tr>

<tr>
 <td class="data-name" valign="top" align="right"></td>
 <td>&nbsp;</td>
 <td>
<label class="drop-down">{lng[lbl_ticket_upload_files]}:</label><br /><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]">
 </td>
</tr>

<tr>
 <td colspan="2"></td>
 <td style="color: #CC3300"><b id="error_mes" style="display: none;">{lng[Please, select all categories, enter your subject and message]}</b></td>
</tr>

<tr>
 <td colspan="2"></td>
 <td><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored" type="button" onclick="submit_ticket();">{lng[Create request]}</button></td>
</tr>
</table>
 </td>
</tr>
</table>
</div>
</form>

{else}

<a name="messages"></a>

<div align="right">
<a href="{$current_location}/support_desk"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Support Desk]}</button></a>
&nbsp;
<a href="/ticket"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Create request]}</button></a>
<span id="ticket-status-links-{$ticket['ticketid']}">
        {if $ticket['status'] != '3' && $ticket['status'] != 'C'}
    	    &nbsp; <a class="close-ticket" href="javascript: void(0);" onclick="javascript: if (confirmed || confirm('{lng[Are you sure?|escape]}', $(this))) close_ticket('{$ticket['ticketid']}', 1);"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Close]}</button></a>
	        &nbsp; <a class="close-ticket" href="javascript: void(0);" onclick="javascript: if (confirmed || confirm('{lng[Are you sure?|escape]}', $(this))) close_ticket('{$ticket['ticketid']}', 2);"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Cancel]}</button></a>
        {/if}
</span>
</div>

<script language="JavaScript" type="text/javascript">
<!--
function original_message(id) {
	var elm = document.getElementById('original_message');
	if (elm.style.display == 'none')
		elm.style.display = 'block';
	else
		elm.style.display = 'none';
}
-->
</script>
<br />

{if $messages}
<table cellspacing="0" cellpadding="0" width="100%">

{foreach $messages as $i}
<tr>
 <th align="left"><a name="m{$i['messageid']}"></a>&nbsp;{$i['firstname']} {$i['lastname']}</th>
 <th align="right">{php echo date($datetime_format, $i['date']);}&nbsp;</th>
</tr>
<tr>
 <td colspan="2" class="ticket-message">{php echo func_eol2br($i['message']);}<br />
{if $i['attachments']}
<i>{lng[Attached files]}:</i>
{foreach $i['attachments'] as $a}<a href="{$current_location}/ticket/attachment/{$a['attachid']}">{$a['file_name']}</a> &nbsp; {/foreach}
<br />
{/if}
<br /></td>
</tr>
<span id="message_{$i['messageid']}" style="display: none;">{$i['message']}</span>
{/foreach}

</table>
{/if}
<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

<div id="original_message" style="margin: 5px; padding: 10px; border: 1px solid #CCC; background-color: #f3f3f3;">
<h3>{lng[Original message]}</h3>
<br />
{if $ticket['cat1']}Category: {$ticket['cat1']}{/if}
{if $ticket['cat2']} &gt; {$ticket['cat2']}{/if}
{if $ticket['cat3']} &gt; {$ticket['cat3']}{/if}
{if $ticket['cat4']} &gt; {$ticket['cat4']}{/if}
{if $ticket['cat5']} &gt; {$ticket['cat5']}{/if}
<br />
{if $ticket['fields']}
 {foreach $ticket['fields'] as $field=>$value}
  {$field}: {$value}<br />
 {/foreach}
<br />
{/if}
Subject: {$ticket['subject']}<br />
Priority: {php $mode="static"; $name="priority"; $value=$ticket['priority'];}{include="common/ticket_priority.php"}<br />
<br />
Message:<br />
{php echo func_eol2br($ticket['message']);}<br /><br />
{if $ticket['attachments']}<i>{lng[Attached files]}:</i>
{foreach $ticket['attachments'] as $a}<a href="{$current_location}/ticket/attachment/{$a['attachid']}">{$a['file_name']}</a> &nbsp; {/foreach}
<br />
{/if}
</div>

<br />
<h3>{lng[lbl_post_new_message]}</h3>
<a name="create_new"></a>
<form method="POST" name="mesform" action="/ticket/{$ticket['ticketid']}" enctype="multipart/form-data" onsubmit="javascript: if(document.mesform.message.value == '') { document.getElementById('error_mes').style.display='block'; return false; } else { document.getElementById('error_mes').style.display='none'; this.disabled=true; submitForm(this, 'create_new'); }">
<input type="hidden" name="mode" value="create_new">

<table cellspacing="0" cellpadding="0" width="100%">
<tr>
 <td width="400" valign="top"><textarea cols="52" rows="7" name="message" onkeyup="javascript: if(this.value && document.getElementById('error_mes').style.display == 'block') document.getElementById('error_mes').style.display='none';"></textarea></td>
 <td valign="top"><b>{lng[lbl_ticket_upload_files]}</b><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]">
 </td>
</tr>
<tr>
 <td><div id="error_mes" style="display: none; color: #cc3300"><b>{lng[err_ticket_message]}</b></div></td>
 <td align="right"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Send message]}</button>&nbsp;</td>
</tr>
</table>
</form>

{/if}
