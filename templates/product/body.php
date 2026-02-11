<!-- Product Detail Page -->

<div class="row" style="margin-top:15px;">
    <!-- Image Gallery -->
    <div class="col l6 m12 s12">
        <div class="spacart-product-gallery">
            <?php
            $mainPhoto = $product->photo_url;
            $photos = !empty($product->photos) ? $product->photos : array();
            ?>
            <img class="main-image responsive-img materialboxed" src="<?php echo $mainPhoto; ?>" alt="<?php echo htmlspecialchars($product->label); ?>">

            <?php if (count($photos) > 1) { ?>
            <div class="spacart-product-thumbs">
                <?php foreach ($photos as $idx => $photo) { ?>
                    <img src="<?php echo $photo['thumb']; ?>"
                         data-full="<?php echo $photo['full']; ?>"
                         class="<?php echo $idx === 0 ? 'active' : ''; ?>"
                         alt="Photo <?php echo $idx + 1; ?>">
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Product Info -->
    <div class="col l6 m12 s12">
        <div class="spacart-product-info">
            <h1><?php echo htmlspecialchars($product->label); ?></h1>

            <?php if ($product->ref) { ?>
                <p class="spacart-product-sku">Réf : <?php echo htmlspecialchars($product->ref); ?></p>
            <?php } ?>

            <!-- Rating -->
            <?php if ($product->review_count > 0) { ?>
            <div class="spacart-rating-summary">
                <div class="spacart-review-stars">
                    <?php for ($s = 1; $s <= 5; $s++) { ?>
                        <i class="material-icons tiny"><?php echo $s <= round($product->avg_rating) ? 'star' : 'star_border'; ?></i>
                    <?php } ?>
                </div>
                <span class="grey-text">(<?php echo $product->review_count; ?> avis)</span>
            </div>
            <?php } ?>

            <!-- Brand -->
            <?php if (!empty($product->brand)) { ?>
                <p style="margin-bottom:5px;">
                    Marque : <a href="#/brands/<?php echo $product->brand->id; ?>" class="spacart-spa-link"><strong><?php echo htmlspecialchars($product->brand->label); ?></strong></a>
                </p>
            <?php } ?>

            <!-- Price -->
            <div class="spacart-product-detail-price">
                <?php echo spacartFormatPrice($product->price); ?>
            </div>

            <!-- Wholesale prices -->
            <?php if (!empty($product->wholesale_prices)) { ?>
            <div style="margin:10px 0;">
                <small class="grey-text">Prix dégressifs :</small>
                <table class="striped" style="font-size:0.85rem;">
                    <?php foreach ($product->wholesale_prices as $wp) { ?>
                    <tr>
                        <td>À partir de <?php echo (int) $wp->min_qty; ?> unités</td>
                        <td><strong><?php echo spacartFormatPrice($wp->price); ?></strong></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php } ?>

            <!-- Stock -->
            <?php if ($product->in_stock) { ?>
                <p class="spacart-product-stock in-stock"><i class="material-icons tiny">check_circle</i> En stock</p>
            <?php } else { ?>
                <p class="spacart-product-stock out-of-stock"><i class="material-icons tiny">cancel</i> Rupture de stock</p>
            <?php } ?>

            <!-- Add to cart form -->
            <form id="spacart-product-add-form" data-product-id="<?php echo $product->rowid; ?>">

                <!-- Variants -->
                <?php if (!empty($product->variants)) { ?>
                    <?php
                    // Group variants by attribute name
                    $variantGroups = array();
                    foreach ($product->variants as $v) {
                        foreach ($v->items as $item) {
                            $variantGroups[$item->attribute_name][] = array(
                                'variant_id' => $v->rowid,
                                'value' => $item->attribute_value,
                                'price' => $v->price,
                                'stock' => $v->stock
                            );
                        }
                    }
                    ?>
                    <?php foreach ($variantGroups as $attrName => $values) { ?>
                    <div class="spacart-variant-group">
                        <label><?php echo htmlspecialchars($attrName); ?></label>
                        <div>
                            <?php
                            $seen = array();
                            foreach ($values as $val) {
                                if (in_array($val['value'], $seen)) continue;
                                $seen[] = $val['value'];
                                $disabled = ($val['stock'] !== null && $val['stock'] <= 0);
                            ?>
                                <span class="spacart-variant-option <?php echo $disabled ? 'disabled' : ''; ?>"
                                      data-variant-id="<?php echo $val['variant_id']; ?>"
                                      data-price="<?php echo $val['price']; ?>">
                                    <?php echo htmlspecialchars($val['value']); ?>
                                </span>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } ?>
                <?php } ?>

                <!-- Options -->
                <?php if (!empty($product->options)) { ?>
                    <?php foreach ($product->options as $grp) { ?>
                    <div class="spacart-option-group">
                        <label><?php echo htmlspecialchars($grp->label); ?> <?php echo $grp->required ? '*' : ''; ?></label>
                        <select class="browser-default spacart-option-select" data-group-id="<?php echo $grp->rowid; ?>" <?php echo $grp->required ? 'required' : ''; ?>>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($grp->options as $opt) { ?>
                                <option value="<?php echo $opt->rowid; ?>">
                                    <?php echo htmlspecialchars($opt->label); ?>
                                    <?php if ($opt->price_modifier > 0) { ?>
                                        (+<?php echo spacartFormatPrice($opt->price_modifier); ?>)
                                    <?php } ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <?php } ?>
                <?php } ?>

                <!-- Quantity + Add to cart -->
                <?php if ($product->in_stock) { ?>
                <div style="display:flex;align-items:center;gap:10px;margin:20px 0;">
                    <div class="spacart-qty-selector">
                        <button type="button" class="spacart-qty-minus">−</button>
                        <input type="number" class="spacart-qty-input" value="1" min="1" max="999">
                        <button type="button" class="spacart-qty-plus">+</button>
                    </div>
                    <button type="submit" class="btn btn-large" style="flex:1;">
                        <i class="material-icons left">add_shopping_cart</i>Ajouter au panier
                    </button>
                </div>
                <?php } ?>

            </form>

            <!-- Wishlist + Share -->
            <div style="margin:15px 0;display:flex;gap:10px;">
                <a href="#!" class="btn-flat spacart-wishlist-btn" data-product-id="<?php echo $product->rowid; ?>">
                    <i class="material-icons"><?php echo !empty($product->in_wishlist) ? 'favorite' : 'favorite_border'; ?></i>
                    Favoris
                </a>
                <a href="#!" class="btn-flat" id="spacart-share-btn" onclick="if(navigator.share)navigator.share({title:'<?php echo addslashes($product->label); ?>',url:window.location.href});">
                    <i class="material-icons">share</i>
                    Partager
                </a>
            </div>

            <!-- Send to friend -->
            <div class="input-field" style="display:flex;gap:5px;">
                <input type="email" id="spacart-friend-email" placeholder="Email d'un ami">
                <a href="#!" id="spacart-send-friend-btn" class="btn btn-small btn-flat">
                    <i class="material-icons">send</i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabs: Description, Specs, Reviews -->
<div class="spacart-product-tabs">
    <div class="row">
        <div class="col s12">
            <ul class="tabs">
                <li class="tab col s4"><a class="active" href="#tab-description">Description</a></li>
                <li class="tab col s4"><a href="#tab-specs">Détails</a></li>
                <li class="tab col s4"><a href="#tab-reviews">Avis (<?php echo $product->review_count; ?>)</a></li>
            </ul>
        </div>

        <div id="tab-description" class="col s12" style="padding:20px 0;">
            <div class="spacart-page-content">
                <?php echo $product->description; ?>
                <?php if ($product->note_public) { ?>
                    <div style="margin-top:15px;">
                        <?php echo $product->note_public; ?>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div id="tab-specs" class="col s12" style="padding:20px 0;">
            <table class="striped">
                <tbody>
                    <tr><td>Référence</td><td><?php echo htmlspecialchars($product->ref); ?></td></tr>
                    <?php if ($product->barcode) { ?>
                    <tr><td>Code-barres</td><td><?php echo htmlspecialchars($product->barcode); ?></td></tr>
                    <?php } ?>
                    <?php if ($product->weight) { ?>
                    <tr><td>Poids</td><td><?php echo $product->weight; ?> <?php echo $product->weight_units == -3 ? 'g' : 'kg'; ?></td></tr>
                    <?php } ?>
                    <?php if ($product->tva_tx) { ?>
                    <tr><td>TVA</td><td><?php echo $product->tva_tx; ?>%</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div id="tab-reviews" class="col s12" style="padding:20px 0;">
            <!-- Rating summary -->
            <?php if ($product->review_count > 0) { ?>
            <div class="spacart-rating-summary" style="margin-bottom:20px;">
                <span class="spacart-rating-big"><?php echo $product->avg_rating; ?></span>
                <div>
                    <div class="spacart-review-stars">
                        <?php for ($s = 1; $s <= 5; $s++) { ?>
                            <i class="material-icons"><?php echo $s <= round($product->avg_rating) ? 'star' : 'star_border'; ?></i>
                        <?php } ?>
                    </div>
                    <span class="grey-text"><?php echo $product->review_count; ?> avis</span>
                </div>
            </div>
            <?php } ?>

            <!-- Reviews list -->
            <?php if (!empty($product->reviews)) { ?>
                <?php foreach ($product->reviews as $review) { ?>
                <div class="spacart-review-item">
                    <div class="spacart-review-stars">
                        <?php for ($s = 1; $s <= 5; $s++) { ?>
                            <i class="material-icons tiny"><?php echo $s <= $review->rating ? 'star' : 'star_border'; ?></i>
                        <?php } ?>
                    </div>
                    <?php if ($review->title) { ?>
                        <strong><?php echo htmlspecialchars($review->title); ?></strong>
                    <?php } ?>
                    <p><?php echo nl2br(htmlspecialchars($review->comment)); ?></p>
                    <div class="spacart-review-meta">
                        Par <?php echo htmlspecialchars($review->customer_name); ?> le <?php echo date('d/m/Y', strtotime($review->date_creation)); ?>
                    </div>
                </div>
                <?php } ?>
            <?php } else { ?>
                <p class="grey-text">Aucun avis pour le moment.</p>
            <?php } ?>

            <!-- Add review form -->
            <div style="margin-top:20px;">
                <h6>Laisser un avis</h6>
                <form id="spacart-review-form">
                    <input type="hidden" name="product_id" value="<?php echo $product->rowid; ?>">
                    <div class="input-field">
                        <input type="text" name="customer_name" id="review-name" required>
                        <label for="review-name">Votre nom</label>
                    </div>
                    <div>
                        <label>Note</label>
                        <div style="margin:5px 0 15px;">
                            <?php for ($s = 1; $s <= 5; $s++) { ?>
                            <label style="cursor:pointer;">
                                <input type="radio" name="rating" value="<?php echo $s; ?>" <?php echo $s === 5 ? 'checked' : ''; ?> style="display:none;">
                                <i class="material-icons" style="color:#ffc107;"><?php echo $s <= 5 ? 'star' : 'star_border'; ?></i>
                            </label>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="input-field">
                        <input type="text" name="title" id="review-title">
                        <label for="review-title">Titre (optionnel)</label>
                    </div>
                    <div class="input-field">
                        <textarea name="comment" id="review-comment" class="materialize-textarea" required></textarea>
                        <label for="review-comment">Votre avis</label>
                    </div>
                    <button type="submit" class="btn">Publier l'avis</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Related Products -->
<?php if (!empty($product->related)) { ?>
<div class="section">
    <h5 class="spacart-section-title">Produits similaires</h5>
    <div class="row">
        <?php foreach ($product->related as $rProduct) {
            $product_card = $rProduct;
            // Use same variable name for the card template
            $product = $rProduct;
        ?>
            <div class="col l3 m6 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php }
        // Restore main product reference
        $product = $tpl_vars['product'];
        ?>
    </div>
</div>
<?php } ?>

<!-- Recently Viewed -->
<?php if (!empty($recently_viewed)) { ?>
<div class="section">
    <h5 class="spacart-section-title">Récemment consultés</h5>
    <div class="row">
        <?php foreach ($recently_viewed as $product) { ?>
            <div class="col l3 m6 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php }
        $product = $tpl_vars['product'];
        ?>
    </div>
</div>
<?php } ?>
