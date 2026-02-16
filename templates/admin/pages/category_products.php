<?php echo $category_location; ?>

<br /><br />

<a href="/admin/category/<?php echo $category['categoryid']; ?>">{lng[Modify category]}</a>

<br /><br />

<form action="/admin/category/<?php echo $category['categoryid']; ?>" method="post">
<input type="hidden" name="mode" value="category_products" />

<?php
if (!isset($_GET['direction']) || $_GET['direction'])
	$direction = "&direction=0";
else
	$direction = "&direction=1";
?>

<h3>{lng[Category products]}</h3>
<?php
if ($products) {
?>

<table width="100%" class="lines-table">
<tr>
 <th>{lng[SKU]}</th>
 <th>{lng[Product name]}</th>
 <th>{lng[Position]}</th>
</tr>
<?php
	foreach ($products as $v) {
		echo '
<tr>
 <td nowrap><a href="/admin/products/'.$v['productid'].'">'.$v['sku'].'</a></td>
 <td><a href="/admin/products/'.$v['productid'].'">'.$v['name'].'</a></td>
 <td><input type="text" size="10" name="posted_data['.$v['productid'].'][orderby]" value="'.$v['orderby'].'"></td>
</tr>
		';
	}
?>
</table>
<br />
<button>{lng[Save]}</button>
</form>
<?php
} else {?>
<center>{lng[No products in this category]}</center>
<?php}
?>
