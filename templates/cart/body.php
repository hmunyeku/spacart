<!-- Cart Page -->

<h5 style="margin-top:15px;">
    <i class="material-icons left">shopping_cart</i>Mon Panier
</h5>

<?php if ($cart && !empty($cart->items)) { ?>

<div class="row">
    <!-- Cart Items -->
    <div class="col l8 m12 s12">
        <table class="spacart-cart-table">
            <thead>
                <tr>
                    <th style="width:80px;">Image</th>
                    <th>Produit</th>
                    <th style="width:100px;">Prix</th>
                    <th style="width:120px;">Quantité</th>
                    <th style="width:100px;">Total</th>
                    <th style="width:50px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart->items as $item) { ?>
                <tr>
                    <td>
                        <img class="cart-item-img" src="<?php echo spacart_product_photo_url($item->fk_product, $item->ref); ?>" alt="<?php echo htmlspecialchars($item->label); ?>">
                    </td>
                    <td>
                        <a href="#/product/<?php echo $item->fk_product; ?>" class="cart-item-name spacart-spa-link">
                            <?php echo htmlspecialchars($item->label); ?>
                        </a>
                        <?php if (!empty($item->options)) { ?>
                            <div class="cart-item-variant">
                                <?php foreach ($item->options as $opt) { ?>
                                    <span><?php echo htmlspecialchars($opt['group_label']); ?>: <?php echo htmlspecialchars($opt['option_label']); ?></span><br>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <small class="grey-text">Réf: <?php echo htmlspecialchars($item->ref); ?></small>
                    </td>
                    <td>
                        <?php echo spacartFormatPrice($item->price_ht); ?>
                    </td>
                    <td>
                        <div class="spacart-qty-selector" style="display:inline-flex;">
                            <button type="button" onclick="var i=this.nextElementSibling;i.value=Math.max(1,parseInt(i.value)-1);i.dispatchEvent(new Event('change'));">−</button>
                            <input type="number" class="spacart-cart-qty" data-item-id="<?php echo $item->rowid; ?>" value="<?php echo (int) $item->qty; ?>" min="1" style="width:45px;">
                            <button type="button" onclick="var i=this.previousElementSibling;i.value=parseInt(i.value)+1;i.dispatchEvent(new Event('change'));">+</button>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo spacartFormatPrice($item->price_ht * $item->qty); ?></strong>
                    </td>
                    <td>
                        <a href="#!" class="spacart-cart-remove" data-item-id="<?php echo $item->rowid; ?>" title="Supprimer">
                            <i class="material-icons red-text">delete</i>
                        </a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Cart Summary -->
    <div class="col l4 m12 s12">
        <div class="spacart-cart-summary">
            <h6 style="margin-top:0;font-weight:600;">Récapitulatif</h6>

            <div class="spacart-cart-summary-row">
                <span>Sous-total</span>
                <span><?php echo spacartFormatPrice($cart->subtotal); ?></span>
            </div>

            <?php if ($cart->tax_amount > 0) { ?>
            <div class="spacart-cart-summary-row">
                <span>TVA</span>
                <span><?php echo spacartFormatPrice($cart->tax_amount); ?></span>
            </div>
            <?php } ?>

            <?php if ($cart->shipping_cost > 0) { ?>
            <div class="spacart-cart-summary-row">
                <span>Livraison</span>
                <span><?php echo spacartFormatPrice($cart->shipping_cost); ?></span>
            </div>
            <?php } ?>

            <?php if ($cart->coupon_discount > 0) { ?>
            <div class="spacart-cart-summary-row" style="color:#4caf50;">
                <span>Promo (<?php echo htmlspecialchars($cart->coupon_code); ?>)</span>
                <span>-<?php echo spacartFormatPrice($cart->coupon_discount); ?></span>
            </div>
            <?php } ?>

            <?php if ($cart->giftcard_amount > 0) { ?>
            <div class="spacart-cart-summary-row" style="color:#4caf50;">
                <span>Carte cadeau</span>
                <span>-<?php echo spacartFormatPrice($cart->giftcard_amount); ?></span>
            </div>
            <?php } ?>

            <div class="spacart-cart-summary-row total">
                <span>Total</span>
                <span><?php echo spacartFormatPrice($cart->total); ?></span>
            </div>

            <!-- Coupon Code -->
            <?php if (empty($cart->coupon_code)) { ?>
            <div class="spacart-coupon-form" style="margin-top:15px;">
                <input type="text" id="spacart-coupon-code" placeholder="Code promo" style="margin:0;">
                <a href="#!" id="spacart-apply-coupon" class="btn btn-small">OK</a>
            </div>
            <?php } ?>

            <!-- Gift Card -->
            <?php if (empty($cart->giftcard_code)) { ?>
            <div class="spacart-coupon-form">
                <input type="text" id="spacart-giftcard-code" placeholder="Carte cadeau" style="margin:0;">
                <a href="#!" id="spacart-apply-giftcard" class="btn btn-small">OK</a>
            </div>
            <?php } ?>

            <!-- Checkout button -->
            <div style="margin-top:20px;">
                <a href="#/checkout" class="btn btn-large spacart-spa-link" style="width:100%;background:var(--spacart-primary-dark);">
                    <i class="material-icons left">lock</i>Commander
                </a>
            </div>

            <!-- Continue shopping -->
            <div class="center-align" style="margin-top:10px;">
                <a href="#/products" class="spacart-spa-link grey-text" style="font-size:0.9rem;">
                    ← Continuer mes achats
                </a>
            </div>
        </div>
    </div>
</div>

<?php } else { ?>

<div class="spacart-empty-state">
    <i class="material-icons large grey-text">shopping_cart</i>
    <p>Votre panier est vide</p>
    <a href="#/products" class="btn spacart-spa-link">Découvrir nos produits</a>
</div>

<?php } ?>
