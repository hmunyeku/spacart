<div class="container spacart-page">
    <h4>{$page_title}</h4>

    <div class="row">
        <div class="col s12 m8 offset-m2">
            <div class="card">
                <div class="card-content">
                    <span class="card-title center-align"><i class="material-icons medium" style="color:{$primary_color}">card_giftcard</i></span>
                    <h5 class="center-align">Vérifier le solde d'une carte cadeau</h5>

                    <form method="POST" class="spacart-giftcard-check" style="margin-top:20px;">
                        <input type="hidden" name="action" value="check_balance">
                        <div class="input-field">
                            <input type="text" name="code" id="gc_code" value="{$code_value}" required style="text-transform:uppercase;letter-spacing:2px;font-size:18px;text-align:center;">
                            <label for="gc_code">Code de la carte cadeau</label>
                        </div>
                        <div class="center-align">
                            <button type="submit" class="btn waves-effect" style="background:{$primary_color}">
                                <i class="material-icons left">search</i>Vérifier le solde
                            </button>
                        </div>
                    </form>

                    {if $code_checked}
                        {if $giftcard_info === false}
                        <div class="card-panel red lighten-4 center-align" style="margin-top:20px;">
                            <i class="material-icons">error</i>
                            <p>Aucune carte cadeau trouvée avec ce code.</p>
                        </div>
                        {/if}

                        {if $giftcard_info && $giftcard_info->expired}
                        <div class="card-panel orange lighten-4 center-align" style="margin-top:20px;">
                            <i class="material-icons">warning</i>
                            <p>Cette carte cadeau a expiré.</p>
                        </div>
                        {/if}

                        {if $giftcard_info && !$giftcard_info->expired}
                        <div class="card-panel green lighten-4 center-align" style="margin-top:20px;">
                            <h5 style="margin:10px 0 5px;">Solde disponible</h5>
                            <p style="font-size:32px;font-weight:bold;color:#2e7d32;margin:10px 0;">{price $giftcard_info->current_balance}</p>
                            <p class="grey-text">Montant initial : {price $giftcard_info->initial_balance}</p>
                            {if $giftcard_info->expires_at}
                            <p class="grey-text">Valable jusqu'au : {$giftcard_info->expires_at|date}</p>
                            {/if}
                        </div>
                        {/if}
                    {/if}
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <span class="card-title">Comment utiliser votre carte cadeau ?</span>
                    <ol style="line-height:2;">
                        <li>Ajoutez des articles à votre panier</li>
                        <li>Lors du passage en caisse, entrez le code de votre carte cadeau</li>
                        <li>Le montant sera automatiquement déduit de votre total</li>
                        <li>Si le solde est insuffisant, vous pouvez compléter avec un autre moyen de paiement</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
