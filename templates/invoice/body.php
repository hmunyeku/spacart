<!-- Invoice / Order Detail Page -->

<div class="card-panel" style="margin-top:15px;">
    <div class="row" style="margin-bottom:0;">
        <div class="col s8">
            <h5 style="margin-top:0;">Commande <?php echo htmlspecialchars($order->ref); ?></h5>
            <p class="grey-text">
                Date : <?php echo date('d/m/Y H:i', $order->date_commande ?: strtotime($order->date_creation)); ?>
            </p>
        </div>
        <div class="col s4 right-align">
            <?php
            $statusColors = array(-1 => 'red', 0 => 'grey', 1 => 'blue', 2 => 'orange', 3 => 'green');
            $statusLabels = array(-1 => 'Annulée', 0 => 'Brouillon', 1 => 'Validée', 2 => 'En cours', 3 => 'Livrée');
            $color = $statusColors[$order->statut] ?? 'grey';
            $label = $statusLabels[$order->statut] ?? 'Inconnue';
            ?>
            <span class="chip <?php echo $color; ?> white-text"><?php echo $label; ?></span>
        </div>
    </div>
</div>

<!-- Order Lines -->
<table class="striped">
    <thead>
        <tr>
            <th>Produit</th>
            <th class="right-align">Prix unitaire</th>
            <th class="center-align">Quantité</th>
            <th class="right-align">TVA</th>
            <th class="right-align">Total HT</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($order->lines)) { foreach ($order->lines as $line) { ?>
        <tr>
            <td>
                <?php if ($line->fk_product > 0) { ?>
                    <a href="#/product/<?php echo $line->fk_product; ?>" class="spacart-spa-link"><?php echo htmlspecialchars($line->desc ?: $line->product_label); ?></a>
                <?php } else { ?>
                    <?php echo htmlspecialchars($line->desc); ?>
                <?php } ?>
            </td>
            <td class="right-align"><?php echo spacartFormatPrice($line->subprice); ?></td>
            <td class="center-align"><?php echo (int) $line->qty; ?></td>
            <td class="right-align"><?php echo $line->tva_tx; ?>%</td>
            <td class="right-align"><strong><?php echo spacartFormatPrice($line->total_ht); ?></strong></td>
        </tr>
        <?php } } ?>
    </tbody>
</table>

<!-- Totals -->
<div class="row">
    <div class="col l4 offset-l8 m6 offset-m6 s12">
        <div class="spacart-cart-summary" style="margin-top:15px;">
            <div class="spacart-cart-summary-row">
                <span>Total HT</span>
                <span><?php echo spacartFormatPrice($order->total_ht); ?></span>
            </div>
            <div class="spacart-cart-summary-row">
                <span>TVA</span>
                <span><?php echo spacartFormatPrice($order->total_tva); ?></span>
            </div>
            <div class="spacart-cart-summary-row total">
                <span>Total TTC</span>
                <span><?php echo spacartFormatPrice($order->total_ttc); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Notes -->
<?php if ($order->note_public) { ?>
<div class="card-panel" style="margin-top:15px;">
    <h6>Informations de livraison</h6>
    <pre style="font-family:inherit;white-space:pre-wrap;"><?php echo htmlspecialchars($order->note_public); ?></pre>
</div>
<?php } ?>

<!-- Actions -->
<div style="margin-top:20px;">
    <a href="#/profile" class="btn btn-flat spacart-spa-link">
        <i class="material-icons left">arrow_back</i> Retour au compte
    </a>
    <a href="javascript:window.print();" class="btn btn-flat right">
        <i class="material-icons left">print</i> Imprimer
    </a>
</div>
