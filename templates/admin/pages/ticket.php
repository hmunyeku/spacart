{if $ticket}
<h1>{lng[Ticket]} #{$ticket['ticketid']}</h1>
{else}
<h1>{lng[New estimate request]}</h1>
{/if}

{if $ticket}
<h3>{$ticket['subject']}</h3>
{else}
<h3>{lng[Ticket info]}</h3>
{/if}

<div align="right"><a href="/admin/support_desk?mode=search">{lng[Back to search results]}</a></div>

<form method="POST" name="ticketform" enctype="multipart/form-data"{* class="noajax"*}>
<input type="hidden" name="mode" value="">
<table cellspacing='1' cellpadding='2' width='100%'>

{if $ticket}
<tr>
 <td valign="top" align='right'>{lng[Admin notes]}:</td>
 <td width='10'>&nbsp;</td>
 <td><textarea name="ticket[notes]" rows="10" cols="80">{$ticket['notes']}</textarea></td>
</tr>
{php $name="ticket[type]"; $type=$ticket['type'];}
<tr>
        <td align='right'>Type:</td>
        <td width="10">&nbsp;</td>
        <td>{include="common/ticket_type.php"}
{if $product}
<a href="/admin/products/{$product['productid']}" target="_blank">{$product['name']}</a>
{/if}
        </td>
</tr>

{*
<tr>
	<td valign="top" align='right'>Ticket info:</td>
	<td width="10">&nbsp;</td>
	<td>
{if $ticket_cats}
{php $ticket_cat = $ticket['cat1'].'|||'.$ticket['cat2'].'|||'.$ticket['cat3'].'|||'.$ticket['cat4'].'|||'.$ticket['cat5'];}
<select name="ticket_category">
{foreach $ticket_cats as $k=>$v}
<option value="{$k}"{if $k == $ticket_cat} selected{/if}>{$v}</option>
{/foreach}
</select>
<br />
{/if}
{if $ticket['fields']}
 {foreach $ticket['fields'] as $field=>$value}
 <br />
  {$field}: {$value}<br />
 {/foreach}
<br />
{/if}
	</td>
</tr>
*}
<tr>
        <td align='right'>Date:</td>
        <td width="10">&nbsp;</td>
        <td>{php echo date($datetime_format, $ticket['date']);}</td>
</tr>
{/if}
<tr>
        <td align='right'>Priority:</td>
        <td width="10">&nbsp;</td>
        <td>
        {php $name="ticket[priority]"; $value=$ticket['priority'];}
		{include="common/ticket_priority.php"}
        </td>
</tr>

<tr>
 <td align='right'>{lng[Customer]}:</td>
 <td width='10'>&nbsp;</td>
 <td>
{if $ticket['userid']}
<a href="/admin/user/{$ticket['userid']}">{$ticket['customer']['firstname']} {$ticket['customer']['lastname']} ({$ticket['email']})</a>
{else}
{$ticket['email']}
{/if}
 </td>
</tr>

<tr>
 <td align='right'>{lng[Ticket subject]}:</td>
 <td width='10'>&nbsp;</td>
 <td><input type="text" id="ticket_subject" name="ticket[subject]" size="85" value="{php echo escape($ticket['subject'], 2);}"></td>
</tr>


<tr>
 <td valign='top' align='right'>{lng[Ticket message]}:</td>
 <td width='10'>&nbsp;</td>
 <td><textarea name="ticket[message]" id="ticket_message" cols="85" rows="5">{$ticket['message']}</textarea></td>
</tr>

<tr>
 <td align='right'>{lng[Status]}:</td>
 <td width='10'>&nbsp;</td>
 <td>
{php $name="ticket[status]"; $status=$ticket['status'];}
{include="common/ticket_status.php"}
 </td>
</tr>

{if $ticket}
{if $ticket['attachments']}
<tr>
 <td valign='top' align='right'>{lng[Attached files]}:</td>
 <td width='10'>&nbsp;</td>
 <td>
{foreach $ticket['attachments'] as $a}<a href="{$current_location}/ticket/attachment/{$a['attachid']}">{$a['file_name']}</a> &nbsp; {/foreach}
<br />
 </td>
</tr>
{/if}
{else}
<tr>
 <td valign='top' align='right'>{lng[lbl_attach_files}:</td>
 <td width='10'>&nbsp;</td>
 <td>
<table cellspacing="1" cellpadding="2" id="wp_table">
<tr id="f_tr">
        <td id="f_box_1"><input type="file" name="attachments[]" size="40"></td>
        <td>{include file="buttons/multirow_add.tpl" mark="f"}</td>
</tr>
</table>
 </td>
</tr>
{/if}

<tr>
 <td colspan="2"></td>
 <td>
<br />
<input type='button' value='{if !$ticket}{lng[Add|escape]}{else}{lng[Save|escape]}{/if}' onclick="javascript: submitForm(this, '');">
{if $ticket}
&nbsp;
<input type='button' value='{lng[Delete]}' onclick="if (confirm('Are you sure?', $(this)) || confirmed) submitForm(this, 'delete');">
{if $messages}
&nbsp;
<input type='button' value='{lng[Delete all messages]}' onclick="if (confirm('Are you sure?', $(this)) || confirmed) submitForm(this, 'delete_messages');">
{/if}
{/if}
 </td>
</tr>
</table>

</form>

<br /><br />
{if $tickets && $ticket}
<b>Move all messages to another ticket(will remove the current ticket):</b>
<select id="move_to">
<option value=""></option>
{foreach $tickets as $i}
<option value="{$i['ticketid']}">{$i['ticketid']} - {$i['subject']}</option>
{/foreach}
</select>
<button onclick="if (!$('#move_to').val()) return false; if (confirm('Are you sure?', $(this)) || confirmed) self.location='/admin/ticket/{$ticket['ticketid']}?mode=move&to='+$('#move_to').val();">Move</button>
{/if}

{if $ticket}
<br />
<a name="messages"></a>
<h3>{lng[Ticket messages]}</h3>

{if $messages}
<script>
function edit_message(id) {
        document.mesform.message.value=document.getElementById('message_'+id).innerHTML;
        document.mesform.messageid.value=id;
        document.mesform.mode.value='update_message';
        document.getElementById('update_message').disabled=false;
}
</script>

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

<table cellspacing='0' cellpadding'0' width='100%'>
{foreach $messages as $i}
<tr>
 <td align='left'><a name="m{$i['messageid']}"></a>&nbsp;<a href="/admin/user/{$i['userid']}">{$i['firstname']} {$i['lastname']} ({$i['email']})</a>{if $i['ip']} (IP: {$i['ip']}){/if}</td>
 <td align='right' width="150">{php echo date($datetime_format, $i['date']);}</td>
 <td align='right' width='200'><a href="#add_new" onclick="javascript: edit_message('{$i['messageid']}')">Edit</a> / <a href="javascript: void(0);" onclick="javascript: if (confirm('Delete this message?')) self.location='/admin/ticket/{$ticket['ticketid']}?mode=delete_message&messageid={$i['messageid']}';">Delete</a>&nbsp;</td>
</tr>
<tr>
 <td><hr />{php echo func_eol2br($i['message']);}

<br />

{if $i['attachments']}
<br />
{foreach $i['attachments'] as $a}<a href="{$current_location}/ticket/attachment/{$a['attachid']}">{$a['file_name']}</a> &nbsp; {/foreach}
<br />
{/if}
 <br /></td>
 <td colspan="2">
{if $tickets && $ticket}
Clone message to:
<select class="ticket_clone_to" id="move_to_{$i['messageid']}">
<option value=""></option>
{foreach $tickets as $t}
<option value="{$t['ticketid']}">{$t['ticketid']} - {$t['subject']}</option>
{/foreach}
</select>
<button onclick="if (!$('#move_to_{$i['messageid']}').val()) return false; if (confirm('Are you sure?', $(this)) || confirmed) self.location='/admin/ticket/{$ticket['ticketid']}?messageid={$i['messageid']}&mode=clone_message&to='+$('#move_to_{$i['messageid']}').val();">Clone</button>
{/if}
 </td>
</tr>
<span id="message_{$i['messageid']}" class="display-none">{$i['message']}</span>
{/foreach}
</table>
{/if}

<h3>{lng[Add/Edit]}</h3>
<a name="add_new"></a>
<form method="POST" name="mesform" enctype="multipart/form-data"{* class="noajax"*}>
<input type="hidden" name="mode" value="add_message">
<input type="hidden" name="messageid">
<table cellspacing='0' cellpadding'0' width="600">
<tr>
 <td valign="top"><textarea cols="80" rows="10" name="message"></textarea></td>
 <td valign="top">
<b>{lng[Upload files]}</b><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]"><br />
<input size="40" type="file" name="attachments[]">
 </td>
</tr>
<tr>
 <td align='right'>
<input type="button" value="{lng[Save]}" onclick="javascript: submitForm(this, 'update_message');" id="update_message" disabled>&nbsp;
<input type="button" value="{lng[Add]}" onclick="javascript: submitForm(this, 'add_message');">&nbsp;
 </td>
 <td></td>
</tr>
</table>
</form>

{/if}
