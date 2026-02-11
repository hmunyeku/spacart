<!-- Category / Product Listing Page -->

<div class="row">
    <!-- Filters Sidebar (desktop) -->
    <div class="col l3 hide-on-med-and-down">
        <?php include SPACART_TPL_PATH.'/common/filter.php'; ?>
    </div>

    <!-- Products Grid -->
    <div class="col l9 m12 s12">

        <!-- Header -->
        <div class="row" style="margin-bottom:10px;">
            <div class="col s8">
                <?php if (!empty($category)) { ?>
                    <h5 style="margin-top:0;"><?php echo htmlspecialchars($category->label); ?></h5>
                    <?php if ($category->description) { ?>
                        <p class="grey-text" style="font-size:0.9rem;"><?php echo htmlspecialchars($category->description); ?></p>
                    <?php } ?>
                <?php } elseif (!empty($brand)) { ?>
                    <h5 style="margin-top:0;"><?php echo htmlspecialchars($brand->label); ?></h5>
                <?php } else { ?>
                    <h5 style="margin-top:0;">Tous les produits</h5>
                <?php } ?>
                <p class="grey-text"><?php echo $total; ?> produit<?php echo $total > 1 ? 's' : ''; ?></p>
            </div>
            <div class="col s4 right-align">
                <!-- Sort -->
                <select id="spacart-sort" class="browser-default" style="max-width:200px;display:inline-block;">
                    <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Plus récent</option>
                    <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Plus ancien</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nom Z-A</option>
                    <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popularité</option>
                </select>
            </div>
        </div>

        <!-- Mobile filter trigger -->
        <div class="hide-on-large-only" style="margin-bottom:15px;">
            <a href="#!" class="btn btn-small btn-flat" onclick="document.getElementById('spacart-mobile-filters').style.display=document.getElementById('spacart-mobile-filters').style.display==='none'?'block':'none';">
                <i class="material-icons left">filter_list</i>Filtres
            </a>
            <div id="spacart-mobile-filters" style="display:none;padding:15px;background:#f9f9f9;border-radius:6px;margin-top:10px;">
                <?php include SPACART_TPL_PATH.'/common/filter.php'; ?>
            </div>
        </div>

        <!-- Subcategory chips -->
        <?php if (!empty($subcategories)) { ?>
        <div style="margin-bottom:15px;">
            <?php foreach ($subcategories as $sub) { ?>
                <a href="#/category/<?php echo $sub->id; ?>" class="chip spacart-spa-link"><?php echo htmlspecialchars($sub->label); ?></a>
            <?php } ?>
        </div>
        <?php } ?>

        <!-- Product Grid -->
        <?php if (!empty($products)) { ?>
            <div class="row">
                <?php foreach ($products as $product) { ?>
                    <div class="col l4 m6 s6">
                        <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
                    </div>
                <?php } ?>
            </div>

            <!-- Pagination -->
            <?php include SPACART_TPL_PATH.'/common/pagination.php'; ?>

        <?php } else { ?>
            <div class="spacart-empty-state">
                <i class="material-icons large grey-text">search_off</i>
                <p>Aucun produit trouvé</p>
                <a href="#/products" class="btn spacart-spa-link">Voir tous les produits</a>
            </div>
        <?php } ?>
    </div>
</div>
