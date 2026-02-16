<?php
/**
 * SpaCart Backoffice - Currencies
 *
 * Manage currencies and exchange rates for the storefront.
 * Reads/writes Dolibarr llx_multicurrency tables. Uses currencies SQL VIEW.
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Devises';
$current_page = 'currencies';

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// Get the shop base currency
$base_currency = getDolGlobalString('SPACART_CURRENCY', 'EUR');

// Get base currency Dolibarr rate (vs MAIN_MONNAIE, typically USD)
$sql_base = "SELECT mr.rate FROM ".$prefix."multicurrency mc JOIN ".$prefix."multicurrency_rate mr ON mr.fk_multicurrency=mc.rowid WHERE mc.code='".$db->escape($base_currency)."' AND mc.entity=".$entity." LIMIT 1";
$res_base = $db->query($sql_base);
$obj_base = $db->fetch_object($res_base);
$base_doli_rate = $obj_base ? floatval($obj_base->rate) : 1;

// =====================================================================
// POST actions (CSRF-protected)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new currency ---
	if ($action === 'add_currency') {
		$code       = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
		$symbol     = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
		$rate       = isset($_POST['rate']) ? (float) $_POST['rate'] : 0;
		$is_default = !empty($_POST['is_default']) ? 1 : 0;

		$errors = array();
		if (!preg_match('/^[A-Z]{3}$/', $code)) {
			$errors[] = 'Le code devise doit contenir exactement 3 lettres majuscules (ex: EUR, USD, GBP).';
		}
		if ($symbol === '') {
			$errors[] = 'Le symbole est obligatoire.';
		}
		if ($rate <= 0) {
			$errors[] = 'Le taux de change doit etre superieur a 0.';
		}

		if (!empty($errors)) {
			foreach ($errors as $err) spacartAdminFlash($err, 'warning');
		} else {
			$sql_dup = "SELECT rowid FROM ".$prefix."multicurrency WHERE code = '".$db->escape($code)."' AND entity=".$entity;
			$res_dup = $db->query($sql_dup);
			if ($res_dup && $db->num_rows($res_dup) > 0) {
				spacartAdminFlash('Une devise avec le code "'.$code.'" existe deja.', 'warning');
			} else {
				if ($is_default) { $rate = 1.0; }
				$sql2 = "SELECT label FROM ".$prefix."c_currencies WHERE code_iso='".$db->escape($code)."'";
				$res2 = $db->query($sql2);
				$obj2 = $db->fetch_object($res2);
				$name = $obj2 ? $obj2->label : $code;

				$sql = "INSERT INTO ".$prefix."multicurrency (code, name, entity, date_create) VALUES ('".$db->escape($code)."', '".$db->escape($name)."', ".$entity.", NOW())";
				$db->query($sql);
				$newid = $db->last_insert_id($prefix.'multicurrency');

				if ($newid) {
					$dolibarr_rate = $rate * $base_doli_rate;
					$db->query("INSERT INTO ".$prefix."multicurrency_rate (fk_multicurrency, rate, date_sync, entity) VALUES (".$newid.", ".$dolibarr_rate.", NOW(), ".$entity.")");
					if ($symbol) {
						$db->query("INSERT INTO spacart_currency_symbols (code, symbol) VALUES ('".$db->escape($code)."', '".$db->escape($symbol)."') ON DUPLICATE KEY UPDATE symbol='".$db->escape($symbol)."'");
					}
					if ($is_default) {
						dolibarr_set_const($db, 'SPACART_CURRENCY', $code, 'chaine', 0, '', $entity);
					}
					spacartAdminFlash('Devise "'.$code.'" ajoutee avec succes.', 'success');
				} else {
					spacartAdminFlash('Erreur lors de la creation de la devise.', 'danger');
				}
			}
		}
		header('Location: ?page=currencies');
		exit;
	}

	// --- Edit currency ---
	if ($action === 'edit_currency') {
		$edit_id    = (int) ($_POST['currency_id'] ?? 0);
		$code       = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
		$symbol     = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
		$rate       = isset($_POST['rate']) ? (float) $_POST['rate'] : 0;
		$is_default = !empty($_POST['is_default']) ? 1 : 0;

		$errors = array();
		if ($edit_id <= 0) $errors[] = 'ID invalide.';
		if (!preg_match('/^[A-Z]{3}$/', $code)) $errors[] = 'Le code devise doit contenir exactement 3 lettres majuscules.';
		if ($symbol === '') $errors[] = 'Le symbole est obligatoire.';
		if ($rate <= 0) $errors[] = 'Le taux de change doit etre superieur a 0.';

		if (!empty($errors)) {
			foreach ($errors as $err) spacartAdminFlash($err, 'warning');
		} else {
			$sql_dup = "SELECT rowid FROM ".$prefix."multicurrency WHERE code = '".$db->escape($code)."' AND rowid != ".$edit_id." AND entity=".$entity;
			$res_dup = $db->query($sql_dup);
			if ($res_dup && $db->num_rows($res_dup) > 0) {
				spacartAdminFlash('Une autre devise avec le code "'.$code.'" existe deja.', 'warning');
			} else {
				if ($is_default) {
					$rate = 1.0;
					dolibarr_set_const($db, 'SPACART_CURRENCY', $code, 'chaine', 0, '', $entity);
				}
				$dolibarr_rate = $rate * $base_doli_rate;
				$db->query("UPDATE ".$prefix."multicurrency SET code='".$db->escape($code)."' WHERE rowid=".$edit_id." AND entity=".$entity);
				$db->query("UPDATE ".$prefix."multicurrency_rate SET rate=".$dolibarr_rate.", date_sync=NOW() WHERE fk_multicurrency=".$edit_id." AND entity=".$entity);
				if ($symbol) {
					$db->query("INSERT INTO spacart_currency_symbols (code, symbol) VALUES ('".$db->escape($code)."', '".$db->escape($symbol)."') ON DUPLICATE KEY UPDATE symbol='".$db->escape($symbol)."'");
				}
				spacartAdminFlash('Devise modifiee avec succes.', 'success');
			}
		}
		header('Location: ?page=currencies');
		exit;
	}

	// --- Delete currency ---
	if ($action === 'delete_currency') {
		$del_id = (int) ($_POST['currency_id'] ?? 0);
		if ($del_id > 0) {
			$sql_def = "SELECT code FROM ".$prefix."multicurrency WHERE rowid = ".$del_id." AND entity=".$entity;
			$res_def = $db->query($sql_def);
			$obj_def = $db->fetch_object($res_def);
			if ($obj_def && $obj_def->code === $base_currency) {
				spacartAdminFlash('Impossible de supprimer la devise par defaut.', 'danger');
			} else {
				$db->query("DELETE FROM ".$prefix."multicurrency_rate WHERE fk_multicurrency=".$del_id." AND entity=".$entity);
				$db->query("DELETE FROM ".$prefix."multicurrency WHERE rowid=".$del_id." AND entity=".$entity);
				spacartAdminFlash('Devise supprimee.', 'success');
			}
		}
		header('Location: ?page=currencies');
		exit;
	}

	// --- Toggle active ---
	if ($action === 'toggle_active') {
		spacartAdminFlash('Les devises Dolibarr sont toujours actives. Supprimez la devise pour la desactiver.', 'warning');
		header('Location: ?page=currencies');
		exit;
	}

	// --- Set default ---
	if ($action === 'set_default') {
		$def_id = (int) ($_POST['currency_id'] ?? 0);
		if ($def_id > 0) {
			$sql = "SELECT code FROM ".$prefix."multicurrency WHERE rowid=".$def_id." AND entity=".$entity;
			$res = $db->query($sql);
			$obj = $db->fetch_object($res);
			if ($obj) {
				dolibarr_set_const($db, 'SPACART_CURRENCY', $obj->code, 'chaine', 0, '', $entity);
				spacartAdminFlash('Devise par defaut mise a jour: '.$obj->code.'. Son taux sera 1.0 dans la boutique.', 'success');
			}
		}
		header('Location: ?page=currencies');
		exit;
	}
}

// =====================================================================
// Filters from GET
// =====================================================================
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';
$pg       = isset($_GET['pg']) ? (int) $_GET['pg'] : 1;
$per_page = 20;
$edit_id  = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

// =====================================================================
// Fetch currencies from the VIEW
// =====================================================================
$currencies = array();
$sql = "SELECT c.id as rowid, c.code, c.symbol, c.rate, 1 as active, ";
$sql .= "CASE WHEN c.`main` = 1 THEN 1 ELSE 0 END as is_default, ";
$sql .= "mr.date_sync as date_creation, mr.date_sync as tms ";
$sql .= "FROM currencies c ";
$sql .= "JOIN ".$prefix."multicurrency m ON m.rowid = c.id ";
$sql .= "LEFT JOIN ".$prefix."multicurrency_rate mr ON mr.fk_multicurrency = m.rowid ";
$sql .= "WHERE m.entity = ".$entity;

if ($search !== '') {
	$s = $db->escape($search);
	$sql .= " AND (c.code LIKE '%".$s."%' OR c.symbol LIKE '%".$s."%')";
}

$sql .= " ORDER BY c.`main` DESC, c.code ASC";

$resql = $db->query($sql);
$total = 0;
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$currencies[] = $obj;
		$total++;
	}
}

$pagination = array(
	'total' => $total,
	'total_pages' => max(1, ceil($total / $per_page)),
	'current_page' => $pg,
	'limit' => $per_page,
	'offset' => ($pg - 1) * $per_page,
);

// Currency being edited
$edit_cur = null;
if ($edit_id > 0) {
	$sql_edit = "SELECT c.id as rowid, c.code, c.symbol, c.rate, 1 as active, ";
	$sql_edit .= "CASE WHEN c.`main` = 1 THEN 1 ELSE 0 END as is_default ";
	$sql_edit .= "FROM currencies c WHERE c.id = ".$edit_id;
	$resql_edit = $db->query($sql_edit);
	if ($resql_edit) {
		$edit_cur = $db->fetch_object($resql_edit);
	}
}

$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<h1 class="h3 mb-2 mb-md-0"><i class="bi bi-currency-exchange me-2"></i>Devises</h1>
	<div class="d-flex gap-2">
		<span class="btn btn-outline-secondary" title="Les taux sont synchronises automatiquement via le cron ECB">
			<i class="bi bi-arrow-repeat me-1"></i>Source: Dolibarr MultiDevise
		</span>
		<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addCurrencyForm">
			<i class="bi bi-plus-lg me-1"></i>Ajouter une devise
		</button>
	</div>
</div>

<!-- Info box about base rate -->
<div class="alert alert-info d-flex align-items-center mb-4" role="alert">
	<i class="bi bi-info-circle me-2"></i>
	<div>
		Les devises sont synchronisees avec le module <strong>MultiDevise</strong> de Dolibarr.
		La devise de base (<strong><?php echo spacartAdminEscape($base_currency); ?></strong>) a un taux de <strong>1.0</strong>.
		Les taux sont mis a jour automatiquement via la BCE (ECB).
	</div>
</div>

<!-- Add Currency Form (collapsible) -->
<div class="collapse mb-4" id="addCurrencyForm">
	<div class="admin-card">
		<div class="card-header">
			<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nouvelle devise</h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_currency">
				<div class="row g-3">
					<div class="col-md-2">
						<label class="form-label" for="add-code">Code <span class="text-danger">*</span></label>
						<input type="text" class="form-control text-uppercase" id="add-code" name="code" required maxlength="3" minlength="3" pattern="[A-Za-z]{3}" placeholder="EUR">
						<div class="form-text">3 lettres (ISO 4217)</div>
					</div>
					<div class="col-md-2">
						<label class="form-label" for="add-symbol">Symbole <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="add-symbol" name="symbol" required maxlength="10" placeholder="&euro;">
					</div>
					<div class="col-md-3">
						<label class="form-label" for="add-rate">Taux de change <span class="text-danger">*</span></label>
						<input type="number" class="form-control" id="add-rate" name="rate" required step="0.000001" min="0.000001" value="1.000000" placeholder="1.000000">
						<div class="form-text">Relatif a la devise de base (<?php echo spacartAdminEscape($base_currency); ?>)</div>
					</div>
					
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="add-default" name="is_default" value="1">
							<label class="form-check-label" for="add-default">Par defaut</label>
						</div>
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-success">
						<i class="bi bi-check-lg me-1"></i>Creer
					</button>
					<button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#addCurrencyForm">
						Annuler
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Edit Currency Form (shown when edit=ID) -->
<?php if ($edit_cur): ?>
<div class="mb-4">
	<div class="admin-card border-primary">
		<div class="card-header bg-primary bg-opacity-10">
			<h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Modifier la devise #<?php echo (int) $edit_id; ?></h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="edit_currency">
				<input type="hidden" name="currency_id" value="<?php echo (int) $edit_id; ?>">
				<div class="row g-3">
					<div class="col-md-2">
						<label class="form-label" for="edit-code">Code <span class="text-danger">*</span></label>
						<input type="text" class="form-control text-uppercase" id="edit-code" name="code" required maxlength="3" minlength="3" pattern="[A-Za-z]{3}" value="<?php echo spacartAdminEscape($edit_cur->code); ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label" for="edit-symbol">Symbole <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit-symbol" name="symbol" required maxlength="10" value="<?php echo spacartAdminEscape($edit_cur->symbol); ?>">
					</div>
					<div class="col-md-3">
						<label class="form-label" for="edit-rate">Taux de change <span class="text-danger">*</span></label>
						<input type="number" class="form-control" id="edit-rate" name="rate" required step="0.000001" min="0.000001" value="<?php echo number_format((float) $edit_cur->rate, 6, '.', ''); ?>">
					</div>
					
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="edit-default" name="is_default" value="1" <?php echo ((int) $edit_cur->is_default === 1) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="edit-default">Par defaut</label>
						</div>
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Enregistrer
					</button>
					<a href="?page=currencies" class="btn btn-outline-secondary ms-2">Annuler</a>
				</div>
			</form>
		</div>
	</div>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="filter-bar">
			<input type="hidden" name="page" value="currencies">
			<div class="row g-3 align-items-end">
				<div class="col-md-6 col-lg-4">
					<label class="form-label" for="filter-search">Recherche</label>
					<input type="text" class="form-control" id="filter-search" name="search" value="<?php echo spacartAdminEscape($search); ?>" placeholder="Code, symbole...">
				</div>
				<div class="col-md-3 col-lg-2">
					<button type="submit" class="btn btn-outline-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>
				<?php if ($search !== ''): ?>
					<div class="col-md-3 col-lg-2">
						<a href="?page=currencies" class="btn btn-outline-secondary w-100">
							<i class="bi bi-x-lg me-1"></i>Effacer
						</a>
					</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- Currencies Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width: 60px;">ID</th>
						<th>Code</th>
						<th>Symbole</th>
						<th>Taux de change</th>
						<th class="text-center">Active</th>
						<th class="text-center">Par defaut</th>
						<th>Derniere MAJ</th>
						<th class="text-center" style="width: 180px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($currencies)): ?>
						<tr>
							<td colspan="8">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-currency-exchange"></i></div>
									<p>Aucune devise trouvee</p>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($currencies as $cur): ?>
							<tr>
								<td><strong><?php echo (int) $cur->rowid; ?></strong></td>
								<td><code class="fw-bold"><?php echo spacartAdminEscape($cur->code); ?></code></td>
								<td><span class="fs-5"><?php echo spacartAdminEscape($cur->symbol); ?></span></td>
								<td>
									<?php
									$rate_val = (float) $cur->rate;
									echo number_format($rate_val, 4, '.', ' ');
									if ((int) $cur->is_default === 1) {
										echo ' <span class="text-muted small">(base)</span>';
									}
									?>
								</td>
								<td class="text-center">
									<span class="btn btn-sm btn-success" title="Toujours active (geree par Dolibarr)">
										<i class="bi bi-check-circle"></i> Oui
									</span>
								</td>
								<td class="text-center">
									<?php if ((int) $cur->is_default === 1): ?>
										<span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i>Defaut</span>
									<?php else: ?>
										<form method="post" class="d-inline">
											<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
											<input type="hidden" name="action" value="set_default">
											<input type="hidden" name="currency_id" value="<?php echo (int) $cur->rowid; ?>">
											<button type="submit" class="btn btn-sm btn-outline-secondary" title="Definir comme devise par defaut (taux = 1.0)" aria-label="Definir comme devise par defaut">
												<i class="bi bi-star"></i>
											</button>
										</form>
									<?php endif; ?>
								</td>
								<td><?php echo spacartAdminFormatDate($cur->tms ?? $cur->date_creation); ?></td>
								<td class="text-center">
									<div class="d-flex justify-content-center gap-1">
										<!-- Edit -->
										<a href="?page=currencies&edit=<?php echo (int) $cur->rowid; ?>" class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la devise">
											<i class="bi bi-pencil"></i>
										</a>
										<!-- Delete -->
										<?php if ((int) $cur->is_default !== 1): ?>
											<form method="post" class="d-inline">
												<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
												<input type="hidden" name="action" value="delete_currency">
												<input type="hidden" name="currency_id" value="<?php echo (int) $cur->rowid; ?>">
												<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la devise"
														data-confirm="Supprimer cette devise ?">
													<i class="bi bi-trash"></i>
												</button>
											</form>
										<?php else: ?>
											<button type="button" class="btn btn-sm btn-outline-danger" disabled title="Impossible de supprimer la devise par defaut" aria-label="Impossible de supprimer la devise par defaut">
												<i class="bi bi-trash"></i>
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Pagination -->
	<?php if ($pagination['total_pages'] > 1): ?>
		<div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
			<small class="text-muted">
				<?php echo $pagination['total']; ?> devise(s) -
				Page <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?>
			</small>
			<nav aria-label="Pagination devises">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_url = '?page=currencies';
					if ($search !== '') {
						$base_url .= '&search='.urlencode($search);
					}
					?>
					<!-- Previous -->
					<li class="page-item <?php echo ($pagination['current_page'] <= 1) ? 'disabled' : ''; ?>">
						<a class="page-link" href="<?php echo $base_url.'&pg='.($pagination['current_page'] - 1); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>
					<?php
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];
					$start = max(1, $cp - 2);
					$end = min($total_pages, $cp + 2);

					if ($start > 1) {
						echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&pg=1">1</a></li>';
						if ($start > 2) {
							echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
						}
					}

					for ($i = $start; $i <= $end; $i++) {
						$active = ($i === $cp) ? ' active' : '';
						echo '<li class="page-item'.$active.'"><a class="page-link" href="'.$base_url.'&pg='.$i.'">'.$i.'</a></li>';
					}

					if ($end < $total_pages) {
						if ($end < $total_pages - 1) {
							echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
						}
						echo '<li class="page-item"><a class="page-link" href="'.$base_url.'&pg='.$total_pages.'">'.$total_pages.'</a></li>';
					}
					?>
					<!-- Next -->
					<li class="page-item <?php echo ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : ''; ?>">
						<a class="page-link" href="<?php echo $base_url.'&pg='.($pagination['current_page'] + 1); ?>" aria-label="Suivant">
							<i class="bi bi-chevron-right"></i>
						</a>
					</li>
				</ul>
			</nav>
		</div>
	<?php endif; ?>
</div>

<?php
include __DIR__.'/../includes/footer.php';
