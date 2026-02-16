<?php
if ($type == "D") {
?>
<h1>{lng[Shipping charges]}</h1>
<?php
} else {
?>
<h1>{lng[Shipping markups]}</h1>
<?php
}
?>

<form method="get" name="zoneform">

<input type="hidden" name="type" value="{$type}" />

<b>{lng[Edit rates for]}</b><br />

<select name="shippingid" onchange="document.zoneform.submit()">
  <option value="">{lng[All methods]}</option>
{foreach $shipping as $s}
<option value="{$s['shippingid']}"{if $_GET['shippingid'] != "" && $_GET['shippingid'] == $s['shippingid']} selected="selected"{/if}'>{php echo func_trademark($s['shipping'])} ({if $s['destination'] == "I"}{lng[International|escape]}{else}{lng[National|escape]}{/if}))</option>';
{/foreach}
</select>

<select name="zoneid" onchange="document.zoneform.submit()">
  <option value="">{lng[All zones]}</option>
<?php
foreach ($zones as $z)
	echo '<option value="'.$z['zoneid'].'"'.(($_GET['zoneid'] != "" && $_GET['zoneid'] == $z['zoneid']) ? ' selected="selected"' : '').'>'.$z['zone'].'</option>';
?>
</select>

</form>

<br /><br />

<?php
if ($shipping_rates_avail > 0) {
?>
<div align="right"><a href="#addrate">{lng[Add shipping rates values]}</a></div>

<br /><br />

<form method="post" name="shippingratesform">
<input type="hidden" name="mode" value="update" />
<input type="hidden" name="zoneid" value="{$_GET['zoneid']}" />
<input type="hidden" name="shippingid" value="<?php echo $_GET['shippingid']; ?>" />
<input type="hidden" name="type" value="<?php echo $type; ?>" />

<table cellpadding="0" cellspacing="1" width="1000">

<?php
foreach ($zones_list as $z) {
	if ($z['shipping_methods']) {
		echo '<tr>
  <td><h3>'.$z['zone']['zone'].'</h3></td>
</tr>';

		if ($z['shipping_methods']) {
			foreach ($z['shipping_methods'] as $shipid=>$shipping_method) {
				echo '<tr><td><hr /></td></tr>
<tr>
  <th>
<table cellpadding="2" cellspacing="0" width="100%">
<tr>
  <th><input type="checkbox" id="sm_'.$z['zone']['zoneid'].'_'.$shipid.'" name="sm_'.$z['zone']['zoneid'].'_'.$shipid.'" onchange="javascript: bc = false; if ($(this).is(\':checked\')) { $(\'.checkboxes_'.$z['zone']['zoneid'].'_'.$shipid.'\')[0].checked = true; }else $(\'.checkboxes_'.$z['zone']['zoneid'].'_'.$shipid.'\')[0].checked = false; " /></th>
  <th><b><label for="sm_'.$z['zone']['zoneid'].'_'.$shipid.'">'.func_trademark($shipping_method['shipping']).' (';
?>
{if $shipping_method['destination'] == "I"}{lng[International|escape]}{else}{lng[National|escape]}{/if}
<?php
  echo ')</label></b></th>
  <th align="right">
  </th>
</tr>
</table>
  </th>
</tr>

<tr>
  <td><hr /></td>
</tr>

<tr>
  <td>
';
				if ($shipping_method['rates']) {
					echo '<table cellpadding="0" cellspacing="3" width="100%">';

					foreach ($shipping_method['rates'] as $shipping_rate) {
						echo '<tr>
  <td rowspan="2" nowrap="nowrap"> &nbsp; <input type="checkbox" name="posted_data['.$shipping_rate['rateid'].'][to_delete]" class="checkboxes_'.$z['zone']['zoneid'].'_'.$shipid.'" /></td>
  <td>{lng[Weight range]}:</td>
  <td nowrap="nowrap">
<input type="text" name="posted_data['.$shipping_rate['rateid'].'][minweight]" size="9" value="'.$shipping_rate['minweight'].'" />
-
<input type="text" name="posted_data['.$shipping_rate['rateid'].'][maxweight]" size="9" value="'.$shipping_rate['maxweight'].'" />
  </td>
  <td>{lng[Flat charge]} ('.$config['General']['currency_symbol'].'):</td>
  <td nowrap="nowrap"><input type="text" name="posted_data['.$shipping_rate['rateid'].'][rate]" size="5" value="'.$shipping_rate['rate'].'" /></td>
  <td>{lng[Percent charge]}:</td>
  <td><input type="text" name="posted_data['.$shipping_rate['rateid'].'][rate_p]" size="5" value="'.$shipping_rate['rate_p'].'" /></td>
</tr>

<tr>
  <td>{lng[Subtotal range]}:</td>
  <td nowrap="nowrap">
<input type="text" name="posted_data['.$shipping_rate['rateid'].'][mintotal]" size="9" value="'.$shipping_rate['mintotal'].'" />
-
<input type="text" name="posted_data['.$shipping_rate['rateid'].'][maxtotal]" size="9" value="'.$shipping_rate['maxtotal'].'" />
  </td>
  <td>{lng[Per item charge]} ('.$config['General']['currency_symbol'].'):</td>
  <td nowrap="nowrap"><input type="text" name="posted_data['.$shipping_rate['rateid'].'][item_rate]" size="5" value="'.$shipping_rate['item_rate'].'" /></td>
  <td>Per '.$config['General']['weight_symbol'].' charge ('.$config['General']['currency_symbol'].'):</td>
  <td nowrap="nowrap"><input type="text" name="posted_data['.$shipping_rate['rateid'].'][weight_rate]" size="5" value="'.$shipping_rate['weight_rate'].'" /></td>
</tr>
<tr>
  <td colspan="7"><hr /></td>
</tr>
';
					}

					echo '</table>
  </td>
</tr>
';
				}
			}
		}
	} else {
	}
}
?>
<tr>
  <td>
<div class="fixed_save_button">
<button type="button" onclick="javascript: submitForm(this, 'delete');">{lng[Delete selected]}</button>
&nbsp;&nbsp;&nbsp;&nbsp;
<button type="submit">{lng[Update]}</button>
</div>
  </td>
</tr>

</table>
</form>

<br /><br /><br />

<a name="addrate"></a>

<?php
}
?>

<br />
<?php
if ($type == "D") {
?>
<h3>{lng[Add shipping charges]}</h3>
<?php
} else {
?>
<h3>{lng[Add shipping markups]}</h3>
<?php
}

if ($shipping != "") {
?>

<form method="post" name="addshippingrate">
<input type="hidden" name="mode" value="add" />
<input type="hidden" name="zoneid" value="<?php echo $zoneid; ?>" />
<input type="hidden" name="shippingid" value="<?php echo $shippingid; ?>" />
<input type="hidden" name="type" value="<?php echo $type; ?>" />

<table cellpadding="0" cellspacing="3">

<tr>
  <td width="150"><b>{lng[Shipping method]}:</b></td>
  <td>
  <select name="shippingid_new">
    <option value="">{lng[Select one]}</option>
{foreach $shipping as $s}
<option value="{$s['shippingid']}"'>{php echo func_trademark($s['shipping'])} ({if $s['destination'] == "I"}{lng[International|escape]}{else}{lng[National|escape]}{/if}))</option>';
{/foreach}
  </select>
  </td>
</tr>

<tr>
  <td><b>{lng[Zone]}:</b></td>
  <td>
  <select name="zoneid_new">
<?php
	foreach ($zones as $z)
		echo '<option value="'.$z['zoneid'].'"'.($_GET['zoneid'] == $z['zoneid'] ? ' selected="selected"' : '').'>'.$z['zone'].'</option>';
?>
  </select>
  </td>
</tr>

{*
<tr>
  <td><b>{lng[Apply rate to]}:</b></td>
  <td>
  <select name="apply_to_new">
    <option value="DST" selected="selected">DST ({lng[Discounted subtotal]})</option>
    <option value="ST">ST ({lng[Subtotal]})</option>
  </select>
  </td>
</tr>
*}

</table>

<table cellpadding="0" cellspacing="3" width="1000">

<tr>
  <td><b>{lng[Weight range]}:</b></td>
  <td nowrap="nowrap">
<input type="text" name="minweight_new" size="9" value="0.00" />
-
<input type="text" name="maxweight_new" size="9" value="<?php echo price_format($maxvalue); ?>" />
  </td>
  <td><b>{lng[Flat charge]} (<?php echo $config['General']['currency_symbol']; ?>):</b></td>
  <td nowrap="nowrap"><input type="text" name="rate_new" size="5" value="0.00" /></td>
  <td><b>{lng[Percent charge]}:</b></td>
  <td><input type="text" name="rate_p_new" size="5" value="0.00" /></td>
</tr>

<tr>
  <td width="150"><b>{lng[Subtotal range]}:</b></td>
  <td nowrap="nowrap"><input type="text" name="mintotal_new" size="9" value="0.00" />
-
<input type="text" name="maxtotal_new" size="9" value="<?php echo price_format($maxvalue); ?>" />
  </td>
  <td><b>{lng[Per item charge]} (<?php echo $config['General']['currency_symbol']; ?>):</b></td>
  <td nowrap="nowrap"><input type="text" name="item_rate_new" size="5" value="0.00" /></td>
  <td><b>Per <?php echo $config['General']['weight_symbol']; ?> charge (<?php echo $config['General']['currency_symbol']; ?>):</b></td>
  <td nowrap="nowrap"><input type="text" name="weight_rate_new" size="5" value="0.00" /></td>
</tr>

</table>

<br />
<button type="submit">{lng[Add]}</button>

</form>

<?php
} elseif ($type == "D") {
?>
{lng[User-defined shipping methods are not defined]}
<?php
}
?>
