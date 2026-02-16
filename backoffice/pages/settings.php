<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/settings.php
 * \ingroup    spacart
 * \brief      SpaCart admin - General settings configuration page
 *
 * Settings are stored in llx_spacart_config (name/value pairs per entity).
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Configuration generale';
$current_page = 'settings';

// -------------------------------------------------------------------
// Settings definition (field_name => array of properties)
// Grouped by section for rendering
// -------------------------------------------------------------------
$settings_sections = array(
	'Boutique' => array(
		'shop_name' => array(
			'label'   => 'Nom de la boutique',
			'type'    => 'text',
			'default' => 'Ma Boutique',
			'hint'    => 'Nom affiche sur le site et dans les emails',
		),
		'shop_email' => array(
			'label'   => 'Email de contact',
			'type'    => 'email',
			'default' => '',
			'hint'    => 'Adresse email principale de la boutique',
		),
		'shop_phone' => array(
			'label'   => 'Telephone',
			'type'    => 'text',
			'default' => '',
			'hint'    => 'Numero de telephone affiche aux clients',
		),
		'shop_address' => array(
			'label'   => 'Adresse',
			'type'    => 'textarea',
			'default' => '',
			'hint'    => 'Adresse postale de la boutique',
		),
	),
	'Monnaie & Format' => array(
		'currency_code' => array(
			'label'   => 'Code devise',
			'type'    => 'select',
			'options' => array('EUR' => 'EUR - Euro', 'USD' => 'USD - Dollar US', 'GBP' => 'GBP - Livre sterling'),
			'default' => 'EUR',
			'hint'    => 'Code ISO de la devise utilisee',
		),
		'currency_symbol' => array(
			'label'   => 'Symbole devise',
			'type'    => 'text',
			'default' => "\xe2\x82\xac",
			'hint'    => 'Symbole affiche a cote des prix',
		),
		'price_decimals' => array(
			'label'   => 'Decimales prix',
			'type'    => 'select',
			'options' => array('0' => '0', '1' => '1', '2' => '2', '3' => '3'),
			'default' => '2',
			'hint'    => 'Nombre de decimales pour l\'affichage des prix',
		),
	),
	'Commandes' => array(
		'order_prefix' => array(
			'label'   => 'Prefixe commandes',
			'type'    => 'text',
			'default' => 'SPC',
			'hint'    => 'Prefixe ajoute aux numeros de commande (ex: SPC-00001)',
		),
		'min_order_amount' => array(
			'label'   => 'Montant minimum commande',
			'type'    => 'number',
			'default' => '0',
			'hint'    => 'Montant minimum requis pour passer commande (0 = pas de minimum)',
			'step'    => '0.01',
			'min'     => '0',
		),
		'allow_guest_checkout' => array(
			'label'   => 'Autoriser commande sans compte',
			'type'    => 'toggle',
			'default' => '1',
			'hint'    => 'Permettre aux visiteurs de commander sans creer de compte',
		),
	),
	'SEO' => array(
		'meta_title' => array(
			'label'   => 'Titre meta par defaut',
			'type'    => 'text',
			'default' => '',
			'hint'    => 'Balise &lt;title&gt; utilisee sur les pages sans titre specifique',
		),
		'meta_description' => array(
			'label'   => 'Description meta',
			'type'    => 'textarea',
			'default' => '',
			'hint'    => 'Description meta par defaut pour le referencement',
		),
		'google_analytics_id' => array(
			'label'   => 'ID Google Analytics',
			'type'    => 'text',
			'default' => '',
			'hint'    => 'Identifiant de suivi (ex: G-XXXXXXXXXX ou UA-XXXXXXX-X)',
		),
	),
	'Emails' => array(
		'email_from_name' => array(
			'label'   => 'Nom expediteur',
			'type'    => 'text',
			'default' => '',
			'hint'    => 'Nom affiche comme expediteur des emails transactionnels',
		),
		'email_from_address' => array(
			'label'   => 'Email expediteur',
			'type'    => 'email',
			'default' => '',
			'hint'    => 'Adresse email utilisee pour l\'envoi des emails',
		),
		'email_order_confirmation' => array(
			'label'   => 'Envoyer confirmation commande',
			'type'    => 'toggle',
			'default' => '1',
			'hint'    => 'Envoyer un email de confirmation au client apres chaque commande',
		),
		'email_new_account' => array(
			'label'   => 'Envoyer email bienvenue',
			'type'    => 'toggle',
			'default' => '1',
			'hint'    => 'Envoyer un email de bienvenue lors de la creation d\'un compte client',
		),
	),
);

// Build a flat list of all field names for processing
$all_field_names = array();
foreach ($settings_sections as $section_name => $fields) {
	foreach ($fields as $field_name => $field_def) {
		$all_field_names[] = $field_name;
	}
}

// -------------------------------------------------------------------
// Handle POST: save settings
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$entity = (int) $conf->entity;
	$errors = 0;

	$db->begin();

	foreach ($all_field_names as $name) {
		// For toggles, unchecked fields won't be in POST
		$value = '';
		if (isset($_POST[$name])) {
			$value = trim($_POST[$name]);
		} else {
			// Check if this is a toggle field - unchecked = '0'
			$is_toggle = false;
			foreach ($settings_sections as $section_fields) {
				if (isset($section_fields[$name]) && $section_fields[$name]['type'] === 'toggle') {
					$is_toggle = true;
					break;
				}
			}
			if ($is_toggle) {
				$value = '0';
			}
		}

		$name_esc = $db->escape($name);
		$value_esc = $db->escape($value);

		// Check if row exists
		$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_config";
		$sql_check .= " WHERE name = '".$name_esc."'";
		$sql_check .= " AND entity = ".$entity;
		$resql_check = $db->query($sql_check);

		if ($resql_check && $db->num_rows($resql_check) > 0) {
			// Update existing
			$sql_update = "UPDATE ".MAIN_DB_PREFIX."spacart_config";
			$sql_update .= " SET value = '".$value_esc."'";
			$sql_update .= " WHERE name = '".$name_esc."'";
			$sql_update .= " AND entity = ".$entity;
			if (!$db->query($sql_update)) {
				$errors++;
			}
		} else {
			// Determine category from section name
			$category = 'General';
			foreach ($settings_sections as $section_name => $fields) {
				if (isset($fields[$name])) {
					$category = $section_name;
					break;
				}
			}

			// Insert new
			$sql_insert = "INSERT INTO ".MAIN_DB_PREFIX."spacart_config";
			$sql_insert .= " (category, name, value, entity)";
			$sql_insert .= " VALUES (";
			$sql_insert .= "'".$db->escape($category)."',";
			$sql_insert .= " '".$name_esc."',";
			$sql_insert .= " '".$value_esc."',";
			$sql_insert .= " ".$entity;
			$sql_insert .= ")";
			if (!$db->query($sql_insert)) {
				$errors++;
			}
		}
	}

	if ($errors === 0) {
		$db->commit();
		spacartAdminFlash('Les parametres ont ete enregistres avec succes.', 'success');
	} else {
		$db->rollback();
		spacartAdminFlash('Erreur lors de l\'enregistrement de certains parametres ('.$errors.' erreur'.($errors > 1 ? 's' : '').').', 'danger');
	}

	header('Location: ?page=settings');
	exit;
}

// -------------------------------------------------------------------
// Load current settings from DB
// -------------------------------------------------------------------
$settings = array();
$sql_load = "SELECT name, value FROM ".MAIN_DB_PREFIX."spacart_config WHERE entity = ".(int) $conf->entity;
$resql_load = $db->query($sql_load);
if ($resql_load) {
	while ($obj = $db->fetch_object($resql_load)) {
		$settings[$obj->name] = $obj->value;
	}
}

/**
 * Get a setting value with fallback to default
 *
 * @param  string $key     Setting name
 * @param  string $default Default value
 * @return string Setting value
 */
function _settingsVal($key, $default = '')
{
	global $settings;
	return isset($settings[$key]) ? $settings[$key] : $default;
}

// -------------------------------------------------------------------
// CSRF token
// -------------------------------------------------------------------
$csrf_token = spacartAdminGetCSRFToken();

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><i class="bi bi-gear me-2"></i>Configuration generale</h1>
		<p class="text-muted mb-0">Parametres globaux de la boutique en ligne</p>
	</div>
</div>

<!-- Settings Form -->
<form method="post" action="?page=settings" id="settingsForm" class="track-changes">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">

	<?php foreach ($settings_sections as $section_name => $fields): ?>
	<!-- Section: <?php echo spacartAdminEscape($section_name); ?> -->
	<div class="form-section mb-4">
		<div class="form-section-title">
			<h5 class="mb-0">
				<?php
				// Section icons
				$section_icons = array(
					'Boutique'         => 'bi-shop',
					'Monnaie & Format' => 'bi-currency-exchange',
					'Commandes'        => 'bi-box-seam',
					'SEO'              => 'bi-search',
					'Emails'           => 'bi-envelope',
				);
				$icon = isset($section_icons[$section_name]) ? $section_icons[$section_name] : 'bi-gear';
				?>
				<i class="bi <?php echo $icon; ?> me-2"></i><?php echo spacartAdminEscape($section_name); ?>
			</h5>
		</div>
		<div class="form-section-body">
			<?php foreach ($fields as $field_name => $field_def):
				$val = _settingsVal($field_name, $field_def['default']);
			?>
			<div class="form-group mb-3">
				<label for="field-<?php echo spacartAdminEscape($field_name); ?>" class="form-label">
					<?php echo spacartAdminEscape($field_def['label']); ?>
				</label>

				<?php if ($field_def['type'] === 'text' || $field_def['type'] === 'email' || $field_def['type'] === 'number'): ?>
					<input type="<?php echo $field_def['type']; ?>"
						   class="form-control"
						   id="field-<?php echo spacartAdminEscape($field_name); ?>"
						   name="<?php echo spacartAdminEscape($field_name); ?>"
						   value="<?php echo spacartAdminEscape($val); ?>"
						   <?php if (!empty($field_def['step'])): ?>step="<?php echo spacartAdminEscape($field_def['step']); ?>"<?php endif; ?>
						   <?php if (isset($field_def['min'])): ?>min="<?php echo spacartAdminEscape($field_def['min']); ?>"<?php endif; ?>
						   >

				<?php elseif ($field_def['type'] === 'textarea'): ?>
					<textarea class="form-control"
							  id="field-<?php echo spacartAdminEscape($field_name); ?>"
							  name="<?php echo spacartAdminEscape($field_name); ?>"
							  rows="3"><?php echo spacartAdminEscape($val); ?></textarea>

				<?php elseif ($field_def['type'] === 'select'): ?>
					<select class="form-select"
							id="field-<?php echo spacartAdminEscape($field_name); ?>"
							name="<?php echo spacartAdminEscape($field_name); ?>">
						<?php foreach ($field_def['options'] as $opt_value => $opt_label): ?>
							<option value="<?php echo spacartAdminEscape($opt_value); ?>"<?php echo ((string) $val === (string) $opt_value) ? ' selected' : ''; ?>>
								<?php echo spacartAdminEscape($opt_label); ?>
							</option>
						<?php endforeach; ?>
					</select>

				<?php elseif ($field_def['type'] === 'toggle'): ?>
					<div class="form-check form-switch">
						<input class="form-check-input toggle-switch"
							   type="checkbox"
							   role="switch"
							   id="field-<?php echo spacartAdminEscape($field_name); ?>"
							   name="<?php echo spacartAdminEscape($field_name); ?>"
							   value="1"
							   <?php echo ((int) $val === 1) ? 'checked' : ''; ?>>
						<label class="form-check-label" for="field-<?php echo spacartAdminEscape($field_name); ?>">
							<?php echo ((int) $val === 1) ? 'Active' : 'Desactive'; ?>
						</label>
					</div>
				<?php endif; ?>

				<?php if (!empty($field_def['hint'])): ?>
					<div class="form-hint">
						<small class="text-muted"><?php echo $field_def['hint']; ?></small>
					</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endforeach; ?>

	<!-- Save Button -->
	<div class="d-flex justify-content-end mb-4">
		<button type="submit" class="btn btn-primary btn-lg">
			<i class="bi bi-check-lg me-2"></i>Enregistrer
		</button>
	</div>
</form>

<!-- Dirty form bar -->
<div class="dirty-bar" id="dirtyBar" style="display:none;">
	<span><i class="bi bi-exclamation-circle me-1"></i>Modifications non sauvegardees</span>
	<div class="d-flex gap-2">
		<button type="button" class="btn btn-sm btn-outline-light" id="dirtyBarCancel">Annuler</button>
		<button type="button" class="btn btn-sm btn-light" id="dirtyBarSave">Sauvegarder</button>
	</div>
</div>

<!-- Toggle label update script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	// Update toggle labels dynamically when switched
	var toggles = document.querySelectorAll('.toggle-switch');
	toggles.forEach(function(toggle) {
		toggle.addEventListener('change', function() {
			var label = this.closest('.form-check').querySelector('.form-check-label');
			if (label) {
				label.textContent = this.checked ? 'Active' : 'Desactive';
			}
		});
	});
});
</script>

<?php
include __DIR__.'/../includes/footer.php';
