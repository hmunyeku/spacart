<!-- Profile Page -->

<h5 style="margin-top:15px;">
    <i class="material-icons left">person</i>Mon Compte
</h5>

<div class="row">
    <div class="col l8 m12 s12">

        <!-- Profile Info -->
        <div class="card spacart-profile-section">
            <div class="card-content">
                <span class="card-title">Informations personnelles</span>
                <form id="spacart-profile-form">
                    <div class="row" style="margin-bottom:0;">
                        <div class="input-field col s6">
                            <input type="text" name="firstname" id="prof-firstname" value="<?php echo htmlspecialchars($customer->firstname); ?>">
                            <label for="prof-firstname" class="active">Prénom</label>
                        </div>
                        <div class="input-field col s6">
                            <input type="text" name="lastname" id="prof-lastname" value="<?php echo htmlspecialchars($customer->lastname); ?>">
                            <label for="prof-lastname" class="active">Nom</label>
                        </div>
                    </div>
                    <div class="input-field">
                        <input type="email" name="email" id="prof-email" value="<?php echo htmlspecialchars($customer->email); ?>">
                        <label for="prof-email" class="active">Email</label>
                    </div>
                    <div class="input-field">
                        <input type="tel" name="phone" id="prof-phone" value="<?php echo htmlspecialchars($customer->phone); ?>">
                        <label for="prof-phone" class="active">Téléphone</label>
                    </div>
                    <div class="input-field">
                        <input type="text" name="company" id="prof-company" value="<?php echo htmlspecialchars($customer->company); ?>">
                        <label for="prof-company" class="active">Société</label>
                    </div>

                    <div class="divider" style="margin:15px 0;"></div>
                    <p class="grey-text">Changer le mot de passe (laisser vide pour ne pas modifier)</p>
                    <div class="input-field">
                        <input type="password" name="new_password" id="prof-newpwd">
                        <label for="prof-newpwd">Nouveau mot de passe</label>
                    </div>

                    <button type="submit" class="btn">
                        <i class="material-icons left">save</i>Enregistrer
                    </button>
                </form>
            </div>
        </div>

        <!-- Addresses -->
        <div class="card spacart-profile-section">
            <div class="card-content">
                <span class="card-title">Mes adresses</span>
                <?php if (!empty($addresses)) { ?>
                    <?php foreach ($addresses as $addr) { ?>
                    <div class="spacart-address-card <?php echo $addr->is_default ? 'default' : ''; ?>">
                        <?php if ($addr->is_default) { ?>
                            <span class="badge" style="background:var(--spacart-primary);color:#fff;border-radius:3px;padding:2px 8px;font-size:0.75rem;">Par défaut</span>
                        <?php } ?>
                        <strong><?php echo htmlspecialchars($addr->firstname.' '.$addr->lastname); ?></strong><br>
                        <?php echo htmlspecialchars($addr->address); ?><br>
                        <?php echo htmlspecialchars($addr->zip.' '.$addr->city); ?><br>
                        <?php if ($addr->country_name) echo htmlspecialchars($addr->country_name); ?><br>
                        <?php if ($addr->phone) { ?><i class="material-icons tiny">phone</i> <?php echo htmlspecialchars($addr->phone); ?><?php } ?>
                        <span class="chip" style="font-size:0.75rem;"><?php echo $addr->type === 'billing' ? 'Facturation' : 'Livraison'; ?></span>
                    </div>
                    <?php } ?>
                <?php } else { ?>
                    <p class="grey-text">Aucune adresse enregistrée.</p>
                <?php } ?>
            </div>
        </div>

    </div>

    <!-- Sidebar: Orders -->
    <div class="col l4 m12 s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">Mes commandes</span>
                <?php if (!empty($orders)) { ?>
                    <table class="striped" style="font-size:0.85rem;">
                        <thead>
                            <tr><th>Réf</th><th>Date</th><th>Total</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order) { ?>
                            <tr class="spacart-order-row">
                                <td>
                                    <a href="#/invoice/<?php echo $order->rowid; ?>" class="spacart-spa-link"><?php echo htmlspecialchars($order->ref); ?></a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($order->date_commande ?: $order->date_creation)); ?></td>
                                <td><?php echo spacartFormatPrice($order->total_ttc); ?></td>
                                <td><span class="chip" style="font-size:0.7rem;"><?php echo $order->status_label; ?></span></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="grey-text">Aucune commande</p>
                <?php } ?>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card">
            <div class="card-content">
                <a href="#/wishlist" class="spacart-spa-link"><i class="material-icons left">favorite</i>Ma wishlist</a><br><br>
                <a href="#/orders" class="spacart-spa-link"><i class="material-icons left">receipt</i>Toutes mes commandes</a><br><br>
                <a href="#!" id="spacart-logout-link" style="color:#ff5252;"><i class="material-icons left">logout</i>Déconnexion</a>
            </div>
        </div>
    </div>
</div>
