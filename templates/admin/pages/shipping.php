<h1>{lng[Shipping methods]}</h1>

<?php
if ($config['Shipping']['enable_shipping'] != 'Y') {
?>

<br />

<b>{lng[Shipping is disabled. You can manage it <a href="configuration/Shipping">here</a>.]}</b>

<br />

<?php
} else {
?>

<form method="post" name="shippingmethodsform">
<input type="hidden" name="carrier" value="<?php echo escape($carrier, 2); ?>" />

<script type="text/javascript">//<![CDATA[
var expands = new Array(<?php foreach ($carriers as $v) echo "'".$v['code']."',"; ?>'');
function expand_all(flag) {
  var x;
  for (x = 0; x < expands.length; x++) {
    if (expands[x].length == 0)
      continue;

    if (!flag)
      $("#box"+expands[x]).hide();
    else
      $("#box"+expands[x]).show();
  }
}
//]]></script>

<table cellpadding="2" cellspacing="1" width="700">

<tr class="TableHead">
  <th>{lng[Shipping method]}</th>
  <th>{lng[Delivery time]}</th>
  <th>{lng[Destination]}</th>
  <th>{lng[Pos]}</th>
  <th>{lng[Active]}</th>
  <th></th>
</tr>
<?php
foreach ($shipping as $s) {
	if ($s['code'] == "") {
		echo '
<tr>
  <td><input type="text" name="data['.$s['shippingid'].'][shipping]" size="27" value="'.escape($s['shipping'], 2).'" /></td>
  <td align="center"><input type="text" name="data['.$s['shippingid'].'][shipping_time]" size="8" value="'.escape($s['shipping_time'], 2).'" /></td>
  <td align="center"><select name="data['.$s['shippingid'].'][destination]">
    <option value="I"'.($s['destination'] == 'I' ? ' selected="selected"' : '').'>{lng[International]}</option>
    <option value="N"'.($s['destination'] == 'N' ? ' selected="selected"' : '').'>{lng[National]}</option>
  </select></td>
  <td align="center"><input type="text" name="data['.$s['shippingid'].'][orderby]" size="4" value="'.$s['orderby'].'" /></td>
  <td nowrap="nowrap" align="center"><input type="checkbox" name="data['.$s['shippingid'].'][active]" value="Y"'.($s['active'] == 'Y' ? ' checked="checked"' : '').' /></td>
  <td><button type="button" onclick="self.location=\''.$current_location.'/admin/shipping/?mode=delete&amp;shippingid='.$s['shippingid'].'\'">{lng[Delete]}</button></td>
</tr>';
	}
}
?>

<tr>
  <td colspan="7"><br /><h3>{lng[Add shipping method]}</td>
</tr>

<tr>
  <td><input type="text" name="add[shipping]" size="27" /></td>
  <td align="center"><input type="text" name="add[shipping_time]" size="10" /></td>
  <td align="center"><select name="add[destination]">
    <option value="I">{lng[International]}</option>
    <option value="N">{lng[National]}</option>
  </select></td>

  <td align="center"><input type="text" name="add[orderby]" size="4" value="0" /></td>
  <td align="center"><input type="checkbox" name="add[active]" value="Y" checked="checked" /></td>
  <td></td>
</tr>

<tr>
  <td colspan="7">&nbsp;</td>
</tr>

<tr>
  <td colspan="7">
<div class="fixed_save_button">
    <button type="submit">{lng[Apply changes]}</button>
</div>
  </td>
</tr>


</table>
</form>

<br /><br />

<?php
}
?>
