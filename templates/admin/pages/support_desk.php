{if !$tickets}

<br />

<h3>{lng[Search for tickets]}</h3>

<form name="searchform" action="/admin/support_desk/search" method="post" href="/admin/support_desk/?mode=search">

<table cellpadding="4" cellspacing="0" width="100%">

{php $empty='Y'; $name="posted_data[type]"; $type=$search_prefilled['type'];}
<tr>
        <td align='right'>Type:</td>
        <td width="10">&nbsp;</td>
        <td>{include="common/ticket_type.php"}</td>
</tr>

{if $ticket_cats}
{php $ticket_cat = $search_prefilled['cat1'].'|||'.$search_prefilled['cat2'].'|||'.$search_prefilled['cat3'].'|||'.$search_prefilled['cat4'].'|||'.$search_prefilled['cat5'];}
<tr>
        <td align='right'>Category:</td>
        <td width="10">&nbsp;</td>
        <td>
<select name="posted_data[ticket_category]">
<option value="">All</option>
{foreach $ticket_cats as $k=>$v}
<option value="{$k}"{if $k == $ticket_cat} selected{/if}>{$v}</option>
{/foreach}
</select>
        </td>
</tr>
{/if}

{php $empty='Y'; $name="posted_data[priority]"; $value=$search_prefilled['priority'];}
<tr>
        <td align='right'>Priority:</td>
        <td width="10">&nbsp;</td>
        <td>{include="common/ticket_priority.php"}</td>
</tr>

<tr>
        <td align='right'>{lng[Customer email]}:</td>
        <td width="10">&nbsp;</td>
        <td><input type='text' name='posted_data[email]' value="{php echo escape($search_prefilled['email'], 2);}" size="20"></td>
</tr>

<tr>
        <td align='right'>{lng[Subject]}:</td>
        <td width="10">&nbsp;</td>
        <td><input type='text' name='posted_data[subject]' value="{php echo escape($search_prefilled['subject'], 2);}" size="80"></td>
</tr>

<tr>
        <td align='right'>{lng[Message]}:</td>
        <td width="10">&nbsp;</td>
        <td><input type='text' name='posted_data[message]' value="{php echo escape($search_prefilled['message'], 2);}" size="80"></td>
</tr>
{php $name="posted_data[status]"; $empty='Y'; $status=$search_prefilled['status'];}
<tr>
        <td align='right'>{lng[Status]}:</td>
        <td width="10">&nbsp;</td>
        <td>{include="common/ticket_status.php"}</td>
</tr>

<tr>
        <td align='right'>{lng[Ticket ID]}:</td>
        <td width="10">&nbsp;</td>
        <td>
<input type='text' name='posted_data[ticketid1]' value='{$search_prefilled['ticketid1']}' size="7">
        </td>
</tr>

<tr>
        <td align='right'>{lng[During date period]}:</td>
        <td width="10">&nbsp;</td>
        <td>
<table cellpadding="0" cellspacing="0">
<tr>
        <td width="5"><input type="radio" id="date_period_A" name="posted_data[date_period]" value="A"{if $search_prefilled['date_period'] == "" or $search_prefilled['date_period'] == "A"} checked="checked"{/if} /></td>
        <td class="OptionLabel"><label for="date_period_A">{lng[All]}</label></td>

        <td width="5"><input type="radio" id="date_period_M" name="posted_data[date_period]" value="M"{if $search_prefilled['date_period'] == "M"} checked="checked"{/if} /></td>
        <td class="OptionLabel"><label for="date_period_M">{lng[This month]}</label></td>

        <td width="5"><input type="radio" id="date_period_W" name="posted_data[date_period]" value="W"{if $search_prefilled['date_period'] == "W"} checked="checked"{/if} /></td>
        <td class="OptionLabel"><label for="date_period_W">{lng[This week]}</label></td>

        <td width="5"><input type="radio" id="date_period_D" name="posted_data[date_period]" value="D"{if $search_prefilled['date_period'] == "D"} checked="checked"{/if} /></td>
        <td class="OptionLabel"><label for="date_period_D">{lng[Today]}</label></td>
</tr>
<tr>
        <td width="5"><input type="radio" id="date_period_C" name="posted_data[date_period]" value="C"{if $search_prefilled['date_period'] == "C"} checked="checked"{/if} /></td>
        <td colspan="9" class="OptionLabel"><label for="date_period_C">{lng[Specify period below]}</label></td>
</tr>
</table>
        </td>
</tr>

<tr>
        <td align="right" nowrap="nowrap">{lng[Search period]}:</td>
        <td width="10">&nbsp;</td>
        <td><input type="text" id="date_from" name="posted_data[date_from]" value="<?php echo $search_prefilled['date_from']; ?>" size="7" /> - <input type="text" id="date_to" name="posted_data[date_to]" value="<?php echo $search_prefilled['date_to']; ?>" size="7" /></td>
</tr>

<tr>
        <td></td>
        <td><input id="unread" type="checkbox" name="posted_data[unread]" value="Y"{if $search_prefilled['unread'] == "Y"} checked{/if}></td>
        <td><label for="unread" style='cursor: pointer;'>{lng[Unread tickets]}</label></td>
</tr>

<tr>
        <td colspan='2'</td>
        <td><input type="submit" value="{lng[Search]}" />&nbsp;&nbsp;&nbsp;<input type="button" value="{lng[Reset]}" onclick="javascript: self.location='/admin/support_desk/reset';" /></td>
</tr>

</table>


</form>

<br />

{/if}


{if $_GET['mode'] == "search" && $tickets}

<a name="results"></a>

{if $_GET['mode'] == "search"}
{if $total_items > "0"}
{lng[Results found]}: {$total_items}<br />
{lng[Displaying]} {$first_item}-{$last_item} {lng[results]}
{else}
{lng[No tickets found]}
{/if}
{/if}

<br /><br />

<h3>{lng[Search results]}</h3>

<div align="right"><a href="/admin/support_desk/">{lng[Search again]}</a></div>

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

<br /><a href="javascript: void(0);" onclick="javascript: check_all(document.ticketsform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.ticketsform, 'to_delete', false);">{lng[Uncheck all]}</a>

<form action="tickets.php" method="post" name="ticketsform">
<input type="hidden" name="mode" value="" />
{if $navigation_page > 1}
<input type="hidden" name="pagestr" value="&amp;page={$navigation_page}" />
{/if}


<table cellpadding="2" cellspacing="1" width="100%" class="lines-table">

<tr>
        <th>&nbsp;</th>
        <th>#</th>
        <th>{lng[Status]}</th>
        <th>{lng[Type]}</th>
        <th>{lng[Subject]}</th>
        <th>{lng[Customer]}</th>
        <th>{lng[Messages]}</th>
        <th>{lng[Date]}</th>
        <th>{lng[Priority]}</th>
</tr>

{foreach $tickets as $i}
<tr>
        <td width="5"><input type="checkbox" name="to_delete[{$i['ticketid']}]" /></td>
        <td width='30'><a href="/admin/ticket/{$i['ticketid']}">{$i['ticketid']}</a></td>
        <td width="100" align='center'>{php $mode = ''; $name="statuses[".$i['ticketid']."]"; $status=$i['status'];}{include="common/ticket_status.php"}</td>
        <td width="100" align='center'>{php $mode='static'; $name="types[".$i['ticketid']."]"; $type=$i['type'];}{include="common/ticket_type.php"}</td>
        <td><a href="/admin/ticket/{$i['ticketid']}">{$i['subject']}{if $i['admin_read'] == 'N'} <font color="red">(Not read)</font>{/if}</a></td>
        <td width='200' align='center'>
{if $i['userid']}
        <a href="/admin/user/{$i['userid']}">{$i['customer']['firstname']} {$i['customer']['lastname']} ({$i['email']})</a>
{else}
{$i['email']}
{/if}
        </td>
        <td width='70' align='center'>{$i['count']}</td>
        <td width='150' align='center'><a href="/admin/ticket/{$i['ticketid']}">{php echo date($datetime_format, $i['date']);}</a></td>
        <td width="70" align="center">{php $mode="static"; $value=$i['priority'];}{include="common/ticket_priority.php"}</td>
</tr>
{/foreach}

<tr>
        <td colspan="9">
<div class="fixed_save_button">
        <input type="button" value="{lng[Update]}" onclick="javascript: submitForm(this, 'update');" />
        <input type="button" value="{lng[Delete selected]}" onclick="if (confirm('Are you sure?', $(this)) || confirmed) submitForm(this, 'delete');" />
</div>
        </td>
</tr>


</table>
</form>

<br />

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>

{/if}

<br />

