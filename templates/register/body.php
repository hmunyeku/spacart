{if $config['General']['recaptcha_key']}
<script src='https://www.google.com/recaptcha/api.js?onload=recaptchaOnload&render=explicit' async defer></script>
{/if}

<h1>{lng[Register]}</h1>
<center>
<table class="login_table">
<tr>
 <td valign="top">
<form name="register" method="POST" action="/{if $get['0'] == 'register'}register{else}profile{/if}">
{php echo spacart_csrf_field();}
    <div class="group">
      <input class="firstname" maxlength="32" required type="text" name="firstname" value="{php echo escape($userinfo['firstname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[First name]}</label>
    </div>

    <div class="group">
      <input maxlength="32" type="text" required name="lastname" value="{php echo escape($userinfo['lastname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Last name]}</label>
    </div>

    <div class="group">
      <input type="text" name="email" required value="{php echo escape($userinfo['email'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Email]}</label>
    </div>
<div class="register-email-error">Sorry this email address is already a registered user <a href="/login" onclick="return login_popup();" class="ajax_mobile_link login-link">Login</a></div>

    <div class="group">
      <input type="password" required name="password" value="" autocomplete="off" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Password]}</label>
    </div>

{if $memberships}
{php $name="pending_membershipid"; $value = 0;}
    <div class="group group-select">
 <label>{lng[Sign up for membership]}:</label>
{include="common/membership.php"}
    </div>
{/if}

{if $config['General']['recaptcha_key']}
<div id="recaptcha_register" data-sitekey="{$config['General']['recaptcha_key']}"></div>
{/if}

<div align="center"><button type="button">{lng[Register]}</button> &nbsp; <a href="/login" onclick="return login_popup();" class="ajax_mobile_link main-button login-link">{lng[Login]}</a></div>

{*
<table class="login-table">
<tr>
 <td class="name">{lng[Fisrtname]}:</td>
 <td><input class="firstname" maxlength="32" type="text" name="firstname" value="{php echo escape($userinfo['firstname'], 2);}" /></td>
</tr>
<tr>
 <td class="name">{lng[Lastname]}:</td>
 <td><input maxlength="32" type="text" name="lastname" value="{php echo escape($userinfo['lastname'], 2);}" /></td>
</tr>
<tr>
 <td class="name">{lng[Email]}:</td>
 <td><input type="text" name="email" value="{php echo escape($userinfo['email'], 2);}" /></td>
</tr>
<tr>
 <td class="name">{lng[Password]}:</td>
 <td><input type="password" name="password" value="" autocomplete="off" /></td>
</tr>
{if $memberships}
{php $name="pending_membershipid"; $value = 0;}
<tr>
 <td class="name">{lng[Sign up for membership]}:</td>
 <td>{include="common/membership.php"}</td>
</tr>
{/if}
<tr>
 <td></td>
 <td><br /><button type="button">{lng[Register]}</button> &nbsp; <a href="/login" onclick="javascript: return login_popup();" class="main-button login-link">{lng[Login]}</a></td>
</tr>
</table>
*}
</form>
 </td>
</tr>
</table>

</center>