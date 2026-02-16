<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/theme.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Theme customizer (colors, font, CSS)
 *
 * Settings saved to llx_const via dolibarr_set_const():
 *   SPACART_THEME_COLOR, SPACART_THEME_COLOR_2, SPACART_FONT_FAMILY,
 *   SPACART_CONTAINER_WIDTH, SPACART_CUSTOM_CSS
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Theme';
$current_page = 'theme';

global $db, $conf;

// -------------------------------------------------------------------
// Handle POST: save theme settings
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=theme');
		exit;
	}

	$fields = array(
		'SPACART_THEME_COLOR'    => isset($_POST['theme_color']) ? trim($_POST['theme_color']) : '#1565c0',
		'SPACART_THEME_COLOR_2'  => isset($_POST['theme_color_2']) ? trim($_POST['theme_color_2']) : '#ff6f00',
		'SPACART_FONT_FAMILY'    => isset($_POST['font_family']) ? trim($_POST['font_family']) : 'Open Sans',
		'SPACART_CONTAINER_WIDTH' => isset($_POST['container_width']) ? trim($_POST['container_width']) : '1200',
		'SPACART_CUSTOM_CSS'     => isset($_POST['custom_css']) ? trim($_POST['custom_css']) : '',
	);

	$errors = 0;
	foreach ($fields as $name => $value) {
		// Validate color fields
		if (in_array($name, array('SPACART_THEME_COLOR', 'SPACART_THEME_COLOR_2'))) {
			if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
				$value = ($name === 'SPACART_THEME_COLOR') ? '#1565c0' : '#ff6f00';
			}
		}

		// Validate font family
		if ($name === 'SPACART_FONT_FAMILY') {
			$allowed_fonts = array('Open Sans', 'Roboto', 'Lato', 'Poppins');
			if (!in_array($value, $allowed_fonts)) {
				$value = 'Open Sans';
			}
		}

		// Validate container width
		if ($name === 'SPACART_CONTAINER_WIDTH') {
			$value = max(960, min(1920, (int) $value));
			$value = (string) $value;
		}

		// Use Dolibarr's native constant setter
		if (function_exists('dolibarr_set_const')) {
			$result = dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $conf->entity);
			if ($result <= 0) {
				$errors++;
			}
		} else {
			// Fallback: direct SQL to llx_const
			$name_esc = $db->escape($name);
			$value_esc = $db->escape($value);
			$entity = (int) $conf->entity;

			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."const";
			$sql_check .= " WHERE name = '".$name_esc."'";
			$sql_check .= " AND entity = ".$entity;
			$resql_check = $db->query($sql_check);

			if ($resql_check && $db->num_rows($resql_check) > 0) {
				$sql_upd = "UPDATE ".MAIN_DB_PREFIX."const SET value = '".$value_esc."'";
				$sql_upd .= " WHERE name = '".$name_esc."' AND entity = ".$entity;
				if (!$db->query($sql_upd)) {
					$errors++;
				}
			} else {
				$sql_ins = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, type, visible, note, entity)";
				$sql_ins .= " VALUES ('".$name_esc."', '".$value_esc."', 'chaine', 0, '', ".$entity.")";
				if (!$db->query($sql_ins)) {
					$errors++;
				}
			}
		}
	}

	if ($errors === 0) {
		spacartAdminFlash('Les parametres du theme ont ete enregistres avec succes.', 'success');
	} else {
		spacartAdminFlash('Erreur lors de l\'enregistrement de certains parametres ('.$errors.' erreur'.($errors > 1 ? 's' : '').').', 'danger');
	}

	header('Location: ?page=theme');
	exit;
}

// -------------------------------------------------------------------
// Load current theme values
// -------------------------------------------------------------------
$theme_color = getDolGlobalString('SPACART_THEME_COLOR', '#1565c0');
$theme_color_2 = getDolGlobalString('SPACART_THEME_COLOR_2', '#ff6f00');
$font_family = getDolGlobalString('SPACART_FONT_FAMILY', 'Open Sans');
$container_width = getDolGlobalString('SPACART_CONTAINER_WIDTH', '1200');
$custom_css = getDolGlobalString('SPACART_CUSTOM_CSS', '');

$available_fonts = array(
	'Open Sans' => 'Open Sans',
	'Roboto'    => 'Roboto',
	'Lato'      => 'Lato',
	'Poppins'   => 'Poppins',
);

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

// -------------------------------------------------------------------
// Include header
// -------------------------------------------------------------------
include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><i class="bi bi-palette2 me-2"></i>Theme</h1>
		<p class="text-muted mb-0">Personnaliser l'apparence de la boutique en ligne</p>
	</div>
</div>

<form method="post" action="?page=theme" id="themeForm" class="track-changes">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">

	<div class="row g-4">
		<!-- Left column: Settings -->
		<div class="col-12 col-lg-7">

			<!-- Colors Section -->
			<div class="admin-card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="bi bi-palette me-2"></i>Couleurs</h5>
				</div>
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label for="theme-color" class="form-label">Couleur principale</label>
							<div class="d-flex align-items-center gap-2">
								<input type="color" class="form-control form-control-color" id="theme-color"
									   name="theme_color" value="<?php echo spacartAdminEscape($theme_color); ?>"
									   title="Choisir la couleur principale">
								<input type="text" class="form-control" id="theme-color-text"
									   value="<?php echo spacartAdminEscape($theme_color); ?>"
									   pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="max-width: 120px;">
							</div>
							<div class="form-hint">
								<small class="text-muted">Couleur des boutons, liens, en-tetes principaux</small>
							</div>
						</div>
						<div class="col-md-6">
							<label for="theme-color-2" class="form-label">Couleur secondaire</label>
							<div class="d-flex align-items-center gap-2">
								<input type="color" class="form-control form-control-color" id="theme-color-2"
									   name="theme_color_2" value="<?php echo spacartAdminEscape($theme_color_2); ?>"
									   title="Choisir la couleur secondaire">
								<input type="text" class="form-control" id="theme-color-2-text"
									   value="<?php echo spacartAdminEscape($theme_color_2); ?>"
									   pattern="^#[0-9a-fA-F]{6}$" maxlength="7" style="max-width: 120px;">
							</div>
							<div class="form-hint">
								<small class="text-muted">Couleur des accents, badges, surbrillance</small>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Typography Section -->
			<div class="admin-card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="bi bi-fonts me-2"></i>Typographie</h5>
				</div>
				<div class="card-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label for="font-family" class="form-label">Police de caracteres</label>
							<select class="form-select" id="font-family" name="font_family">
								<?php foreach ($available_fonts as $value => $label): ?>
									<option value="<?php echo spacartAdminEscape($value); ?>"
											<?php echo ($font_family === $value) ? 'selected' : ''; ?>
											style="font-family: '<?php echo spacartAdminEscape($value); ?>', sans-serif;">
										<?php echo spacartAdminEscape($label); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="form-hint">
								<small class="text-muted">Police Google Fonts utilisee sur le site</small>
							</div>
						</div>
						<div class="col-md-6">
							<label for="container-width" class="form-label">Largeur du conteneur (px)</label>
							<input type="number" class="form-control" id="container-width" name="container_width"
								   value="<?php echo spacartAdminEscape($container_width); ?>"
								   min="960" max="1920" step="10">
							<div class="form-hint">
								<small class="text-muted">Largeur maximale du contenu (960-1920px)</small>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Custom CSS Section -->
			<div class="admin-card mb-4">
				<div class="card-header">
					<h5 class="mb-0"><i class="bi bi-code-slash me-2"></i>CSS personnalise</h5>
				</div>
				<div class="card-body">
					<label for="custom-css" class="form-label">Code CSS additionnel</label>
					<textarea class="form-control font-monospace" id="custom-css" name="custom_css"
							  rows="10" placeholder="/* Ajoutez votre CSS personnalise ici */
.my-class {
    color: red;
}"><?php echo spacartAdminEscape($custom_css); ?></textarea>
					<div class="form-hint">
						<small class="text-muted">Ce CSS sera injecte apres les styles du theme. Utilisez-le pour des ajustements fins.</small>
					</div>
				</div>
			</div>

			<!-- Save Button -->
			<div class="d-flex justify-content-end mb-4">
				<button type="submit" class="btn btn-primary btn-lg">
					<i class="bi bi-check-lg me-2"></i>Enregistrer le theme
				</button>
			</div>
		</div>

		<!-- Right column: Live Preview -->
		<div class="col-12 col-lg-5">
			<div class="admin-card" style="position: sticky; top: 80px;">
				<div class="card-header">
					<h5 class="mb-0"><i class="bi bi-eye me-2"></i>Apercu en direct</h5>
				</div>
				<div class="card-body">
					<!-- Preview Product Card -->
					<div id="preview-container" style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; max-width: 300px; margin: 0 auto;">
						<!-- Product Image placeholder -->
						<div id="preview-header" style="height: 180px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 48px;"
							 class="preview-primary-bg">
							<i class="bi bi-bag"></i>
						</div>

						<div style="padding: 16px;" id="preview-body">
							<!-- Product title -->
							<h6 id="preview-title" class="mb-1" style="margin: 0;">Produit exemple</h6>
							<p class="text-muted small mb-2">Categorie</p>

							<!-- Price -->
							<div class="mb-3">
								<span id="preview-price" class="fw-bold fs-5">49,90 &euro;</span>
								<span class="text-muted text-decoration-line-through ms-2 small">59,90 &euro;</span>
							</div>

							<!-- Badge -->
							<span id="preview-badge" class="badge mb-3" style="display: inline-block;">Nouveau</span>

							<!-- Button -->
							<div>
								<button type="button" id="preview-btn" class="btn btn-sm w-100" style="color: #fff;">
									<i class="bi bi-cart-plus me-1"></i>Ajouter au panier
								</button>
							</div>
						</div>
					</div>

					<!-- Font preview -->
					<div class="mt-4 p-3 border rounded" id="preview-font-area">
						<p class="mb-1 fw-bold" id="preview-font-label">Police : <span id="preview-font-name"><?php echo spacartAdminEscape($font_family); ?></span></p>
						<p class="mb-0" id="preview-font-sample">
							Aa Bb Cc Dd Ee Ff Gg 0123456789
						</p>
					</div>
				</div>
			</div>
		</div>
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

<!-- Live preview update script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	var colorInput = document.getElementById('theme-color');
	var colorText = document.getElementById('theme-color-text');
	var color2Input = document.getElementById('theme-color-2');
	var color2Text = document.getElementById('theme-color-2-text');
	var fontSelect = document.getElementById('font-family');

	// Preview elements
	var previewHeader = document.getElementById('preview-header');
	var previewPrice = document.getElementById('preview-price');
	var previewBadge = document.getElementById('preview-badge');
	var previewBtn = document.getElementById('preview-btn');
	var previewTitle = document.getElementById('preview-title');
	var previewBody = document.getElementById('preview-body');
	var previewFontArea = document.getElementById('preview-font-area');
	var previewFontName = document.getElementById('preview-font-name');
	var previewFontSample = document.getElementById('preview-font-sample');

	function updatePreview() {
		var primary = colorInput.value;
		var secondary = color2Input.value;
		var font = fontSelect.value;

		// Primary color: header bg, button bg
		previewHeader.style.backgroundColor = primary;
		previewBtn.style.backgroundColor = primary;
		previewBtn.style.borderColor = primary;
		previewTitle.style.color = primary;

		// Secondary color: price, badge
		previewPrice.style.color = secondary;
		previewBadge.style.backgroundColor = secondary;
		previewBadge.style.color = '#fff';

		// Font
		var fontStack = "'" + font + "', sans-serif";
		previewBody.style.fontFamily = fontStack;
		previewFontArea.style.fontFamily = fontStack;
		previewFontName.textContent = font;
	}

	// Sync color picker <-> text input
	colorInput.addEventListener('input', function() {
		colorText.value = this.value;
		updatePreview();
	});
	colorText.addEventListener('input', function() {
		if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
			colorInput.value = this.value;
			updatePreview();
		}
	});

	color2Input.addEventListener('input', function() {
		color2Text.value = this.value;
		updatePreview();
	});
	color2Text.addEventListener('input', function() {
		if (/^#[0-9a-fA-F]{6}$/.test(this.value)) {
			color2Input.value = this.value;
			updatePreview();
		}
	});

	fontSelect.addEventListener('change', function() {
		updatePreview();
	});

	// Initial preview
	updatePreview();
});
</script>

<?php
include __DIR__.'/../includes/footer.php';
