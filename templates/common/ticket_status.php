{if $mode == "static"}

{if $status == "O"}
{lng[Open]}
{elseif $status == "C"}
{lng[Closed]}
{elseif $status == "S"}
{lng[lbl_ticket_scheduled]}
{elseif $status == "1"}
{lng[Waiting for consultant]}
{elseif $status == "2"}
{lng[Waiting for client]}
{elseif $status == "3"}
{lng[Cancelled]}
{/if}

{else}

<select name="{$name}" {$extra}{if $onchange} onchange="{$onchange}"{/if}>
{if $empty == "Y"}
<option value=""{if $status == ""} selected{/if}>{lng[All]}</option>
{/if}
<option value="O"{if $status == "O"} selected{/if}>{lng[Open]}</option>
<option value="C"{if $status == "C"} selected{/if}>{lng[Closed]}</option>
{*
<option value="S"{if $status == "S"} selected{/if}>{lng[lbl_ticket_scheduled]}</option>
*}
{*
<option value="1"{if $status == "1"} selected{/if}>{lng[Waiting for consultant]}</option>
<option value="2"{if $status == "2"} selected{/if}>{lng[Waiting for client]}</option>
*}
<option value="3"{if $status == "3"} selected{/if}>{lng[Cancelled]}</option>
</select>

{/if}
