<!-- Product Card -->
<div class="card spacart-product-card">
    <div class="card-image">
        <a href="#/product/<?php echo $product->rowid; ?>" class="spacart-spa-link">
            <img src="<?php echo $product->photo_url; ?>" alt="<?php echo htmlspecialchars($product->label); ?>">
        </a>

        <?php if (!empty($product->is_new)) { ?>
            <span class="spacart-badge spacart-badge-new">Nouveau</span>
        <?php } ?>

        <?php if (isset($product->in_stock) && !$product->in_stock) { ?>
            <span class="spacart-badge spacart-badge-outofstock">Rupture</span>
        <?php } ?>

        <a href="#!" class="btn-floating halfway-fab spacart-quickview-btn" data-product-id="<?php echo $product->rowid; ?>" title="Aperçu rapide">
            <i class="material-icons">visibility</i>
        </a>
    </div>

    <div class="card-content">
        <a href="#/product/<?php echo $product->rowid; ?>" class="spacart-spa-link">
            <span class="card-title"><?php echo htmlspecialchars($product->label); ?></span>
        </a>
        <p class="spacart-product-price">
            <?php echo spacartFormatPrice($product->price); ?>
        </p>
    </div>

    <div class="card-action">
        <?php if (!isset($product->in_stock) || $product->in_stock) { ?>
            <a href="#!" class="spacart-add-to-cart" data-product-id="<?php echo $product->rowid; ?>" title="Ajouter au panier">
                <i class="material-icons">add_shopping_cart</i>
            </a>
        <?php } else { ?>
            <span class="grey-text" style="font-size:0.8rem;">Indisponible</span>
        <?php } ?>

        <a href="#!" class="spacart-wishlist-btn" data-product-id="<?php echo $product->rowid; ?>" title="Ajouter aux favoris">
            <i class="material-icons">favorite_border</i>
        </a>
    </div>
</div>
