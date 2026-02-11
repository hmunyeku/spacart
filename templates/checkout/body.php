<!-- Checkout Page - 3 Steps -->

<form id="spacart-checkout-form">

<!-- Steps Indicator -->
<ul class="spacart-checkout-steps">
    <li class="spacart-checkout-step active">
        <span class="step-number">1</span> Identification
    </li>
    <span class="spacart-checkout-step-separator"></span>
    <li class="spacart-checkout-step">
        <span class="step-number">2</span> Livraison
    </li>
    <span class="spacart-checkout-step-separator"></span>
    <li class="spacart-checkout-step">
        <span class="step-number">3</span> Paiement
    </li>
</ul>

<div class="row">
    <!-- Main Content -->
    <div class="col l8 m12 s12">

        <!-- Step 1: Identification -->
        <div class="spacart-checkout-panel active" id="checkout-step-1">
            <div class="card-panel">
                <h5>Identification</h5>

                <?php if ($is_logged_in && $customer) { ?>
                    <p>Connecté en tant que <strong><?php echo htmlspecialchars($customer->firstname.' '.$customer->lastname); ?></strong> (<?php echo htmlspecialchars($customer->email); ?>)</p>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($customer->email); ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $customer->rowid; ?>">
                <?php } else { ?>
                    <div style="margin-bottom:15px;">
                        <a href="#!" id="spacart-checkout-guest" class="btn btn-small">Commander en invité</a>
                        <a href="#!" id="spacart-checkout-login" class="btn btn-small btn-flat">J'ai un compte</a>
                    </div>

                    <!-- Guest form -->
                    <div id="spacart-checkout-guest-form">
                        <div class="row" style="margin-bottom:0;">
                            <div class="input-field col s6">
                                <input type="text" name="firstname" id="co-firstname" required>
                                <label for="co-firstname">Prénom *</label>
                            </div>
                            <div class="input-field col s6">
                                <input type="text" name="lastname" id="co-lastname" required>
                                <label for="co-lastname">Nom *</label>
                            </div>
                        </div>
                        <div class="input-field">
                            <input type="email" name="email" id="co-email" required>
                            <label for="co-email">Email *</label>
                        </div>
                        <div class="input-field">
                            <input type="tel" name="phone" id="co-phone">
                            <label for="co-phone">Téléphone</label>
                        </div>
                    </div>

                    <!-- Login form -->
                    <div id="spacart-checkout-login-form" style="display:none;">
                        <div class="input-field">
                            <input type="email" name="login_email" id="co-login-email">
                            <label for="co-login-email">Email</label>
                        </div>
                        <div class="input-field">
                            <input type="password" name="login_password" id="co-login-password">
                            <label for="co-login-password">Mot de passe</label>
                        </div>
                        <a href="#!" class="btn btn-small" onclick="SpaCart.util.ajax({url:SpaCartConfig.apiUrl+'/customer/login',method:'POST',data:{email:document.getElementById('co-login-email').value,password:document.getElementById('co-login-password').value,token:SpaCartConfig.sessionToken},success:function(r){if(r.success)window.location.reload();else SpaCart.util.toast(r.message,'error');}});">Se connecter</a>
                    </div>
                <?php } ?>

                <div class="right-align" style="margin-top:20px;">
                    <a href="#!" class="btn spacart-checkout-next">
                        Continuer <i class="material-icons right">arrow_forward</i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Step 2: Shipping Address & Method -->
        <div class="spacart-checkout-panel" id="checkout-step-2">
            <div class="card-panel">
                <h5>Adresse de livraison</h5>

                <?php if (!empty($addresses)) { ?>
                <div style="margin-bottom:15px;">
                    <p>Utiliser une adresse existante :</p>
                    <?php foreach ($addresses as $addr) { if ($addr->type === 'shipping') { ?>
                    <label>
                        <input type="radio" name="use_address" value="<?php echo $addr->rowid; ?>" class="with-gap"
                               data-fn="<?php echo htmlspecialchars($addr->firstname); ?>"
                               data-ln="<?php echo htmlspecialchars($addr->lastname); ?>"
                               data-addr="<?php echo htmlspecialchars($addr->address); ?>"
                               data-zip="<?php echo htmlspecialchars($addr->zip); ?>"
                               data-city="<?php echo htmlspecialchars($addr->city); ?>"
                               data-country="<?php echo $addr->fk_country; ?>"
                               data-phone="<?php echo htmlspecialchars($addr->phone); ?>"
                               <?php echo $addr->is_default ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars($addr->firstname.' '.$addr->lastname.', '.$addr->address.', '.$addr->zip.' '.$addr->city); ?></span>
                    </label><br>
                    <?php } } ?>
                    <label>
                        <input type="radio" name="use_address" value="new" class="with-gap">
                        <span>Nouvelle adresse</span>
                    </label>
                </div>
                <?php } ?>

                <div id="spacart-new-shipping-address">
                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s6">
                            <input type="text" name="shipping_firstname" id="co-ship-fn" required>
                            <label for="co-ship-fn">Prénom *</label>
                        </div>
                        <div class="input-field col s6">
                            <input type="text" name="shipping_lastname" id="co-ship-ln" required>
                            <label for="co-ship-ln">Nom *</label>
                        </div>
                    </div>
                    <div class="input-field">
                        <input type="text" name="shipping_address" id="co-ship-addr" required>
                        <label for="co-ship-addr">Adresse *</label>
                    </div>
                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s4">
                            <input type="text" name="shipping_zip" id="co-ship-zip" required>
                            <label for="co-ship-zip">Code postal *</label>
                        </div>
                        <div class="input-field col s8">
                            <input type="text" name="shipping_city" id="co-ship-city" required>
                            <label for="co-ship-city">Ville *</label>
                        </div>
                    </div>
                    <div class="input-field">
                        <select name="shipping_country" class="browser-default">
                            <?php foreach ($countries as $c) { ?>
                                <option value="<?php echo $c->rowid; ?>" <?php echo $c->code === 'FR' ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c->label); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="input-field">
                        <input type="tel" name="shipping_phone" id="co-ship-phone">
                        <label for="co-ship-phone">Téléphone</label>
                    </div>
                </div>

                <!-- Same billing address? -->
                <p style="margin-top:15px;">
                    <label>
                        <input type="checkbox" name="same_billing" id="spacart-same-billing" class="filled-in" checked>
                        <span>Adresse de facturation identique</span>
                    </label>
                </p>

                <!-- Billing address (hidden by default) -->
                <div id="spacart-billing-address-form" style="display:none;margin-top:15px;">
                    <h6>Adresse de facturation</h6>
                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s6">
                            <input type="text" name="billing_firstname" id="co-bill-fn">
                            <label for="co-bill-fn">Prénom</label>
                        </div>
                        <div class="input-field col s6">
                            <input type="text" name="billing_lastname" id="co-bill-ln">
                            <label for="co-bill-ln">Nom</label>
                        </div>
                    </div>
                    <div class="input-field">
                        <input type="text" name="billing_address" id="co-bill-addr">
                        <label for="co-bill-addr">Adresse</label>
                    </div>
                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s4">
                            <input type="text" name="billing_zip" id="co-bill-zip">
                            <label for="co-bill-zip">Code postal</label>
                        </div>
                        <div class="input-field col s8">
                            <input type="text" name="billing_city" id="co-bill-city">
                            <label for="co-bill-city">Ville</label>
                        </div>
                    </div>
                    <div class="input-field">
                        <select name="billing_country" class="browser-default">
                            <?php foreach ($countries as $c) { ?>
                                <option value="<?php echo $c->rowid; ?>" <?php echo $c->code === 'FR' ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c->label); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="divider" style="margin:20px 0;"></div>

                <!-- Shipping methods -->
                <h6>Méthode de livraison</h6>
                <?php if (!empty($shipping_methods)) { ?>
                    <?php foreach ($shipping_methods as $idx => $method) { ?>
                    <p>
                        <label>
                            <input type="radio" name="shipping_method" value="<?php echo $method->rowid; ?>"
                                   class="with-gap" <?php echo $idx === 0 ? 'checked' : ''; ?>
                                   data-cost="<?php echo $method->cost; ?>">
                            <span>
                                <strong><?php echo htmlspecialchars($method->label); ?></strong>
                                <?php if ($method->delivery_time) { ?>
                                    <small class="grey-text">(<?php echo htmlspecialchars($method->delivery_time); ?>)</small>
                                <?php } ?>
                                -
                                <?php if ($method->is_free) { ?>
                                    <span style="color:#4caf50;font-weight:700;">Gratuit</span>
                                <?php } else { ?>
                                    <strong><?php echo spacartFormatPrice($method->cost); ?></strong>
                                <?php } ?>
                            </span>
                        </label>
                    </p>
                    <?php } ?>
                <?php } else { ?>
                    <p class="grey-text">Aucune méthode de livraison disponible</p>
                <?php } ?>

                <div style="display:flex;justify-content:space-between;margin-top:20px;">
                    <a href="#!" class="btn btn-flat spacart-checkout-prev">
                        <i class="material-icons left">arrow_back</i> Retour
                    </a>
                    <a href="#!" class="btn spacart-checkout-next">
                        Continuer <i class="material-icons right">arrow_forward</i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Step 3: Payment -->
        <div class="spacart-checkout-panel" id="checkout-step-3">
            <div class="card-panel">
                <h5>Paiement</h5>

                <!-- Payment methods -->
                <div style="margin-bottom:20px;">
                    <?php if (!empty($stripe_enabled) && !empty($stripe_pk)) { ?>
                    <p>
                        <label>
                            <input type="radio" name="payment_method" value="stripe" class="with-gap" checked>
                            <span><strong>Carte bancaire</strong> <i class="material-icons tiny">credit_card</i></span>
                        </label>
                    </p>
                    <?php } ?>

                    <?php if (!empty($paypal_enabled)) { ?>
                    <p>
                        <label>
                            <input type="radio" name="payment_method" value="paypal" class="with-gap" <?php echo empty($stripe_enabled) ? 'checked' : ''; ?>>
                            <span><strong>PayPal</strong></span>
                        </label>
                    </p>
                    <?php } ?>

                    <p>
                        <label>
                            <input type="radio" name="payment_method" value="bank_transfer" class="with-gap" <?php echo empty($stripe_enabled) && empty($paypal_enabled) ? 'checked' : ''; ?>>
                            <span><strong>Virement bancaire</strong></span>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="radio" name="payment_method" value="cod" class="with-gap">
                            <span><strong>Paiement à la livraison</strong></span>
                        </label>
                    </p>
                </div>

                <!-- Stripe card element placeholder (uses Dolibarr Stripe SDK) -->
                <?php if (!empty($stripe_enabled) && !empty($stripe_pk)) { ?>
                <div id="spacart-stripe-card" style="display:none;padding:15px;border:1px solid #ddd;border-radius:4px;margin-bottom:15px;">
                    <div id="card-element"></div>
                    <div id="card-errors" style="color:#ff5252;margin-top:8px;font-size:0.85rem;"></div>
                </div>
                <script>
                    // Pass Dolibarr Stripe publishable key to JS
                    if (typeof SpaCartConfig !== 'undefined') {
                        SpaCartConfig.stripePk = '<?php echo $stripe_pk; ?>';
                    }
                </script>
                <?php } ?>

                <!-- Order notes -->
                <div class="input-field">
                    <textarea name="order_notes" id="co-notes" class="materialize-textarea"></textarea>
                    <label for="co-notes">Notes de commande (optionnel)</label>
                </div>

                <div style="display:flex;justify-content:space-between;margin-top:20px;">
                    <a href="#!" class="btn btn-flat spacart-checkout-prev">
                        <i class="material-icons left">arrow_back</i> Retour
                    </a>
                    <a href="#!" class="btn btn-large" id="spacart-place-order" style="background:var(--spacart-primary-dark);">
                        <i class="material-icons left">lock</i> Confirmer la commande
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Order Summary Sidebar -->
    <div class="col l4 m12 s12">
        <div class="spacart-checkout-order-summary">
            <h6 style="margin-top:0;font-weight:600;">Votre commande</h6>

            <?php foreach ($cart->items as $item) { ?>
            <div style="display:flex;align-items:center;padding:8px 0;border-bottom:1px solid #eee;">
                <img src="<?php echo spacart_product_photo_url($item->fk_product, $item->ref); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:3px;margin-right:8px;">
                <div style="flex:1;min-width:0;">
                    <span style="font-size:0.85rem;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?php echo htmlspecialchars($item->label); ?>
                    </span>
                    <small class="grey-text">x<?php echo (int) $item->qty; ?></small>
                </div>
                <span style="font-size:0.85rem;font-weight:500;"><?php echo spacartFormatPrice($item->price_ht * $item->qty); ?></span>
            </div>
            <?php } ?>

            <div style="margin-top:10px;">
                <div class="spacart-cart-summary-row">
                    <span>Sous-total</span>
                    <span><?php echo spacartFormatPrice($cart->subtotal); ?></span>
                </div>
                <div class="spacart-cart-summary-row">
                    <span>Livraison</span>
                    <span id="spacart-checkout-shipping-cost"><?php echo spacartFormatPrice($cart->shipping_cost); ?></span>
                </div>
                <?php if ($cart->coupon_discount > 0) { ?>
                <div class="spacart-cart-summary-row" style="color:#4caf50;">
                    <span>Promo</span>
                    <span>-<?php echo spacartFormatPrice($cart->coupon_discount); ?></span>
                </div>
                <?php } ?>
                <div class="spacart-cart-summary-row total">
                    <span>Total</span>
                    <span id="spacart-checkout-total"><?php echo spacartFormatPrice($cart->total); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

</form>
