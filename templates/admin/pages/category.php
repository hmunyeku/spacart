<?php echo $category_location; ?>

<br /><br />

<?php if (empty($_GET['parentid'])) { ?>
<ul class="admin-tabs">
<li<?php if (empty($get['3'])) echo ' class="active"'; ?>><a href="/admin/category/<?php echo $category['categoryid']; ?>">{lng[Details]}</a></li>
{if $category['categoryid']}
<li<?php if ($get['3'] == 'banners') echo ' class="active"'; ?>><a href="/admin/category/<?php echo $category['categoryid']; ?>/banners">{lng[Banners]}</a></li>
{/if}
</li>
<div class="clear"></div>
<br />
<?php } ?>

<form name="category_form" action="/admin/category/<?php echo $category['categoryid']; ?>/<?php echo $get['3']; ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="mode" value="" />
<?php
if ($get['3'] == 'banners') {
?>

<table cellpadding="3" cellspacing="1">

<tr>
	<th width="10">&nbsp;</th>
	<th>{lng[Banner]}</th>
	<th>{lng[Link]}</th>
	<th>{lng[Alt]}</th>
	<th width="100">{lng[Pos]}</th>
</tr>

<?php
if ($banners) {
	foreach ($banners as $b) {
?>
<tr>
	<td><input type="checkbox" name="to_delete[<?php echo $b['bannerid'];?>]" /></td>
	<td align="center"><a href="<?php echo $b['image_url'];?>" target="_blank"><img src="<?php echo $b['image_url'];?>" width="100"></a></td>
	<td><input size="30" type="text" name="to_update[<?php echo $b['bannerid'];?>][url]" value="<?php echo escape($b['url']);?>" /></td>
	<td><input size="30" type="text" name="to_update[<?php echo $b['bannerid'];?>][alt]" value="<?php echo escape($b['alt']);?>" /></td>
	<td><input size="5" type="text" name="to_update[<?php echo $b['bannerid'];?>][pos]" value="<?php echo $b['pos'];?>"></td>
</tr>
<?php
	}
?>
<tr>
	<td colspan="5" class="SubmitBox">
	<br />
	<button type="button" onclick="javascript: submitForm(this, 'update');">{lng[Update]}</button>
	<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
	</td>
</tr>

<?php
} else {
?>
<tr>
 <td colspan="5" align="center">{lng[No banners for this category]}</td>
</tr>
<?php
}
?>

<tr>
<td colspan="5"><br /><h3>{lng[Add new]}</td>
</tr>

<tr>
	<td>&nbsp;</td>
	<td><input type="file" size="10" name="userfile" /></td>
	<td><input size="30" type="text" name="new_url"></td>
	<td><input size="30" type="text" name="new_alt"></td>
	<td align="center"><input type="text" size="5" name="new_pos" /></td>
</tr>

<tr>
	<td colspan="5">
<button type="button" onclick="javascript: submitForm(this, 'add');">{lng[Add]}</button>
	</td>
</tr>

</table>

<?php
} else {
?>
<script src="/ckeditor/ckeditor.js"></script>
<?php if (!empty($_GET['parentid'])) { ?>
<input type="hidden" name="parentid" value="{$_GET['parentid']}" />
<?php } ?>
<table cellpadding="3" cellspacing="1" class="category normal-table">
<tr>
	<td height="10" nowrap="nowrap">{lng[Title]}</td>
	<td width="10" height="10" class="star">*</td>
	<td height="10">
		<input type="text" name="title" maxlength="255" size="65" value="<?php echo escape($category['title']); ?>" onchange="javascript: if (this.form.cleanurl.value == '') copy_clean_url(this, this.form.cleanurl);" />
	</td>
</tr>

<tr>
	<td height="10" nowrap="nowrap">{lng[Clean URL]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<input type="text" name="cleanurl" maxlength="255" size="65" value="<?php echo $category['cleanurl']; ?>" />
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Position]}</td>
	<td width="10" height="10">&nbsp;</td>
	<td height="10">
		<input type="text" name="orderby" size="5" value="<?php echo $category['orderby']; ?>" />
	</td>
</tr>

<tr>
	<td height="10" nowrap="nowrap">{lng[Enabled]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<input type="checkbox" name="enabled" value="1" <?php if (!$category || $category['enabled'] == 1) echo ' checked="checked"'; ?> />
	</td>
</tr>

<tr class="standard">
 <td valign="top" class="hide-td-for-mdl"></td>
	<td width="10" height="10">&nbsp;</td>
 <td><div class="select-title">{lng[Category icon]}</div>
	<?php
	if ($category_icon) {
		if ($category_icon['y'] > 100) echo '<a href="'.$current_location.'/photos/category/'.$category['categoryid'].'/'.$category_icon['iconid'].'/'.$category_icon['file'].'" target="_blank">';
		$image = $category_icon;
		$image['new_width'] = 500;
		$image['new_height'] = 100;
		include SITE_ROOT . '/includes/icon.php';
		if ($category_icon['y'] > 100) echo '</a>';
		echo '<br />';
?>
<label><input type="checkbox" name="delete_icon" value="1" /> {lng[Delete icon]}</label><br /><br />
<?php
	}
	?>
	<input type="file" name="icon" />
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
		CKEDITOR.on( 'instanceReady', function( ev ) {
			editor = ev.editor;
		    $('*').removeAttr("title");
		});
	</script>
		<textarea class="ckeditor" id="ck_editor" cols="65" rows="7" name="description"><?php echo $category['description']; ?></textarea>
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Title tag]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<input type="text" size="65" name="meta_title" value="<?php echo escape($category['meta_title']); ?>" />
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Meta keywords]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<textarea cols="65" rows="4" name="meta_keywords"><?php echo $category['meta_keywords']; ?></textarea>
	</td>
</tr>

<tr>
	<td height="10" class="FormButton" nowrap="nowrap">{lng[Meta description]}</td>
	<td width="10" height="10"></td>
	<td height="10">
		<textarea cols="65" rows="4" name="meta_description"><?php echo $category['meta_description']; ?></textarea>
	</td>
</tr>

</table>
<br /><br /><br /><br />
<div class="fixed_save_button">
<button class="button-margin-left" type="button" onclick="javascript: submit_category();">{lng[Save]}</button>
<?php
if ($category && $new_category != 'Y') {
?>
&nbsp; <b>{lng[Category location]}:</b>

<?php
echo $categories_tree;
?>

<button type="button" onclick="javascript: submitForm(this, 'move');">{lng[Update]}</button>
<?php
}
?>
</div>
<?php
}
?>
</form>