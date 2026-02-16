<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/pages/admin_users.php
 * \ingroup    spacart
 * \brief      SpaCart admin - Manage backoffice admin users (CRUD)
 *
 * Table: llx_spacart_admin (rowid, username, password_hash, email, firstname,
 * lastname, role, active, last_login, date_creation)
 *
 * @package    spacart
 * @subpackage backoffice
 */

if (!defined('SPACART_ADMIN')) {
	define('SPACART_ADMIN', true);
}

$page_title = 'Utilisateurs admin';
$current_page = 'admin_users';

// -------------------------------------------------------------------
// Current admin session ID (to prevent self-delete / self-deactivate)
// -------------------------------------------------------------------
$my_admin_id = isset($_SESSION['spacart_admin_id']) ? (int) $_SESSION['spacart_admin_id'] : 0;

// -------------------------------------------------------------------
// Role definitions
// -------------------------------------------------------------------
$role_options = array(
	'admin'   => 'Administrateur',
	'manager' => 'Manager',
	'editor'  => 'Editeur',
);
$role_badges = array(
	'admin'   => 'bg-danger',
	'manager' => 'bg-primary',
	'editor'  => 'bg-info',
);

// -------------------------------------------------------------------
// Handle POST actions
// -------------------------------------------------------------------
$form_errors = array();
$form_data = array();
$action = isset($_GET['action']) ? $_GET['action'] : '';
$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && spacartAdminCheckCSRF()) {
	$post_action = isset($_POST['action']) ? $_POST['action'] : '';

	// ---- ADD NEW USER ----
	if ($post_action === 'add') {
		$username  = isset($_POST['username']) ? trim($_POST['username']) : '';
		$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
		$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
		$lastname  = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
		$password  = isset($_POST['password']) ? $_POST['password'] : '';
		$role      = isset($_POST['role']) ? $_POST['role'] : 'editor';
		$active    = !empty($_POST['active']) ? 1 : 0;

		// Validation
		if ($username === '') {
			$form_errors[] = 'Le nom d\'utilisateur est requis.';
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$form_errors[] = 'Une adresse email valide est requise.';
		}
		if ($password === '') {
			$form_errors[] = 'Le mot de passe est requis.';
		} elseif (strlen($password) < 6) {
			$form_errors[] = 'Le mot de passe doit comporter au moins 6 caracteres.';
		}
		if (!isset($role_options[$role])) {
			$role = 'editor';
		}

		// Check username uniqueness
		if (empty($form_errors)) {
			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_admin";
			$sql_check .= " WHERE username = '".$db->escape($username)."'";
			$resql_check = $db->query($sql_check);
			if ($resql_check && $db->num_rows($resql_check) > 0) {
				$form_errors[] = 'Ce nom d\'utilisateur est deja utilise.';
			}
		}

		// Check email uniqueness
		if (empty($form_errors)) {
			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_admin";
			$sql_check .= " WHERE email = '".$db->escape($email)."'";
			$resql_check = $db->query($sql_check);
			if ($resql_check && $db->num_rows($resql_check) > 0) {
				$form_errors[] = 'Cette adresse email est deja utilisee.';
			}
		}

		if (empty($form_errors)) {
			$password_hash = password_hash($password, PASSWORD_BCRYPT);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_admin";
			$sql .= " (username, password_hash, email, firstname, lastname, role, active, date_creation)";
			$sql .= " VALUES (";
			$sql .= "'".$db->escape($username)."',";
			$sql .= " '".$db->escape($password_hash)."',";
			$sql .= " '".$db->escape($email)."',";
			$sql .= " '".$db->escape($firstname)."',";
			$sql .= " '".$db->escape($lastname)."',";
			$sql .= " '".$db->escape($role)."',";
			$sql .= " ".(int) $active.",";
			$sql .= " NOW()";
			$sql .= ")";

			if ($db->query($sql)) {
				spacartAdminFlash('L\'utilisateur "'.$username.'" a ete cree avec succes.', 'success');
				header('Location: ?page=admin_users');
				exit;
			} else {
				$form_errors[] = 'Erreur lors de la creation de l\'utilisateur.';
			}
		}

		// Preserve form data for redisplay on error
		$form_data = array(
			'username'  => $username,
			'email'     => $email,
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'role'      => $role,
			'active'    => $active,
		);
		$action = 'add';
	}

	// ---- EDIT USER ----
	if ($post_action === 'edit') {
		$user_id   = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$username  = isset($_POST['username']) ? trim($_POST['username']) : '';
		$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
		$firstname = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
		$lastname  = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
		$password  = isset($_POST['password']) ? $_POST['password'] : '';
		$role      = isset($_POST['role']) ? $_POST['role'] : 'editor';
		$active    = !empty($_POST['active']) ? 1 : 0;

		if ($user_id <= 0) {
			$form_errors[] = 'Utilisateur invalide.';
		}
		if ($username === '') {
			$form_errors[] = 'Le nom d\'utilisateur est requis.';
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$form_errors[] = 'Une adresse email valide est requise.';
		}
		if ($password !== '' && strlen($password) < 6) {
			$form_errors[] = 'Le mot de passe doit comporter au moins 6 caracteres.';
		}
		if (!isset($role_options[$role])) {
			$role = 'editor';
		}

		// Cannot deactivate own account
		if ($user_id === $my_admin_id && $active === 0) {
			$form_errors[] = 'Vous ne pouvez pas desactiver votre propre compte.';
			$active = 1;
		}

		// Check username uniqueness (exclude self)
		if (empty($form_errors)) {
			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_admin";
			$sql_check .= " WHERE username = '".$db->escape($username)."'";
			$sql_check .= " AND rowid != ".(int) $user_id;
			$resql_check = $db->query($sql_check);
			if ($resql_check && $db->num_rows($resql_check) > 0) {
				$form_errors[] = 'Ce nom d\'utilisateur est deja utilise.';
			}
		}

		// Check email uniqueness (exclude self)
		if (empty($form_errors)) {
			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_admin";
			$sql_check .= " WHERE email = '".$db->escape($email)."'";
			$sql_check .= " AND rowid != ".(int) $user_id;
			$resql_check = $db->query($sql_check);
			if ($resql_check && $db->num_rows($resql_check) > 0) {
				$form_errors[] = 'Cette adresse email est deja utilisee.';
			}
		}

		if (empty($form_errors)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."spacart_admin SET";
			$sql .= " username = '".$db->escape($username)."',";
			$sql .= " email = '".$db->escape($email)."',";
			$sql .= " firstname = '".$db->escape($firstname)."',";
			$sql .= " lastname = '".$db->escape($lastname)."',";
			$sql .= " role = '".$db->escape($role)."',";
			$sql .= " active = ".(int) $active;

			// Only update password if provided
			if ($password !== '') {
				$password_hash = password_hash($password, PASSWORD_BCRYPT);
				$sql .= ", password_hash = '".$db->escape($password_hash)."'";
			}

			$sql .= " WHERE rowid = ".(int) $user_id;

			if ($db->query($sql)) {
				spacartAdminFlash('L\'utilisateur "'.$username.'" a ete mis a jour.', 'success');
				header('Location: ?page=admin_users');
				exit;
			} else {
				$form_errors[] = 'Erreur lors de la mise a jour de l\'utilisateur.';
			}
		}

		// Preserve form data for redisplay on error
		$form_data = array(
			'username'  => $username,
			'email'     => $email,
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'role'      => $role,
			'active'    => $active,
		);
		$action = 'edit';
		$edit_id = $user_id;
	}

	// ---- DELETE USER ----
	if ($post_action === 'delete') {
		$user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

		if ($user_id <= 0) {
			spacartAdminFlash('Utilisateur invalide.', 'danger');
		} elseif ($user_id === $my_admin_id) {
			spacartAdminFlash('Vous ne pouvez pas supprimer votre propre compte.', 'danger');
		} else {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."spacart_admin WHERE rowid = ".(int) $user_id;
			if ($db->query($sql)) {
				spacartAdminFlash('L\'utilisateur a ete supprime.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la suppression.', 'danger');
			}
		}

		header('Location: ?page=admin_users');
		exit;
	}

	// ---- TOGGLE ACTIVE ----
	if ($post_action === 'toggle_active') {
		$user_id    = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
		$new_active = isset($_POST['new_active']) ? (int) $_POST['new_active'] : 0;

		if ($user_id <= 0) {
			spacartAdminFlash('Utilisateur invalide.', 'danger');
		} elseif ($user_id === $my_admin_id) {
			spacartAdminFlash('Vous ne pouvez pas desactiver votre propre compte.', 'danger');
		} else {
			$sql = "UPDATE ".MAIN_DB_PREFIX."spacart_admin";
			$sql .= " SET active = ".((int) $new_active ? 1 : 0);
			$sql .= " WHERE rowid = ".(int) $user_id;
			if ($db->query($sql)) {
				$label = ($new_active ? 'active' : 'desactive');
				spacartAdminFlash('L\'utilisateur a ete '.$label.'.', 'success');
			} else {
				spacartAdminFlash('Erreur lors de la mise a jour.', 'danger');
			}
		}

		header('Location: ?page=admin_users');
		exit;
	}
}

// -------------------------------------------------------------------
// If editing, load user data
// -------------------------------------------------------------------
$edit_user = null;
if ($action === 'edit' && $edit_id > 0 && empty($form_data)) {
	$sql = "SELECT rowid, username, email, firstname, lastname, role, active";
	$sql .= " FROM ".MAIN_DB_PREFIX."spacart_admin";
	$sql .= " WHERE rowid = ".(int) $edit_id;
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql) > 0) {
		$edit_user = $db->fetch_object($resql);
		$form_data = array(
			'username'  => $edit_user->username,
			'email'     => $edit_user->email,
			'firstname' => $edit_user->firstname,
			'lastname'  => $edit_user->lastname,
			'role'      => $edit_user->role,
			'active'    => (int) $edit_user->active,
		);
	} else {
		spacartAdminFlash('Utilisateur introuvable.', 'danger');
		$action = '';
	}
}

// -------------------------------------------------------------------
// Fetch all admin users for list
// -------------------------------------------------------------------
$admin_users = array();
$sql = "SELECT rowid, username, email, firstname, lastname, role, active, last_login, date_creation";
$sql .= " FROM ".MAIN_DB_PREFIX."spacart_admin";
$sql .= " ORDER BY date_creation ASC";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$admin_users[] = $obj;
	}
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
		<h1 class="h3 mb-1">Utilisateurs admin</h1>
		<p class="text-muted mb-0"><?php echo count($admin_users); ?> utilisateur<?php echo count($admin_users) > 1 ? 's' : ''; ?></p>
	</div>
	<div>
		<?php if ($action !== 'add'): ?>
		<a href="?page=admin_users&amp;action=add" class="btn btn-primary">
			<i class="bi bi-plus-lg me-1"></i>Ajouter un utilisateur
		</a>
		<?php endif; ?>
	</div>
</div>

<?php
// -------------------------------------------------------------------
// Display form errors
// -------------------------------------------------------------------
if (!empty($form_errors)): ?>
<div class="alert alert-danger">
	<i class="bi bi-exclamation-triangle me-2"></i>
	<strong>Erreur<?php echo count($form_errors) > 1 ? 's' : ''; ?> :</strong>
	<ul class="mb-0 mt-1">
		<?php foreach ($form_errors as $err): ?>
		<li><?php echo spacartAdminEscape($err); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php
// -------------------------------------------------------------------
// ADD / EDIT FORM
// -------------------------------------------------------------------
if ($action === 'add' || $action === 'edit'):
	$is_edit = ($action === 'edit');
	$form_title = $is_edit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur';
?>
<div class="admin-card mb-4">
	<div class="card-header">
		<h5 class="mb-0">
			<i class="bi <?php echo $is_edit ? 'bi-pencil' : 'bi-person-plus'; ?> me-2"></i><?php echo $form_title; ?>
		</h5>
	</div>
	<div class="card-body">
		<form method="post" action="?page=admin_users<?php echo $is_edit ? '&amp;action=edit&amp;id='.(int) $edit_id : ''; ?>">
			<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
			<input type="hidden" name="action" value="<?php echo $is_edit ? 'edit' : 'add'; ?>">
			<?php if ($is_edit): ?>
			<input type="hidden" name="user_id" value="<?php echo (int) $edit_id; ?>">
			<?php endif; ?>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="field-username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
					<input type="text" class="form-control" id="field-username" name="username"
						   value="<?php echo spacartAdminEscape(isset($form_data['username']) ? $form_data['username'] : ''); ?>"
						   required>
				</div>
				<div class="col-md-6 mb-3">
					<label for="field-email" class="form-label">Email <span class="text-danger">*</span></label>
					<input type="email" class="form-control" id="field-email" name="email"
						   value="<?php echo spacartAdminEscape(isset($form_data['email']) ? $form_data['email'] : ''); ?>"
						   required>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="field-firstname" class="form-label">Prenom</label>
					<input type="text" class="form-control" id="field-firstname" name="firstname"
						   value="<?php echo spacartAdminEscape(isset($form_data['firstname']) ? $form_data['firstname'] : ''); ?>">
				</div>
				<div class="col-md-6 mb-3">
					<label for="field-lastname" class="form-label">Nom</label>
					<input type="text" class="form-control" id="field-lastname" name="lastname"
						   value="<?php echo spacartAdminEscape(isset($form_data['lastname']) ? $form_data['lastname'] : ''); ?>">
				</div>
			</div>

			<div class="row">
				<div class="col-md-6 mb-3">
					<label for="field-password" class="form-label">
						Mot de passe <?php echo $is_edit ? '<small class="text-muted">(laisser vide pour ne pas modifier)</small>' : '<span class="text-danger">*</span>'; ?>
					</label>
					<input type="password" class="form-control" id="field-password" name="password"
						   <?php echo $is_edit ? '' : 'required'; ?>
						   minlength="6"
						   autocomplete="new-password">
				</div>
				<div class="col-md-3 mb-3">
					<label for="field-role" class="form-label">Role <span class="text-danger">*</span></label>
					<select class="form-select" id="field-role" name="role" required>
						<?php foreach ($role_options as $role_key => $role_label): ?>
						<option value="<?php echo spacartAdminEscape($role_key); ?>"<?php echo (isset($form_data['role']) && $form_data['role'] === $role_key) ? ' selected' : ''; ?>>
							<?php echo spacartAdminEscape($role_label); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<div class="form-hint mt-1">
						<small class="text-muted">
							<span class="badge bg-danger">Admin</span> = Acces complet &nbsp;
							<span class="badge bg-primary">Manager</span> = Commandes, produits, clients &nbsp;
							<span class="badge bg-info">Editeur</span> = Contenu CMS uniquement
						</small>
					</div>
				</div>
				<div class="col-md-3 mb-3">
					<label class="form-label d-block">Statut</label>
					<div class="form-check form-switch mt-2">
						<input class="form-check-input" type="checkbox" role="switch"
							   id="field-active" name="active" value="1"
							   <?php echo (!isset($form_data['active']) || (int) $form_data['active'] === 1) ? 'checked' : ''; ?>>
						<label class="form-check-label" for="field-active">Actif</label>
					</div>
				</div>
			</div>

			<div class="d-flex justify-content-end gap-2 mt-3">
				<a href="?page=admin_users" class="btn btn-outline-secondary">
					<i class="bi bi-x-lg me-1"></i>Annuler
				</a>
				<button type="submit" class="btn btn-primary">
					<i class="bi bi-check-lg me-1"></i><?php echo $is_edit ? 'Mettre a jour' : 'Creer l\'utilisateur'; ?>
				</button>
			</div>
		</form>
	</div>
</div>
<?php endif; ?>

<!-- Admin Users Table -->
<div class="admin-card">
	<div class="card-body p-0">
		<div class="table-responsive">
			<table class="admin-table table-hover mb-0">
				<thead>
					<tr>
						<th>Nom d'utilisateur</th>
						<th>Nom complet</th>
						<th>Email</th>
						<th class="text-center">Role</th>
						<th class="text-center">Actif</th>
						<th>Derniere connexion</th>
						<th class="text-center">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if (empty($admin_users)): ?>
					<tr>
						<td colspan="7">
							<div class="empty-state-inline">
								<div class="empty-state-icon"><i class="bi bi-people"></i></div>
								<p>Aucun utilisateur admin trouve</p>
							</div>
						</td>
					</tr>
					<?php else: ?>
						<?php foreach ($admin_users as $au): ?>
						<tr<?php echo ((int) $au->active !== 1) ? ' class="table-secondary"' : ''; ?>>
							<td>
								<strong><?php echo spacartAdminEscape($au->username); ?></strong>
								<?php if ((int) $au->rowid === $my_admin_id): ?>
									<span class="badge bg-secondary ms-1">Vous</span>
								<?php endif; ?>
							</td>
							<td><?php echo spacartAdminEscape(trim($au->firstname.' '.$au->lastname) ?: '-'); ?></td>
							<td>
								<a href="mailto:<?php echo spacartAdminEscape($au->email); ?>">
									<?php echo spacartAdminEscape($au->email); ?>
								</a>
							</td>
							<td class="text-center">
								<?php
								$badge_class = isset($role_badges[$au->role]) ? $role_badges[$au->role] : 'bg-secondary';
								$role_label = isset($role_options[$au->role]) ? $role_options[$au->role] : $au->role;
								?>
								<span class="badge <?php echo $badge_class; ?>"><?php echo spacartAdminEscape($role_label); ?></span>
							</td>
							<td class="text-center">
								<?php if ((int) $au->active === 1): ?>
									<span class="badge badge-status status-active">Actif</span>
								<?php else: ?>
									<span class="badge badge-status status-inactive">Inactif</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $au->last_login ? spacartAdminFormatDate($au->last_login) : '<span class="text-muted">Jamais</span>'; ?>
							</td>
							<td class="text-center">
								<div class="d-flex justify-content-center gap-1">
									<!-- Edit -->
									<a href="?page=admin_users&amp;action=edit&amp;id=<?php echo (int) $au->rowid; ?>"
									   class="btn btn-sm btn-outline-primary" title="Modifier" aria-label="Modifier l'utilisateur">
										<i class="bi bi-pencil"></i>
									</a>

									<!-- Toggle active -->
									<?php if ((int) $au->rowid !== $my_admin_id): ?>
									<form method="post" action="?page=admin_users" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="toggle_active">
										<input type="hidden" name="user_id" value="<?php echo (int) $au->rowid; ?>">
										<input type="hidden" name="new_active" value="<?php echo ((int) $au->active === 1) ? '0' : '1'; ?>">
										<?php if ((int) $au->active === 1): ?>
											<button type="submit" class="btn btn-sm btn-outline-warning" title="Desactiver" aria-label="Desactiver l'utilisateur"
													data-confirm="Desactiver cet utilisateur ?">
												<i class="bi bi-toggle-on"></i>
											</button>
										<?php else: ?>
											<button type="submit" class="btn btn-sm btn-outline-success" title="Activer" aria-label="Activer l'utilisateur"
													data-confirm="Activer cet utilisateur ?">
												<i class="bi bi-toggle-off"></i>
											</button>
										<?php endif; ?>
									</form>

									<!-- Delete -->
									<form method="post" action="?page=admin_users" class="d-inline">
										<input type="hidden" name="_csrf_token" value="<?php echo spacartAdminEscape($csrf_token); ?>">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="user_id" value="<?php echo (int) $au->rowid; ?>">
										<button type="submit" class="btn btn-sm btn-outline-danger btn-delete" title="Supprimer" aria-label="Supprimer l'utilisateur"
												data-confirm="Etes-vous sur de vouloir supprimer cet utilisateur ? Cette action est irreversible.">
											<i class="bi bi-trash"></i>
										</button>
									</form>
									<?php else: ?>
									<span class="btn btn-sm btn-outline-secondary disabled" title="Compte actuel">
										<i class="bi bi-lock"></i>
									</span>
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
</div>

<?php
include __DIR__.'/../includes/footer.php';
