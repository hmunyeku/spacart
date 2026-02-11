<!-- Brands Page -->

<h5 style="margin-top:15px;">Nos Marques</h5>

<?php if (!empty($brands)) { ?>
<div class="row">
    <?php foreach ($brands as $brand) { ?>
    <div class="col l3 m4 s6">
        <a href="#/brands/<?php echo $brand->id; ?>" class="spacart-spa-link">
            <div class="card-panel center-align hoverable">
                <i class="material-icons large" style="color:<?php echo $brand->color ? htmlspecialchars($brand->color) : 'var(--spacart-primary)'; ?>;">loyalty</i>
                <h6><?php echo htmlspecialchars($brand->label); ?></h6>
                <span class="grey-text" style="font-size:0.85rem;"><?php echo $brand->product_count; ?> produit<?php echo $brand->product_count > 1 ? 's' : ''; ?></span>
            </div>
        </a>
    </div>
    <?php } ?>
</div>
<?php } else { ?>
<div class="spacart-empty-state">
    <i class="material-icons large grey-text">loyalty</i>
    <p>Aucune marque pour le moment</p>
</div>
<?php } ?>
