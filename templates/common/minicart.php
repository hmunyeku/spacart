<!-- Mini Cart Content -->
<div class="spacart-minicart">
    {if $cart['count'] > 0}
        <div class="spacart-minicart-items">
            <?php if (!empty($cart['items'])) { foreach ($cart['items'] as $item) { ?>
                <div class="spacart-minicart-item" data-id="<?php echo $item->rowid; ?>">
                    <div class="spacart-minicart-item-img">
                        <img src="<?php echo spacart_product_photo_url($item->fk_product, $item->ref); ?>" alt="<?php echo htmlspecialchars($item->label); ?>">
                    </div>
                    <div class="spacart-minicart-item-info">
                        <a href="#/product/<?php echo $item->fk_product; ?>" class="spacart-spa-link"><?php echo htmlspecialchars($item->label); ?></a>
                        <span class="spacart-minicart-item-qty"><?php echo (int) $item->qty; ?> x <?php echo spacartFormatPrice($item->price_ht); ?></span>
                    </div>
                    <a href="#!" class="spacart-minicart-remove" data-cart-item="<?php echo $item->rowid; ?>">
                        <i class="material-icons tiny">close</i>
                    </a>
                </div>
            <?php } } ?>
        </div>
        <div class="spacart-minicart-footer">
            <div class="spacart-minicart-total">
                <strong>Total : {price $cart['total']}</strong>
            </div>
            <a href="#/cart" class="btn btn-small spacart-spa-link" style="width:48%;">Panier</a>
            <a href="#/checkout" class="btn btn-small spacart-spa-link" style="width:48%;background:var(--spacart-primary-dark);">Commander</a>
        </div>
    {else}
        <div class="spacart-minicart-empty center-align" style="padding:30px;">
            <i class="material-icons large grey-text">shopping_cart</i>
            <p class="grey-text">Votre panier est vide</p>
            <a href="#/products" class="btn btn-small spacart-spa-link">Decouvrir</a>
        </div>
    {/if}
</div>
