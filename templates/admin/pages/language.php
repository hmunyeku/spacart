{if $translate_mode}
<a href="/admin/language?exit_translate_mode=1" class="mdl-button">Exit "Translate" mode</a>
{else}
<a href="/admin/language?translate_mode=1" class="mdl-button">Enter "Translate" mode</a>
{/if}
&nbsp; (Make sure you do it in Development mode - see settings.php)
<br /><br />
<form method="post" enctype="multipart/form-data" name="fpform" class="noajax">
<table width="800">
<tr>
 <td width="50%">
<table width="400" cellpadding="3" cellspacing="1">
<tr>
 <td colspan="2"><h3>{lng[Export language labels]}</td>
</tr>

<tr>
  <td width="100%"></td>
  <td>
  <button type="button" onclick="self.location='/admin/language/{$get[2]}?mode=export';">{lng[Export]}</button>
  </td>
</tr>

</table>
 </td>
 <td>
<table width="400" cellpadding="3" cellspacing="1">

<tr>
 <td colspan="2"><h3>{lng[Import language labels]}</td>
</tr>

<tr>
  <td width="100%"><input type="file" name="file" /></td>
  <td>
  <button type="submit">{lng[Import]}</button>
  </td>
</tr>
</table>
 </td>
</tr>
</table>
</form>