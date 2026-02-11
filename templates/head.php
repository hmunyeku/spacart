<!-- SpaCart Header -->
<header>
    <!-- Top bar -->
    <div class="spacart-topbar" style="background: var(--spacart-primary-dark); color: #fff; padding: 5px 0; font-size: 0.85rem;">
        <div class="container">
            <div class="row" style="margin-bottom:0;">
                <div class="col s6">
                    <span>{$config['company_email']}</span>
                </div>
                <div class="col s6 right-align">
                    {if $is_logged_in}
                        <a href="#/profile" class="white-text"><i class="material-icons tiny">person</i> {$customer->firstname}</a>
                        <span class="white-text"> | </span>
                        <a href="#/logout" class="white-text" id="spacart-logout-link">Deconnexion</a>
                    {else}
                        <a href="#/login" class="white-text"><i class="material-icons tiny">person</i> Connexion</a>
                        <span class="white-text"> | </span>
                        <a href="#/register" class="white-text">Inscription</a>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="spacart-nav">
        <div class="nav-wrapper container">
            <!-- Mobile hamburger -->
            <a href="#" data-target="spacart-sidenav" class="sidenav-trigger"><i class="material-icons">menu</i></a>

            <!-- Brand -->
            <a href="#/" class="brand-logo spacart-spa-link">{$config['title']}</a>

            <!-- Desktop Nav -->
            <ul class="right hide-on-med-and-down">
                <li>
                    <a href="#/" class="spacart-spa-link">Accueil</a>
                </li>
                <li>
                    <a class="dropdown-trigger" href="#!" data-target="spacart-cat-dropdown">
                        Categories <i class="material-icons right">arrow_drop_down</i>
                    </a>
                </li>
                <li>
                    <a href="#/brands" class="spacart-spa-link">Marques</a>
                </li>
                <li>
                    <a href="#/blog" class="spacart-spa-link">Blog</a>
                </li>

                <!-- Search -->
                <li>
                    <div class="spacart-search-wrapper">
                        <i class="material-icons" id="spacart-search-toggle">search</i>
                        <div class="spacart-search-box" id="spacart-search-box" style="display:none;">
                            <input type="text" id="spacart-search-input" placeholder="Rechercher..." autocomplete="off">
                            <div id="spacart-search-results" class="spacart-search-dropdown"></div>
                        </div>
                    </div>
                </li>

                <!-- Wishlist -->
                <li>
                    <a href="#/wishlist" class="spacart-spa-link" title="Ma liste de souhaits">
                        <i class="material-icons">favorite_border</i>
                    </a>
                </li>

                <!-- Mini Cart -->
                <li>
                    <a href="#/cart" class="spacart-spa-link spacart-minicart-trigger" id="spacart-minicart-trigger">
                        <i class="material-icons">shopping_cart</i>
                        <span class="spacart-cart-badge" id="spacart-cart-badge">{$cart['count']}</span>
                    </a>
                    <!-- Mini-cart dropdown -->
                    <div class="spacart-minicart-dropdown" id="spacart-minicart-dropdown">
                        <div id="spacart-minicart-content">
                            {include="common/minicart.php"}
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Categories Dropdown -->
    <ul id="spacart-cat-dropdown" class="dropdown-content">
        <?php if (!empty($cat_tree)) { foreach ($cat_tree as $cat) { ?>
            <li><a href="#/category/<?php echo $cat['id']; ?>" class="spacart-spa-link"><?php echo htmlspecialchars($cat['label']); ?></a></li>
            <?php if (!empty($cat['children'])) { foreach ($cat['children'] as $sub) { ?>
                <li><a href="#/category/<?php echo $sub['id']; ?>" class="spacart-spa-link">&nbsp;&nbsp;<?php echo htmlspecialchars($sub['label']); ?></a></li>
            <?php } } ?>
        <?php } } ?>
        <li class="divider"></li>
        <li><a href="#/products" class="spacart-spa-link">Tous les produits</a></li>
    </ul>

    <!-- Mobile Sidenav -->
    <ul class="sidenav" id="spacart-sidenav">
        <li>
            <div class="user-view" style="background: var(--spacart-primary);">
                <a href="#/"><span class="white-text name">{$config['title']}</span></a>
                <a href="#/"><span class="white-text email">{$config['company_slogan']}</span></a>
            </div>
        </li>
        <li><a href="#/" class="spacart-spa-link sidenav-close"><i class="material-icons">home</i>Accueil</a></li>
        <li><a href="#/products" class="spacart-spa-link sidenav-close"><i class="material-icons">store</i>Tous les produits</a></li>
        <li><div class="divider"></div></li>
        <li><a class="subheader">Categories</a></li>
        <?php if (!empty($cat_tree)) { foreach ($cat_tree as $cat) { ?>
            <li><a href="#/category/<?php echo $cat['id']; ?>" class="spacart-spa-link sidenav-close"><?php echo htmlspecialchars($cat['label']); ?></a></li>
        <?php } } ?>
        <li><div class="divider"></div></li>
        <li><a href="#/cart" class="spacart-spa-link sidenav-close"><i class="material-icons">shopping_cart</i>Panier (<span class="spacart-cart-count">{$cart['count']}</span>)</a></li>
        <li><a href="#/wishlist" class="spacart-spa-link sidenav-close"><i class="material-icons">favorite</i>Wishlist</a></li>
        {if $is_logged_in}
            <li><a href="#/profile" class="spacart-spa-link sidenav-close"><i class="material-icons">person</i>Mon compte</a></li>
            <li><a href="#/orders" class="spacart-spa-link sidenav-close"><i class="material-icons">receipt</i>Mes commandes</a></li>
        {else}
            <li><a href="#/login" class="spacart-spa-link sidenav-close"><i class="material-icons">login</i>Connexion</a></li>
        {/if}
        <li><div class="divider"></div></li>
        <li><a href="#/blog" class="spacart-spa-link sidenav-close"><i class="material-icons">article</i>Blog</a></li>
        <li><a href="#/news" class="spacart-spa-link sidenav-close"><i class="material-icons">newspaper</i>Actualites</a></li>
        <?php if (!empty($spacart_pages)) { foreach ($spacart_pages as $pg) { if ($pg->show_in_menu) { ?>
            <li><a href="#/page/<?php echo $pg->slug; ?>" class="spacart-spa-link sidenav-close"><i class="material-icons">description</i><?php echo htmlspecialchars($pg->title); ?></a></li>
        <?php } } } ?>
    </ul>
</header>
