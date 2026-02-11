/**
 * SpaCart SPA Engine - Dolibarr E-Commerce Module
 * Single Page Application router, AJAX navigation, cart management
 */
(function() {
    'use strict';

    var SC = window.SpaCart = {};
    var config = window.SpaCartConfig || {};

    // =============================================
    // State
    // =============================================
    SC.state = {
        currentPage: '',
        loading: false,
        cartCount: config.cartCount || 0,
        cartTotal: config.cartTotal || 0,
        searchTimer: null,
        bannerTimer: null,
        initialized: false
    };

    // =============================================
    // Utilities
    // =============================================
    SC.util = {
        formatPrice: function(amount) {
            var num = parseFloat(amount) || 0;
            var symbol = config.currencySymbol || '€';
            return num.toFixed(2).replace('.', ',') + ' ' + symbol;
        },

        ajax: function(opts) {
            var xhr = new XMLHttpRequest();
            var method = (opts.method || 'GET').toUpperCase();
            var url = opts.url;
            var data = opts.data || null;

            if (method === 'GET' && data) {
                var params = [];
                for (var k in data) {
                    if (data.hasOwnProperty(k)) {
                        params.push(encodeURIComponent(k) + '=' + encodeURIComponent(data[k]));
                    }
                }
                url += (url.indexOf('?') > -1 ? '&' : '?') + params.join('&');
                data = null;
            }

            xhr.open(method, url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            if (config.sessionToken) {
                xhr.setRequestHeader('X-SpaCart-Token', config.sessionToken);
            }

            if (method === 'POST' && data && typeof data === 'object' && !(data instanceof FormData)) {
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                var postParams = [];
                for (var pk in data) {
                    if (data.hasOwnProperty(pk)) {
                        postParams.push(encodeURIComponent(pk) + '=' + encodeURIComponent(data[pk]));
                    }
                }
                data = postParams.join('&');
            }

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        var response;
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch(e) {
                            response = xhr.responseText;
                        }
                        if (opts.success) opts.success(response, xhr);
                    } else {
                        if (opts.error) opts.error(xhr);
                    }
                    if (opts.complete) opts.complete(xhr);
                }
            };

            xhr.send(data);
            return xhr;
        },

        debounce: function(func, wait) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        escapeHtml: function(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        showLoader: function() {
            var loader = document.getElementById('spacart-loader');
            if (loader) loader.style.display = 'flex';
            SC.state.loading = true;
        },

        hideLoader: function() {
            var loader = document.getElementById('spacart-loader');
            if (loader) loader.style.display = 'none';
            SC.state.loading = false;
        },

        toast: function(message, type) {
            type = type || 'info';
            var classes = 'spacart-toast rounded';
            if (type === 'success') classes += ' success';
            else if (type === 'error') classes += ' error';

            if (typeof M !== 'undefined' && M.toast) {
                M.toast({html: message, classes: classes, displayLength: 3000});
            }
        },

        scrollToTop: function() {
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    };

    // =============================================
    // Router - Hash-based SPA navigation
    // =============================================
    SC.router = {
        navigate: function(path) {
            if (path.charAt(0) !== '/') path = '/' + path;
            window.location.hash = '#' + path;
        },

        getPath: function() {
            var hash = window.location.hash || '#/';
            return hash.substring(1); // Remove #
        },

        parsePath: function(path) {
            var parts = path.replace(/^\/+|\/+$/g, '').split('/');
            return {
                page: parts[0] || 'home',
                segments: parts.slice(1),
                id: parts[1] || null
            };
        },

        loadPage: function(path, pushState) {
            if (SC.state.loading) return;

            var parsed = SC.router.parsePath(path);

            // Build URL for AJAX request
            var url = config.baseUrl;
            if (path && path !== '/') {
                url += path;
            }

            // Add ajax flag
            url += (url.indexOf('?') > -1 ? '&' : '?') + 'ajax=1';

            SC.util.showLoader();

            SC.util.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    if (typeof data === 'object' && data.html !== undefined) {
                        // Update content
                        var content = document.getElementById('spacart-content');
                        if (content) {
                            content.innerHTML = data.html;
                        }

                        // Update title
                        if (data.title) {
                            document.title = data.title;
                            var titleEl = document.getElementById('spacart-page-title');
                            if (titleEl) titleEl.textContent = data.title;
                        }

                        // Update breadcrumbs
                        if (data.breadcrumbs) {
                            var bcEl = document.getElementById('spacart-breadcrumbs');
                            if (bcEl) bcEl.innerHTML = data.breadcrumbs;
                        }

                        // Update cart badge
                        if (data.cart_count !== undefined) {
                            SC.cart.updateBadge(data.cart_count, data.cart_total);
                        }

                        // Track page
                        SC.state.currentPage = parsed.page;

                        // Re-init Materialize components in new content
                        SC.initMaterialize();

                        // Bind new SPA links
                        SC.bindSpaLinks(content);

                        // Bind page-specific handlers
                        SC.bindPageHandlers(parsed.page);

                        // Scroll to top
                        SC.util.scrollToTop();

                        // GA tracking
                        if (window.gtag) {
                            gtag('event', 'page_view', {
                                page_path: path,
                                page_title: data.title
                            });
                        }
                    }
                },
                error: function(xhr) {
                    SC.util.toast('Erreur de chargement de la page', 'error');
                    console.error('SpaCart: Page load error', xhr.status, xhr.statusText);
                },
                complete: function() {
                    SC.util.hideLoader();
                }
            });
        }
    };

    // =============================================
    // Cart Operations
    // =============================================
    SC.cart = {
        add: function(productId, qty, variantId, options) {
            qty = qty || 1;
            var data = {
                action: 'add',
                product_id: productId,
                qty: qty,
                token: config.sessionToken
            };
            if (variantId) data.variant_id = variantId;
            if (options) {
                for (var k in options) {
                    if (options.hasOwnProperty(k)) {
                        data['option_' + k] = options[k];
                    }
                }
            }

            SC.util.ajax({
                url: config.apiUrl + '/cart/add',
                method: 'POST',
                data: data,
                success: function(resp) {
                    if (resp.success) {
                        SC.cart.updateBadge(resp.cart_count, resp.cart_total);
                        SC.cart.refreshMiniCart();
                        SC.util.toast(resp.message || 'Produit ajouté au panier', 'success');
                    } else {
                        SC.util.toast(resp.message || 'Erreur', 'error');
                    }
                },
                error: function() {
                    SC.util.toast('Erreur lors de l\'ajout au panier', 'error');
                }
            });
        },

        update: function(itemId, qty) {
            SC.util.ajax({
                url: config.apiUrl + '/cart/update',
                method: 'POST',
                data: {
                    action: 'update',
                    item_id: itemId,
                    qty: qty,
                    token: config.sessionToken
                },
                success: function(resp) {
                    if (resp.success) {
                        SC.cart.updateBadge(resp.cart_count, resp.cart_total);
                        SC.cart.refreshMiniCart();
                        // If on cart page, reload it
                        if (SC.state.currentPage === 'cart') {
                            SC.router.loadPage('/cart');
                        }
                    }
                }
            });
        },

        remove: function(itemId) {
            SC.util.ajax({
                url: config.apiUrl + '/cart/remove/' + itemId,
                method: 'POST',
                data: {
                    action: 'remove',
                    item_id: itemId,
                    token: config.sessionToken
                },
                success: function(resp) {
                    if (resp.success) {
                        SC.cart.updateBadge(resp.cart_count, resp.cart_total);
                        SC.cart.refreshMiniCart();
                        if (SC.state.currentPage === 'cart') {
                            SC.router.loadPage('/cart');
                        }
                        SC.util.toast('Produit retiré du panier', 'success');
                    }
                }
            });
        },

        applyCoupon: function(code) {
            SC.util.ajax({
                url: config.apiUrl + '/cart/coupon',
                method: 'POST',
                data: {
                    action: 'coupon',
                    code: code,
                    token: config.sessionToken
                },
                success: function(resp) {
                    if (resp.success) {
                        SC.cart.updateBadge(resp.cart_count, resp.cart_total);
                        SC.util.toast(resp.message || 'Coupon appliqué', 'success');
                        if (SC.state.currentPage === 'cart') {
                            SC.router.loadPage('/cart');
                        }
                    } else {
                        SC.util.toast(resp.message || 'Code invalide', 'error');
                    }
                }
            });
        },

        applyGiftCard: function(code) {
            SC.util.ajax({
                url: config.apiUrl + '/cart/giftcard',
                method: 'POST',
                data: {
                    action: 'giftcard',
                    code: code,
                    token: config.sessionToken
                },
                success: function(resp) {
                    if (resp.success) {
                        SC.cart.updateBadge(resp.cart_count, resp.cart_total);
                        SC.util.toast(resp.message || 'Carte cadeau appliquée', 'success');
                        if (SC.state.currentPage === 'cart') {
                            SC.router.loadPage('/cart');
                        }
                    } else {
                        SC.util.toast(resp.message || 'Code invalide', 'error');
                    }
                }
            });
        },

        updateBadge: function(count, total) {
            SC.state.cartCount = parseInt(count) || 0;
            SC.state.cartTotal = parseFloat(total) || 0;

            // Desktop badge
            var badge = document.getElementById('spacart-cart-badge');
            if (badge) {
                badge.textContent = SC.state.cartCount;
                badge.style.display = SC.state.cartCount > 0 ? '' : 'none';
            }

            // Mobile badge
            var mobileBadge = document.getElementById('spacart-cart-badge-mobile');
            if (mobileBadge) {
                mobileBadge.textContent = SC.state.cartCount;
            }

            // Mobile fab visibility
            var mobileFab = document.getElementById('spacart-mobile-minicart');
            if (mobileFab) {
                mobileFab.style.display = SC.state.cartCount > 0 ? '' : 'none';
            }

            // Cart count in sidenav
            var sidenavCounts = document.querySelectorAll('.spacart-cart-count');
            for (var i = 0; i < sidenavCounts.length; i++) {
                sidenavCounts[i].textContent = SC.state.cartCount;
            }

            // Update config
            config.cartCount = SC.state.cartCount;
            config.cartTotal = SC.state.cartTotal;
        },

        refreshMiniCart: function() {
            SC.util.ajax({
                url: config.baseUrl + '/cart?ajax=1&minicart=1',
                method: 'GET',
                success: function(resp) {
                    var container = document.getElementById('spacart-minicart-content');
                    if (container && resp.html) {
                        container.innerHTML = resp.html;
                        SC.bindSpaLinks(container);
                        SC.bindMiniCartActions(container);
                    }
                }
            });
        }
    };

    // =============================================
    // Search
    // =============================================
    SC.search = {
        init: function() {
            var toggle = document.getElementById('spacart-search-toggle');
            var box = document.getElementById('spacart-search-box');
            var input = document.getElementById('spacart-search-input');
            var results = document.getElementById('spacart-search-results');

            if (!toggle || !box || !input) return;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (box.style.display === 'none') {
                    box.style.display = 'block';
                    input.focus();
                } else {
                    box.style.display = 'none';
                    results.innerHTML = '';
                    results.classList.remove('active');
                }
            });

            input.addEventListener('input', SC.util.debounce(function() {
                var query = input.value.trim();
                if (query.length < 2) {
                    results.innerHTML = '';
                    results.classList.remove('active');
                    return;
                }

                SC.util.ajax({
                    url: config.baseUrl + '/instant_search',
                    data: {ajax: 1, q: query},
                    success: function(resp) {
                        if (resp.html) {
                            results.innerHTML = resp.html;
                            results.classList.add('active');
                            SC.bindSpaLinks(results);
                        } else {
                            results.innerHTML = '<div style="padding:15px;text-align:center;color:#999;">Aucun résultat</div>';
                            results.classList.add('active');
                        }
                    }
                });
            }, 300));

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var query = input.value.trim();
                    if (query) {
                        SC.router.navigate('/search/' + encodeURIComponent(query));
                        box.style.display = 'none';
                        results.innerHTML = '';
                        results.classList.remove('active');
                    }
                }
                if (e.key === 'Escape') {
                    box.style.display = 'none';
                    results.innerHTML = '';
                    results.classList.remove('active');
                }
            });

            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!box.contains(e.target) && e.target !== toggle) {
                    box.style.display = 'none';
                    results.innerHTML = '';
                    results.classList.remove('active');
                }
            });
        }
    };

    // =============================================
    // Wishlist
    // =============================================
    SC.wishlist = {
        toggle: function(productId, btn) {
            SC.util.ajax({
                url: config.apiUrl + '/customer/wishlist',
                method: 'POST',
                data: {
                    action: 'toggle',
                    product_id: productId,
                    token: config.sessionToken
                },
                success: function(resp) {
                    if (resp.success) {
                        if (btn) {
                            var icon = btn.querySelector('i');
                            if (icon) {
                                icon.textContent = resp.in_wishlist ? 'favorite' : 'favorite_border';
                            }
                        }
                        SC.util.toast(resp.message || (resp.in_wishlist ? 'Ajouté aux favoris' : 'Retiré des favoris'), 'success');
                    } else {
                        if (resp.login_required) {
                            SC.util.toast('Connectez-vous pour utiliser la wishlist', 'error');
                            SC.router.navigate('/login');
                        }
                    }
                }
            });
        }
    };

    // =============================================
    // Mini Cart UI
    // =============================================
    SC.miniCart = {
        init: function() {
            var trigger = document.getElementById('spacart-minicart-trigger');
            var dropdown = document.getElementById('spacart-minicart-dropdown');

            if (!trigger || !dropdown) return;

            // Hover on desktop
            var showTimer, hideTimer;

            trigger.addEventListener('mouseenter', function() {
                clearTimeout(hideTimer);
                showTimer = setTimeout(function() {
                    dropdown.classList.add('active');
                }, 200);
            });

            trigger.parentElement.addEventListener('mouseleave', function() {
                clearTimeout(showTimer);
                hideTimer = setTimeout(function() {
                    dropdown.classList.remove('active');
                }, 300);
            });

            dropdown.addEventListener('mouseenter', function() {
                clearTimeout(hideTimer);
            });

            dropdown.addEventListener('mouseleave', function() {
                hideTimer = setTimeout(function() {
                    dropdown.classList.remove('active');
                }, 300);
            });

            // Bind remove buttons in mini cart
            SC.bindMiniCartActions(dropdown);
        }
    };

    // =============================================
    // Banner Slider
    // =============================================
    SC.banners = {
        init: function() {
            var slider = document.querySelector('.spacart-banner-slider');
            if (!slider) return;

            var slides = slider.querySelectorAll('.spacart-banner-slide');
            var dots = slider.querySelectorAll('.spacart-banner-dot');
            if (slides.length <= 1) return;

            var current = 0;

            function showSlide(idx) {
                for (var i = 0; i < slides.length; i++) {
                    slides[i].classList.remove('active');
                    if (dots[i]) dots[i].classList.remove('active');
                }
                slides[idx].classList.add('active');
                if (dots[idx]) dots[idx].classList.add('active');
                current = idx;
            }

            // Auto-advance
            if (SC.state.bannerTimer) clearInterval(SC.state.bannerTimer);
            SC.state.bannerTimer = setInterval(function() {
                showSlide((current + 1) % slides.length);
            }, 5000);

            // Dot clicks
            for (var d = 0; d < dots.length; d++) {
                (function(idx) {
                    dots[idx].addEventListener('click', function() {
                        showSlide(idx);
                    });
                })(d);
            }
        }
    };

    // =============================================
    // Newsletter
    // =============================================
    SC.newsletter = {
        init: function() {
            var btn = document.getElementById('spacart-newsletter-btn');
            var input = document.getElementById('spacart-newsletter-email');
            if (!btn || !input) return;

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var email = input.value.trim();
                if (!email || email.indexOf('@') === -1) {
                    SC.util.toast('Veuillez entrer un email valide', 'error');
                    return;
                }
                SC.util.ajax({
                    url: config.apiUrl + '/newsletter',
                    method: 'POST',
                    data: {email: email, token: config.sessionToken},
                    success: function(resp) {
                        if (resp.success) {
                            SC.util.toast(resp.message || 'Inscription réussie !', 'success');
                            input.value = '';
                        } else {
                            SC.util.toast(resp.message || 'Erreur', 'error');
                        }
                    }
                });
            });
        }
    };

    // =============================================
    // Logout
    // =============================================
    SC.auth = {
        init: function() {
            var logoutLink = document.getElementById('spacart-logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    SC.util.ajax({
                        url: config.apiUrl + '/customer/logout',
                        method: 'POST',
                        data: {token: config.sessionToken},
                        success: function(resp) {
                            config.isLoggedIn = false;
                            SC.util.toast('Déconnexion réussie', 'success');
                            window.location.reload();
                        }
                    });
                });
            }
        }
    };

    // =============================================
    // Bind SPA Links
    // =============================================
    SC.bindSpaLinks = function(container) {
        container = container || document;
        var links = container.querySelectorAll('.spacart-spa-link');
        for (var i = 0; i < links.length; i++) {
            (function(link) {
                // Avoid double-binding
                if (link.dataset.spaBound) return;
                link.dataset.spaBound = '1';

                link.addEventListener('click', function(e) {
                    var href = link.getAttribute('href');
                    if (!href || href === '#!' || href === '#') {
                        e.preventDefault();
                        return;
                    }
                    if (href.indexOf('#/') === 0) {
                        e.preventDefault();
                        var path = href.substring(1); // Remove #
                        window.location.hash = '#' + path;

                        // Close sidenav on mobile
                        var sidenav = M.Sidenav.getInstance(document.getElementById('spacart-sidenav'));
                        if (sidenav) sidenav.close();
                    }
                });
            })(links[i]);
        }
    };

    // =============================================
    // Bind Mini Cart Remove Actions
    // =============================================
    SC.bindMiniCartActions = function(container) {
        var removes = container.querySelectorAll('.spacart-minicart-remove');
        for (var i = 0; i < removes.length; i++) {
            (function(btn) {
                if (btn.dataset.spaBound) return;
                btn.dataset.spaBound = '1';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var itemId = btn.dataset.cartItem;
                    if (itemId) SC.cart.remove(itemId);
                });
            })(removes[i]);
        }
    };

    // =============================================
    // Page-Specific Handlers
    // =============================================
    SC.bindPageHandlers = function(page) {
        switch(page) {
            case 'home':
                SC.banners.init();
                SC.bindProductCards();
                break;

            case 'product':
                SC.bindProductDetail();
                break;

            case 'category':
            case 'search':
            case 'brands':
                SC.bindProductCards();
                SC.bindFilters();
                SC.bindPagination();
                break;

            case 'cart':
                SC.bindCartPage();
                break;

            case 'checkout':
                SC.bindCheckout();
                break;

            case 'login':
            case 'register':
                SC.bindAuthForms();
                break;

            case 'profile':
                SC.bindProfilePage();
                break;

            case 'blog':
            case 'news':
                SC.bindComments();
                break;
        }
    };

    SC.bindProductCards = function() {
        // Add to cart buttons
        var addBtns = document.querySelectorAll('.spacart-add-to-cart');
        for (var i = 0; i < addBtns.length; i++) {
            (function(btn) {
                if (btn.dataset.spaBound) return;
                btn.dataset.spaBound = '1';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var pid = btn.dataset.productId;
                    if (pid) SC.cart.add(pid, 1);
                });
            })(addBtns[i]);
        }

        // Quick view buttons
        var qvBtns = document.querySelectorAll('.spacart-quickview-btn');
        for (var q = 0; q < qvBtns.length; q++) {
            (function(btn) {
                if (btn.dataset.spaBound) return;
                btn.dataset.spaBound = '1';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var pid = btn.dataset.productId;
                    if (pid) SC.quickView(pid);
                });
            })(qvBtns[q]);
        }

        // Wishlist buttons
        var wBtns = document.querySelectorAll('.spacart-wishlist-btn');
        for (var w = 0; w < wBtns.length; w++) {
            (function(btn) {
                if (btn.dataset.spaBound) return;
                btn.dataset.spaBound = '1';
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var pid = btn.dataset.productId;
                    if (pid) SC.wishlist.toggle(pid, btn);
                });
            })(wBtns[w]);
        }
    };

    SC.bindProductDetail = function() {
        // Quantity selector
        var qtyMinus = document.querySelector('.spacart-qty-minus');
        var qtyPlus = document.querySelector('.spacart-qty-plus');
        var qtyInput = document.querySelector('.spacart-qty-input');

        if (qtyMinus && qtyPlus && qtyInput) {
            qtyMinus.addEventListener('click', function() {
                var val = parseInt(qtyInput.value) || 1;
                if (val > 1) qtyInput.value = val - 1;
            });
            qtyPlus.addEventListener('click', function() {
                var val = parseInt(qtyInput.value) || 1;
                qtyInput.value = val + 1;
            });
        }

        // Add to cart form
        var addForm = document.getElementById('spacart-product-add-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var pid = addForm.dataset.productId;
                var qty = parseInt(qtyInput ? qtyInput.value : 1) || 1;
                var variantId = null;
                var options = {};

                // Get selected variant
                var selectedVariant = addForm.querySelector('.spacart-variant-option.selected');
                if (selectedVariant) {
                    variantId = selectedVariant.dataset.variantId;
                }

                // Get selected options
                var optionSelects = addForm.querySelectorAll('.spacart-option-select');
                for (var i = 0; i < optionSelects.length; i++) {
                    var sel = optionSelects[i];
                    if (sel.value) {
                        options[sel.dataset.groupId] = sel.value;
                    }
                }

                SC.cart.add(pid, qty, variantId, options);
            });
        }

        // Variant selection
        var variantOpts = document.querySelectorAll('.spacart-variant-option');
        for (var v = 0; v < variantOpts.length; v++) {
            (function(opt) {
                opt.addEventListener('click', function() {
                    if (opt.classList.contains('disabled')) return;

                    // Deselect others in same group
                    var group = opt.closest('.spacart-variant-group');
                    if (group) {
                        var siblings = group.querySelectorAll('.spacart-variant-option');
                        for (var s = 0; s < siblings.length; s++) {
                            siblings[s].classList.remove('selected');
                        }
                    }
                    opt.classList.add('selected');

                    // Update price if variant has different price
                    if (opt.dataset.price) {
                        var priceEl = document.querySelector('.spacart-product-detail-price');
                        if (priceEl) {
                            priceEl.textContent = SC.util.formatPrice(opt.dataset.price);
                        }
                    }
                });
            })(variantOpts[v]);
        }

        // Image gallery
        var thumbs = document.querySelectorAll('.spacart-product-thumbs img');
        var mainImg = document.querySelector('.spacart-product-gallery .main-image');
        for (var t = 0; t < thumbs.length; t++) {
            (function(thumb) {
                thumb.addEventListener('click', function() {
                    if (mainImg) {
                        mainImg.src = thumb.dataset.full || thumb.src;
                    }
                    for (var tt = 0; tt < thumbs.length; tt++) {
                        thumbs[tt].classList.remove('active');
                    }
                    thumb.classList.add('active');
                });
            })(thumbs[t]);
        }

        // Tabs
        var tabsEl = document.querySelector('.spacart-product-tabs .tabs');
        if (tabsEl && typeof M !== 'undefined') {
            M.Tabs.init(tabsEl);
        }

        // Review form
        var reviewForm = document.getElementById('spacart-review-form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(reviewForm);
                var data = {token: config.sessionToken};
                formData.forEach(function(value, key) {
                    data[key] = value;
                });

                SC.util.ajax({
                    url: config.apiUrl + '/reviews',
                    method: 'POST',
                    data: data,
                    success: function(resp) {
                        if (resp.success) {
                            SC.util.toast('Avis publié !', 'success');
                            SC.router.loadPage(SC.router.getPath());
                        } else {
                            SC.util.toast(resp.message || 'Erreur', 'error');
                        }
                    }
                });
            });
        }

        // Send to friend
        var sendFriendBtn = document.getElementById('spacart-send-friend-btn');
        if (sendFriendBtn) {
            sendFriendBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var email = document.getElementById('spacart-friend-email');
                if (email && email.value) {
                    SC.util.ajax({
                        url: config.apiUrl + '/contact',
                        method: 'POST',
                        data: {
                            action: 'send_to_friend',
                            email: email.value,
                            product_id: addForm ? addForm.dataset.productId : '',
                            token: config.sessionToken
                        },
                        success: function(resp) {
                            SC.util.toast(resp.message || 'Envoyé !', resp.success ? 'success' : 'error');
                        }
                    });
                }
            });
        }
    };

    SC.bindFilters = function() {
        // Price range filter
        var priceForm = document.getElementById('spacart-price-filter');
        if (priceForm) {
            priceForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var minP = document.getElementById('spacart-price-min');
                var maxP = document.getElementById('spacart-price-max');
                var currentPath = SC.router.getPath();
                var base = currentPath.split('?')[0];
                var params = new URLSearchParams(currentPath.split('?')[1] || '');
                if (minP && minP.value) params.set('price_min', minP.value);
                if (maxP && maxP.value) params.set('price_max', maxP.value);
                var qstr = params.toString();
                SC.router.navigate(base + (qstr ? '?' + qstr : ''));
            });
        }

        // Sort dropdown
        var sortSelect = document.getElementById('spacart-sort');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                var currentPath = SC.router.getPath();
                var base = currentPath.split('?')[0];
                var params = new URLSearchParams(currentPath.split('?')[1] || '');
                params.set('sort', sortSelect.value);
                params.delete('page');
                var qstr = params.toString();
                SC.router.navigate(base + (qstr ? '?' + qstr : ''));
            });
        }
    };

    SC.bindPagination = function() {
        var pageLinks = document.querySelectorAll('.spacart-page-link');
        for (var i = 0; i < pageLinks.length; i++) {
            (function(link) {
                if (link.dataset.spaBound) return;
                link.dataset.spaBound = '1';
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var page = link.dataset.page;
                    var currentPath = SC.router.getPath();
                    var base = currentPath.split('?')[0];
                    var params = new URLSearchParams(currentPath.split('?')[1] || '');
                    params.set('page', page);
                    var qstr = params.toString();
                    SC.router.navigate(base + (qstr ? '?' + qstr : ''));
                });
            })(pageLinks[i]);
        }
    };

    SC.bindCartPage = function() {
        // Quantity inputs
        var qtyInputs = document.querySelectorAll('.spacart-cart-qty');
        for (var i = 0; i < qtyInputs.length; i++) {
            (function(input) {
                input.addEventListener('change', function() {
                    var itemId = input.dataset.itemId;
                    var qty = parseInt(input.value) || 1;
                    SC.cart.update(itemId, qty);
                });
            })(qtyInputs[i]);
        }

        // Remove buttons
        var removeBtns = document.querySelectorAll('.spacart-cart-remove');
        for (var r = 0; r < removeBtns.length; r++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var itemId = btn.dataset.itemId;
                    if (itemId) SC.cart.remove(itemId);
                });
            })(removeBtns[r]);
        }

        // Coupon form
        var couponBtn = document.getElementById('spacart-apply-coupon');
        if (couponBtn) {
            couponBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var codeInput = document.getElementById('spacart-coupon-code');
                if (codeInput && codeInput.value.trim()) {
                    SC.cart.applyCoupon(codeInput.value.trim());
                }
            });
        }

        // Gift card form
        var gcBtn = document.getElementById('spacart-apply-giftcard');
        if (gcBtn) {
            gcBtn.addEventListener('click', function(e) {
                e.preventDefault();
                var codeInput = document.getElementById('spacart-giftcard-code');
                if (codeInput && codeInput.value.trim()) {
                    SC.cart.applyGiftCard(codeInput.value.trim());
                }
            });
        }
    };

    SC.bindCheckout = function() {
        var steps = document.querySelectorAll('.spacart-checkout-panel');
        var stepBtns = document.querySelectorAll('.spacart-checkout-step');
        var currentStep = 0;

        function showStep(idx) {
            for (var i = 0; i < steps.length; i++) {
                steps[i].classList.remove('active');
                if (stepBtns[i]) {
                    stepBtns[i].classList.remove('active');
                    if (i < idx) stepBtns[i].classList.add('done');
                    else stepBtns[i].classList.remove('done');
                }
            }
            if (steps[idx]) steps[idx].classList.add('active');
            if (stepBtns[idx]) stepBtns[idx].classList.add('active');
            currentStep = idx;
        }

        // Next step buttons
        var nextBtns = document.querySelectorAll('.spacart-checkout-next');
        for (var n = 0; n < nextBtns.length; n++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Validate current step
                    if (SC.validateCheckoutStep(currentStep)) {
                        showStep(currentStep + 1);
                        SC.util.scrollToTop();
                    }
                });
            })(nextBtns[n]);
        }

        // Prev step buttons
        var prevBtns = document.querySelectorAll('.spacart-checkout-prev');
        for (var p = 0; p < prevBtns.length; p++) {
            (function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showStep(currentStep - 1);
                    SC.util.scrollToTop();
                });
            })(prevBtns[p]);
        }

        // Guest / login toggle
        var guestBtn = document.getElementById('spacart-checkout-guest');
        var loginBtn = document.getElementById('spacart-checkout-login');
        var guestForm = document.getElementById('spacart-checkout-guest-form');
        var loginForm = document.getElementById('spacart-checkout-login-form');

        if (guestBtn) {
            guestBtn.addEventListener('click', function() {
                if (guestForm) guestForm.style.display = 'block';
                if (loginForm) loginForm.style.display = 'none';
            });
        }
        if (loginBtn) {
            loginBtn.addEventListener('click', function() {
                if (guestForm) guestForm.style.display = 'none';
                if (loginForm) loginForm.style.display = 'block';
            });
        }

        // Shipping method selection
        var shippingMethods = document.querySelectorAll('input[name="shipping_method"]');
        for (var s = 0; s < shippingMethods.length; s++) {
            (function(radio) {
                radio.addEventListener('change', function() {
                    SC.updateCheckoutTotals();
                });
            })(shippingMethods[s]);
        }

        // Same billing address toggle
        var sameAddrCheck = document.getElementById('spacart-same-billing');
        var billingForm = document.getElementById('spacart-billing-address-form');
        if (sameAddrCheck && billingForm) {
            sameAddrCheck.addEventListener('change', function() {
                billingForm.style.display = sameAddrCheck.checked ? 'none' : 'block';
            });
        }

        // Final order submit
        var placeOrderBtn = document.getElementById('spacart-place-order');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', function(e) {
                e.preventDefault();
                SC.placeOrder();
            });
        }

        showStep(0);
    };

    SC.validateCheckoutStep = function(step) {
        // Basic client-side validation
        var currentPanel = document.querySelectorAll('.spacart-checkout-panel')[step];
        if (!currentPanel) return true;

        var required = currentPanel.querySelectorAll('[required]');
        for (var i = 0; i < required.length; i++) {
            if (!required[i].value.trim()) {
                required[i].classList.add('invalid');
                required[i].focus();
                SC.util.toast('Veuillez remplir tous les champs obligatoires', 'error');
                return false;
            }
        }

        // Validate email format
        var emailInputs = currentPanel.querySelectorAll('input[type="email"]');
        for (var e = 0; e < emailInputs.length; e++) {
            if (emailInputs[e].value && emailInputs[e].value.indexOf('@') === -1) {
                emailInputs[e].classList.add('invalid');
                SC.util.toast('Email invalide', 'error');
                return false;
            }
        }

        return true;
    };

    SC.updateCheckoutTotals = function() {
        var selected = document.querySelector('input[name="shipping_method"]:checked');
        if (!selected) return;

        SC.util.ajax({
            url: config.apiUrl + '/shipping/calculate',
            method: 'GET',
            data: {method_id: selected.value, token: config.sessionToken},
            success: function(resp) {
                if (resp.success) {
                    var shippingCostEl = document.getElementById('spacart-checkout-shipping-cost');
                    var totalEl = document.getElementById('spacart-checkout-total');
                    if (shippingCostEl) shippingCostEl.textContent = SC.util.formatPrice(resp.shipping_cost);
                    if (totalEl) totalEl.textContent = SC.util.formatPrice(resp.total);
                }
            }
        });
    };

    SC.placeOrder = function() {
        var form = document.getElementById('spacart-checkout-form');
        if (!form) return;

        var data = {};
        var inputs = form.querySelectorAll('input, select, textarea');
        for (var i = 0; i < inputs.length; i++) {
            var inp = inputs[i];
            if (inp.name) {
                if (inp.type === 'checkbox') {
                    data[inp.name] = inp.checked ? '1' : '0';
                } else if (inp.type === 'radio') {
                    if (inp.checked) data[inp.name] = inp.value;
                } else {
                    data[inp.name] = inp.value;
                }
            }
        }
        data.token = config.sessionToken;

        SC.util.showLoader();

        SC.util.ajax({
            url: config.apiUrl + '/checkout/validate',
            method: 'POST',
            data: data,
            success: function(resp) {
                if (resp.success) {
                    if (resp.payment_redirect) {
                        // Redirect to payment gateway
                        window.location.href = resp.payment_redirect;
                    } else if (resp.stripe_client_secret) {
                        // Handle Stripe inline payment
                        SC.stripe.processPayment(resp.stripe_client_secret, resp.order_id);
                    } else {
                        // Order placed (offline payment)
                        SC.util.toast('Commande confirmée !', 'success');
                        SC.cart.updateBadge(0, 0);
                        SC.router.navigate('/invoice/' + resp.order_id);
                    }
                } else {
                    SC.util.toast(resp.message || 'Erreur lors de la commande', 'error');
                }
            },
            error: function() {
                SC.util.toast('Erreur de connexion', 'error');
            },
            complete: function() {
                SC.util.hideLoader();
            }
        });
    };

    SC.bindAuthForms = function() {
        // Login form
        var loginForm = document.getElementById('spacart-login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var email = loginForm.querySelector('[name="email"]');
                var pass = loginForm.querySelector('[name="password"]');
                var remember = loginForm.querySelector('[name="remember"]');

                SC.util.ajax({
                    url: config.apiUrl + '/customer/login',
                    method: 'POST',
                    data: {
                        email: email ? email.value : '',
                        password: pass ? pass.value : '',
                        remember: (remember && remember.checked) ? '1' : '0',
                        token: config.sessionToken
                    },
                    success: function(resp) {
                        if (resp.success) {
                            config.isLoggedIn = true;
                            SC.util.toast('Connexion réussie', 'success');
                            // Reload to update header
                            window.location.reload();
                        } else {
                            SC.util.toast(resp.message || 'Identifiants incorrects', 'error');
                        }
                    }
                });
            });
        }

        // Register form
        var registerForm = document.getElementById('spacart-register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = {};
                var inputs = registerForm.querySelectorAll('input, select');
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].name) formData[inputs[i].name] = inputs[i].value;
                }
                formData.token = config.sessionToken;

                // Validate password match
                if (formData.password !== formData.password_confirm) {
                    SC.util.toast('Les mots de passe ne correspondent pas', 'error');
                    return;
                }

                SC.util.ajax({
                    url: config.apiUrl + '/customer/register',
                    method: 'POST',
                    data: formData,
                    success: function(resp) {
                        if (resp.success) {
                            config.isLoggedIn = true;
                            SC.util.toast('Inscription réussie !', 'success');
                            window.location.reload();
                        } else {
                            SC.util.toast(resp.message || 'Erreur lors de l\'inscription', 'error');
                        }
                    }
                });
            });
        }
    };

    SC.bindProfilePage = function() {
        var profileForm = document.getElementById('spacart-profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = {};
                var inputs = profileForm.querySelectorAll('input, select');
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].name) formData[inputs[i].name] = inputs[i].value;
                }
                formData.token = config.sessionToken;

                SC.util.ajax({
                    url: config.apiUrl + '/customer/profile',
                    method: 'POST',
                    data: formData,
                    success: function(resp) {
                        if (resp.success) {
                            SC.util.toast('Profil mis à jour', 'success');
                        } else {
                            SC.util.toast(resp.message || 'Erreur', 'error');
                        }
                    }
                });
            });
        }
    };

    SC.bindComments = function() {
        var commentForm = document.getElementById('spacart-comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = {};
                var inputs = commentForm.querySelectorAll('input, textarea');
                for (var i = 0; i < inputs.length; i++) {
                    if (inputs[i].name) formData[inputs[i].name] = inputs[i].value;
                }
                formData.token = config.sessionToken;

                var endpoint = commentForm.dataset.type === 'news'
                    ? config.apiUrl + '/news/comment'
                    : config.apiUrl + '/blog/comment';

                SC.util.ajax({
                    url: endpoint,
                    method: 'POST',
                    data: formData,
                    success: function(resp) {
                        if (resp.success) {
                            SC.util.toast('Commentaire publié', 'success');
                            SC.router.loadPage(SC.router.getPath());
                        } else {
                            SC.util.toast(resp.message || 'Erreur', 'error');
                        }
                    }
                });
            });
        }
    };

    // =============================================
    // Quick View
    // =============================================
    SC.quickView = function(productId) {
        SC.util.ajax({
            url: config.baseUrl + '/product/' + productId,
            data: {ajax: 1, quickview: 1},
            success: function(resp) {
                var container = document.getElementById('spacart-quickview-content');
                if (container && resp.html) {
                    container.innerHTML = resp.html;
                    SC.bindSpaLinks(container);
                    SC.bindProductDetail();

                    var modal = document.getElementById('spacart-quickview-modal');
                    if (modal && typeof M !== 'undefined') {
                        var instance = M.Modal.getInstance(modal) || M.Modal.init(modal);
                        instance.open();
                    }
                }
            }
        });
    };

    // =============================================
    // Initialize Materialize Components
    // =============================================
    SC.initMaterialize = function() {
        if (typeof M === 'undefined') return;

        // Dropdowns
        var dropdowns = document.querySelectorAll('.dropdown-trigger');
        M.Dropdown.init(dropdowns, {
            coverTrigger: false,
            constrainWidth: false,
            hover: true
        });

        // Sidenav
        var sidenavs = document.querySelectorAll('.sidenav');
        M.Sidenav.init(sidenavs);

        // Modals
        var modals = document.querySelectorAll('.modal');
        M.Modal.init(modals);

        // Selects
        var selects = document.querySelectorAll('select:not(.browser-default)');
        M.FormSelect.init(selects);

        // Tooltips
        var tooltips = document.querySelectorAll('.tooltipped');
        M.Tooltip.init(tooltips);

        // Text areas (auto resize)
        var textareas = document.querySelectorAll('.materialize-textarea');
        M.textareaAutoResize(textareas);

        // Tabs
        var tabs = document.querySelectorAll('.tabs');
        M.Tabs.init(tabs);

        // Update text fields (for pre-filled inputs)
        M.updateTextFields();
    };

    // =============================================
    // Stripe Integration (basic)
    // =============================================
    SC.stripe = {
        instance: null,

        init: function() {
            if (!config.stripeKey || typeof Stripe === 'undefined') return;
            SC.stripe.instance = Stripe(config.stripeKey);
        },

        processPayment: function(clientSecret, orderId) {
            if (!SC.stripe.instance) {
                SC.util.toast('Erreur Stripe: non initialisé', 'error');
                return;
            }

            SC.stripe.instance.confirmCardPayment(clientSecret).then(function(result) {
                if (result.error) {
                    SC.util.toast(result.error.message, 'error');
                } else {
                    if (result.paymentIntent.status === 'succeeded') {
                        SC.util.toast('Paiement confirmé !', 'success');
                        SC.cart.updateBadge(0, 0);
                        SC.router.navigate('/invoice/' + orderId);
                    }
                }
            });
        }
    };

    // =============================================
    // Boot - Initialize everything
    // =============================================
    SC.init = function() {
        if (SC.state.initialized) return;
        SC.state.initialized = true;

        // Init Materialize
        SC.initMaterialize();

        // Bind all SPA links
        SC.bindSpaLinks();

        // Init components
        SC.search.init();
        SC.miniCart.init();
        SC.newsletter.init();
        SC.auth.init();

        // Init Stripe if key is present
        if (config.stripeKey) {
            SC.stripe.init();
        }

        // Parse initial page
        var path = SC.router.getPath();
        var parsed = SC.router.parsePath(path);
        SC.state.currentPage = parsed.page;
        SC.bindPageHandlers(parsed.page);

        // Update cart badge
        SC.cart.updateBadge(config.cartCount, config.cartTotal);

        // Hash change listener (SPA navigation)
        window.addEventListener('hashchange', function() {
            var newPath = SC.router.getPath();
            SC.router.loadPage(newPath);
        });

        console.log('SpaCart SPA Engine initialized');
    };

    // =============================================
    // DOM Ready
    // =============================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', SC.init);
    } else {
        SC.init();
    }

})();
