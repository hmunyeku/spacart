<select name="{$name}">
<option value="0">{lng[No membership]}</option>
{if $memberships}
{foreach $memberships as $m}
<option value="{$m['membershipid']}"{if $value == $m['membershipid']} selected{/if}>{$m['membership']}</option>
{/foreach}
{/if}
</select>
