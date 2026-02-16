<?php if (empty($_GET['parentid'])) { ?>
<ul class="admin-tabs">
<li<?php if (empty($get['2'])) echo ' class="active"'; ?>><a href="/admin/homepage">{lng[Details]}</a></li>
<li<?php if ($get['2'] == 'banners') echo ' class="active"'; ?>><a href="/admin/homepage/banners">{lng[Banners]}</a></li>
</li>
<div class="clear"></div>
<br />
<?php } ?>

<form name="category_form" action="/admin/homepage/<?php echo $get['2']; ?>" method="post" enctype="multipart/form-data"{* class="noajax"*}>
<input type="hidden" name="mode" value="" />
<?php
if ($get['2'] == 'banners') {
?>

<table cellpadding="3" cellspacing="1">

<tr>
	<th width="10">&nbsp;</th>
	<th>{lng[Banner]}</th>
	<th>{lng[Link]}</th>
	<th>{lng[Alt]}</th>
	<th>{lng[Title]}</th>
	<th>{lng[Subtitle]}</th>
	<th>{lng[Button]}</th>
	<th>{lng[Button URL]}</th>
	<th>{lng[Text Pos]}</th>
	<th>{lng[Text Color]}</th>
	<th>{lng[Overlay]}</th>
	<th width="60">{lng[Pos]}</th>
</tr>

<?php
if ($banners) {
	foreach ($banners as $b) {
?>
<tr>
	<td><input type="checkbox" name="to_delete[<?php echo $b['bannerid'];?>]" /></td>
	<td align="center"><a href="<?php echo $b['image_url'];?>" target="_blank"><img src="<?php echo $b['image_url'];?>" width="100"></a></td>
	<td><input size="20" type="text" name="to_update[<?php echo $b['bannerid'];?>][url]" value="<?php echo escape($b['url']);?>" /></td>
	<td><input size="20" type="text" name="to_update[<?php echo $b['bannerid'];?>][alt]" value="<?php echo escape($b['alt']);?>" /></td>
	<td><input size="15" type="text" name="to_update[<?php echo $b['bannerid'];?>][title]" value="<?php echo escape($b['title']);?>" /></td>
	<td><input size="15" type="text" name="to_update[<?php echo $b['bannerid'];?>][subtitle]" value="<?php echo escape($b['subtitle']);?>" /></td>
	<td><input size="10" type="text" name="to_update[<?php echo $b['bannerid'];?>][button_text]" value="<?php echo escape($b['button_text']);?>" /></td>
	<td><input size="15" type="text" name="to_update[<?php echo $b['bannerid'];?>][button_url]" value="<?php echo escape($b['button_url']);?>" /></td>
	<td><select name="to_update[<?php echo $b['bannerid'];?>][text_position]">
		<option value="left"<?php if ($b['text_position']=='left') echo ' selected';?>>Left</option>
		<option value="center"<?php if ($b['text_position']=='center') echo ' selected';?>>Center</option>
		<option value="right"<?php if ($b['text_position']=='right') echo ' selected';?>>Right</option>
	</select></td>
	<td><input size="7" type="text" name="to_update[<?php echo $b['bannerid'];?>][text_color]" value="<?php echo escape($b['text_color']);?>" /></td>
	<td><input size="15" type="text" name="to_update[<?php echo $b['bannerid'];?>][overlay_color]" value="<?php echo escape($b['overlay_color']);?>" /></td>
	<td><input size="5" type="text" name="to_update[<?php echo $b['bannerid'];?>][pos]" value="<?php echo $b['pos'];?>"></td>
</tr>
<?php
	}
?>
<tr>
	<td colspan="12" class="SubmitBox">
	<br />
	<button type="button" onclick="javascript: submitForm(this, 'update');">{lng[Update]}</button>
	<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
	</td>
</tr>

<?php
} else {
?>
<tr>
 <td colspan="12" align="center">{lng[No banners for  homepage]}</td>
</tr>
<?php
}
?>

<tr>
<td colspan="12"><br /><h3>{lng[Add new]}</td>
</tr>

<tr>
	<td>&nbsp;</td>
	<td><input type="file" size="10" name="userfile" /></td>
	<td><input size="20" type="text" name="new_url" placeholder="Link URL"></td>
	<td><input size="20" type="text" name="new_alt" placeholder="Alt text"></td>
	<td colspan="8"></td>
</tr>

<tr>
	<td colspan="12">
<button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button>
	</td>
</tr>

</table>

<?php
} else {
?>
<script src="/ckeditor/ckeditor.js"></script>
<table cellpadding="3" cellspacing="1" class="category normal-table">
<tr>
	<td height="10" nowrap="nowrap">{lng[Title]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<input type="text" name="site_title" maxlength="255" size="80" value="<?php echo escape(lng('Site title'), 2); ?>" />
	</td>
</tr>

<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
	<td width="10" height="10">&nbsp;</td>
 <td><div class="select-title">{lng[Description]}</div>
	<script>
		var editor;
		// The instanceReady event is fired, when an instance of CKEditor has finished
		// its initialization.
		CKEDITOR.on( 'instanceReady', function( e ) {
			editor = e.editor;
		    $('*').removeAttr("title");
		});
	</script>
		<textarea class="ckeditor" id="ck_editor" cols="65" rows="7" name="site_description"><?php echo lng('Site description'); ?></textarea>
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Title tag]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<input type="text" size="65" name="meta_title" value="<?php echo escape(lng('Homepage meta title'), 2); ?>" />
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Meta keywords]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<textarea cols="65" rows="4" name="meta_keywords"><?php echo lng('Homepage meta keywords'); ?></textarea>
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Meta description]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<textarea cols="65" rows="4" name="meta_description"><?php echo lng('Homepage meta description'); ?></textarea>
	</td>
</tr>

</table>
<br /><br /><br /><br />
<div class="fixed_save_button">
<button class="button-margin-left" type="submit">{lng[Save]}</button>
</div>

<?php
}
?>
</form>