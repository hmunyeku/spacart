<div class="padding-page">
{if $config['General']['recaptcha_key']}
<script src='https://www.google.com/recaptcha/api.js?onload=recaptchaOnload&render=explicit' async defer></script>
{/if}
<h1>{lng[Contact us]}</h1>
<br />
<form method="POST" name="help" id="help_form" onsubmit="javascript: if(!document.help.subject.value || !document.help.message.value || !document.help.email.value) { $('#error_mes').show(); return false; } else $('#error_mes').hide();">
<input type="hidden" name="mode" value="send">

    <div class="group">
      <input type="text" name="email" required value="{php echo escape($userinfo['email'], 2);}" onkeyup="javascript: if(this.value) $('#error_mes').hide();">
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Your E-mail]}</label>
    </div>

    <div class="group">
      <input type="text" name="subject" required onkeyup="javascript: if(this.value) $('#error_mes').hide();">
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Subject]}</label>
    </div>

    <div class="group">
      <textarea name="message" required class="help-message" onkeyup="javascript: if(this.value) $('#error_mes').hide();"></textarea>
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Message]}</label>
    </div>

<div id="error_mes">{lng[Please, enter your E-mail, subject and message.]}</div>
{if $config['General']['recaptcha_key']}
<div id="recaptcha_contact" data-sitekey="{$config['General']['recaptcha_key']}"></div><br />
{/if}

<button type="button" class="submit_help">{lng[Send]}</button>
</form>
</div>
<br>