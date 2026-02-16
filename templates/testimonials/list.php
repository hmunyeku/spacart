{if $_GET['mode'] == 'login'}
<script>
login_popup();
</script>
{/if}
<br />
<button onclick="self.location='{$current_location}/testimonials/new';">{lng[Write testimonial]}</button>
<br /><br />
<hr size="1" />
{if $total_pages > 2}
<br />
{include="common/navigation.php"}
<br />
{/if}
<table cellspacing="0" cellpadding="0" class="testimonials-list">
{foreach $testimonials as $t}
<tr>
 <th align="left">{$t['name']}{if $t['url']} (<a rel="nofollow" href="{$t['url']}" target="_blank">{$t['url']}</a>){/if}</th>
 <th align="right">{php echo date($date_format, $t['date']);}</th>
</tr>
<tr>
 <td colspan="2" class="message" align="left"><div class="message-box">{$t['message']}</div></td>
</tr>
{/foreach}
</table>
{if $total_pages > 2}
<br />
{include="common/navigation.php"}
<br />
{/if}

<hr size="1" />
<br />
<button onclick="self.location='{$current_location}/testimonials/new';">{lng[Write testimonial]}</button>
<br />