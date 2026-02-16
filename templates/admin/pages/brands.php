<?php
if ($brands) {
?>
<a href="{$current_location}/admin/brands/new">{lng[Add new]}</a>
<br /><br />
<form method="post" name="brandsform">
<input type="hidden" name="mode" value="update" />

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<?php
	echo '<br />';
}
?>

<a href="javascript: void(0);" onclick="javascript: check_all(document.brandsform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.brandsform, 'to_delete', false);">{lng[Uncheck all]}</a>

<table cellpadding="3" cellspacing="1" width="600" class="lines-table">
<tr>
	<th width="10">&nbsp;</th>
	<th width="60%">{lng[Name]}</th>
	<th width="20%">{lng[Active]}</th>
	<th width="20%">{lng[Pos]}</th>
</tr>

<?php
foreach ($brands as $b) {
	echo '<tr>
	<td><input type="checkbox" name="to_delete['.$b['brandid'].']" value="Y" /></td>
	<td><a href="'.$current_location.'/admin/brands/'.$b['brandid'].'">'.$b['name'].'</a></td>
	<td align="center"><input type="checkbox" name="to_update['.$b['brandid'].'][active]" value="Y"'.($b['active'] == 'Y' ? ' checked="checked"' : '').' /></td>
	<td align="center"><input type="text" size="5" name="to_update['.$b['brandid'].'][orderby]" value="'.$b['orderby'].'" /></td>
</tr>';
}
?>
</table>
<div class="fixed_save_button">
<button type="button" onclick="javascript: submitForm(this, 'update');">{lng[Update]}</button> &nbsp;
<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
</div>
</form>

<?php
} else {
?>
<script src="{$current_location}/ckeditor/ckeditor.js"></script>
<form method="post" name="brandform" enctype="multipart/form-data">

{if ($get['2'] == 'new')}
<h3>{lng[New brand|escape]}</h3>
{else}
<h3>{$brand['title']}</h3>
{/if}

<table cellpadding="3" cellspacing="1" width="90%" class="normal-table">
<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Image]}</div>
<?php
if ($brand['image']) {
	$image = $brand['image'];
	$image['new_width'] = 400;
	$image['new_height'] = 100;
	$image['link'] = 'Y';
	$image['blank'] = 'Y';
	include SITE_ROOT . '/includes/brand_image.php';
?>
<br />
 <a href="<?php echo $current_location.'/admin/brands/'.$brand['brandid'].'/?mode=delete_image'; ?>">{lng[Delete image]}</a><br />
<?php
}
?>
<input type="file" name="userfile" />
 </td>
</tr>

<tr>
 <td><b>{lng[Name]}</b></td>
 <td><input type="text" name="name" size="40" value="<?php echo escape($brand['name'], 2); ?>" onchange="javascript: if (this.form.cleanurl.value == '') copy_clean_url(this, this.form.cleanurl);" /></td>
</tr>

<tr>
 <td><b>{lng[Clean URL]}</b></td>
 <td><input type="text" name="cleanurl" size="40" value="<?php echo escape($brand['cleanurl'], 2); ?>" /></td>
</tr>

<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
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
		<textarea class="ckeditor" id="ck_editor" cols="65" rows="7" name="descr"><?php echo $brand['descr']; ?></textarea>
 </td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta title]}</b></td>
 <td><input type="text" size="80" name="meta_title" value="<?php echo $brand['meta_title']; ?>" /></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta keywords]}</b></td>
 <td><textarea cols="80" rows="5" name="meta_keywords"><?php echo $brand['meta_keywords']; ?></textarea></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta description]}</b></td>
 <td><textarea cols="80" rows="5" name="meta_descr"><?php echo $brand['meta_descr']; ?></textarea></td>
</tr>

<tr>
 <td><b>{lng[Active]}</b></td>
 <td><input type="checkbox" name="active" value="Y"<?php echo ((!$brand || $brand['active'] == 'Y') ? ' checked="checked"' : ''); ?> //></td>
</tr>

<tr>
 <td><b>{lng[Position]}</b></td>
 <td><input type="text" size="5" name="orderby" value="<?php echo $brand['orderby']; ?>" /></td>
</tr>

</table>
<div class="fixed_save_button">
<button type="button" onclick="submitBrand()"><?php if ($get['2'] == 'new') { ?>{lng[Add|escape]}<?php } else { ?>{lng[Save|escape]}<?php } ?></button>
</div>
</form>
<?php
}
?>