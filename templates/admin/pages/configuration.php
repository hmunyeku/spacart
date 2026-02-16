<form method="POST">
{if $get['2'] == 'General'}
<h1>General settings</h1>
{elseif $get['2'] == 'Company'}
<h1>Company information</h1>
{elseif $get['2'] == 'Blog'}
<h1>Blog settings</h1>
{/if}

 <table class="configuration normal-table">
<script>
var states = {ldelim}{rdelim};
	user_state = "<?php if ($get['2'] == 'Company') echo escape($config['Company']['location_state'], 2);else echo escape($config['General']['default_state'], 2); ?>";
<?php
foreach ($countries as $v) {
	if (!empty($v['states'])) {
		echo 'states.'.$v['code'].' = {states: []};'."\n";
		foreach ($v['states'] as $k=>$s) {
			echo 'states.'.$v['code'].'.states['.$k.'] = {code: "'.escape($s['code'], 2).'", state: "'.escape($s['state'], 2).'"};'."\n";
		}
	}
}
?>
</script>
<?php
foreach ($conf as $v) {
	if ($v['type'] == 'separator')
		echo '<tr><td colspan="2"><h3>'.$v['comment'].'</h3></td></tr>';
	else {
		echo '<tr><td class="name">'.$v['comment'].'</td><td class="value">';

		if ($v['type'] == 'text' || $v['type'] == 'numeric') {
			echo '<input type="text" name="'.$v['name'].'" value="'.escape($v['value'], 2).'" />';
		} elseif ($v['type'] == 'password') {
			echo '<input type="password" name="'.$v['name'].'" value="'.escape($v['value'], 2).'" />';
		} elseif ($v['type'] == 'checkbox') {
			echo '<input type="checkbox" name="'.$v['name'].'" value="Y"'.($v['value'] == 'Y' ? ' checked="checked"' : '').' />';
		} elseif ($v['type'] == 'textarea') {
			echo '<textarea name="'.$v['name'].'">'.$v['value'].'</textarea>';
		} elseif ($v['type'] == 'selector') {
			echo '<select name="'.$v['name'].'">';
			foreach ($v['variants'] as $vt)
				echo '<option value="'.$vt['0'].'"'.($config[$get['2']][$v['name']] == $vt['0'] ? ' selected' : '').'>'.$vt['1'].'</option>';

			echo '</select>';
		} elseif ($v['name'] == 'default_country') {
			echo '<select name="default_country" id="country">';
			foreach ($countries as $c) {
				echo '<option value="'.$c['code'].'"'.(($c['code'] == $v['value']) ? ' selected' : '').'>'.$c['country'].'</option>';
			}

			echo '</select>';
		} elseif ($v['name'] == 'default_state') {
			$found = false;
			foreach ($countries as $c)
				if ($c['code'] == $config['General']['default_country'] && !empty($c['states'])) {
					$found = true;
					echo '<select name="default_state" id="state">';
					foreach ($c['states'] as $s)
						echo '<option value="'.escape($s['code'], 2).'"'.($s['code'] == $config['General']['default_state'] ? ' selected' : '').'>'.$s['state'].'</option>';

					echo '</select>';
				}

			if (!$found) {
?>
 <input type="text" name="posted_data[state]" id="state" value="<?php echo escape($config['General']['default_state'], 2); ?>" /></td>
<?php
			}
		} elseif ($v['name'] == 'location_country') {
			echo '<select name="location_country" id="country">';
			foreach ($countries as $c) {
				echo '<option value="'.$c['code'].'"'.(($c['code'] == $v['value']) ? ' selected' : '').'>'.$c['country'].'</option>';
			}

			echo '</select>';
		} elseif ($v['name'] == 'location_state') {
			$found = false;
			foreach ($countries as $c) {
				if ($c['code'] == $config['Company']['location_country'] && !empty($c['states'])) {
					$found = true;
					echo '<select name="location_state" id="state">';
					foreach ($c['states'] as $s)
						echo '<option value="'.escape($s['code'], 2).'"'.($s['code'] == $config['Company']['location_state'] ? ' selected' : '').'>'.$s['state'].'</option>';

					echo '</select>';
				}
   }
#exit(print_R($config));
			if (!$found) {
?>
 <input type="text" name="posted_data[location_state]" id="state" value="<?php echo escape($config['Company']['location_state'], 2); ?>" /></td>
<?php
			}
		}

		echo ($v['name'] == 'currency_api_key' ? ' &nbsp; <a href="https://currencylayer.com/" target="_blank">https://currencylayer.com/</a><br /><br />' : '').'';
		echo ($v['name'] == 'shop_closed_key' ? ' &nbsp; <a href="'.$current_location.'/?shopkey='.$v['value'].'" target="_blank">'.$current_location.'/?shopkey='.$v['value'].'</a><br /><br />' : '').'</td></tr>';
	}
}
?>
<tr>
 <td><br />
<div class="fixed_save_button">
 <button>{lng[Save]}</button>
</div>
 </td>
 <td></td>
</tr>
</table>
</form>