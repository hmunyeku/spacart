<?php
/**
 * SpaCart Backoffice - Product Add / Edit
 *
 * Full product form: general info, stock & weight, category, SEO.
 * Handles both INSERT (new product) and UPDATE (existing product).
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// =====================================================================
// Load product if editing
// =====================================================================
$id = (int) ($_GET['id'] ?? 0);
$product = null;

if ($id > 0) {
	$sql = "SELECT * FROM ".$prefix."product WHERE rowid = ".$id." AND entity = ".$entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$product = $db->fetch_object($resql);
	} else {
		spacartAdminFlash('Produit introuvable.', 'danger');
		header('Location: ?page=products');
		exit;
	}
}

$is_edit = ($product !== null);
$page_title = $is_edit ? 'Modifier le produit' : 'Ajouter un produit';
$current_page = 'products';

// =====================================================================
// Load current category link (for edit mode)
// =====================================================================
$current_cat_id = 0;
if ($is_edit) {
	$sql_cat = "SELECT fk_categorie FROM ".$prefix."categorie_product WHERE fk_product = ".(int) $product->rowid." LIMIT 1";
	$resql_cat = $db->query($sql_cat);
	if ($resql_cat && $db->num_rows($resql_cat) > 0) {
		$obj_cat = $db->fetch_object($resql_cat);
		$current_cat_id = (int) $obj_cat->fk_categorie;
	}
}

// =====================================================================
// Load all categories for the dropdown
// =====================================================================
$categories = array();
$sql_cats = "SELECT rowid, label FROM ".$prefix."categorie WHERE type = 0 AND entity = ".$entity." ORDER BY label ASC";
$resql_cats = $db->query($sql_cats);
if ($resql_cats) {
	while ($obj = $db->fetch_object($resql_cats)) {
		$categories[] = $obj;
	}
}

// =====================================================================
// POST handling (save product)
// =====================================================================
$errors = array();
$form_data = array(
	'ref'              => $is_edit ? $product->ref : '',
	'label'            => $is_edit ? $product->label : '',
	'description'      => $is_edit ? $product->description : '',
	'price'            => $is_edit ? (float) $product->price : '',
	'tva_tx'           => $is_edit ? (float) $product->tva_tx : 20,
	'price_ttc'        => $is_edit ? (float) $product->price_ttc : '',
	'tosell'           => $is_edit ? (int) $product->tosell : 1,
	'stock'            => $is_edit ? (int) $product->stock : 0,
	'weight'           => $is_edit ? (float) $product->weight : '',
	'weight_units'     => $is_edit ? (int) $product->weight_units : 0,
	'fk_categorie'     => $current_cat_id,
	'meta_title'       => '',
	'meta_description' => '',
);

// Load SEO extrafields if they exist (from llx_product_extrafields)
if ($is_edit) {
	$sql_extra = "SELECT * FROM ".$prefix."product_extrafields WHERE fk_object = ".(int) $product->rowid;
	$resql_extra = $db->query($sql_extra);
	if ($resql_extra && $db->num_rows($resql_extra) > 0) {
		$extra = $db->fetch_object($resql_extra);
		if (isset($extra->spacart_meta_title)) {
			$form_data['meta_title'] = $extra->spacart_meta_title;
		}
		if (isset($extra->spacart_meta_description)) {
			$form_data['meta_description'] = $extra->spacart_meta_description;
		}
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	// Collect form data
	$form_data['ref']              = trim($_POST['ref'] ?? '');
	$form_data['label']            = trim($_POST['label'] ?? '');
	$form_data['description']      = trim($_POST['description'] ?? '');
	$form_data['price']            = (float) ($_POST['price'] ?? 0);
	$form_data['tva_tx']           = (float) ($_POST['tva_tx'] ?? 20);
	$form_data['price_ttc']        = (float) ($_POST['price_ttc'] ?? 0);
	$form_data['tosell']           = (int) ($_POST['tosell'] ?? 0);
	$form_data['stock']            = (int) ($_POST['stock'] ?? 0);
	$form_data['weight']           = (float) ($_POST['weight'] ?? 0);
	$form_data['weight_units']     = (int) ($_POST['weight_units'] ?? 0);
	$form_data['fk_categorie']     = (int) ($_POST['fk_categorie'] ?? 0);
	$form_data['meta_title']       = trim($_POST['meta_title'] ?? '');
	$form_data['meta_description'] = trim($_POST['meta_description'] ?? '');

	// Validate required fields
	if ($form_data['ref'] === '') {
		$errors[] = 'La reference est obligatoire.';
	}
	if ($form_data['label'] === '') {
		$errors[] = 'Le libelle est obligatoire.';
	}

	// Check ref uniqueness
	if ($form_data['ref'] !== '') {
		$sql_check = "SELECT rowid FROM ".$prefix."product WHERE ref = '".$db->escape($form_data['ref'])."' AND entity = ".$entity;
		if ($is_edit) {
			$sql_check .= " AND rowid <> ".(int) $product->rowid;
		}
		$res_check = $db->query($sql_check);
		if ($res_check && $db->num_rows($res_check) > 0) {
			$errors[] = 'Cette reference existe deja pour un autre produit.';
		}
	}

	// Calculate price TTC from price HT + TVA if price_ttc not manually set or is 0
	if ($form_data['price'] > 0 && $form_data['price_ttc'] <= 0) {
		$form_data['price_ttc'] = round($form_data['price'] * (1 + $form_data['tva_tx'] / 100), 2);
	}

	// No errors -> save
	if (empty($errors)) {
		$db->begin();
		$save_ok = true;

		if ($is_edit) {
			// UPDATE
			$sql = "UPDATE ".$prefix."product SET";
			$sql .= " ref = '".$db->escape($form_data['ref'])."'";
			$sql .= ", label = '".$db->escape($form_data['label'])."'";
			$sql .= ", description = '".$db->escape($form_data['description'])."'";
			$sql .= ", price = ".((float) $form_data['price']);
			$sql .= ", tva_tx = ".((float) $form_data['tva_tx']);
			$sql .= ", price_ttc = ".((float) $form_data['price_ttc']);
			$sql .= ", tosell = ".((int) $form_data['tosell']);
			$sql .= ", tobuy = ".((int) $form_data['tosell']);
			$sql .= ", stock = ".((int) $form_data['stock']);
			$sql .= ", weight = ".((float) $form_data['weight']);
			$sql .= ", weight_units = ".((int) $form_data['weight_units']);
			$sql .= ", tms = NOW()";
			$sql .= " WHERE rowid = ".(int) $product->rowid;
			$sql .= " AND entity = ".$entity;

			if (!$db->query($sql)) {
				$save_ok = false;
			}

			$product_id = (int) $product->rowid;
		} else {
			// INSERT
			$sql = "INSERT INTO ".$prefix."product";
			$sql .= " (ref, label, description, price, tva_tx, price_ttc, tosell, tobuy,";
			$sql .= " stock, weight, weight_units, fk_product_type, entity, datec, tms)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($form_data['ref'])."'";
			$sql .= ", '".$db->escape($form_data['label'])."'";
			$sql .= ", '".$db->escape($form_data['description'])."'";
			$sql .= ", ".((float) $form_data['price']);
			$sql .= ", ".((float) $form_data['tva_tx']);
			$sql .= ", ".((float) $form_data['price_ttc']);
			$sql .= ", ".((int) $form_data['tosell']);
			$sql .= ", ".((int) $form_data['tosell']);
			$sql .= ", ".((int) $form_data['stock']);
			$sql .= ", ".((float) $form_data['weight']);
			$sql .= ", ".((int) $form_data['weight_units']);
			$sql .= ", 0";  // fk_product_type = 0 (product)
			$sql .= ", ".$entity;
			$sql .= ", NOW()";
			$sql .= ", NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				$product_id = (int) $db->last_insert_id($prefix.'product');
			} else {
				$save_ok = false;
				$product_id = 0;
			}
		}

		// Update category link
		if ($save_ok && $product_id > 0) {
			// Remove existing category links for this product
			$db->query("DELETE FROM ".$prefix."categorie_product WHERE fk_product = ".$product_id);

			// Insert new category link if selected
			if ($form_data['fk_categorie'] > 0) {
				$sql_link = "INSERT INTO ".$prefix."categorie_product (fk_categorie, fk_product)";
				$sql_link .= " VALUES (".$form_data['fk_categorie'].", ".$product_id.")";
				if (!$db->query($sql_link)) {
					// Non-fatal: log but continue
				}
			}
		}

		// Save SEO extrafields
		if ($save_ok && $product_id > 0 && ($form_data['meta_title'] !== '' || $form_data['meta_description'] !== '')) {
			// Check if extrafields row exists
			$sql_ef_check = "SELECT rowid FROM ".$prefix."product_extrafields WHERE fk_object = ".$product_id;
			$res_ef = $db->query($sql_ef_check);
			$ef_exists = ($res_ef && $db->num_rows($res_ef) > 0);

			// Only save if the extrafield columns exist (graceful handling)
			if ($ef_exists) {
				$sql_ef = "UPDATE ".$prefix."product_extrafields SET";
				$sql_ef .= " spacart_meta_title = '".$db->escape($form_data['meta_title'])."'";
				$sql_ef .= ", spacart_meta_description = '".$db->escape($form_data['meta_description'])."'";
				$sql_ef .= " WHERE fk_object = ".$product_id;
				@$db->query($sql_ef);
			} else {
				$sql_ef = "INSERT INTO ".$prefix."product_extrafields (fk_object, spacart_meta_title, spacart_meta_description)";
				$sql_ef .= " VALUES (".$product_id.", '".$db->escape($form_data['meta_title'])."', '".$db->escape($form_data['meta_description'])."')";
				@$db->query($sql_ef);
			}
		}

		if ($save_ok) {
			$db->commit();
			$msg = $is_edit ? 'Produit mis a jour avec succes.' : 'Produit cree avec succes.';
			spacartAdminFlash($msg, 'success');
			header('Location: ?page=product_edit&id='.$product_id);
			exit;
		} else {
			$db->rollback();
			$errors[] = 'Erreur lors de la sauvegarde en base de donnees.';
		}
	}
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<h1 class="h3 mb-2 mb-md-0">
		<i class="bi bi-<?php echo $is_edit ? 'pencil-square' : 'plus-circle'; ?> me-2"></i>
		<?php echo spacartAdminEscape($page_title); ?>
	</h1>
	<a href="?page=products" class="btn btn-outline-secondary" aria-label="Retour aux produits">
		<i class="bi bi-arrow-left me-1"></i>Retour aux produits
	</a>
</div>

<!-- Error messages -->
<?php if (!empty($errors)): ?>
	<div class="alert alert-danger alert-dismissible fade show" role="alert">
		<i class="bi bi-exclamation-triangle me-2"></i>
		<strong>Erreur(s) :</strong>
		<ul class="mb-0 mt-1">
			<?php foreach ($errors as $err): ?>
				<li><?php echo spacartAdminEscape($err); ?></li>
			<?php endforeach; ?>
		</ul>
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
	</div>
<?php endif; ?>

<form method="post" id="productForm" class="track-changes">
	<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">

	<div class="row">
		<!-- Left column: main form -->
		<div class="col-lg-8">

			<!-- Section 1: Informations generales -->
			<div class="admin-card mb-4">
				<div class="form-section">
					<div class="form-section-title">
						<i class="bi bi-info-circle me-2"></i>Informations generales
					</div>
					<div class="form-section-body">
						<!-- Ref -->
						<div class="form-group mb-3">
							<label class="form-label" for="field-ref">Reference <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="field-ref" name="ref" value="<?php echo spacartAdminEscape($form_data['ref']); ?>" required maxlength="128" placeholder="ex: PROD-001">
						</div>

						<!-- Label -->
						<div class="form-group mb-3">
							<label class="form-label" for="field-label">Libelle <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="field-label" name="label" value="<?php echo spacartAdminEscape($form_data['label']); ?>" required maxlength="255" placeholder="Nom du produit">
						</div>

						<!-- Description -->
						<div class="form-group mb-3">
							<label class="form-label" for="field-description">Description</label>
							<textarea class="form-control" id="field-description" name="description" rows="5" placeholder="Description du produit..."><?php echo spacartAdminEscape($form_data['description']); ?></textarea>
						</div>

						<!-- Price row -->
						<div class="row">
							<!-- Price HT -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-price">Prix HT</label>
									<div class="input-group">
										<input type="number" class="form-control" id="field-price" name="price" value="<?php echo ($form_data['price'] !== '' ? spacartAdminEscape($form_data['price']) : ''); ?>" step="0.01" min="0" placeholder="0.00">
										<span class="input-group-text">&euro;</span>
									</div>
								</div>
							</div>

							<!-- TVA rate -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-tva">Taux TVA</label>
									<select class="form-select" id="field-tva" name="tva_tx">
										<option value="0" <?php echo ((float) $form_data['tva_tx'] == 0) ? 'selected' : ''; ?>>0%</option>
										<option value="5.5" <?php echo ((float) $form_data['tva_tx'] == 5.5) ? 'selected' : ''; ?>>5,5%</option>
										<option value="10" <?php echo ((float) $form_data['tva_tx'] == 10) ? 'selected' : ''; ?>>10%</option>
										<option value="20" <?php echo ((float) $form_data['tva_tx'] == 20) ? 'selected' : ''; ?>>20%</option>
									</select>
								</div>
							</div>

							<!-- Price TTC -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-price-ttc">Prix TTC</label>
									<div class="input-group">
										<input type="number" class="form-control" id="field-price-ttc" name="price_ttc" value="<?php echo ($form_data['price_ttc'] !== '' ? spacartAdminEscape($form_data['price_ttc']) : ''); ?>" step="0.01" min="0" placeholder="Auto">
										<span class="input-group-text">&euro;</span>
									</div>
									<small class="form-text text-muted">Laissez vide pour calcul automatique</small>
								</div>
							</div>
						</div>

						<!-- Status toggle -->
						<div class="form-group">
							<label class="form-label d-block">Statut</label>
							<div class="form-check form-switch">
								<input class="form-check-input toggle-switch" type="checkbox" id="field-tosell" name="tosell" value="1" <?php echo ((int) $form_data['tosell'] === 1) ? 'checked' : ''; ?>>
								<label class="form-check-label" for="field-tosell" id="tosell-label">
									<?php echo ((int) $form_data['tosell'] === 1) ? 'En vente' : 'Hors vente'; ?>
								</label>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Section 2: Stock & Poids -->
			<div class="admin-card mb-4">
				<div class="form-section">
					<div class="form-section-title">
						<i class="bi bi-boxes me-2"></i>Stock &amp; Poids
					</div>
					<div class="form-section-body">
						<div class="row">
							<!-- Stock -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-stock">Stock</label>
									<input type="number" class="form-control" id="field-stock" name="stock" value="<?php echo (int) $form_data['stock']; ?>" min="0" step="1">
								</div>
							</div>

							<!-- Weight -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-weight">Poids</label>
									<input type="number" class="form-control" id="field-weight" name="weight" value="<?php echo ($form_data['weight'] !== '' ? spacartAdminEscape($form_data['weight']) : ''); ?>" step="0.001" min="0" placeholder="0.000">
								</div>
							</div>

							<!-- Weight unit -->
							<div class="col-md-4 mb-3">
								<div class="form-group">
									<label class="form-label" for="field-weight-unit">Unite</label>
									<select class="form-select" id="field-weight-unit" name="weight_units">
										<option value="0" <?php echo ((int) $form_data['weight_units'] === 0) ? 'selected' : ''; ?>>kg</option>
										<option value="-3" <?php echo ((int) $form_data['weight_units'] === -3) ? 'selected' : ''; ?>>g</option>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Section 4: SEO -->
			<div class="admin-card mb-4">
				<div class="form-section">
					<div class="form-section-title">
						<i class="bi bi-search me-2"></i>SEO
					</div>
					<div class="form-section-body">
						<div class="form-group mb-3">
							<label class="form-label" for="field-meta-title">Meta titre</label>
							<input type="text" class="form-control" id="field-meta-title" name="meta_title" value="<?php echo spacartAdminEscape($form_data['meta_title']); ?>" maxlength="255" placeholder="Titre pour les moteurs de recherche">
							<small class="form-text text-muted">Recommande : 50 a 60 caracteres</small>
						</div>
						<div class="form-group">
							<label class="form-label" for="field-meta-desc">Meta description</label>
							<textarea class="form-control" id="field-meta-desc" name="meta_description" rows="3" maxlength="500" placeholder="Description pour les moteurs de recherche"><?php echo spacartAdminEscape($form_data['meta_description']); ?></textarea>
							<small class="form-text text-muted">Recommande : 150 a 160 caracteres</small>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Right column: sidebar panels -->
		<div class="col-lg-4">

			<!-- Actions panel -->
			<div class="admin-card mb-4">
				<div class="form-section">
					<div class="form-section-title">
						<i class="bi bi-lightning me-2"></i>Actions
					</div>
					<div class="form-section-body">
						<div class="d-grid gap-2">
							<button type="submit" class="btn btn-primary">
								<i class="bi bi-check-lg me-1"></i>
								<?php echo $is_edit ? 'Enregistrer les modifications' : 'Creer le produit'; ?>
							</button>
							<a href="?page=products" class="btn btn-outline-secondary">
								<i class="bi bi-x-lg me-1"></i>Annuler
							</a>
						</div>
						<?php if ($is_edit): ?>
							<hr>
							<small class="text-muted">
								ID : <?php echo (int) $product->rowid; ?><br>
								Cree le : <?php echo spacartAdminFormatDate($product->datec ?? ''); ?><br>
								Modifie le : <?php echo spacartAdminFormatDate($product->tms ?? ''); ?>
							</small>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Section 3: Categorie -->
			<div class="admin-card mb-4">
				<div class="form-section">
					<div class="form-section-title">
						<i class="bi bi-tag me-2"></i>Categorie
					</div>
					<div class="form-section-body">
						<div class="form-group">
							<label class="form-label" for="field-category">Categorie produit</label>
							<select class="form-select" id="field-category" name="fk_categorie">
								<option value="0">-- Aucune --</option>
								<?php foreach ($categories as $cat): ?>
									<option value="<?php echo (int) $cat->rowid; ?>" <?php echo ($form_data['fk_categorie'] === (int) $cat->rowid) ? 'selected' : ''; ?>>
										<?php echo spacartAdminEscape($cat->label); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<!-- Quick info (edit mode) -->
			<?php if ($is_edit): ?>
				<div class="admin-card mb-4">
					<div class="form-section">
						<div class="form-section-title">
							<i class="bi bi-link-45deg me-2"></i>Liens rapides
						</div>
						<div class="form-section-body">
							<a href="<?php echo DOL_URL_ROOT; ?>/product/card.php?id=<?php echo (int) $product->rowid; ?>" target="_blank" class="btn btn-sm btn-outline-info w-100 mb-2">
								<i class="bi bi-box-arrow-up-right me-1"></i>Voir dans Dolibarr
							</a>
						</div>
					</div>
				</div>
			<?php endif; ?>
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

<!-- Auto-calculate Price TTC and toggle label JS -->
<script>
(function() {
	'use strict';

	var priceHT  = document.getElementById('field-price');
	var tvaSel   = document.getElementById('field-tva');
	var priceTTC = document.getElementById('field-price-ttc');
	var tosell   = document.getElementById('field-tosell');
	var tosellLabel = document.getElementById('tosell-label');

	function calcTTC() {
		var ht = parseFloat(priceHT.value);
		var tva = parseFloat(tvaSel.value);
		if (!isNaN(ht) && ht > 0) {
			var ttc = ht * (1 + tva / 100);
			priceTTC.value = ttc.toFixed(2);
		}
	}

	if (priceHT && tvaSel && priceTTC) {
		priceHT.addEventListener('input', calcTTC);
		tvaSel.addEventListener('change', calcTTC);
	}

	if (tosell && tosellLabel) {
		tosell.addEventListener('change', function() {
			tosellLabel.textContent = tosell.checked ? 'En vente' : 'Hors vente';
		});
	}
})();
</script>

<?php
include __DIR__.'/../includes/footer.php';
