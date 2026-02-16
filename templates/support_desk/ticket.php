{if !$predefined}
{php $predefined = array();}
{/if}
{if $ticket}
<h1>{lng[Ticket]} #{$ticket['ticketid']} (<span id="ticket-status-{$ticket['ticketid']}">{php $mode = "static"; $status=$ticket['status'];}{include="common/ticket_status.php"}</span>)</h1>
{else}
<h1>{lng[New ticket]}</h1>
{/if}
<br />
{if !$ticket}
{if $config['General']['recaptcha_key']}
<script src='https://www.google.com/recaptcha/api.js?onload=recaptchaOnload&render=explicit' async defer></script>
{/if}

<form action="/ticket{if $_GET['productid']}?productid={$_GET['productid']}{/if}" method="POST" name="ticketform" enctype="multipart/form-data">
<input type="hidden" name="mode" value="">
<input type="hidden" name="productid" value="{$product['productid']}">

<div class="ticket-div">
<table cellspacing="1" cellpadding="2" width="" class="tickettable">
<tr>
 <td valign="top" class="width-50p">
 <table width="100%">
{if !$login}
<tr>
 <td class='data-name' align="right">{lng[Email]}:</td>
 <td class="star">*</td>
 <td>
<div class="its4mobile">{lng[Email]}</div>
 <input type="text" name="email" id="ticket_email" value="{php echo escape($predefined['email'], 2);}"></td>
</tr>
{/if}

<tr>
 <td class='data-name' align="right">{lng[Ticket priority]}:</td>
 <td>&nbsp;</td>
{php $name="priority"; $value=$predefined['priority'];}
 <td>
<div class="its4mobile">{lng[Ticket priority]}</div>
 {include="common/ticket_priority.php"}</td>
</tr>

<tr>
 <td class='data-name' align="right">{lng[Subject]}:</td>
 <td class="star">*</td>
 <td>
<div class="its4mobile">{lng[Subject]}</div>
 <input type="text" name="subject" id="ticket_subject" value="{php echo escape($predefined['subject'], 2);}"></td>
</tr>

<tr>
 <td class='data-name' valign="top" align="right">{lng[Message]}:</td>
 <td class="star">*</td>
 <td>
<div class="its4mobile">{lng[Message]}</div>
 <textarea name="message" id="ticket_message">{$predefined['message']}</textarea></td>
</tr>

<tr>
 <td class="data-name" valign="top" align="right"></td>
 <td>&nbsp;</td>
 <td>
<label class="drop-down">{lng[Upload files]}:</label><br /><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]">
 </td>
</tr>

<tr>
 <td class="data-name" valign="top" align="right"></td>
 <td>&nbsp;</td>
 <td class="error-cc3300"><b id="error_mes">{lng[Please, select all categories, enter your subject and message]}</b></td>
</tr>

<tr>
 <td class="data-name" valign="top" align="right"></td>
 <td>&nbsp;</td>
 <td>
{if $config['General']['recaptcha_key']}
<div id="recaptcha_ticket" data-sitekey="{$config['General']['recaptcha_key']}"></div><br />
{/if}
 </td>
</tr>

<tr>
 <td class="data-name" valign="top" align="right"></td>
 <td>&nbsp;</td>
 <td><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored" type="button" onclick="submit_ticket();">{lng[Create ticket]}</button></td>
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
<span id="message_{$i['messageid']}" class="display-none">{$i['message']}</span>
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

<div id="original_message">
<h3>{lng[Request information]}</h3>
<br />
{if $ticket['cat1']}Category: {$ticket['cat1']}{/if}
{if $ticket['cat2']} &gt; {$ticket['cat2']}{/if}
{if $ticket['cat3']} &gt; {$ticket['cat3']}{/if}
{if $ticket['cat4']} &gt; {$ticket['cat4']}{/if}
{if $ticket['cat5']} &gt; {$ticket['cat5']}{/if}
<br />
{if $ticket['fields']}
<br />
<table width="100%">
 {foreach $ticket['fields'] as $field=>$value}
  <tr>
   <td width="200" valign="top"><u>{$field}</u>:</td>
   <td valign="top">{$value}</td>
  </tr>
 {/foreach}

</table>
{/if}

Subject: {$ticket['subject']}<br />
Priority: {php $mode="static"; $name="priority"; $value=$ticket['priority'];}{include="common/ticket_priority.php"}<br />
<br />
Message:<br />
{php echo func_eol2br($ticket['message']);}<br />

<br />
{if $ticket['attachments']}<i>{lng[Attached files]}:</i>
{foreach $ticket['attachments'] as $a}<a href="{$current_location}/ticket/attachment/{$a['attachid']}">{$a['file_name']}</a> &nbsp; {/foreach}
<br />
{/if}
</div>

{if $ticket['status'] != '3' && $ticket['status'] != 'C'}
<br />
<h3>{lng[Post new message]}</h3>
<a name="create_new"></a>
<form method="POST" name="mesform" id="ticketmesform" action="/ticket/{$ticket['ticketid']}" enctype="multipart/form-data">
<input type="hidden" name="mode" value="create_new">

<table cellspacing="0" cellpadding="0" width="100%">
<tr>
 <td width="400" valign="top"><textarea cols="52" rows="7" name="message" id="post_new_message"></textarea></td>
 <td valign="top"><b>{lng[Upload files]}</b><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]">
 </td>
</tr>
<tr>
 <td><div id="error_mes"><b>{lng[err_ticket_message]}</b></div></td>
 <td align="right"><button class="mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--colored">{lng[Send message]}</button>&nbsp;</td>
</tr>
</table>
</form>
{/if}

{/if}
