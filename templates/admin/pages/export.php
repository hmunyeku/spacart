<form action="/admin/export{if $_GET['product']}?product={$_GET['product']}{/if}" method="post" accept-charset="utf-8" class="list-form noajax" name="exportForm">

{if $_GET['product']}
<input type="hidden" name="export_product" value="{$_GET['product']}" />
<br />
Export product #{$_GET['product']}. <a href="/admin/export">Reset</a>
<hr />
{/if}

<br />
<a href="/admin/export/download" target="_blank">Download last generated file</a>

<br />
<br />
<a href="javascript: void(0);" onclick="javascript: import_checkboxes('Y');">Check all</a> / <a href="javascript: void(0);" onclick="javascript: import_checkboxes();">Uncheck all</a>

<table width="400" class="export" cellspacing="1" cellpadding="2">
{if !$_GET['product']}
<tr>
 <th width="15"><input type="checkbox" name="import[categories]" /></th>
 <th colspan="2">Categories</th>
</tr>
<tr>
 <td colspan="3" class="sep"></td>
</tr>
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[cat_banners]" /></td>
    <td>Banners</td>
   </tr>
  </table>
 </td>
</tr>

<tr>
 <th width="15"><input type="checkbox" name="import[brands]" /></th>
 <th colspan="2">Brands</th>
</tr>
{/if}
<tr>
 <td colspan="3" class="sep"></td>
</tr>

{if $warehouse_enabled}
<tr>
 <th width="15"><input type="checkbox" name="import[warehouses]" /></th>
 <th colspan="2">Warehouses</th>
</tr>
<tr>
 <td colspan="3" class="sep"></td>
</tr>
{/if}

<tr>
 <th width="15"><input type="checkbox" name="import[products]" /></th>
 <th colspan="2" width="100%">Products</th>
</tr>
<tr>
 <td colspan="3" class="sep"></td>
</tr>

{if $warehouse_enabled}
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[inventory]" /></td>
    <td>Warehouses inventory</td>
   </tr>
  </table>
 </td>
</tr>
{/if}
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[images]" /></td>
    <td>Images</td>
   </tr>
  </table>
 </td>
</tr>
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[options]" /></td>
    <td>Products Options</td>
   </tr>
  </table>
 </td>
</tr>
<tr>
 <td></td>
 <td class="third" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[variants]" /></td>
    <td>Variants</td>
   </tr>
  </table>
 </td>
</tr>
<tr>
 <td></td>
 <td class="third" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[variant_images]" /></td>
    <td>Variants images</td>
   </tr>
  </table>
 </td>
</tr>
{if $warehouse_enabled}
<tr>
 <td></td>
 <td class="third" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[variant_inventory]" /></td>
    <td>Variants warehouses inventory</td>
   </tr>
  </table>
 </td>
</tr>
{/if}
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[wholesale]" /></td>
    <td>Wholesale pricing</td>
   </tr>
  </table>
 </td>
</tr>
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[related]" /></td>
    <td>Related items</td>
   </tr>
  </table>
 </td>
</tr>
{*
<tr>
 <td></td>
 <td class="second" colspan="2">
  <table>
   <tr>
    <td><input type="checkbox" name="import[reviews]" /></td>
    <td>Customer reviews</td>
   </tr>
  </table>
 </td>
</tr>
*}
<tr>
 <td></td>
 <td><br /><button type="submit">Export</button></td>
</tr>
</table>

</form>

