<h1>{lng[Please, login to use the admin area]}</h1>

<form name="login" method="POST" action="/admin/login" class="noajax">
<input type="hidden" name="token" value="{$token}" />
<div class="logsec">
<input placeholder="E-mail" class="def email<?php if ($_POST['email']) echo ' def'; ?>" type="text" name="email" value="<?php if (DEMO) { echo 'a@a.com'; }  elseif ($_POST['email']) { echo $_POST['email']; } ?>" />
<br />
<?php
if (!empty($_POST['password'])) {
?>
<input placeholder="Password" type="password" class="def" name="password" value="{$_POST['password']}" />
<?php
} else {
?>
<input placeholder="Password" type="password" class="def" name="password" value="<?php if (DEMO) echo '01230';?>" />
<?php
}
?>
<br />
<div class="login_error">{lng[Please, enter correct email and password]}</div>
<button type="button">Login</button>
&nbsp;
<a href="/admin/login" onclick="javascript: return restore_password();" class="register mdl-button mdl-js-button mdl-js-ripple-effect">Password recovery</a>
</div>
</form>