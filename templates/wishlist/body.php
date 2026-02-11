<!-- Wishlist Page -->

<h5 style="margin-top:15px;">
    <i class="material-icons left">favorite</i>Ma Liste de Souhaits
</h5>

<?php if (!empty($wishlist)) { ?>

<div class="row">
    <?php foreach ($wishlist as $item) { ?>
    <div class="col l12 m12 s12">
        <div class="spacart-wishlist-item card-panel">
            <img src="<?php echo $item->photo_url; ?>" alt="<?php echo htmlspecialchars($item->label); ?>">
            <div class="spacart-wishlist-item-info">
                <a href="#/product/<?php echo $item->fk_product; ?>" class="spacart-spa-link">
                    <strong><?php echo htmlspecialchars($item->label); ?></strong>
                </a>
                <p class="spacart-product-price"><?php echo spacartFormatPrice($item->price); ?></p>
                <small class="grey-text">Ajouté le <?php echo date('d/m/Y', strtotime($item->date_creation)); ?></small>
            </div>
            <div style="flex-shrink:0;display:flex;gap:8px;align-items:center;">
                <?php if ($item->stock_reel > 0 || $item->stock_reel === null) { ?>
                    <a href="#!" class="btn btn-small spacart-add-to-cart" data-product-id="<?php echo $item->fk_product; ?>">
                        <i class="material-icons left">add_shopping_cart</i>Ajouter
                    </a>
                <?php } else { ?>
                    <span class="chip grey white-text">Rupture</span>
                <?php } ?>
                <a href="#!" class="btn-flat spacart-wishlist-btn" data-product-id="<?php echo $item->fk_product; ?>" title="Retirer">
                    <i class="material-icons red-text">delete</i>
                </a>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<?php } else { ?>

<div class="spacart-empty-state">
    <i class="material-icons large grey-text">favorite_border</i>
    <p>Votre liste de souhaits est vide</p>
    <a href="#/products" class="btn spacart-spa-link">Découvrir nos produits</a>
</div>

<?php } ?>
