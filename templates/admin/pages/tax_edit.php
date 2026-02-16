<h1>{lng[Tax details]}</h1>

<form method="post" name="taxdetailsform" onsubmit="javascript: return submitTaxForm();">
<input type="hidden" name="mode" value="details" />
<input type="hidden" name="taxid" value="<?php echo $tax_details['taxid']; ?>" />

<table cellpadding="3" cellspacing="1" width="600" class="normal-table">

<tr>
  <td width="20%">{lng[Name]}:</td>
  <td width="10" class="star">*</td>
  <td width="80%"><input type="text" size="15" maxlength="10" name="tax_service_name" value="<?php echo escape($tax_details['tax_name'], 2); ?>" /></td>
</tr>
{*
<tr>
  <td>{lng[Tax display name]}:</td>
  <td></td>
  <td><input type="text" size="45" name="tax_display_name" value="<?php echo escape($tax_details['tax_display_name'], 2); ?>" /></td>
</tr>

<tr>
  <td>{lng[Tax reg number]}:</td>
  <td></td>
  <td><input type="text" size="32" maxlength="32" name="tax_regnumber" value="<?php echo escape($tax_details['regnumber'], 2); ?>" /></td>
</tr>

<tr>
  <td>{lng[Tax priority]}:</td>
  <td></td>
  <td><input type="text" size="10" name="tax_priority" value="<?php echo escape($tax_details['priority'], 2); ?>" /></td>
</tr>
*}
<tr>
  <td>{lng[Status]}:</td>
  <td></td>
  <td>
  <select name="active">
    <option value="Y">{lng[Enabled]}</option>
    <option value="N"<?php if ($tax_details['active'] == "N") echo ' selected="selected"'; ?>>{lng[Disabled]}</option>
  </select>
  </td>
</tr>
{*
<tr>
  <td>{lng[Tax apply to]}:</td>
  <td class="star">*</td>
  <td>
<table cellpadding="0" cellspacing="0" width="100%">

<tr>
  <td><input type="text" size="25" id="tax_formula" name="tax_formula" value="=<?php echo escape($tax_details['formula'], 2); ?>" readonly="readonly" /></td>
  <td nowrap>
<button type="button" onclick="javacript: undoFormula('tax_formula');" />{lng[Undo]}</button>
<button  type="button" onclick="javacript: undoFormula('tax_formula', 1);" />{lng[Redo]}</button>
<button  type="button" onclick="javacript: addElm('tax_formula', '=', '=');" />{lng[Clear]}</button>
  </td>
</tr>

<tr>
  <td class="tax-padding">
<input type="button" value=" + " onclick="javascript: addElm('tax_formula', '+', 1);" /></button>
<input type="button" value=" - " onclick="javascript: addElm('tax_formula', '-', 1);" /></button>
<input type="button" value=" * " onclick="javascript: addElm('tax_formula', '*', 1);" /></button>
<input type="button" value=" / " onclick="javascript: addElm('tax_formula', '/', 1);" /></button>
  </td>
  <td class="tax-padding" nowrap>
  <select id="unit_tax_formula">
  <option value="">&nbsp;</option>
<?php
foreach ($taxes_units as $key=>$item)
	echo '<option value="'.$key.'">'.$key.($key != $item ? ' ('.$item.')' : '').'</option>';
?>
  </select>&nbsp;
  <input type="button" value="{lng[Add|escape]}" onclick="javascript: if(document.getElementById('unit_tax_formula').value != '') addElm('tax_formula', document.getElementById('unit_tax_formula').value, 2);" /></button>
  </td>
</tr>
*}
{*
<tr>
  <td>
  <input type="text" id="value_tax_formula" />
  <input type="button" size="8" value="{lng[Add|escape]}" onclick="javascript: document.getElementById('value_tax_formula').value = (isNaN(parseFloat(document.getElementById('value_tax_formula').value)) ? '' : Math.abs(parseFloat(document.getElementById('value_tax_formula').value))); if (document.getElementById('value_tax_formula').value != '') addElm('tax_formula', document.getElementById('value_tax_formula').value, 2);" /></button>
  </td>
</tr>
*}
{*
</table>
  </td>
</tr>
*}
{*
<tr>
  <td nowrap="nowrap">{lng[Tax rates depended on]}:</td>
  <td></td>
  <td>
  <select name="address_type">
    <option value="S"<?php echo ($tax_details['address_type'] == "S" ? ' selected="selected"' : ''); ?>>{lng[Shipping address]}</option>
    <option value="B"<?php echo ($tax_details['address_type'] == "B" ? ' selected="selected"' : ''); ?>>{lng[Billing address]}</option>
  </select>
  </td>
</tr>
*}
{*
<tr>
  <td colspan="2"></td>
  <td><label><input type="checkbox" name="price_includes_tax" value="Y"<?php echo ($tax_details['price_includes_tax'] == "Y" ? ' checked="checked"' : ''); ?> /> {lng[Included in product price]}</label></td>
</tr>

<tr>
  <td colspan="2"></td>
  <td><label><input type="checkbox" name="display_including_tax" value="Y" onclick="javascript: document.taxdetailsform.display_info.disabled = !document.taxdetailsform.display_including_tax.checked;"<?php echo ($tax_details['display_including_tax'] == "Y" ? ' checked="checked"' : ''); ?> /> {lng[Display including tax]}</label></td>
</tr>

<tr>
  <td colspan="2"></td>
  <td>{lng[Display also]}:<br />
<label><input type="radio" value=""<?php echo $tax_details['display_info'] == '' ? ' checked="checked"' : ''; ?> name="display_info"<?php echo $tax_details['display_including_tax'] != "Y" ? ' disabled="disabled"': ''; ?>> {lng[Display tax none]}</label>
<label><input type="radio" value="1"<?php echo $tax_details['display_info'] == '1' ? ' checked="checked"' : ''; ?> name="display_info"<?php echo $tax_details['display_including_tax'] != "Y" ? ' disabled="disabled"': ''; ?>> {lng[Display tax rate]}</label>
<label><input type="radio" value="2"<?php echo $tax_details['display_info'] == '2' ? ' checked="checked"' : ''; ?> name="display_info"<?php echo $tax_details['display_including_tax'] != "Y" ? ' disabled="disabled"': ''; ?>> {lng[Display tax cost]}</label>
<label><input type="radio" value="3"<?php echo $tax_details['display_info'] == '3' ? ' checked="checked"' : ''; ?> name="display_info"<?php echo $tax_details['display_including_tax'] != "Y" ? ' disabled="disabled"': ''; ?>> {lng[Display tax rate and cost]}</label>
  </td>
</tr>
*}
<tr>
  <td colspan="2"></td>
  <td><br /></td>
</tr>

</table>
<button class="button-margin-left" type="submit">{lng[Save]}</button>
</form>

<?php
if ($tax_details['taxid'] && $tax_details['taxid'] != 'add') {
?>
<br />
<h3>{lng[Tax rates]}</h3>
<a name="rates"></a>

<a href="javascript: void(0);" onclick="javascript: check_all(document.taxratesform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.taxratesform, 'to_delete', false);">{lng[Uncheck all]}</a>

<form method="post" name="taxratesform">
<input type="hidden" name="mode" value="update_rates" />
<input type="hidden" name="taxid" value="<?php echo $tax_details['taxid']; ?>" />

<table cellpadding="3" cellspacing="1" width="100%" class="lines-table">

<tr>
  <th width="10">&nbsp;</th>
  <th width="30%">{lng[Zone]}</th>
  <th width="20%" align="center">{lng[Membership]}</th>
  <th width="30%" align="center">{lng[Tax rate value]}</th>
  <th width="20%" align="center">{lng[Tax apply to]}</th>
</tr>

<?php
if ($tax_rates) {
	foreach ($tax_rates as $t) {
		echo '<tr>
  <td><input type="checkbox" name="to_delete['.$t['rateid'].']" /></td>
  <td>';
?>
{if $t['zoneid'] == 0}{lng[Default zone|escape]}{else}<a href='{$current_location}/admin/zones/{$t['zoneid']}'>{$t['zone_name']}</a>{/if}
<?php
  echo '</td><td align="center">
<a href="/admin/taxes/'.$tax_details['taxid'].'?rateid='.$t['rateid'].'#rates">
		';
?>
{if ($t['membershipids'])}
{foreach $t['membershipids'] as $m}
{$m}<br />
{/foreach}
{else}
{lng[All|escape]}
{/if}
<?php
		echo '</a>
</td>
  <td align="center" nowrap="nowrap">
<input type="text" size="10" maxlength="13" name="posted_data['.$t['rateid'].'][rate_value]" value="'.$t['rate_value'].'" />
<select name="posted_data['.$t['rateid'].'][rate_type]">
  <option value="%"'.($t['rate_type'] == "%" ? ' selected="selected"' : '').'>%</option>
  <option value="$"'.($t['rate_type'] == "$" ? ' selected="selected"' : '').'>'.$config['General']['currency_symbol'].'</option>
</select>
  </td>
  <td align="center"><a href="/admin/taxes/'.$tax_details['taxid'].'?rateid='.$t['rateid'].'#rates">'.($t['formula'] == "" ? $tax_details['formula'] : $t['formula']).($t['shipping'] ? '+Shipping' : '').'</a></td>
</tr>
		';
	}
?>

<?php
} else {
?>

<tr>
  <td colspan="5" align="center">{lng[No tax rates defined]}</td>
</tr>

<?php
}
?>
</table>
{if $tax_rates}
<br />
  <button type="button" onclick="javascript: submitForm(this, 'delete_rates');">{lng[Delete selected]}</button>
  <button type="submit">{lng[Update]}</button>
{/if}
</form>

<br />
<a name="rates"></a>

<form method="post" name="tax_rate_edit" onsubmit="javascript: return submitTaxForm('rate');">
<input type="hidden" name="mode" value="rate_details" />
<input type="hidden" name="taxid" value="<?php echo $tax_details['taxid']; ?>" />
<input type="hidden" name="rateid" value="<?php echo $rate_details['rateid'] ? $rate_details['rateid'] : 0; ?>" />
<?php
if ($rate_details) {
?>
<h3>Edit rate</h3>
<?php
} else {
?>
<h3>Add rate</h3>
<?php
}
?>

<br />

<table cellpadding="3" cellspacing="1" width="600" class="normal-table">

<tr>
  <td width="15%" class="display-none">{lng[Tax rate value]}:</td>
  <td class="star">*</td>
  <td width="85%">
  <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
    <input class="mdl-textfield__input custom-element" type="text" size="20" maxlength="13" name="rate_value" value="<?php echo $rate_details['rate_value']; ?>" id="sample3">
    <label class="mdl-textfield__label" for="sample3">{lng[Tax rate value]}</label>
  </div>

  <select name="rate_type" onchange="javascript: $('#box2').toggle();" class="custom-element">
    <option value="%">%</option>
    <option value="$"<?php if ($rate_details['rate_type'] == '$') echo ' selected="selected"'; ?>><?php echo $config['General']['currency_symbol']; ?></option>
  </select>
  <br /><div id="box2"<?php if ($rate_details['rate_type'] != '$') echo ' class="display-none"'; ?>>An absolute tax value is added to each item in the cart, not to the cart subtotal</div>
  </td>
</tr>

<tr>
  <td>{lng[Zone]}:</td>
  <td></td>
  <td>
  <select name="zoneid">
    <option value="0">{lng[Default zone]}</option>
<?php
foreach ($zones as $z)
	echo '<option value="'.$z['zoneid'].'"'.($rate_details['zoneid'] == $z['zoneid'] ? ' selected="selected"' : '').'>'.$z['zone_name'].'</option>';
?>
  </select>
  </td>
</tr>

<tr>
  <td>{lng[Membership]}:</td>
  <td></td>
 <td><select name="membershipids[]" multiple="multiple" size="5">
  <option value="-1">{lng[All]}</option>
<?php
		if ($memberships)
			foreach ($memberships as $m) {
				echo '<option value="'.$m['membershipid'].'"'.(!empty($rate_details['membershipids']) && $rate_details['membershipids'][$m['membershipid']] != '' ? ' selected="selected"' : '').'>'.$m['membership'].'</option>';
			}
?>
</select>
  </td>
</tr>

<tr>
  <td>{lng[Apply tax to]}:</td>
  <td class="star">&nbsp;</td>
  <td>
  <select name="formula">
	<option value="DST"{if $rate_details['formula'] == "DST"} selected{/if}>Discounted subtotal</option>
	<option value="ST"{if $rate_details['formula'] == "ST"} selected{/if}>Subtotal</option>
  </select>
<br /><br />
<label>
<input type="checkbox" class="custom-element" name="shipping" value="1"{if $rate_details['shipping']} checked{/if}> {lng[Apply to shipping]}
</label>
  </td>
</tr>

<tr>
  <td colspan="2"></td>
  <td>
<br />
  </td>
</tr>

</table>

<button class="button-margin-left" type="submit"><?php if ($rate_details['rateid']) {?>{lng[Update]}<?php } else { ?>{lng[Add]}<?php } ?></button>
<?php
if ($rate_details['rateid']) {
?>
<button type="button" onclick="javascript: self.location='/admin/taxes/<?php echo $rate_details['taxid']; ?>';">{lng[Cancel]}</button>
<?php
}
?>
</form>



<?php
}
?>