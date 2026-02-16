<?php
if (!isset($_GET['direction']) || $_GET['direction'])
	$direction = "&direction=0";
else
	$direction = "&direction=1";
?>
<h3>{lng[Products]}</h3>
<form method="POST" name="psform" href="/admin/products">
<input type="hidden" name="mode" value="search" />
<table class="search_table">
<tr>
 <td class="data-name">{lng[Search by substring]}:</td>
 <td><input type="text" name="substring" value="<?php echo escape($search_products['substring'], 2); ?>" size="40" />
&nbsp;
<button>{lng[Search]}</button>
 </td>
</tr>
<tr>
 <td colspan="2"><a href="javascript: void(0);" onclick="$(this).hide(); $('#advanced_search').show();">{lng[Advanced search]}</a></td>
</tr>
</table>

<table id="advanced_search" class="search_table">
<tr>
 <td class="data-name top">{lng[Search in category]}:</td>
 <td><?php echo $categories_tree; ?><br />
{lng[as]} <label><input type="checkbox" name="main_category" value="1"<?php if (!$search_products || $search_products['main_category']) echo 'checked="checked"'; ?> /> {lng[Main category]}</label>
&nbsp; <label><input type="checkbox" name="additional_category" value="1"<?php if ($search_products['additional_category']) echo ' checked="checked"'; ?> /> {lng[Additional category]}</label><br />
<label><input type="checkbox" name="in_subcategories" value="1"<?php if ($search_products['in_subcategories']) echo ' checked="checked"'; ?> /> {lng[also search in subcategories]}</label><br />
 </td>
</tr>
<?php
if ($brands) {
?>
<tr>
 <td class="data-name">{lng[Search by brand]}:</td>
 <td><select name="brandid">
 <option value=""></option>
<?php
	foreach ($brands as $b) {
		echo '<option value="'.$b['brandid'].'"'.($search_products['brandid'] == $b['brandid'] ?  ' selected="selected"' : '').'>'.$b['name'].'</option>';
	}
?>

 </select></td>
</tr>
<?php
}
?>
<tr>
 <td class="data-name">{lng[SKU]}:</td>
 <td><input type="text" name="sku" value="<?php echo escape($search_products['sku'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[Product #ID]}:</td>
 <td><input type="text" name="productid" value="<?php echo escape($search_products['productid'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[Price]}:</td>
 <td><input type="text" size="8" name="price_min" value="<?php echo escape($search_products['price_min'], 2); ?>" /> - <input type="text" size="8" name="price_max" value="<?php echo escape($search_products['price_max'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[List price]}:</td>
 <td><input type="text" size="8" name="list_price_min" value="<?php echo escape($search_products['list_price_min'], 2); ?>" /> - <input type="text" size="8" name="list_price_max" value="<?php echo escape($search_products['list_price_max'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[Quantity]}:</td>
 <td><input type="text" size="8" name="avail_min" value="<?php echo escape($search_products['avail_min'], 2); ?>" /> - <input type="text" size="8" name="avail_max" value="<?php echo escape($search_products['avail_max'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[Weight (lbs)]}:</td>
 <td><input type="text" size="8" name="weight_min" value="<?php echo escape($search_products['weight_min'], 2); ?>" /> - <input type="text" size="8" name="weight_max" value="<?php echo escape($search_products['weight_max'], 2); ?>" /></td>
</tr>
<tr>
 <td class="data-name">{lng[Availability]}:</td>
 <td><select name="status">
 <option value=""></option>
 <option value="1"<?php if ($search_products['status'] == 1) echo ' selected="selected"'; ?>>{lng[Available for sale]}</option>
 <option value="2"<?php if ($search_products['status'] == 2) echo ' selected="selected"'; ?>>{lng[Not available]}</option>
 <option value="3"<?php if ($search_products['status'] == 3) echo ' selected="selected"'; ?>>{lng[Hidden, but available for sale]}</option>
 </select></td>
</tr>
<tr>
 <td></td>
 <td><br /><button>{lng[Search]}</button></td>
</tr>
</table>
<br /><br />
<?php
if (empty($products)) {
?>
{lng[No products found]}
<?php
} else {
?>

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
}
?>
<br /><a href="javascript: void(0);" onclick="javascript: check_all(document.psform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.psform, 'to_delete', false);">{lng[Uncheck all]}</a>

<table width="100%" class="lines-table">
<tr>
 <th width="10"></th>
 <th><a href="/admin/products?sort=sku<?php echo $direction; ?>">{lng[SKU]}</a></th>
 <th width="100%"><a href="/admin/products?sort=name&direction={if $_GET['direction']}0{else}1{/if}">{lng[Product name]}</a></th>
 <th><a href="/admin/products?sort=price<?php echo $direction; ?>">{lng[Price]}</a></th>
 <th><a href="/admin/products?sort=avail<?php echo $direction; ?>">{lng[In stock]}</a></th>
 <th>{lng[Status]}</th>
</tr>
<?php
	foreach ($products as $v) {		echo '
<tr>
 <td><input type="checkbox" pid="'.$v['productid'].'" name="to_delete['.$v['productid'].']" value="1" /></td>
 <td nowarp>'.$v['sku'].'</td>
 <td><a href="/admin/products/'.$v['productid'].'">'.$v['name'].'</a></td>
 <td><input type="text" size="10" name="posted_data['.$v['productid'].'][price]" value="'.$v['price'].'"></td>
 <td><input type="text" size="10" name="posted_data['.$v['productid'].'][avail]" value="'.$v['avail'].'"></td>
 <td>
<select name="posted_data['.$v['productid'].'][status]">
 <option value="1">{lng[Available for sale]}</option>
 <option value="2"'.($v['status'] == 2 ? ' selected' : '').'>{lng[Not available]}</option>
 <option value="3"'.($v['status'] == 3 ? ' selected' : '').'>{lng[Hidden, but available for sale]}</option>
</select>
 </td>
</tr>
		';	}
?>
</table>
<br />

<div class="fixed_save_button">
<button type="button" onclick="submitForm(this, 'update');">{lng[Save]}</button>
&nbsp; <button type="button" onclick="export_selected();">{lng[Export selected]}</button>
&nbsp;
<button type="button" onclick="if (confirm('Are you sure?', $(this)) || confirmed) submitForm(this, 'delete_products');">{lng[Delete selected]}</button>
</div>

<?php
}
?>
</form>

<script>
function export_selected() {
	var selected = '';
	$('.lines-table input').each(function() {
		if ($(this).is(':checked'))
			selected += $(this).attr('pid')+',';
	});

	if (selected)
		self.location='/admin/export?product='+selected;
	else
		alert('You not selected any products');
}
</script>