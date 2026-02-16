{lng[Total subscribers]}: <?php echo $subscribers ? $subscribers : '0';?> <a href="/admin/subscribtions/export">{lng[Export]}</a>
<br /><br />
<script src="<?php echo $current_location; ?>/ckeditor/ckeditor.js"></script>
<form method="post" name="letterform" enctype="multipart/form-data">
<input type="hidden" name="mode" value="save" />
<input type="hidden" name="id" value="<?php echo $letter['id']; ?>" />

{if ($get['3'] == 'new')}
<h3>{lng[New letter|escape]}</h3>
{else}
<h3>{$letter['subject']}</h3>
{/if}

<table cellpadding="3" cellspacing="1" width="90%">
<?php
if ($letter['date']) {
?>
<tr>
 <td><b>{lng[Added]}:</b></td>
 <td><?php echo date($datetime_format, $letter['date']); ?></td>
</tr>
<?php
}
?>

<tr>
 <td><b>{lng[Subject]}:</b></td>
 <td><input type="text" name="subject" size="40" value="<?php echo escape($letter['subject'], 2); ?>" /></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Message]}:</b></td>
 <td>
	<script>
		var editor;
		// The instanceReady event is fired, when an instance of CKEditor has finished
		// its initialization.
		CKEDITOR.on( 'instanceReady', function( ev ) {
			editor = ev.editor;
		    $('*').removeAttr("title");
		});
	</script>
		<textarea class="ckeditor" id="ck_editor" cols="65" rows="7" name="message"><?php echo $letter['message']; ?></textarea>
<br /><br />
 </td>
</tr>

<tr>
 <td></td>
 <td>
<div class="fixed_save_button">
 <button type="submit">{lng[Save]}</button> &nbsp; <button type="button" onclick="javascript: if (confirmed || confirm('', $(this))) submitForm(this, 'send');">{lng[Send]}</button>
</div>
</td>
</tr>

<tr>
 <td colspan="2"><h3>{lng[Test email]}</h3></td>
 <td></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Email address #1]}:</b></td>
 <td><input type="text" name="email1" value="<?php echo $company_email; ?>" /></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Email address #2]}:</b></td>
 <td><input type="text" name="email2" /></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Email address #3]}:</b></td>
 <td><input type="text" name="email3" /></td>
</tr>

<tr>
 <td></td>
 <td>
<button type="button" onclick="javascript: submitForm(this, 'test');">{lng[Test]}</button>
</td>
</tr>

</table>
</form>