<h1>{lng[Login]}</h1>
<table class="login_table">
<tr>
 <td valign="top">
<form name="login" method="POST" action="/login">
{php echo spacart_csrf_field();}

    <div class="group">
      <input class="email" required type="text" name="email" value="{if $_POST['email']}{php echo escape($_POST['email'], 2);}{/if}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Email]}</label>
    </div>
<div class="register-email-error" style="display:none">{lng[Invalid email or password]}. <a href="/register" onclick="return register_popup();" class="register ajax_mobile_link">{lng[Create new user]}</a></div>
    <div class="group">
      <input type="password" required name="password" value="" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Password]}</label>
    </div>
<div class="register-email-error-2" style="display:none">{lng[Invalid email or password]}. <a href="javascript: void(0);" onclick="return restore_password()">{lng[Recover password]}</a></div>

<button type="button">{lng[Login]}</button> &nbsp; <a href="/register" onclick="return register_popup();" class="main-button register ajax_mobile_link">{lng[Register]}</a>
<br /><br />
<a href="javascript: void(0);" class="main-button nowrap" onclick="return restore_password()">{lng[Password recovery]}</a></td>
</form>
 </td>
</tr>
</table>