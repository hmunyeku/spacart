<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/blog.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Blog posts management (CRUD, search, filter, pagination)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title   = 'Blog';
$current_page = 'blog';

global $db, $conf;

$entity = (int) $conf->entity;
$prefix = MAIN_DB_PREFIX;

// ============================================================
// POST actions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!spacartAdminCheckCSRF()) {
		spacartAdminFlash('Jeton CSRF invalide. Veuillez reessayer.', 'danger');
		header('Location: ?page=blog');
		exit;
	}

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	// --- Add new blog post ---
	if ($action === 'add') {
		$title   = trim($_POST['title'] ?? '');
		$slug    = trim($_POST['slug'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$image   = trim($_POST['image'] ?? '');
		$status  = isset($_POST['status']) ? (int) $_POST['status'] : 0;
		$author  = trim($_POST['author'] ?? '');

		if ($title === '') {
			spacartAdminFlash('Le titre est obligatoire.', 'danger');
			header('Location: ?page=blog&action=add');
			exit;
		}

		// Auto-generate slug from title if empty
		if ($slug === '') {
			$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
		}

		$sql = "INSERT INTO ".$prefix."spacart_blog";
		$sql .= " (title, slug, content, image, status, author, entity, date_creation)";
		$sql .= " VALUES (";
		$sql .= "'".$db->escape($title)."',";
		$sql .= " '".$db->escape($slug)."',";
		$sql .= " '".$db->escape($content)."',";
		$sql .= " '".$db->escape($image)."',";
		$sql .= " ".(int) $status.",";
		$sql .= " '".$db->escape($author)."',";
		$sql .= " ".$entity.",";
		$sql .= " NOW()";
		$sql .= ")";

		if ($db->query($sql)) {
			spacartAdminFlash('Article de blog cree avec succes.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la creation de l\'article.', 'danger');
		}
		header('Location: ?page=blog');
		exit;
	}

	// --- Update existing blog post ---
	if ($action === 'edit' && !empty($_POST['id'])) {
		$id      = (int) $_POST['id'];
		$title   = trim($_POST['title'] ?? '');
		$slug    = trim($_POST['slug'] ?? '');
		$content = trim($_POST['content'] ?? '');
		$image   = trim($_POST['image'] ?? '');
		$status  = isset($_POST['status']) ? (int) $_POST['status'] : 0;
		$author  = trim($_POST['author'] ?? '');

		if ($title === '') {
			spacartAdminFlash('Le titre est obligatoire.', 'danger');
			header('Location: ?page=blog&action=edit&id='.$id);
			exit;
		}

		if ($slug === '') {
			$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
		}

		$sql = "UPDATE ".$prefix."spacart_blog SET";
		$sql .= " title = '".$db->escape($title)."',";
		$sql .= " slug = '".$db->escape($slug)."',";
		$sql .= " content = '".$db->escape($content)."',";
		$sql .= " image = '".$db->escape($image)."',";
		$sql .= " status = ".(int) $status.",";
		$sql .= " author = '".$db->escape($author)."'";
		$sql .= " WHERE rowid = ".$id;
		$sql .= " AND entity = ".$entity;

		if ($db->query($sql)) {
			spacartAdminFlash('Article de blog mis a jour.', 'success');
		} else {
			spacartAdminFlash('Erreur lors de la mise a jour de l\'article.', 'danger');
		}
		header('Location: ?page=blog');
		exit;
	}

	// --- Delete blog post ---
	if ($action === 'delete' && !empty($_POST['id'])) {
		$id = (int) $_POST['id'];
		if ($id > 0) {
			$sql = "DELETE FROM ".$prefix."spacart_blog";
			$sql .= " WHERE rowid = ".$id;
			$sql .= " AND entity = ".$entity;
			if ($db->query($sql)) {
				spacartAdminFlash('Article supprime.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}
		header('Location: ?page=blog');
		exit;
	}

	// --- Toggle publish/draft ---
	if ($action === 'toggle_status' && !empty($_POST['id'])) {
		$id         = (int) $_POST['id'];
		$new_status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : 0;
		if ($id > 0 && in_array($new_status, array(0, 1))) {
			$sql = "UPDATE ".$prefix."spacart_blog";
			$sql .= " SET status = ".$new_status;
			$sql .= " WHERE rowid = ".$id;
			$sql .= " AND entity = ".$entity;
			if ($db->query($sql)) {
				$label = ($new_status === 1) ? 'publie' : 'brouillon';
				spacartAdminFlash('Article passe en '.$label.'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors du changement de statut.', 'danger');
			}
		}
		header('Location: ?page=blog');
		exit;
	}
}

// ============================================================
// Check for add/edit action (GET) to show form
// ============================================================
$form_action = isset($_GET['action']) ? $_GET['action'] : '';
$edit_id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$edit_post   = null;

if ($form_action === 'edit' && $edit_id > 0) {
	$sql = "SELECT rowid, title, slug, content, image, status, author";
	$sql .= " FROM ".$prefix."spacart_blog";
	$sql .= " WHERE rowid = ".$edit_id;
	$sql .= " AND entity = ".$entity;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$edit_post = $db->fetch_object($resql);
	} else {
		spacartAdminFlash('Article introuvable.', 'danger');
		$form_action = '';
	}
}

$show_form = ($form_action === 'add' || ($form_action === 'edit' && $edit_post));

// ============================================================
// Filters from GET
// ============================================================
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? (int) $_GET['status'] : -1;
$pg           = max(1, (int) ($_GET['pg'] ?? 1));
$per_page     = 20;

// ============================================================
// Build WHERE clause (for list view)
// ============================================================
$where = "b.entity = ".$entity;

if ($search !== '') {
	$search_esc = $db->escape($search);
	$where .= " AND b.title LIKE '%".$search_esc."%'";
}
if ($filter_status !== -1) {
	$where .= " AND b.status = ".(int) $filter_status;
}

// ============================================================
// Pagination
// ============================================================
$sql_count = "SELECT COUNT(*) as nb FROM ".$prefix."spacart_blog as b WHERE ".$where;
$pagination = spacartAdminPaginate($sql_count, $pg, $per_page);

// ============================================================
// Fetch blog posts
// ============================================================
$posts = array();
if (!$show_form) {
	$sql  = "SELECT b.rowid, b.title, b.slug, b.status, b.author, b.date_creation";
	$sql .= " FROM ".$prefix."spacart_blog as b";
	$sql .= " WHERE ".$where;
	$sql .= " ORDER BY b.date_creation DESC";
	$sql .= " LIMIT ".(int) $pagination['limit']." OFFSET ".(int) $pagination['offset'];

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$posts[] = $obj;
		}
		$db->free($resql);
	}
}

// Build filter query string for pagination links
$filter_params = array('page' => 'blog');
if ($search !== '')        $filter_params['search'] = $search;
if ($filter_status !== -1) $filter_params['status'] = $filter_status;

// CSRF token
$csrf_token = spacartAdminGetCSRFToken();

// ============================================================
// Include header
// ============================================================
include __DIR__.'/../includes/header.php';
?>

<?php if ($show_form): ?>
<!-- ============================================================== -->
<!-- Add / Edit Form -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<h1 class="h3 mb-0">
		<?php echo $form_action === 'edit' ? 'Modifier l\'article' : 'Nouvel article'; ?>
	</h1>
	<a href="?page=blog" class="btn btn-outline-secondary">
		<i class="bi bi-arrow-left me-1"></i>Retour a la liste
	</a>
</div>

<div class="admin-card">
	<div class="card-body">
		<form method="post" action="?page=blog">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="<?php echo $form_action === 'edit' ? 'edit' : 'add'; ?>">
			<?php if ($form_action === 'edit' && $edit_post): ?>
				<input type="hidden" name="id" value="<?php echo (int) $edit_post->rowid; ?>">
			<?php endif; ?>

			<div class="row g-3">
				<!-- Title -->
				<div class="col-md-8">
					<label for="field-title" class="form-label">Titre <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="field-title" name="title" required
						   value="<?php echo spacartAdminEscape($edit_post ? $edit_post->title : ''); ?>"
						   oninput="if(!document.getElementById('field-slug').dataset.manual){document.getElementById('field-slug').value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');}">
				</div>

				<!-- Slug -->
				<div class="col-md-4">
					<label for="field-slug" class="form-label">Slug</label>
					<input type="text" class="form-control" id="field-slug" name="slug"
						   value="<?php echo spacartAdminEscape($edit_post ? $edit_post->slug : ''); ?>"
						   placeholder="auto-genere depuis le titre"
						   oninput="this.dataset.manual='1';">
					<div class="form-text">Laissez vide pour generer automatiquement</div>
				</div>

				<!-- Author -->
				<div class="col-md-6">
					<label for="field-author" class="form-label">Auteur</label>
					<input type="text" class="form-control" id="field-author" name="author"
						   value="<?php echo spacartAdminEscape($edit_post ? $edit_post->author : ''); ?>">
				</div>

				<!-- Status -->
				<div class="col-md-3">
					<label for="field-status" class="form-label">Statut</label>
					<select class="form-select" id="field-status" name="status">
						<option value="0"<?php echo ($edit_post && (int) $edit_post->status === 0) ? ' selected' : (!$edit_post ? ' selected' : ''); ?>>Brouillon</option>
						<option value="1"<?php echo ($edit_post && (int) $edit_post->status === 1) ? ' selected' : ''; ?>>Publie</option>
					</select>
				</div>

				<!-- Image URL -->
				<div class="col-md-3">
					<label for="field-image" class="form-label">Image (URL)</label>
					<input type="text" class="form-control" id="field-image" name="image"
						   value="<?php echo spacartAdminEscape($edit_post ? $edit_post->image : ''); ?>"
						   placeholder="https://...">
				</div>

				<!-- Content -->
				<div class="col-12">
					<label for="field-content" class="form-label">Contenu</label>
					<textarea class="form-control" id="field-content" name="content" rows="12"><?php echo spacartAdminEscape($edit_post ? $edit_post->content : ''); ?></textarea>
				</div>
			</div>

			<div class="d-flex justify-content-end mt-4">
				<a href="?page=blog" class="btn btn-outline-secondary me-2">Annuler</a>
				<button type="submit" class="btn btn-primary">
					<i class="bi bi-check-lg me-1"></i><?php echo $form_action === 'edit' ? 'Enregistrer' : 'Creer'; ?>
				</button>
			</div>
		</form>
	</div>
</div>

<?php else: ?>
<!-- ============================================================== -->
<!-- List view -->
<!-- ============================================================== -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between mb-4">
	<div>
		<h1 class="h3 mb-1">Blog</h1>
		<p class="text-muted mb-0"><?php echo (int) $pagination['total']; ?> article<?php echo $pagination['total'] > 1 ? 's' : ''; ?> au total</p>
	</div>
	<a href="?page=blog&amp;action=add" class="btn btn-primary">
		<i class="bi bi-plus-lg me-1"></i>Nouvel article
	</a>
</div>

<!-- Filter bar -->
<div class="admin-card mb-4">
	<div class="card-body">
		<form method="get" class="row g-3 align-items-end">
			<input type="hidden" name="page" value="blog">

			<!-- Search -->
			<div class="col-md-5">
				<label for="filter-search" class="form-label">Recherche</label>
				<input type="text" class="form-control" id="filter-search" name="search"
					   value="<?php echo spacartAdminEscape($search); ?>"
					   placeholder="Titre...">
			</div>

			<!-- Status -->
			<div class="col-md-3">
				<label for="filter-status" class="form-label">Statut</label>
				<select class="form-select" id="filter-status" name="status">
					<option value="">Tous</option>
					<option value="0"<?php echo ($filter_status === 0) ? ' selected' : ''; ?>>Brouillon</option>
					<option value="1"<?php echo ($filter_status === 1) ? ' selected' : ''; ?>>Publie</option>
				</select>
			</div>

			<!-- Filter button -->
			<div class="col-md-2">
				<button type="submit" class="btn btn-primary w-100">
					<i class="bi bi-funnel me-1"></i>Filtrer
				</button>
			</div>

			<?php if ($search !== '' || $filter_status !== -1): ?>
			<div class="col-md-2">
				<a href="?page=blog" class="btn btn-outline-secondary w-100">
					<i class="bi bi-x-circle me-1"></i>Reinitialiser
				</a>
			</div>
			<?php endif; ?>
		</form>
	</div>
</div>

<!-- Blog posts table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Titre</th>
						<th>Auteur</th>
						<th class="text-center">Statut</th>
						<th>Date de creation</th>
						<th class="text-center" style="width:160px;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($posts)): ?>
					<tr>
						<td colspan="5">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-journal-text"></i></div>
								<p>Aucun article trouve</p>
								<small class="text-muted">Creez votre premier article de blog</small>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($posts as $post): ?>
						<tr>
							<td>
								<strong><?php echo spacartAdminEscape($post->title); ?></strong>
								<?php if (!empty($post->slug)): ?>
									<br><small class="text-muted">/<?php echo spacartAdminEscape($post->slug); ?></small>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminEscape($post->author ?: '-'); ?></td>
							<td class="text-center">
								<?php if ((int) $post->status === 1): ?>
									<?php echo spacartAdminStatusBadge('published', 'Publie'); ?>
								<?php else: ?>
									<?php echo spacartAdminStatusBadge('draft', 'Brouillon'); ?>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminFormatDate($post->date_creation, 'd/m/Y H:i'); ?></td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Edit -->
									<a href="?page=blog&amp;action=edit&amp;id=<?php echo (int) $post->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Modifier">
										<i class="bi bi-pencil"></i>
									</a>

									<!-- Toggle status -->
									<form method="post" action="?page=blog" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_status">
										<input type="hidden" name="id" value="<?php echo (int) $post->rowid; ?>">
										<input type="hidden" name="new_status" value="<?php echo ((int) $post->status === 1) ? '0' : '1'; ?>">
										<?php if ((int) $post->status === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Passer en brouillon">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Publier">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=blog" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="id" value="<?php echo (int) $post->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer l'article" data-confirm="Supprimer cet article ?">
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
				sur <?php echo (int) $pagination['total']; ?> article<?php echo $pagination['total'] > 1 ? 's' : ''; ?>
			</div>
			<nav aria-label="Pagination blog">
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
					// Next
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
