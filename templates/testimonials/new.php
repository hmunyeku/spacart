{if $config['General']['recaptcha_key']}
<script src='https://www.google.com/recaptcha/api.js?onload=recaptchaOnload&render=explicit' async defer></script>
{/if}

<form method="POST" onsubmit="javascript: return check_new_testimonial();">
<table class="new_testimonial">
<tr>
  <td class="name">{lng[Name]}:</td>
  <td class="star">*</td>
  <td><input id="testimonial_name" type="text" size="32" name="name" value="<?php echo escape($preentered['name'], 2); ?>" /></td>
</tr>

<tr>
  <td class="name">{lng[Your site URL]}:</td>
  <td></td>
  <td><input type="text" size="32" name="url" value="<?php echo escape($preentered['url'], 2); ?>" /></td>
</tr>

<tr>
  <td class="name">{lng[Testimonial]}:</td>
  <td class="star">*</td>
  <td><textarea id="testimonial_message" name="message" cols="40" rows="10">{$preentered['message']}</textarea></td>
</tr>

<tr>
  <td colspan="2">&nbsp;</td>
  <td><br />

{if $config['General']['recaptcha_key']}
<div id="recaptcha_contact" data-sitekey="{$config['General']['recaptcha_key']}"></div><br />
{/if}

<button>{lng[Add testimonial]}</button>
  </td>
</tr>
</table>
</form>
