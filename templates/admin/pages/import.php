<form method="post" accept-charset="utf-8" class="list-form noajax" name="exportForm" enctype="multipart/form-data">
<br />
<table width="800" class="import" cellspacing="1" cellpadding="2">
<tr>
 <td>Select import file</th>
 <td><input type="file" name="file" size="40" /></td>
</tr>
<tr>
 <td>Delimiter</th>
 <td>
<select name="delimiter">
  <option value=",">Comma</option>
  <option value=";">Semicolon</option>
  <option value="tab">Tab</option>
</select>
 </td>
</tr>
<tr>
 <td></th>
 <td><button type="submit">Import</button></td>
</tr>
</table>

</form>

<br /><br />

<b>To make import file, first export and see how it should look.</b>

<br /><br />

<font class="star">*</font> You can use URLs as any image path in the import file. It will be downloaded and added to your collection
<br />
<br />
<font class="star">*</font><font class="star">*</font> Existing images, or if you just wish, should be uploaded manually as into the "/var/import_export/" and separate folders. Folders names are:<br /><br />
<table>
<tr>
 <td width="150">categories_banners</td>
 <td>Categories banners</td>
</tr>
<tr>
 <td>products_images</td>
 <td>Products images</td>
</tr>
<tr>
 <td>variants_images</td>
 <td>Variants images</td>
</tr>
</table>
<br />
File name must be as in import file.
<br /><br />
Categories banners, products images, variants images - if you import them for specific category/product/variant, old images removed and replaced by new.
<br />
<br />
<br />
<font class="star">*</font><font class="star">*</font><font class="star">*</font> You can use SKU instead of !PRODUCT_ID in the following tables:<br />
<ul>
 <li>Products images</li>
 <li>Products options</li>
 <li>Variants</li>
 <li>Wholesale prices</li>
</ul>


<font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font> You can also use SKU instead of !VARIANT_ID

<br /><br />

<font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font> Import support importing new items. Just insert new ID's. Categories does not support to add them.

<br /><br />

<font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font><font class="star">*</font> Import headers are must be. Import fields header is not recognized yet so import rows must include all fields.
