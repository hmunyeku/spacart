{if $mode == "plain"}
{if $value == "1"}{lng[lbl_ticket_priority_1]}{elseif $value == "2"}{lng[lbl_ticket_priority_2]}{elseif $value == "3"}{lng[lbl_ticket_priority_3]}{elseif $value == "4"}{lng[lbl_ticket_priority_4]}{/if}
{elseif $mode == "static"}
{if $value == "1"}
<font color="blue">{lng[lbl_ticket_priority_1]}</font>
{elseif $value == "2"}
<font color="black">{lng[lbl_ticket_priority_2]}</font>
{elseif $value == "3"}
<font color="red">{lng[lbl_ticket_priority_3]}</font>
{elseif $value == "4"}
<font color="red"><b>{lng[lbl_ticket_priority_4]}</b></font>
{/if}
{else}
<select name="{$name}" {$extra}>
{if $empty == "Y"}
<option value=""{if $value == ""} selected{/if}>{lng[All]}</option>
{/if}
<option value="1"{if $value == "1"} selected{/if}>{lng[lbl_ticket_priority_1]}</option>
<option value="2"{if $value == "2" || (!$value && $empty != "Y")} selected{/if}>{lng[lbl_ticket_priority_2]}</option>
<option value="3"{if $value == "3"} selected{/if}>{lng[lbl_ticket_priority_3]}</option>
<option value="4"{if $value == "4"} selected{/if}>{lng[lbl_ticket_priority_4]}</option>
</select>
{/if}
