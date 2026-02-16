<form method="POST" name="prform">
<input type="hidden" name="section" value="related">
<input type="hidden" name="mode" value="update">
<?php
if ($related_products) {
?>
<a href="javascript: void(0);" onclick="javascript: check_all(document.prform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.prform, 'to_delete', false);">{lng[Uncheck all]}</a>
<?php
}
?>
<table width="500" class="lines-table">
<?php
if ($related_products) {
?>
<tr>
 <th width="10">&nbsp;</th>
 <th width="100%">{lng[Product]}</th>
 <th>{lng[Pos]}</th>
</tr>
<?php
foreach ($related_products as $v) {
	echo '
<tr>
 <td><input type="checkbox" name="to_delete['.$v['productid'].']"></td>
 <td><a href="'.$current_location.'/admin/products/'.$v['productid'].'" target="_blank">'.$v['name'].'</a></td>
 <td><input size="5" type="text" name="posted_data['.$v['productid'].'][orderby]" value="'.$v['orderby'].'"></td>
</tr>
	';
}
?>
<tr>
 <td colspan="3"><br><button type="submit">{lng[Update]}</button> <button type="button" onclick="submitForm(this, 'delete');">{lng[Delete selected]}</button></td>
</tr>
<?php
}
?>
<tr>
 <td colspan="3"><br /><h3>{lng[Add new]}</h3></td>
</tr>
<tr>
 <td colspan="3">
    <input type="hidden" name="newproductid" />
    <input type="text" size="35" name="newproduct" disabled="disabled" />
<script>
var popup_product_pid = document.prform.newproductid,
	popup_product_pname = document.prform.newproduct;
</script>
    <button type="button" onclick="javascript: popup_product();">{lng[Browse...]}</button>
 </td>
</tr>
<tr>
 <td colspan="3"><br><button>{lng[Add]}</button></td>
</tr>
</table>
</form>