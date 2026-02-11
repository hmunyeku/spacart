<!-- SpaCart Footer -->
<footer class="page-footer" style="background: #263238;">
    <div class="container">
        <div class="row">
            <!-- Company info -->
            <div class="col l4 m6 s12">
                <h5 class="white-text">{$config['company_name']}</h5>
                <p class="grey-text text-lighten-4">{$config['company_slogan']}</p>
                {if !empty($config['company_email'])}
                    <p class="grey-text text-lighten-4">
                        <i class="material-icons tiny">email</i> {$config['company_email']}
                    </p>
                {/if}
            </div>

            <!-- Quick links -->
            <div class="col l3 m6 s12">
                <h5 class="white-text">Boutique</h5>
                <ul>
                    <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/">Accueil</a></li>
                    <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/products">Tous les produits</a></li>
                    <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/brands">Marques</a></li>
                    <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/cart">Panier</a></li>
                </ul>
            </div>

            <!-- CMS Pages -->
            <div class="col l3 m6 s12">
                <h5 class="white-text">Informations</h5>
                <ul>
                    <?php if (!empty($spacart_pages)) { foreach ($spacart_pages as $pg) { if ($pg->show_in_menu) { ?>
                        <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/page/<?php echo $pg->slug; ?>"><?php echo htmlspecialchars($pg->title); ?></a></li>
                    <?php } } } ?>
                    <li><a class="grey-text text-lighten-3 spacart-spa-link" href="#/blog">Blog</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col l2 m6 s12">
                <h5 class="white-text">Newsletter</h5>
                <p class="grey-text text-lighten-4">Recevez nos offres</p>
                <div class="input-field">
                    <input type="email" id="spacart-newsletter-email" class="white-text" placeholder="Votre email">
                    <a href="#!" id="spacart-newsletter-btn" class="btn-small" style="background:var(--spacart-primary);">OK</a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-copyright" style="background: #1a1a2e;">
        <div class="container">
            &copy; <?php echo date('Y'); ?> {$config['company_name']} - Propulse par SpaCart / Dolibarr
            <a class="grey-text text-lighten-4 right spacart-spa-link" href="#/">Retour en haut</a>
        </div>
    </div>
</footer>

<!-- Mobile mini-cart (bottom sheet) -->
<div class="spacart-mobile-minicart" id="spacart-mobile-minicart" style="display:none;">
    <a href="#/cart" class="spacart-spa-link btn-floating btn-large halfway-fab" style="background:var(--spacart-primary);">
        <i class="material-icons">shopping_cart</i>
        <span class="spacart-cart-badge-mobile" id="spacart-cart-badge-mobile">{$cart['count']}</span>
    </a>
</div>
