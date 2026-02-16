<h1>{lng[Set your password]}</h1><br />
<form method="POST" name="cpform">
{php echo spacart_csrf_field();}
<div class="logsec cpsec">
<input type="text" name="new_pswd" value="{lng[New password|escape]}" /><br /><br />
<input type="text" name="con_pswd" value="{lng[Confirm password|escape]}" /><br />
<div class="er">{lng[Passwords mismatch]}</div>
<br />
<button type="button">{lng[Change]}</button>
</div>
</form>