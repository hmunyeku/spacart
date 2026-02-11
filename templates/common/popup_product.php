<!-- Quick View Modal Content -->
<div class="row" style="margin-bottom:0;">
    <div class="col m5 s12">
        <img class="responsive-img" src="<?php echo $product->photo_url; ?>" alt="<?php echo htmlspecialchars($product->label); ?>">
    </div>
    <div class="col m7 s12">
        <h5><?php echo htmlspecialchars($product->label); ?></h5>

        <?php if ($product->ref) { ?>
            <p class="spacart-product-sku grey-text">Réf : <?php echo htmlspecialchars($product->ref); ?></p>
        <?php } ?>

        <!-- Rating -->
        <?php if ($product->review_count > 0) { ?>
        <div class="spacart-review-stars" style="margin-bottom:5px;">
            <?php for ($s = 1; $s <= 5; $s++) { ?>
                <i class="material-icons tiny"><?php echo $s <= round($product->avg_rating) ? 'star' : 'star_border'; ?></i>
            <?php } ?>
            <span class="grey-text">(<?php echo $product->review_count; ?>)</span>
        </div>
        <?php } ?>

        <div class="spacart-product-detail-price" style="font-size:1.3rem;">
            <?php echo spacartFormatPrice($product->price); ?>
        </div>

        <!-- Stock -->
        <?php if ($product->in_stock) { ?>
            <p class="spacart-product-stock in-stock" style="font-size:0.85rem;"><i class="material-icons tiny">check_circle</i> En stock</p>
        <?php } else { ?>
            <p class="spacart-product-stock out-of-stock" style="font-size:0.85rem;"><i class="material-icons tiny">cancel</i> Rupture</p>
        <?php } ?>

        <!-- Short description -->
        <?php if ($product->description) { ?>
            <p style="font-size:0.9rem;color:#666;max-height:100px;overflow:hidden;">
                <?php echo strip_tags(substr($product->description, 0, 200)); ?>...
            </p>
        <?php } ?>

        <!-- Add to cart -->
        <?php if ($product->in_stock) { ?>
        <form id="spacart-product-add-form" data-product-id="<?php echo $product->rowid; ?>">
            <div style="display:flex;align-items:center;gap:10px;margin:15px 0;">
                <div class="spacart-qty-selector">
                    <button type="button" class="spacart-qty-minus">−</button>
                    <input type="number" class="spacart-qty-input" value="1" min="1" max="999">
                    <button type="button" class="spacart-qty-plus">+</button>
                </div>
                <button type="submit" class="btn" style="flex:1;">
                    <i class="material-icons left">add_shopping_cart</i>Ajouter
                </button>
            </div>
        </form>
        <?php } ?>

        <a href="#/product/<?php echo $product->rowid; ?>" class="spacart-spa-link modal-close" style="font-size:0.9rem;">
            Voir la fiche complète →
        </a>
    </div>
</div>
