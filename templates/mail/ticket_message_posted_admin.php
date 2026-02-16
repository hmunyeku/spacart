{include="mail/header.php"}
{$email_header}

{lng[Hello]},

<p />{$userinfo['firstname']} {$userinfo['lastname']} has been placed a new message.

<p />Ticket #{$ticket['ticketid']}

<p />Priority: {php $mode='static'; $value=$ticket['priority'];}{include="common/ticket_priority.php"}

<p />{lng[lbl_full_ticket]}<br />

<a href="{$http_location}/admin/ticket/{$ticket['ticketid']}">{$http_location}/admin/ticket/{$ticket['ticketid']}</a>

<p />{lng[lbl_ticket_subject]}: {$ticket['subject']}

<hr size='1' />

{lng[Message]}:

<p />{$message['message']}

<br /><br />

{$signature}
{include="mail/footer.php"}