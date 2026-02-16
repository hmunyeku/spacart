<form method="POST" name="vform" enctype='multipart/form-data' class="noajax">
<input type="hidden" name="section" value="variant_images">
<input type="hidden" name="mode" value="update">

<?php
if ($has_images == 'Y') {?>
<a href="javascript: void(0);" onclick="javascript: check_all(document.vform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.vform, 'to_delete', false);">{lng[Uncheck all]}</a>
<table>
<?php
	foreach ($variants as $v) {
		if ($v['images']) {?>
<tr>
 <td valign="top">
<table>
<?php
			foreach ($v['options'] as $o) {
?>
 <tr>
  <td nowrap><b><?php echo $o['group_name']; ?>:</b></td>
  <td nowrap><?php echo $o['name']; ?></td>
 </tr>
<?php
			}
?>
</table>
 </td>
 <td> &nbsp;</td>
 <td class="variant_images">
<?php
			foreach ($v['images'] as $img) {?>
<div>
<a href="<?php echo $current_location . '/photos/variant/' . $img['variantid'] . '/' . $img['imageid'] . '/' . $img['file']; ?>" target="_blank"><img src="<?php echo $current_location . '/photos/variant/' . $img['variantid'] . '/' . $img['imageid'] . '/' . $img['file']; ?>" height="70" alt="" /></a>
<input type="checkbox" name="to_delete[<?php echo $img['imageid']; ?>]" />
<br />
{lng[Alt]}:<br />
<input type="text" name="posted_data[<?php echo $img['imageid']; ?>][alt]" value="<?php echo escape($img['alt']); ?>" /><br />
{lng[Pos]}:<br />
<input type="text" size="5" name="posted_data[<?php echo $img['imageid']; ?>][pos]" value="<?php echo $img['pos']; ?>" />
</div>
<?php
			}
?>
 </td>
</tr>
<tr>
 <td colspan="3">&nbsp;</td>
</tr>
<?php
		}
	}
?>
<tr>
 <td colspan="3"><button>{lng[Update]}</button> <button type="button" onclick="submitForm(this, 'delete');">{lng[Delete selected]}</button><br /><br /></td>
</tr>
</table>
<?php
}
?>

<div class="addnew">
<h3>{lng[Add image]}</h3>
<table>
<?php
foreach ($option_groups as $v) {
?>

<tr>
 <td width="150" valign="top"><?php echo $v['name']; ?></td>
 <td valign="top">
 <select name="new_group[<?php echo $v['groupid']; ?>][]" multiple size="10">
<?php
if ($v['options']) {	foreach ($v['options'] as $o) {
?>
 <option value="<?php echo $o['optionid']; ?>"><?php echo $o['name']; ?></option>
<?php
	}
}
?>
 </select>
 </td>
</tr>
<?php
}
?>
<tr>
 <td colspan="2">
<br />
<input type="file" name="userfile[0]" /><br />
<input type="file" name="userfile[1]" /><br />
<input type="file" name="userfile[2]" /><br />
<input type="file" name="userfile[3]" /><br />
<input type="file" name="userfile[4]" /><br /><br />
<button type="button" onclick="javascript: submitForm(this, 'upload');">{lng[Upload images]}</button>
 </td>
</tr>
</table>
</div>
</form>