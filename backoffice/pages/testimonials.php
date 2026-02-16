<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/testimonials.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Testimonials management (CRUD, approve/reject)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Temoignages';
$current_page = 'testimonials';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// Determine mode: list vs add/edit
// ============================================================
$mode = 'list';
$edit_id = 0;
if (isset($_GET['action']) && $_GET['action'] === 'add') {
	$mode = 'form';
}
if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['id'])) {
	$mode = 'form';
	$edit_id = (int) $_GET['id'];
}

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=testimonials');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Save (add or update) ---
	if ($action === 'save') {
		$id           = (int) ($_POST['id'] ?? 0);
		$author_name  = trim($_POST['author_name'] ?? '');
		$content      = trim($_POST['content'] ?? '');
		$rating       = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
		$author_image = trim($_POST['author_image'] ?? '');
		$status       = in_array((int) ($_POST['status'] ?? 0), array(0, 1)) ? (int) $_POST['status'] : 0;

		if ($author_name === '' || $content === '') {
			spacartAdminFlash('Le nom de l\'auteur et le contenu sont obligatoires.', 'danger');
			header('Location: ?page=testimonials&action='.($id > 0 ? 'edit&id='.$id : 'add'));
			exit;
		}

		if ($id > 0) {
			// Update
			$sql = "UPDATE ".$prefix."spacart_testimonial SET";
			$sql .= " author_name = '".$db->escape($author_name)."'";
			$sql .= ", content = '".$db->escape($content)."'";
			$sql .= ", rating = ".$rating;
			$sql .= ", author_image = '".$db->escape($author_image)."'";
			$sql .= ", status = ".$status;
			$sql .= " WHERE rowid = ".$id;
			$sql .= " AND entity = ".$entity;

			if ($db->query($sql)) {
				spacartAdminFlash('Temoignage mis a jour avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour du temoignage.', 'danger');
			}
		} else {
			// Insert
			$sql = "INSERT INTO ".$prefix."spacart_testimonial";
			$sql .= " (author_name, content, rating, author_image, status, entity, date_creation)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($author_name)."'";
			$sql .= ", '".$db->escape($content)."'";
			$sql .= ", ".$rating;
			$sql .= ", '".$db->escape($author_image)."'";
			$sql .= ", ".$status;
			$sql .= ", ".$entity;
			$sql .= ", NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('Temoignage ajoute avec succes.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de l\'ajout du temoignage.', 'danger');
			}
		}

		header('Location: ?page=testimonials');
		exit;
	}

	// --- Approve ---
	if ($action === 'approve' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "UPDATE ".$prefix."spacart_testimonial SET status = 1 WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Temoignage approuve.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de l\'approbation.', 'danger');
		}
		header('Location: ?page=testimonials');
		exit;
	}

	// --- Reject (set pending) ---
	if ($action === 'reject' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "UPDATE ".$prefix."spacart_testimonial SET status = 0 WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Temoignage mis en attente.', 'success');
		} else {
			spacartAdminFlash('Erreur lors du changement de statut.', 'danger');
		}
		header('Location: ?page=testimonials');
		exit;
	}

	// --- Delete ---
	if ($action === 'delete' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		$sql = "DELETE FROM ".$prefix."spacart_testimonial WHERE rowid = ".$id." AND entity = ".$entity;
		if ($db->query($sql)) {
			spacartAdminFlash('Temoignage supprime.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la suppression.', 'danger');
		}
		header('Location: ?page=testimonials');
		exit;
	}
}

// ============================================================
// CSRF token
// ============================================================
$csrf_token = spacartAdminGetCSRFToken();

// ============================================================
// FORM MODE: load existing testimonial for edit
// ============================================================
$edit_item = null;
if ($mode === 'form' && $edit_id > 0) {
	$sql = "SELECT rowid, author_name, content, rating, author_image, status";
	$sql .= " FROM ".$prefix."spacart_testimonial";
	$sql .= " WHERE rowid = ".$edit_id;
	$sql .= " AND entity = ".$entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$edit_item = $db->fetch_object($resql);
	} else {
		spacartAdminFlash('Temoignage introuvable.', 'danger');
		header('Location: ?page=testimonials');
		exit;
	}
}

// ============================================================
// LIST MODE: filters, pagination, fetch
// ============================================================
$testimonials = array();
$pagination = array('total' => 0, 'total_pages' => 1, 'current_page' => 1, 'offset' => 0, 'limit' => 20);

if ($mode === 'list') {
	$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;
	$pg       = max(1, (int) ($_GET['pg'] ?? 1));
	$per_page = 20;

	// Build WHERE
	$where = "t.entity = ".$entity;
	if ($filter_status !== -1) {
		$where .= " AND t.status = ".(int) $filter_status;
	}

	// Pagination
	$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_testimonial as t WHERE ".$where;
	$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

	// Fetch
	$sql = "SELECT t.rowid, t.author_name, t.content, t.rating, t.author_image, t.status, t.date_creation";
	$sql .= " FROM ".$prefix."spacart_testimonial as t";
	$sql .= " WHERE ".$where;
	$sql .= " ORDER BY t.date_creation DESC";
	$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$testimonials[] = $obj;
		}
		$db->free($resql);
	}
}

// Filter params for pagination links
$filter_params = array('page' => 'testimonials');
if (isset($filter_status) && $filter_status !== -1) {
	$filter_params['status'] = $filter_status;
}

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<?php if ($mode === 'form'): ?>
<!-- ============================================================== -->
<!-- ADD / EDIT FORM -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1"><?php echo $edit_id > 0 ? 'Modifier le temoignage' : 'Ajouter un temoignage'; ?></h1>
		<p class="text-muted mb-0">
			<a href="?page=testimonials" class="text-decoration-none">
				<i class="bi bi-arrow-left me-1"></i>Retour a la liste
			</a>
		</p>
	</div>
</div>

<div class="admin-card">
	<div class="card-body">
		<form method="post" action="?page=testimonials">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="save">
			<input type="hidden" name="id" value="<?php echo (int) $edit_id; ?>">

			<div class="row g-3">
				<!-- Author name -->
				<div class="col-md-6">
					<label for="field-author_name" class="form-label">Nom de l'auteur <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="field-author_name" name="author_name"
						   value="<?php echo spacartAdminEscape($edit_item ? $edit_item->author_name : ''); ?>"
						   required>
				</div>

				<!-- Rating -->
				<div class="col-md-3">
					<label for="field-rating" class="form-label">Note</label>
					<select class="form-select" id="field-rating" name="rating">
						<?php for ($r = 5; $r >= 1; $r--): ?>
							<option value="<?php echo $r; ?>"<?php echo ($edit_item && (int) $edit_item->rating === $r) ? ' selected' : (!$edit_item && $r === 5 ? ' selected' : ''); ?>>
								<?php echo $r; ?> <?php echo str_repeat('&#9733;', $r); ?>
							</option>
						<?php endfor; ?>
					</select>
				</div>

				<!-- Status -->
				<div class="col-md-3">
					<label for="field-status" class="form-label">Statut</label>
					<select class="form-select" id="field-status" name="status">
						<option value="0"<?php echo ($edit_item && (int) $edit_item->status === 0) ? ' selected' : ''; ?>>En attente</option>
						<option value="1"<?php echo ($edit_item && (int) $edit_item->status === 1) ? ' selected' : ''; ?>>Approuve</option>
					</select>
				</div>

				<!-- Content -->
				<div class="col-12">
					<label for="field-content" class="form-label">Contenu <span class="text-danger">*</span></label>
					<textarea class="form-control" id="field-content" name="content" rows="5"
							  required><?php echo spacartAdminEscape($edit_item ? $edit_item->content : ''); ?></textarea>
				</div>

				<!-- Author image URL -->
				<div class="col-12">
					<label for="field-author_image" class="form-label">URL de l'image auteur</label>
					<input type="url" class="form-control" id="field-author_image" name="author_image"
						   value="<?php echo spacartAdminEscape($edit_item ? $edit_item->author_image : ''); ?>"
						   placeholder="https://...">
					<div class="form-hint">
						<small class="text-muted">URL vers la photo de l'auteur (optionnel)</small>
					</div>
				</div>

				<?php if ($edit_item && !empty($edit_item->author_image)): ?>
				<div class="col-12">
					<label class="form-label">Apercu</label>
					<div>
						<img src="<?php echo spacartAdminEscape($edit_item->author_image); ?>"
							 alt="<?php echo spacartAdminEscape($edit_item->author_name); ?>"
							 style="max-width:80px; max-height:80px; border-radius:50%; object-fit:cover;"
							 onerror="this.style.display='none'">
					</div>
				</div>
				<?php endif; ?>
			</div>

			<div class="d-flex justify-content-end mt-4">
				<a href="?page=testimonials" class="btn btn-outline-secondary me-2">Annuler</a>
				<button type="submit" class="btn btn-primary">
					<i class="bi bi-check-lg me-1"></i><?php echo $edit_id > 0 ? 'Mettre a jour' : 'Ajouter'; ?>
				</button>
			</div>
		</form>
	</div>
</div>

<?php else: ?>
<!-- ============================================================== -->
<!-- LIST MODE -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
	<div>
		<h1 class="h3 mb-1">Temoignages</h1>
		<p class="text-muted mb-0"><?php echo (int) $pagination['total']; ?> temoignage<?php echo $pagination['total'] > 1 ? 's' : ''; ?> au total</p>
	</div>
	<a href="?page=testimonials&amp;action=add" class="btn btn-primary">
		<i class="bi bi-plus-lg me-1"></i>Ajouter un temoignage
	</a>
</div>

<!-- Filter bar -->
<div class="filter-bar mb-4">
	<form method="get" action="" class="row g-2 align-items-end">
		<input type="hidden" name="page" value="testimonials">

		<div class="col-md-4">
			<label for="filter-status" class="form-label">Statut</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">Tous</option>
				<option value="0"<?php echo (isset($filter_status) && $filter_status === 0) ? ' selected' : ''; ?>>En attente</option>
				<option value="1"<?php echo (isset($filter_status) && $filter_status === 1) ? ' selected' : ''; ?>>Approuve</option>
			</select>
		</div>

		<div class="col-md-2">
			<button type="submit" class="btn btn-primary w-100">
				<i class="bi bi-funnel me-1"></i>Filtrer
			</button>
		</div>

		<?php if (isset($filter_status) && $filter_status !== -1): ?>
		<div class="col-md-2">
			<a href="?page=testimonials" class="btn btn-outline-secondary w-100">
				<i class="bi bi-x-circle me-1"></i>Reinitialiser
			</a>
		</div>
		<?php endif; ?>
	</form>
</div>

<!-- Testimonials table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Auteur</th>
						<th>Note</th>
						<th>Contenu</th>
						<th class="text-center">Statut</th>
						<th>Date</th>
						<th class="text-center" style="width:180px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($testimonials)): ?>
					<tr>
						<td colspan="6">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-chat-quote"></i></div>
								<p>Aucun temoignage trouve</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($testimonials as $item): ?>
						<tr>
							<td>
								<div class="d-flex align-items-center gap-2">
									<?php if (!empty($item->author_image)): ?>
										<img src="<?php echo spacartAdminEscape($item->author_image); ?>"
											 alt="" style="width:32px; height:32px; border-radius:50%; object-fit:cover;"
											 onerror="this.style.display='none'">
									<?php else: ?>
										<span class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width:32px; height:32px;">
											<i class="bi bi-person text-muted"></i>
										</span>
									<?php endif; ?>
									<strong><?php echo spacartAdminEscape($item->author_name); ?></strong>
								</div>
							</td>
							<td>
								<span class="text-warning">
									<?php for ($s = 1; $s <= 5; $s++): ?>
										<i class="bi <?php echo $s <= (int) $item->rating ? 'bi-star-fill' : 'bi-star'; ?>"></i>
									<?php endfor; ?>
								</span>
							</td>
							<td><?php echo spacartAdminEscape(spacartAdminTruncate($item->content, 80)); ?></td>
							<td class="text-center">
								<?php if ((int) $item->status === 1): ?>
									<?php echo spacartAdminStatusBadge('active', 'Approuve'); ?>
								<?php else: ?>
									<?php echo spacartAdminStatusBadge('pending', 'En attente'); ?>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($item->date_creation, 'd/m/Y'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Edit -->
									<a href="?page=testimonials&amp;action=edit&amp;id=<?php echo (int) $item->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier le temoignage">
										<i class="bi bi-pencil"></i>
									</a>

									<!-- Approve / Reject -->
									<?php if ((int) $item->status === 0): ?>
									<form method="post" action="?page=testimonials" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="approve">
										<input type="hidden" name="id" value="<?php echo (int) $item->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-success" title="Approuver" aria-label="Approuver le temoignage">
											<i class="bi bi-check-lg"></i>
										</button>
									</form>
									<?php else: ?>
									<form method="post" action="?page=testimonials" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="reject">
										<input type="hidden" name="id" value="<?php echo (int) $item->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-warning" title="Mettre en attente" aria-label="Mettre en attente">
											<i class="bi bi-pause-circle"></i>
										</button>
									</form>
									<?php endif; ?>

									<!-- Delete -->
									<form method="post" action="?page=testimonials" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="id" value="<?php echo (int) $item->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer le temoignage"
												data-confirm="Supprimer ce temoignage ?">
											<i class="bi bi-trash"></i>
										</button>
									</form>
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<?php if ($pagination['total_pages'] > 1): ?>
	<!-- Pagination -->
	<div class="card-footer">
		<div class="d-flex flex-wrap justify-content-between align-items-center">
			<div class="text-muted small mb-2 mb-md-0">
				Affichage de <?php echo (int) $pagination['offset'] + 1; ?>
				a <?php echo min($pagination['offset'] + $pagination['limit'], $pagination['total']); ?>
				sur <?php echo (int) $pagination['total']; ?> temoignage<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination temoignages">
				<ul class="pagination pagination-sm mb-0">
					<?php
					$base_params = $filter_params;
					$total_pages = $pagination['total_pages'];
					$cp = $pagination['current_page'];

					// Previous
					$prev_disabled = ($cp <= 1) ? ' disabled' : '';
					$prev_params = array_merge($base_params, array('pg' => $cp - 1));
					?>
					<li class="page-item<?php echo $prev_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($prev_params); ?>" aria-label="Precedent">
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>

					<?php
					$start = max(1, $cp - 2);
					$end = min($total_pages, $cp + 2);
					if ($cp <= 3) {
						$end = min($total_pages, 5);
					}
					if ($cp >= $total_pages - 2) {
						$start = max(1, $total_pages - 4);
					}

					if ($start > 1): ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => 1))); ?>">1</a>
						</li>
						<?php if ($start > 2): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
					<?php endif; ?>

					<?php for ($i = $start; $i <= $end; $i++): ?>
						<li class="page-item<?php echo ($i === $cp) ? ' active' : ''; ?>">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => $i))); ?>"><?php echo $i; ?></a>
						</li>
					<?php endfor; ?>

					<?php if ($end < $total_pages): ?>
						<?php if ($end < $total_pages - 1): ?>
						<li class="page-item disabled"><span class="page-link">...</span></li>
						<?php endif; ?>
						<li class="page-item">
							<a class="page-link" href="?<?php echo http_build_query(array_merge($base_params, array('pg' => $total_pages))); ?>"><?php echo $total_pages; ?></a>
						</li>
					<?php endif; ?>

					<?php
					$next_disabled = ($cp >= $total_pages) ? ' disabled' : '';
					$next_params = array_merge($base_params, array('pg' => $cp + 1));
					?>
					<li class="page-item<?php echo $next_disabled; ?>">
						<a class="page-link" href="?<?php echo http_build_query($next_params); ?>" aria-label="Suivant">
							<i class="bi bi-chevron-right"></i>
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>
	<?php endif; ?>
</div>

<?php endif; ?>

<?php
include __DIR__.'/../includes/footer.php';
