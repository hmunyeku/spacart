<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/includes/footer.php
 * \ingroup    spacart
 * \brief      SpaCart admin backoffice footer - toast, confirm modal, command palette, scripts
 */

if (!defined('SPACART_ADMIN')) {
	die('Direct access not allowed');
}
?>
	</div><!-- .admin-content -->

	<!-- Admin Footer -->
	<footer class="admin-footer">
		SpaCart v2.0 &middot; Propuls&eacute; par Dolibarr | 2024-2026 CoexDis
	</footer>

</main><!-- .admin-main -->

<!-- Toast Container -->
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

<!-- Confirm Modal -->
<div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-body text-center py-4">
				<div class="confirm-modal-icon icon-warning" id="confirmModalIcon">
					<i class="bi bi-exclamation-triangle"></i>
				</div>
				<h5 class="mb-2" id="confirmModalTitle">Confirmer</h5>
				<p class="text-muted mb-0" id="confirmModalMessage">Etes-vous sur ?</p>
			</div>
			<div class="modal-footer justify-content-center border-top-0 pt-0">
				<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="confirmModalCancel">Annuler</button>
				<button type="button" class="btn btn-danger" id="confirmModalOk">Confirmer</button>
			</div>
		</div>
	</div>
</div>

<!-- Command Palette (Ctrl+K) -->
<div class="command-palette-backdrop" id="cmdPaletteBackdrop"></div>
<div class="command-palette" id="cmdPalette" role="dialog" aria-label="Palette de commandes">
	<input type="text" class="command-palette-input" id="cmdPaletteInput" placeholder="Rechercher une page, une action..." autocomplete="off">
	<div class="command-palette-results" id="cmdPaletteResults"></div>
	<div class="command-palette-footer">
		<span><kbd>&uarr;</kbd><kbd>&darr;</kbd> Naviguer</span>
		<span><kbd>Enter</kbd> Ouvrir</span>
		<span><kbd>Esc</kbd> Fermer</span>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="js/admin.js"></script>
</body>
</html>
