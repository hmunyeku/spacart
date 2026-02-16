<form method="POST" name="poform">
<input type="hidden" name="section" value="options">
<input type="hidden" name="mode" value="update">
  <table cellspacing="1" cellpadding="3" width="400" class="normal-table">

    <tr>
      <td>{lng[Name]}</td>
      <td class="star">*</td>
      <td><input type="text" name="posted_data[name]" value="<?php echo $option_group['name']; ?>" />
    </tr>

    <tr>
      <td>{lng[Text]}</td>
      <td></td>
      <td><input type="text" size="40" name="posted_data[fullname]" value="<?php echo $option_group['fullname']; ?>" />
    </tr>

    <tr>
      <td>{lng[Type]}</td>
      <td></td>
      <td>
      <select name="posted_data[type]" id="data_type">
        <option value="g">{lng[Options group]}</option>
		<option value="t"<?php if ($option_group['type'] == 't') echo ' selected'; ?>>{lng[Text option]}</option>
      </select>
      </td>
    </tr>

    <tr>
      <td nowrap>{lng[Display type]}</td>
      <td></td>
      <td>
      <select name="posted_data[view_type]" id="data_view_type">
<?php
if ($option_group['type'] == 'g' || !$option_group) {
?>
        <option value="s">{lng[Select box]}</option>
        <option value="p"<?php if ($option_group['view_type'] == 'p') echo ' selected'; ?>>{lng[Squares]}</option>
{*
        <option value="r"<?php if ($option_group['view_type'] == 'r') echo ' selected'; ?>>{lng[Radio buttons list]}</option>-->
*}
<?php
} else {
?>
        <option value="t">{lng[Text area]}</option>
        <option value="i"<?php if ($option_group['view_type'] == 'i') echo ' selected'; ?>>{lng[Input box]}</option>
<?php
}
?>
      </select>
      </td>
    </tr>

    <tr>
      <td>{lng[Position]}</td>
      <td></td>
      <td><input size="5" type="text" name="posted_data[orderby]" value="<?php echo $option_group['orderby']; ?>" /></td>
    </tr>

    <tr>
      <td>{lng[Variant]}</td>
      <td></td>
      <td><input type="checkbox" name="posted_data[variant]" value="1"<?php if ($option_group['variant']) echo ' checked'; ?> /></td>
    </tr>

    <tr>
      <td>{lng[Enabled]}</td>
      <td></td>
      <td><input type="checkbox" name="posted_data[enabled]" value="1"<?php if (!$option_group || $option_group['enabled']) echo ' checked'; ?> /></td>
    </tr>

  </table>
<br />
    <h3>{lng[Group options]}</h3>
<?php
if ($options) {
?>
<a href="javascript: void(0);" onclick="javascript: check_all(document.poform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.poform, 'to_delete', false);">{lng[Uncheck all]}</a>
<?php
}
?>
<table cellspacing="1" width="100%">
<tr>
 <th width="10"></th>
 <th width="100%">{lng[Name]}</th>
 <th colspan="2">{lng[Price modifier]}</th>
 <th colspan="2">{lng[Weight modifier]}</th>
 <th>{lng[Pos]}</th>
 <th>{lng[Enabled]}</th>
</tr>
<?php
if ($options) {	foreach ($options as $k=>$v) {		echo '<tr>
 <td><input type="checkbox" name="to_delete['.$v['optionid'].']" /></td>
 <td><input type="text" class="width-95p" name="options_data['.$v['optionid'].'][name]" value="'.escape($v['name']).'" /></td>
 <td><input size="5" type="text" name="options_data['.$v['optionid'].'][price_modifier]" value="'.escape($v['price_modifier']).'" /></td>
 <td>
  <select name="options_data['.$v['optionid'].'][price_modifier_type]">
   <option value="%">{lng[Percent]}</option>
   <option value="$"'.($v['price_modifier_type'] == '$' ? ' selected="selected"' : '').'>{lng[Absolute]}</option>
  </select>
 </td>
 <td><input size="5" type="text" name="options_data['.$v['optionid'].'][weight_modifier]" value="'.escape($v['weight_modifier']).'" /></td>
 <td>
  <select name="options_data['.$v['optionid'].'][weight_modifier_type]">
   <option value="%">{lng[Percent]}</option>
   <option value="$"'.($v['weight_modifier_type'] == '$' ? ' selected="selected"' : '').'>{lng[Absolute]}</option>
  </select>
 </td>
 <td><input size="5" type="text" name="options_data['.$v['optionid'].'][orderby]" value="'.$v['orderby'].'" /></td>
 <td align="center"><input type="checkbox" name="options_data['.$v['optionid'].'][enabled]" value="1" '.($v['enabled'] ? ' checked' : '').'></td>
</tr>';
	}
}
?>
<tr id="add_new">
 <td><a href="javascript: void(0);" onclick="duplicate_row($('#add_new'), $(this));" class="duplicate_plus">+</a></td>
 <td><input type="text" class="width-95p" name="new_option[0][name]" /></td>
 <td><input size="5" type="text" name="new_option[0][price_modifier]" /></td>
 <td>
  <select name="new_option[0][price_modifier_type]">
   <option value="%">{lng[Percent]}</option>
   <option value="$">{lng[Absolute]}</option>
  </select>
 </td>
 <td><input size="5" type="text" name="new_option[0][weight_modifier]" /></td>
 <td>
  <select name="new_option[0][weight_modifier_type]">
   <option value="%">{lng[Percent]}</option>
   <option value="$">{lng[Absolute]}</option>
  </select>
 </td>
 <td><input size="5" type="text" name="new_option[0][orderby]" /></td>
 <td align="center"><input type="checkbox" name="new_option[0][enabled]" checked value="1" /></td>
</tr>
</table>
<br />
<?php
if ($option_group) {
?>
<button type="submit">{lng[Update]}</button> <button type="button" onclick="submitForm(this, 'delete');">{lng[Delete selected]}</button>
<?php
} else {
?>
<button type="submit">{lng[Add option group]}</button>
<?php
}
?>
</form>