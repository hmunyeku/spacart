<?php
/**
 * SpaCart Backoffice - Languages
 *
 * Manage active languages for the storefront.
 * Table: llx_spacart_language (auto-created if missing).
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Langues';
$current_page = 'languages';

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// =====================================================================
// Ensure table exists
// =====================================================================
$db->query("CREATE TABLE IF NOT EXISTS ".$prefix."spacart_language (
	rowid        INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,
	code         VARCHAR(10) NOT NULL,
	label        VARCHAR(100) NOT NULL,
	flag_icon    VARCHAR(10) NOT NULL DEFAULT '',
	active       TINYINT(1) NOT NULL DEFAULT 1,
	is_default   TINYINT(1) NOT NULL DEFAULT 0,
	date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Pre-seed with fr_FR and en_US if table is empty
$sql_check = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_language";
$res_check = $db->query($sql_check);
$obj_check = $db->fetch_object($res_check);
if ((int) $obj_check->nb === 0) {
	$db->query("INSERT INTO ".$prefix."spacart_language (code, label, flag_icon, active, is_default, date_creation) VALUES ('fr_FR', 'Francais', 'fr', 1, 1, NOW())");
	$db->query("INSERT INTO ".$prefix."spacart_language (code, label, flag_icon, active, is_default, date_creation) VALUES ('en_US', 'English', 'us', 1, 0, NOW())");
}

// =====================================================================
// POST actions (CSRF-protected)
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new language ---
	if ($action === 'add_language') {
		$code      = isset($_POST['code']) ? trim($_POST['code']) : '';
		$label     = isset($_POST['label']) ? trim($_POST['label']) : '';
		$flag_icon = isset($_POST['flag_icon']) ? trim($_POST['flag_icon']) : '';
		$active    = !empty($_POST['active']) ? 1 : 0;
		$is_default = !empty($_POST['is_default']) ? 1 : 0;

		if ($code === '' || $label === '') {
			spacartAdminFlash('Le code et le libelle sont obligatoires.', 'warning');
		} else {
			// Check uniqueness
			$sql_dup = "SELECT rowid FROM ".$prefix."spacart_language WHERE code = '".$db->escape($code)."'";
			$res_dup = $db->query($sql_dup);
			if ($res_dup && $db->num_rows($res_dup) > 0) {
				spacartAdminFlash('Une langue avec le code "'.$code.'" existe deja.', 'warning');
			} else {
				// If setting as default, unset previous default
				if ($is_default) {
					$db->query("UPDATE ".$prefix."spacart_language SET is_default = 0 WHERE is_default = 1");
					$active = 1; // Default must be active
				}
				$sql = "INSERT INTO ".$prefix."spacart_language (code, label, flag_icon, active, is_default, date_creation)";
				$sql .= " VALUES ('".$db->escape($code)."', '".$db->escape($label)."', '".$db->escape($flag_icon)."', ".$active.", ".$is_default.", NOW())";
				$resql = $db->query($sql);
				if ($resql) {
					spacartAdminFlash('Langue "'.$label.'" ajoutee avec succes.', 'success');
				} else {
					spacartAdminFlash('Erreur lors de la creation de la langue.', 'danger');
				}
			}
		}
		header('Location: ?page=languages');
		exit;
	}

	// --- Edit language ---
	if ($action === 'edit_language') {
		$edit_id   = (int) ($_POST['language_id'] ?? 0);
		$code      = isset($_POST['code']) ? trim($_POST['code']) : '';
		$label     = isset($_POST['label']) ? trim($_POST['label']) : '';
		$flag_icon = isset($_POST['flag_icon']) ? trim($_POST['flag_icon']) : '';
		$active    = !empty($_POST['active']) ? 1 : 0;
		$is_default = !empty($_POST['is_default']) ? 1 : 0;

		if ($edit_id <= 0 || $code === '' || $label === '') {
			spacartAdminFlash('Donnees invalides pour la modification.', 'warning');
		} else {
			// Check uniqueness (exclude self)
			$sql_dup = "SELECT rowid FROM ".$prefix."spacart_language WHERE code = '".$db->escape($code)."' AND rowid != ".$edit_id;
			$res_dup = $db->query($sql_dup);
			if ($res_dup && $db->num_rows($res_dup) > 0) {
				spacartAdminFlash('Une autre langue avec le code "'.$code.'" existe deja.', 'warning');
			} else {
				// If setting as default, unset previous default
				if ($is_default) {
					$db->query("UPDATE ".$prefix."spacart_language SET is_default = 0 WHERE is_default = 1");
					$active = 1; // Default must be active
				}
				$sql = "UPDATE ".$prefix."spacart_language SET";
				$sql .= " code = '".$db->escape($code)."'";
				$sql .= ", label = '".$db->escape($label)."'";
				$sql .= ", flag_icon = '".$db->escape($flag_icon)."'";
				$sql .= ", active = ".$active;
				$sql .= ", is_default = ".$is_default;
				$sql .= " WHERE rowid = ".$edit_id;
				$resql = $db->query($sql);
				if ($resql) {
					spacartAdminFlash('Langue modifiee avec succes.', 'success');
				} else {
					spacartAdminFlash('Erreur lors de la modification.', 'danger');
				}
			}
		}
		header('Location: ?page=languages');
		exit;
	}

	// --- Delete language ---
	if ($action === 'delete_language') {
		$del_id = (int) ($_POST['language_id'] ?? 0);
		if ($del_id > 0) {
			// Check if it is the default
			$sql_def = "SELECT is_default FROM ".$prefix."spacart_language WHERE rowid = ".$del_id;
			$res_def = $db->query($sql_def);
			$obj_def = $db->fetch_object($res_def);
			if ($obj_def && (int) $obj_def->is_default === 1) {
				spacartAdminFlash('Impossible de supprimer la langue par defaut.', 'danger');
			} else {
				$sql = "DELETE FROM ".$prefix."spacart_language WHERE rowid = ".$del_id;
				$resql = $db->query($sql);
				if ($resql) {
					spacartAdminFlash('Langue supprimee.', 'success');
				} else {
					spacartAdminFlash('Erreur lors de la suppression.', 'danger');
				}
			}
		}
		header('Location: ?page=languages');
		exit;
	}

	// --- Toggle active ---
	if ($action === 'toggle_active') {
		$toggle_id = (int) ($_POST['language_id'] ?? 0);
		if ($toggle_id > 0) {
			// Cannot deactivate the default language
			$sql_def = "SELECT is_default, active FROM ".$prefix."spacart_language WHERE rowid = ".$toggle_id;
			$res_def = $db->query($sql_def);
			$obj_def = $db->fetch_object($res_def);
			if ($obj_def && (int) $obj_def->is_default === 1 && (int) $obj_def->active === 1) {
				spacartAdminFlash('Impossible de desactiver la langue par defaut.', 'warning');
			} else {
				$new_active = ((int) $obj_def->active === 1) ? 0 : 1;
				$db->query("UPDATE ".$prefix."spacart_language SET active = ".$new_active." WHERE rowid = ".$toggle_id);
				spacartAdminFlash('Statut de la langue mis a jour.', 'success');
			}
		}
		header('Location: ?page=languages');
		exit;
	}

	// --- Set default ---
	if ($action === 'set_default') {
		$def_id = (int) ($_POST['language_id'] ?? 0);
		if ($def_id > 0) {
			$db->query("UPDATE ".$prefix."spacart_language SET is_default = 0 WHERE is_default = 1");
			$db->query("UPDATE ".$prefix."spacart_language SET is_default = 1, active = 1 WHERE rowid = ".$def_id);
			spacartAdminFlash('Langue par defaut mise a jour.', 'success');
		}
		header('Location: ?page=languages');
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
// Build WHERE clause
// =====================================================================
$where = "1 = 1";

if ($search !== '') {
	$s = $db->escape($search);
	$where .= " AND (l.code LIKE '%".$s."%' OR l.label LIKE '%".$s."%')";
}

// =====================================================================
// Pagination
// =====================================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_language AS l WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// =====================================================================
// Fetch languages
// =====================================================================
$languages = array();
$sql = "SELECT l.rowid, l.code, l.label, l.flag_icon, l.active, l.is_default, l.date_creation";
$sql .= " FROM ".$prefix."spacart_language AS l";
$sql .= " WHERE ".$where;
$sql .= " ORDER BY l.is_default DESC, l.code ASC";
$sql .= " LIMIT ".$pagination['limit']." OFFSET ".$pagination['offset'];

$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$languages[] = $obj;
	}
}

// Count translation keys for each language
$lang_key_counts = array();
$spacart_root = realpath(__DIR__.'/../../');
foreach ($languages as $lang) {
	$lang_file = $spacart_root.'/langs/'.$lang->code.'/spacart.lang';
	$count = 0;
	if (file_exists($lang_file)) {
		$lines = file($lang_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line !== '' && $line[0] !== '#' && strpos($line, '=') !== false) {
				$count++;
			}
		}
	}
	$lang_key_counts[$lang->code] = $count;
}

// Language being edited
$edit_lang = null;
if ($edit_id > 0) {
	$sql_edit = "SELECT rowid, code, label, flag_icon, active, is_default FROM ".$prefix."spacart_language WHERE rowid = ".$edit_id;
	$resql_edit = $db->query($sql_edit);
	if ($resql_edit) {
		$edit_lang = $db->fetch_object($resql_edit);
	}
}

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

include __DIR__.'/../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<h1 class="h3 mb-2 mb-md-0"><i class="bi bi-translate me-2"></i>Langues</h1>
	<button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#addLanguageForm">
		<i class="bi bi-plus-lg me-1"></i>Ajouter une langue
	</button>
</div>

<!-- Add Language Form (collapsible) -->
<div class="collapse mb-4" id="addLanguageForm">
	<div class="admin-card">
		<div class="card-header">
			<h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nouvelle langue</h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="add_language">
				<div class="row g-3">
					<div class="col-md-3">
						<label class="form-label" for="add-code">Code <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="add-code" name="code" required maxlength="10" placeholder="fr_FR">
						<div class="form-text">Format: fr_FR, en_US, es_ES...</div>
					</div>
					<div class="col-md-3">
						<label class="form-label" for="add-label">Libelle <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="add-label" name="label" required maxlength="100" placeholder="Francais">
					</div>
					<div class="col-md-2">
						<label class="form-label" for="add-flag">Icone drapeau</label>
						<input type="text" class="form-control" id="add-flag" name="flag_icon" maxlength="10" placeholder="fr">
						<div class="form-text">Code pays (fr, us, es...)</div>
					</div>
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="add-active" name="active" value="1" checked>
							<label class="form-check-label" for="add-active">Active</label>
						</div>
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
					<button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="collapse" data-bs-target="#addLanguageForm">
						Annuler
					</button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Edit Language Form (shown when edit=ID) -->
<?php if ($edit_lang): ?>
<div class="mb-4">
	<div class="admin-card border-primary">
		<div class="card-header bg-primary bg-opacity-10">
			<h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Modifier la langue #<?php echo (int) $edit_id; ?></h5>
		</div>
		<div class="card-body">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
				<input type="hidden" name="action" value="edit_language">
				<input type="hidden" name="language_id" value="<?php echo (int) $edit_id; ?>">
				<div class="row g-3">
					<div class="col-md-3">
						<label class="form-label" for="edit-code">Code <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit-code" name="code" required maxlength="10" value="<?php echo spacartAdminEscape($edit_lang->code); ?>">
					</div>
					<div class="col-md-3">
						<label class="form-label" for="edit-label">Libelle <span class="text-danger">*</span></label>
						<input type="text" class="form-control" id="edit-label" name="label" required maxlength="100" value="<?php echo spacartAdminEscape($edit_lang->label); ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label" for="edit-flag">Icone drapeau</label>
						<input type="text" class="form-control" id="edit-flag" name="flag_icon" maxlength="10" value="<?php echo spacartAdminEscape($edit_lang->flag_icon); ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="edit-active" name="active" value="1" <?php echo ((int) $edit_lang->active === 1) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="edit-active">Active</label>
						</div>
					</div>
					<div class="col-md-2">
						<label class="form-label d-block">&nbsp;</label>
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="edit-default" name="is_default" value="1" <?php echo ((int) $edit_lang->is_default === 1) ? 'checked' : ''; ?>>
							<label class="form-check-label" for="edit-default">Par defaut</label>
						</div>
					</div>
				</div>
				<div class="mt-3">
					<button type="submit" class="btn btn-primary">
						<i class="bi bi-check-lg me-1"></i>Enregistrer
					</button>
					<a href="?page=languages" class="btn btn-outline-secondary ms-2">Annuler</a>
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
			<input type="hidden" name="page" value="languages">
			<div class="row g-3 align-items-end">
				<div class="col-md-6 col-lg-4">
					<label class="form-label" for="filter-search">Recherche</label>
					<input type="text" class="form-control" id="filter-search" name="search" value="<?php echo spacartAdminEscape($search); ?>" placeholder="Code, libelle...">
				</div>
				<div class="col-md-3 col-lg-2">
					<button type="submit" class="btn btn-outline-primary w-100">
						<i class="bi bi-funnel me-1"></i>Filtrer
					</button>
				</div>
				<?php if ($search !== ''): ?>
					<div class="col-md-3 col-lg-2">
						<a href="?page=languages" class="btn btn-outline-secondary w-100">
							<i class="bi bi-x-lg me-1"></i>Effacer
						</a>
					</div>
				<?php endif; ?>
			</div>
		</form>
	</div>
</div>

<!-- Languages Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th style="width: 60px;">ID</th>
						<th style="width: 60px;">Drapeau</th>
						<th>Code</th>
						<th>Libelle</th>
						<th class="text-center">Active</th>
						<th class="text-center">Par defaut</th>
						<th class="text-center">Cles traduction</th>
						<th>Date creation</th>
						<th class="text-center" style="width: 180px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($languages)): ?>
						<tr>
							<td colspan="9">
								<div class="empty-state-inline">
									<div class="empty-state-icon"><i class="bi bi-translate"></i></div>
									<p>Aucune langue trouvee</p>
								</div>
							</td>
						</tr>
					<?php else: ?>
						<?php foreach ($languages as $lang): ?>
							<tr>
								<td><strong><?php echo (int) $lang->rowid; ?></strong></td>
								<td class="text-center">
									<?php if (!empty($lang->flag_icon)): ?>
										<span class="fi fi-<?php echo spacartAdminEscape($lang->flag_icon); ?>" title="<?php echo spacartAdminEscape($lang->flag_icon); ?>"></span>
										<span class="text-muted small"><?php echo spacartAdminEscape($lang->flag_icon); ?></span>
									<?php else: ?>
										<span class="text-muted">--</span>
									<?php endif; ?>
								</td>
								<td><code><?php echo spacartAdminEscape($lang->code); ?></code></td>
								<td><?php echo spacartAdminEscape($lang->label); ?></td>
								<td class="text-center">
									<form method="post" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_active">
										<input type="hidden" name="language_id" value="<?php echo (int) $lang->rowid; ?>">
										<button type="submit" class="btn btn-sm <?php echo ((int) $lang->active === 1) ? 'btn-success' : 'btn-outline-secondary'; ?>" title="<?php echo ((int) $lang->active === 1) ? 'Desactiver' : 'Activer'; ?>" aria-label="<?php echo ((int) $lang->active === 1) ? 'Desactiver la langue' : 'Activer la langue'; ?>">
											<?php if ((int) $lang->active === 1): ?>
												<i class="bi bi-check-circle"></i> Oui
											<?php else: ?>
												<i class="bi bi-x-circle"></i> Non
											<?php endif; ?>
										</button>
									</form>
								</td>
								<td class="text-center">
									<?php if ((int) $lang->is_default === 1): ?>
										<span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i>Defaut</span>
									<?php else: ?>
										<form method="post" class="d-inline">
											<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
											<input type="hidden" name="action" value="set_default">
											<input type="hidden" name="language_id" value="<?php echo (int) $lang->rowid; ?>">
											<button type="submit" class="btn btn-sm btn-outline-secondary" title="Definir comme langue par defaut" aria-label="Definir comme langue par defaut">
												<i class="bi bi-star"></i>
											</button>
										</form>
									<?php endif; ?>
								</td>
								<td class="text-center">
									<?php
									$key_count = isset($lang_key_counts[$lang->code]) ? $lang_key_counts[$lang->code] : 0;
									if ($key_count > 0) {
										echo '<span class="badge bg-info text-dark">'.$key_count.' cle(s)</span>';
									} else {
										echo '<span class="badge bg-warning text-dark">Aucun fichier</span>';
									}
									?>
								</td>
								<td><?php echo spacartAdminFormatDate($lang->date_creation); ?></td>
								<td class="text-center">
									<div class="d-flex justify-content-center gap-1">
										<!-- Edit -->
										<a href="?page=languages&edit=<?php echo (int) $lang->rowid; ?>" class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier la langue">
											<i class="bi bi-pencil"></i>
										</a>
										<!-- Delete -->
										<?php if ((int) $lang->is_default !== 1): ?>
											<form method="post" class="d-inline">
												<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
												<input type="hidden" name="action" value="delete_language">
												<input type="hidden" name="language_id" value="<?php echo (int) $lang->rowid; ?>">
												<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer la langue"
														data-confirm="Supprimer cette langue ?">
													<i class="bi bi-trash"></i>
												</button>
											</form>
										<?php else: ?>
											<button type="button" class="btn btn-sm btn-outline-danger" disabled title="Impossible de supprimer la langue par defaut" aria-label="Impossible de supprimer la langue par defaut">
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
				<?php echo $pagination['total']; ?> langue(s) -
				Page <?php echo $pagination['current_page']; ?> / <?php echo $pagination['total_pages']; ?>
			</small>
			<nav aria-label="Pagination langues">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_url = '?page=languages';
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
