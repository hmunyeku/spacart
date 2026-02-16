<?php
echo $product_tabs;
?>
<div class="clear"></div>

<?php
if (empty($get['3'])) {
?>
{include="admin/pages/product_details.php"}
<?php
} elseif ($get['3'] == 'images') {
?>
{include="admin/pages/product_images.php"}
{elseif $get['3'] == 'inventory'}
{include="admin/pages/product_inventory.php"}
<?php
} elseif ($get['3'] == 'related') {
?>
{include="admin/pages/product_related.php"}
<?php
} elseif ($get['3'] == 'options') {
	if ($get['4']) {
?>
{include="admin/pages/product_options_group.php"}
<?php
	} else {?>
{include="admin/pages/product_options.php"}
<?php	}
} elseif ($get['3'] == 'variants') {
?>
{include="admin/pages/product_variants.php"}
<?php
} elseif ($get['3'] == 'variant_images') {
?>
{include="admin/pages/product_variant_images.php"}
<?php
} elseif ($get['3'] == 'wholesale') {
?>
{include="admin/pages/product_wholesale.php"}
<?php
}
?>
