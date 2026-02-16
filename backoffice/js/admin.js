/**
 * SpaCart Admin Backoffice JavaScript v3
 * Vanilla JS - No jQuery dependency
 *
 * Requires: Bootstrap 5.3+ bundle (loaded before this script)
 *
 * Features:
 *   - Sidebar toggle (mobile/desktop)
 *   - Toast notifications (showToast)
 *   - Confirm modal (adminConfirm) - replaces native confirm()
 *   - Command palette (Ctrl+K)
 *   - Keyboard shortcuts (g+d, g+o, g+p, g+c, g+s, n)
 *   - Dark mode toggle + localStorage + prefers-color-scheme
 *   - Dirty form bar
 *   - CSV export (exportTableCSV)
 *   - Button loading helper (setBtnLoading)
 *   - NProgress loading bar
 *   - Dashboard & Statistics charts (Chart.js)
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	/* ======================================================
	   1. Sidebar toggle - mobile & desktop
	   ====================================================== */
	var sidebar  = document.getElementById('adminSidebar');
	var toggle   = document.getElementById('sidebarToggle');
	var closeBtn = document.getElementById('sidebarClose');
	var overlay  = document.getElementById('sidebarOverlay');

	var MOBILE_BP = 992;
	var LS_KEY    = 'spacart_sidebar_collapsed';

	function openMobileSidebar() {
		document.body.classList.add('sidebar-open');
		if (overlay) overlay.classList.add('active');
	}

	function closeMobileSidebar() {
		document.body.classList.remove('sidebar-open');
		if (overlay) overlay.classList.remove('active');
	}

	function toggleDesktopSidebar() {
		var collapsed = document.body.classList.toggle('sidebar-collapsed');
		try { localStorage.setItem(LS_KEY, collapsed ? '1' : '0'); } catch (e) {}
	}

	function restoreDesktopState() {
		if (window.innerWidth < MOBILE_BP) return;
		try {
			if (localStorage.getItem(LS_KEY) === '1') {
				document.body.classList.add('sidebar-collapsed');
			}
		} catch (e) {}
	}

	restoreDesktopState();

	if (toggle) {
		toggle.addEventListener('click', function () {
			if (window.innerWidth < MOBILE_BP) {
				if (document.body.classList.contains('sidebar-open')) {
					closeMobileSidebar();
				} else {
					openMobileSidebar();
				}
			} else {
				toggleDesktopSidebar();
			}
		});
	}

	if (closeBtn) closeBtn.addEventListener('click', closeMobileSidebar);
	if (overlay) overlay.addEventListener('click', closeMobileSidebar);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
			closeMobileSidebar();
		}
	});

	/* ======================================================
	   2. Auto-dismiss alerts after 5 seconds
	   ====================================================== */
	document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
		setTimeout(function () {
			if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
				var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
				if (bsAlert) { bsAlert.close(); return; }
			}
			var closeEl = el.querySelector('[data-bs-dismiss="alert"]');
			if (closeEl) { closeEl.click(); } else { el.remove(); }
		}, 5000);
	});

	/* ======================================================
	   3. Active sidebar link scroll into view
	   ====================================================== */
	var activeLink = document.querySelector('.sidebar-nav .nav-item.active a');
	if (activeLink) {
		activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
	}

	/* ======================================================
	   4. Toast Notification System
	   ====================================================== */
	var toastIcons = {
		success: 'bi-check-circle-fill',
		danger:  'bi-exclamation-triangle-fill',
		warning: 'bi-exclamation-circle-fill',
		info:    'bi-info-circle-fill'
	};

	window.showToast = function (message, type, duration) {
		type = type || 'info';
		duration = duration || 5000;
		var container = document.getElementById('toast-container');
		if (!container) return;

		var toast = document.createElement('div');
		toast.className = 'toast-item toast-' + type;
		toast.style.setProperty('--toast-duration', duration + 'ms');
		toast.innerHTML =
			'<span class="toast-icon"><i class="bi ' + (toastIcons[type] || toastIcons.info) + '"></i></span>' +
			'<span class="toast-body">' + message + '</span>' +
			'<button class="toast-close" aria-label="Fermer"><i class="bi bi-x"></i></button>' +
			'<div class="toast-progress"></div>';

		container.appendChild(toast);

		toast.querySelector('.toast-close').addEventListener('click', function () {
			removeToast(toast);
		});

		setTimeout(function () {
			removeToast(toast);
		}, duration);
	};

	function removeToast(el) {
		if (!el || el.classList.contains('toast-removing')) return;
		el.classList.add('toast-removing');
		setTimeout(function () { el.remove(); }, 200);
	}

	/* ======================================================
	   5. Confirm Modal (replaces native confirm())
	   ====================================================== */
	var confirmModal = null;
	var confirmResolve = null;

	window.adminConfirm = function (message, callback, options) {
		options = options || {};
		var modalEl = document.getElementById('adminConfirmModal');
		if (!modalEl) {
			// Fallback to native confirm if modal not found
			if (callback) callback(confirm(message));
			return;
		}

		var titleEl   = document.getElementById('confirmModalTitle');
		var msgEl     = document.getElementById('confirmModalMessage');
		var okBtn     = document.getElementById('confirmModalOk');
		var cancelBtn = document.getElementById('confirmModalCancel');
		var iconEl    = document.getElementById('confirmModalIcon');

		if (titleEl) titleEl.textContent = options.title || 'Confirmer';
		if (msgEl) msgEl.textContent = message;
		if (okBtn) {
			okBtn.textContent = options.okText || 'Confirmer';
			okBtn.className = 'btn ' + (options.okClass || 'btn-danger');
		}
		if (cancelBtn) cancelBtn.textContent = options.cancelText || 'Annuler';
		if (iconEl) {
			iconEl.className = 'confirm-modal-icon ' + (options.iconClass || 'icon-warning');
			iconEl.innerHTML = '<i class="bi ' + (options.icon || 'bi-exclamation-triangle') + '"></i>';
		}

		if (!confirmModal) {
			confirmModal = new bootstrap.Modal(modalEl);
		}

		// Remove previous listeners
		var newOk = okBtn.cloneNode(true);
		okBtn.parentNode.replaceChild(newOk, okBtn);

		newOk.addEventListener('click', function () {
			confirmModal.hide();
			if (callback) callback(true);
		});

		modalEl.addEventListener('hidden.bs.modal', function handler() {
			modalEl.removeEventListener('hidden.bs.modal', handler);
			// If callback wasn't called via OK, call with false
		}, { once: true });

		confirmModal.show();
	};

	/* ======================================================
	   5b. Replace native confirm() calls
	       Intercept .btn-delete and [data-confirm] clicks
	   ====================================================== */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.btn-delete, [data-confirm]');
		if (!btn) return;

		e.preventDefault();
		e.stopImmediatePropagation();

		var msg = btn.dataset.confirm || 'Etes-vous sur de vouloir supprimer cet element ?';

		adminConfirm(msg, function (confirmed) {
			if (confirmed) {
				// If it's a link, navigate
				if (btn.tagName === 'A' && btn.href) {
					window.location.href = btn.href;
					return;
				}
				// If it's a submit button inside a form, submit the form
				var form = btn.closest('form');
				if (form) {
					// Temporarily remove data-confirm to avoid re-triggering
					var origConfirm = btn.dataset.confirm;
					btn.removeAttribute('data-confirm');
					btn.classList.remove('btn-delete');

					// Create and dispatch a native submit or click
					if (btn.type === 'submit') {
						form.requestSubmit(btn);
					} else {
						form.submit();
					}
					return;
				}
				// Otherwise just click it again
				btn.removeAttribute('data-confirm');
				btn.classList.remove('btn-delete');
				btn.click();
			}
		});
	}, true);

	/* ======================================================
	   6. Image preview on file input change
	   ====================================================== */
	document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
		input.addEventListener('change', function () {
			var target = document.getElementById(input.dataset.preview);
			if (!target || !input.files || !input.files[0]) return;
			var reader = new FileReader();
			reader.onload = function (ev) {
				target.src = ev.target.result;
				target.style.display = 'block';
			};
			reader.readAsDataURL(input.files[0]);
		});
	});

	/* ======================================================
	   7. Select-all checkboxes in table
	   ====================================================== */
	var masterCb = document.getElementById('select-all');
	if (masterCb) {
		masterCb.addEventListener('change', function () {
			document.querySelectorAll('input.row-checkbox').forEach(function (cb) {
				cb.checked = masterCb.checked;
			});
		});
	}

	/* ======================================================
	   8. Debounced search (.admin-search-input)
	   ====================================================== */
	var searchInput = document.querySelector('.admin-search-input');
	if (searchInput) {
		var searchTimer = null;
		searchInput.addEventListener('input', function () {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function () {
				var url = new URL(window.location.href);
				var val = searchInput.value.trim();
				if (val) {
					url.searchParams.set('search', val);
				} else {
					url.searchParams.delete('search');
				}
				url.searchParams.delete('page_num');
				window.location.href = url.toString();
			}, 400);
		});
	}

	/* ======================================================
	   9. Bootstrap tooltips init
	   ====================================================== */
	if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
		document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
			new bootstrap.Tooltip(el);
		});
	}

	/* ======================================================
	   10. Form dirty check with dirty bar
	   ====================================================== */
	var formDirty = false;
	var dirtyBar = document.querySelector('.dirty-bar');

	function setDirty(dirty) {
		formDirty = dirty;
		if (dirtyBar) {
			if (dirty) {
				dirtyBar.classList.add('visible');
			} else {
				dirtyBar.classList.remove('visible');
			}
		}
	}

	document.querySelectorAll('form.track-changes input, form.track-changes select, form.track-changes textarea').forEach(function (el) {
		el.addEventListener('change', function () { setDirty(true); });
		el.addEventListener('input', function () { setDirty(true); });
	});

	document.querySelectorAll('form.track-changes').forEach(function (form) {
		form.addEventListener('submit', function () { setDirty(false); });
	});

	window.addEventListener('beforeunload', function (e) {
		if (formDirty) {
			e.preventDefault();
			e.returnValue = '';
		}
	});

	// Dirty bar cancel button
	var dirtyCancelBtn = document.querySelector('.dirty-bar .btn-dirty-cancel');
	if (dirtyCancelBtn) {
		dirtyCancelBtn.addEventListener('click', function () {
			window.location.reload();
		});
	}

	// Dirty bar save button
	var dirtySaveBtn = document.querySelector('.dirty-bar .btn-dirty-save');
	if (dirtySaveBtn) {
		dirtySaveBtn.addEventListener('click', function () {
			var form = document.querySelector('form.track-changes');
			if (form) form.requestSubmit();
		});
	}

	/* ======================================================
	   11. Button loading helper
	   ====================================================== */
	window.setBtnLoading = function (btn, loading) {
		if (!btn) return;
		if (loading) {
			btn.classList.add('btn-loading');
			btn.disabled = true;
			btn._origText = btn.innerHTML;
		} else {
			btn.classList.remove('btn-loading');
			btn.disabled = false;
			if (btn._origText) btn.innerHTML = btn._origText;
		}
	};

	/* ======================================================
	   12. CSV Export
	   ====================================================== */
	window.exportTableCSV = function (tableSelector, filename) {
		filename = filename || 'export.csv';
		var table = document.querySelector(tableSelector);
		if (!table) return;

		var csv = [];
		var rows = table.querySelectorAll('tr');

		rows.forEach(function (row) {
			var cols = row.querySelectorAll('td, th');
			var rowData = [];
			cols.forEach(function (col) {
				// Skip checkbox and action columns
				if (col.querySelector('input[type="checkbox"]') || col.classList.contains('col-actions')) return;
				var text = col.textContent.trim().replace(/"/g, '""');
				rowData.push('"' + text + '"');
			});
			if (rowData.length > 0) csv.push(rowData.join(';'));
		});

		var blob = new Blob(['\ufeff' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
		var link = document.createElement('a');
		link.href = URL.createObjectURL(blob);
		link.download = filename;
		link.style.display = 'none';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);

		showToast('Export CSV telecharge : ' + filename, 'success', 3000);
	};

	/* ======================================================
	   13. Dark Mode
	   ====================================================== */
	var DARK_KEY = 'spacart_dark_mode';
	var darkToggle = document.getElementById('darkModeToggle');

	function applyTheme(theme) {
		document.documentElement.setAttribute('data-theme', theme);
		if (darkToggle) {
			var icon = darkToggle.querySelector('i');
			if (icon) {
				icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon';
			}
		}
	}

	function initDarkMode() {
		var stored = null;
		try { stored = localStorage.getItem(DARK_KEY); } catch (e) {}

		if (stored === 'dark') {
			applyTheme('dark');
		} else if (stored === 'light') {
			applyTheme('light');
		} else {
			// Follow system preference
			if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				applyTheme('dark');
			} else {
				applyTheme('light');
			}
		}
	}

	initDarkMode();

	if (darkToggle) {
		darkToggle.addEventListener('click', function () {
			var current = document.documentElement.getAttribute('data-theme');
			var next = current === 'dark' ? 'light' : 'dark';
			applyTheme(next);
			try { localStorage.setItem(DARK_KEY, next); } catch (e) {}
		});
	}

	// Listen for system theme changes
	if (window.matchMedia) {
		window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
			var stored = null;
			try { stored = localStorage.getItem(DARK_KEY); } catch (ex) {}
			if (!stored) {
				applyTheme(e.matches ? 'dark' : 'light');
			}
		});
	}

	/* ======================================================
	   14. Command Palette (Ctrl+K)
	   ====================================================== */
	var cmdPalette = document.getElementById('cmdPalette');
	var cmdBackdrop = document.getElementById('cmdPaletteBackdrop');
	var cmdInput = document.getElementById('cmdPaletteInput');
	var cmdResults = document.getElementById('cmdPaletteResults');
	var cmdActiveIndex = -1;

	var cmdPages = [
		{ label: 'Tableau de bord',   icon: 'bi-speedometer2',      url: '?page=dashboard',    shortcut: 'g d' },
		{ label: 'Statistiques',      icon: 'bi-graph-up',          url: '?page=statistics',   shortcut: '' },
		{ label: 'Commandes',         icon: 'bi-box-seam',          url: '?page=orders',       shortcut: 'g o' },
		{ label: 'Factures',          icon: 'bi-receipt',           url: '?page=invoices',     shortcut: '' },
		{ label: 'Produits',          icon: 'bi-tags',              url: '?page=products',     shortcut: 'g p' },
		{ label: 'Nouveau produit',   icon: 'bi-plus-circle',       url: '?page=product_edit', shortcut: 'n' },
		{ label: 'Categories',        icon: 'bi-folder',            url: '?page=categories',   shortcut: '' },
		{ label: 'Marques',           icon: 'bi-bookmark',          url: '?page=brands',       shortcut: '' },
		{ label: 'Avis',              icon: 'bi-star',              url: '?page=reviews',      shortcut: '' },
		{ label: 'Clients',           icon: 'bi-people',            url: '?page=customers',    shortcut: 'g c' },
		{ label: 'Pages CMS',         icon: 'bi-file-earmark-text', url: '?page=pages_cms',    shortcut: '' },
		{ label: 'Blog',              icon: 'bi-journal-text',      url: '?page=blog',         shortcut: '' },
		{ label: 'Actualites',        icon: 'bi-newspaper',         url: '?page=news',         shortcut: '' },
		{ label: 'Temoignages',       icon: 'bi-chat-quote',        url: '?page=testimonials', shortcut: '' },
		{ label: 'Bannieres',         icon: 'bi-image',             url: '?page=banners',      shortcut: '' },
		{ label: 'Page d\'accueil',   icon: 'bi-house',             url: '?page=homepage',     shortcut: '' },
		{ label: 'Coupons',           icon: 'bi-ticket-perforated', url: '?page=coupons',      shortcut: '' },
		{ label: 'Cartes cadeaux',    icon: 'bi-gift',              url: '?page=giftcards',    shortcut: '' },
		{ label: 'Newsletter',        icon: 'bi-envelope-paper',    url: '?page=subscribers',  shortcut: '' },
		{ label: 'Livraison',         icon: 'bi-truck',             url: '?page=shipping',     shortcut: '' },
		{ label: 'Taxes',             icon: 'bi-percent',           url: '?page=taxes',        shortcut: '' },
		{ label: 'Pays',              icon: 'bi-globe',             url: '?page=countries',    shortcut: '' },
		{ label: 'Configuration',     icon: 'bi-gear',              url: '?page=settings',     shortcut: 'g s' },
		{ label: 'Theme',             icon: 'bi-palette',           url: '?page=theme',        shortcut: '' },
		{ label: 'Langues',           icon: 'bi-translate',         url: '?page=languages',    shortcut: '' },
		{ label: 'Devises',           icon: 'bi-currency-exchange', url: '?page=currencies',   shortcut: '' },
		{ label: 'Utilisateurs',      icon: 'bi-person-gear',       url: '?page=admin_users',  shortcut: '' },
	];

	function fuzzyMatch(query, text) {
		query = query.toLowerCase();
		text = text.toLowerCase();
		if (text.indexOf(query) !== -1) return true;
		var qi = 0;
		for (var ti = 0; ti < text.length && qi < query.length; ti++) {
			if (text[ti] === query[qi]) qi++;
		}
		return qi === query.length;
	}

	function renderCmdResults(query) {
		if (!cmdResults) return;
		cmdResults.innerHTML = '';
		cmdActiveIndex = -1;

		var filtered = query
			? cmdPages.filter(function (p) { return fuzzyMatch(query, p.label); })
			: cmdPages;

		filtered.forEach(function (item, idx) {
			var el = document.createElement('a');
			el.className = 'command-palette-item';
			el.href = item.url;
			el.innerHTML =
				'<i class="bi ' + item.icon + '"></i>' +
				'<span>' + item.label + '</span>' +
				(item.shortcut ? '<span class="shortcut">' + item.shortcut + '</span>' : '');
			el.addEventListener('mouseenter', function () {
				setActiveCmd(idx);
			});
			cmdResults.appendChild(el);
		});
	}

	function setActiveCmd(idx) {
		var items = cmdResults.querySelectorAll('.command-palette-item');
		items.forEach(function (it) { it.classList.remove('active'); });
		cmdActiveIndex = idx;
		if (items[idx]) {
			items[idx].classList.add('active');
			items[idx].scrollIntoView({ block: 'nearest' });
		}
	}

	function openCmdPalette() {
		if (!cmdPalette || !cmdBackdrop) return;
		cmdPalette.classList.add('active');
		cmdBackdrop.classList.add('active');
		if (cmdInput) {
			cmdInput.value = '';
			cmdInput.focus();
		}
		renderCmdResults('');
	}

	function closeCmdPalette() {
		if (!cmdPalette || !cmdBackdrop) return;
		cmdPalette.classList.remove('active');
		cmdBackdrop.classList.remove('active');
	}

	// Open with Ctrl+K
	document.addEventListener('keydown', function (e) {
		if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
			e.preventDefault();
			if (cmdPalette && cmdPalette.classList.contains('active')) {
				closeCmdPalette();
			} else {
				openCmdPalette();
			}
		}
	});

	if (cmdBackdrop) {
		cmdBackdrop.addEventListener('click', closeCmdPalette);
	}

	if (cmdInput) {
		cmdInput.addEventListener('input', function () {
			renderCmdResults(cmdInput.value.trim());
		});

		cmdInput.addEventListener('keydown', function (e) {
			var items = cmdResults ? cmdResults.querySelectorAll('.command-palette-item') : [];
			if (e.key === 'Escape') {
				closeCmdPalette();
			} else if (e.key === 'ArrowDown') {
				e.preventDefault();
				setActiveCmd(Math.min(cmdActiveIndex + 1, items.length - 1));
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				setActiveCmd(Math.max(cmdActiveIndex - 1, 0));
			} else if (e.key === 'Enter') {
				e.preventDefault();
				if (items[cmdActiveIndex]) {
					window.location.href = items[cmdActiveIndex].href;
				}
			}
		});
	}

	/* ======================================================
	   15. Keyboard Shortcuts (g+d, g+o, g+p, g+c, g+s, n)
	   ====================================================== */
	var gKeyPressed = false;
	var gTimeout = null;

	document.addEventListener('keydown', function (e) {
		// Don't trigger in inputs/textareas/selects
		var tag = e.target.tagName;
		if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || e.target.isContentEditable) return;
		// Don't trigger if a modal is open or command palette is active
		if (cmdPalette && cmdPalette.classList.contains('active')) return;
		if (document.querySelector('.modal.show')) return;

		if (e.key === 'g') {
			gKeyPressed = true;
			clearTimeout(gTimeout);
			gTimeout = setTimeout(function () { gKeyPressed = false; }, 1000);
			return;
		}

		if (gKeyPressed) {
			gKeyPressed = false;
			clearTimeout(gTimeout);
			var shortcuts = {
				'd': '?page=dashboard',
				'o': '?page=orders',
				'p': '?page=products',
				'c': '?page=customers',
				's': '?page=settings',
			};
			if (shortcuts[e.key]) {
				e.preventDefault();
				window.location.href = shortcuts[e.key];
				return;
			}
		}

		// Direct shortcuts
		if (e.key === 'n') {
			e.preventDefault();
			window.location.href = '?page=product_edit';
		}
	});

	/* ======================================================
	   16. NProgress-style loading bar
	   ====================================================== */
	var nprogressBar = document.getElementById('nprogress-bar');

	window.addEventListener('beforeunload', function () {
		if (nprogressBar) {
			nprogressBar.classList.add('active');
		}
	});

	window.addEventListener('pageshow', function () {
		if (nprogressBar) {
			nprogressBar.classList.remove('active');
			nprogressBar.classList.remove('done');
		}
	});

	// Also trigger on link clicks for smoother feel
	document.querySelectorAll('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])').forEach(function (link) {
		link.addEventListener('click', function () {
			if (nprogressBar) {
				nprogressBar.classList.add('active');
			}
		});
	});

	/* ======================================================
	   17. Dashboard charts helper (Chart.js)
	   ====================================================== */
	window.initDashboardCharts = function (salesData, ordersData) {
		if (typeof Chart === 'undefined') return;

		var salesEl = document.getElementById('chart-sales');
		if (salesEl && salesData) {
			var salesCtx = salesEl.getContext('2d');
			var gradient = salesCtx.createLinearGradient(0, 0, 0, salesEl.height || 300);
			gradient.addColorStop(0, 'rgba(59,130,246,0.25)');
			gradient.addColorStop(1, 'rgba(59,130,246,0.02)');

			new Chart(salesCtx, {
				type: 'line',
				data: {
					labels: salesData.labels || [],
					datasets: [{
						label: 'Ventes (EUR)',
						data: salesData.values || [],
						borderColor: '#3b82f6',
						backgroundColor: gradient,
						borderWidth: 2,
						pointBackgroundColor: '#3b82f6',
						pointRadius: 3,
						pointHoverRadius: 5,
						tension: 0.35,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { display: false } },
					scales: {
						x: { grid: { display: false } },
						y: { beginAtZero: true }
					}
				}
			});
		}

		var ordersEl = document.getElementById('chart-orders');
		if (ordersEl && ordersData) {
			new Chart(ordersEl.getContext('2d'), {
				type: 'bar',
				data: {
					labels: ordersData.labels || [],
					datasets: [{
						label: 'Commandes',
						data: ordersData.values || [],
						backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b'],
						borderRadius: 6,
						maxBarThickness: 48
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { display: false } },
					scales: {
						x: { grid: { display: false } },
						y: { beginAtZero: true, ticks: { stepSize: 1 } }
					}
				}
			});
		}
	};

	/* ======================================================
	   18. Statistics charts helper (Chart.js)
	   ====================================================== */
	window.initStatisticsCharts = function (revenueData, statusData) {
		if (typeof Chart === 'undefined') return;

		// Revenue line chart
		var revenueEl = document.getElementById('chart-revenue-monthly');
		if (revenueEl && revenueData) {
			var ctx = revenueEl.getContext('2d');
			var grad = ctx.createLinearGradient(0, 0, 0, revenueEl.height || 300);
			grad.addColorStop(0, 'rgba(34,197,94,0.2)');
			grad.addColorStop(1, 'rgba(34,197,94,0.02)');

			new Chart(ctx, {
				type: 'line',
				data: {
					labels: revenueData.labels || [],
					datasets: [{
						label: 'CA TTC (EUR)',
						data: revenueData.values || [],
						borderColor: '#22c55e',
						backgroundColor: grad,
						borderWidth: 2,
						pointBackgroundColor: '#22c55e',
						pointRadius: 3,
						pointHoverRadius: 5,
						tension: 0.35,
						fill: true
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: { legend: { display: false } },
					scales: {
						x: { grid: { display: false } },
						y: { beginAtZero: true }
					}
				}
			});
		}

		// Orders by status doughnut
		var statusEl = document.getElementById('chart-orders-status');
		if (statusEl && statusData) {
			new Chart(statusEl.getContext('2d'), {
				type: 'doughnut',
				data: {
					labels: statusData.labels || [],
					datasets: [{
						data: statusData.values || [],
						backgroundColor: ['#ef4444', '#94a3b8', '#06b6d4', '#f59e0b', '#22c55e'],
						borderWidth: 0,
						hoverOffset: 8
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' }
						}
					},
					cutout: '65%'
				}
			});
		}
	};

	/* ======================================================
	   19. Ripple position on button click
	   ====================================================== */
	document.addEventListener('mousedown', function (e) {
		var btn = e.target.closest('.btn:not(.btn-loading)');
		if (!btn) return;
		var rect = btn.getBoundingClientRect();
		var x = ((e.clientX - rect.left) / rect.width * 100).toFixed(0);
		var y = ((e.clientY - rect.top) / rect.height * 100).toFixed(0);
		btn.style.setProperty('--ripple-x', x + '%');
		btn.style.setProperty('--ripple-y', y + '%');
	});

});
