<!-- Search Results Page -->

<h5 style="margin-top:15px;">
    Résultats de recherche
    <?php if ($query) { ?>
        pour "<strong><?php echo htmlspecialchars($query); ?></strong>"
    <?php } ?>
</h5>
<p class="grey-text"><?php echo $total; ?> résultat<?php echo $total > 1 ? 's' : ''; ?></p>

<div class="row">
    <!-- Filters Sidebar -->
    <div class="col l3 hide-on-med-and-down">
        <?php
        $subcategories = array();
        include SPACART_TPL_PATH.'/common/filter.php';
        ?>
    </div>

    <!-- Results -->
    <div class="col l9 m12 s12">
        <!-- Sort -->
        <div class="right-align" style="margin-bottom:15px;">
            <select id="spacart-sort" class="browser-default" style="max-width:200px;display:inline-block;">
                <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Plus récent</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
            </select>
        </div>

        <?php if (!empty($products)) { ?>
            <div class="row">
                <?php foreach ($products as $product) { ?>
                    <div class="col l4 m6 s6">
                        <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
                    </div>
                <?php } ?>
            </div>

            <?php include SPACART_TPL_PATH.'/common/pagination.php'; ?>

        <?php } else { ?>
            <div class="spacart-empty-state">
                <i class="material-icons large grey-text">search_off</i>
                <p>Aucun résultat pour "<?php echo htmlspecialchars($query); ?>"</p>
                <a href="#/products" class="btn spacart-spa-link">Voir tous les produits</a>
            </div>
        <?php } ?>
    </div>
</div>
