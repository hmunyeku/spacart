<h1>{lng[Zone details]}</h1>

<form method="post" name="zoneform" onsubmit="javascript: if (this.zone_name.value == '') { alert('{lng[Zone name must be entered]}'); return false; } else return saveSelects(new Array('zone_countries','zone_states'));"{*{if !$zone['zoneid']} class="noajax"{/if}*}>
<input type="hidden" name="mode" value="details" />

<table cellpadding="3" cellspacing="1" width="1000" class="normal-table">

<tr>
  <td>{lng[Zone name]}</td>
  <td><input type="text" size="50" name="zone_name" value="<?php echo escape($zone['zone_name'], 2); ?>" />
<br /><br />
<button type="submit">{if $zone['zone_name']}{lng[Update|escape]}{else}{lng[Create|escape]}{/if}</button>
<?php
if ($zoneid) {
?>
&nbsp;&nbsp;
<button type="button" onclick="javascript: submitForm(this, 'clone');">{lng[Clone]}</button>
<?php
}
?>
  </td>
</tr>
</table>

<table cellpadding="3" cellspacing="1" width="1000">
<tr>
  <td colspan="3"><br /><br /><h3>{lng[Countries]}</td>
</tr>

<tr>
  <td width="45%">{lng[Set value]}</td>
  <td> </td>
  <td width="45%">{lng[Unset value]}</td>
</tr>

<tr>
  <td>
<input type="hidden" id="zone_countries_store" name="zone_countries_store" value="" />
<select id="zone_countries" multiple="multiple" class="width-100p" size="20">
<?php
foreach ($zone_countries as $c)
	echo '<option value="'.$c['code'].'">'.$c['country'].'</option>';
?>
<option value="">&nbsp;</option>
</select>
<script>
normalizeSelect('zone_countries');
</script>
  </td>
  <td align="center">
<input type="button" value="&lt;&lt;" onclick="javascript: moveSelect(document.getElementById('zone_countries'), document.getElementById('rest_countries'), 'R');" />
<br /><br />
<input type="button" value="&gt;&gt;" onclick="javascript: moveSelect(document.getElementById('zone_countries'), document.getElementById('rest_countries'), 'L');" />
  </td>
  <td>
<select id="rest_countries" multiple="multiple" class="width-100p" size="20">
<?php
foreach ($rest_countries as $c)
	echo '<option value="'.$c['code'].'">'.$c['country'].'</option>';
?>
</select>
  </td>
</tr>

<tr>
  <td colspan="3"><br /><br /><h3>{lng[States]}</td>
</tr>

<tr>
  <td>{lng[Set value]}</td>
  <td> </td>
  <td>{lng[Unset value]}</td>
</tr>

<tr>
  <td>
<input type="hidden" id="zone_states_store" name="zone_states_store" value="" />
<select id="zone_states" multiple="multiple" class="width-100p" size="20">
<?php
foreach ($zone_states as $s)
	echo '<option value="'.$s['country_code'].'_'.$s['code'].'">'.substr($s['country'], 0, 30).': '.$s['state'].'</option>';
?>
<option value="">&nbsp;</option>
</select>
<script type="text/javascript">
normalizeSelect('zone_states');
</script>
  </td>
  <td align="center">
<input type="button" value="&lt;&lt;" onclick="javascript: moveSelect(document.getElementById('zone_states'), document.getElementById('rest_states'), 'R');" />
<br /><br />
<input type="button" value="&gt;&gt;" onclick="javascript: moveSelect(document.getElementById('zone_states'), document.getElementById('rest_states'), 'L');" />
  </td>
  <td>
<select id="rest_states" name="rest_states" multiple="multiple" class="width-100p" size="20">
<?php
foreach ($rest_states as $s)
	echo '<option value="'.$s['country_code'].'_'.$s['code'].'">'.substr($s['country'], 0, 17).': '.$s['state'].'</option>';
?>
</select>
  </td>
</tr>

<tr>
  <td colspan="3"><br /><br /><h3>{lng[Cities]}</td>
</tr>

<tr>
  <td>{lng[Set value]}</td>
  <td> </td>
  <td>{lng[City mask examples]}:</td>
</tr>

<tr>
  <td>
<textarea cols="40" rows="20" class="width-100p" name="zone_cities"><?php
foreach ($zone_elements as $e)
	if ($e['field_type'] == 'T')
		echo $e['field']."\n";
?></textarea>
  </td>
  <td align="center">&nbsp;</td>
  <td valign="top">New Yo%<br />Washington<br />Los Angeles<br />Dallas</td>
</tr>

<tr>
  <td colspan="3"><br /><br /><h3>{lng[Zip/Postal codes]}</td>
</tr>

<tr>
  <td>{lng[Set value]}</td>
  <td> </td>
  <td>{lng[Zipcode mask examples]}:</td>
</tr>

<tr>
  <td>
<textarea cols="40" rows="20" class="width-100p" name="zone_zipcodes"><?php foreach ($zone_elements as $e) if ($e['field_type'] == 'Z') echo $e['field']."\n";?></textarea>
  </td>
  <td align="center">&nbsp;</td>
  <td valign="top">1000%<br />38245<br />4320%</td>
</tr>

</table>

<br />

<div class="fixed_save_button">
<button type="submit">{lng[Save zone details]}</button>
</div>

</form>
