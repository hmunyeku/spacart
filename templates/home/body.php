<!-- Home Page -->

<!-- Banners -->
<?php if (!empty($banners)) { ?>
<div class="spacart-banner-slider">
    <?php foreach ($banners as $idx => $banner) { ?>
        <div class="spacart-banner-slide <?php echo $idx === 0 ? 'active' : ''; ?>">
            <img src="<?php echo SPACART_URL; ?>/img/banners/<?php echo htmlspecialchars($banner->image); ?>" alt="<?php echo htmlspecialchars($banner->title); ?>">
            <?php if ($banner->title || $banner->subtitle) { ?>
            <div class="spacart-banner-overlay">
                <?php if ($banner->title) { ?><h2><?php echo htmlspecialchars($banner->title); ?></h2><?php } ?>
                <?php if ($banner->subtitle) { ?><p><?php echo htmlspecialchars($banner->subtitle); ?></p><?php } ?>
                <?php if ($banner->link) { ?>
                    <a href="<?php echo htmlspecialchars($banner->link); ?>" class="btn spacart-spa-link">Découvrir</a>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
    <div class="spacart-banner-dots">
        <?php foreach ($banners as $idx => $b) { ?>
            <span class="spacart-banner-dot <?php echo $idx === 0 ? 'active' : ''; ?>"></span>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- Top Categories -->
<?php if (!empty($top_categories)) { ?>
<div class="section">
    <h5 class="spacart-section-title center-align">Catégories</h5>
    <div class="row">
        <?php foreach ($top_categories as $cat) { ?>
        <div class="col l2 m4 s6">
            <div class="card-panel center-align hoverable" style="cursor:pointer;">
                <a href="#/category/<?php echo $cat->id; ?>" class="spacart-spa-link">
                    <i class="material-icons large" style="color:var(--spacart-primary);">category</i>
                    <p class="flow-text" style="font-size:1rem;"><?php echo htmlspecialchars($cat->label); ?></p>
                    <span class="grey-text" style="font-size:0.8rem;"><?php echo $cat->product_count; ?> produits</span>
                </a>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- Featured Products -->
<?php if (!empty($featured['items'])) { ?>
<div class="section">
    <h5 class="spacart-section-title">Produits en vedette</h5>
    <div class="row">
        <?php foreach ($featured['items'] as $product) { ?>
            <div class="col l3 m4 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- New Products -->
<?php if (!empty($new_products)) { ?>
<div class="section">
    <h5 class="spacart-section-title">Nouveautés</h5>
    <div class="row">
        <?php foreach ($new_products as $product) { ?>
            <div class="col l3 m4 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php } ?>
    </div>
    <div class="center-align">
        <a href="#/products?sort=date_desc" class="btn spacart-spa-link">Voir toutes les nouveautés</a>
    </div>
</div>
<?php } ?>

<!-- Best Sellers -->
<?php if (!empty($bestsellers)) { ?>
<div class="section">
    <h5 class="spacart-section-title">Meilleures ventes</h5>
    <div class="row">
        <?php foreach ($bestsellers as $product) { ?>
            <div class="col l3 m4 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- Most Viewed -->
<?php if (!empty($most_viewed)) { ?>
<div class="section">
    <h5 class="spacart-section-title">Les plus consultés</h5>
    <div class="row">
        <?php foreach ($most_viewed as $product) { ?>
            <div class="col l3 m4 s6">
                <?php include SPACART_TPL_PATH.'/common/product_card.php'; ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<!-- Testimonials -->
<?php if (!empty($testimonials)) { ?>
<div class="section" style="background:#f5f5f5;padding:30px 0;margin:0 -15px;">
    <div class="container">
        <h5 class="spacart-section-title center-align">Ce que disent nos clients</h5>
        <div class="row">
            <?php foreach ($testimonials as $test) { ?>
            <div class="col l4 m6 s12">
                <div class="card spacart-testimonial-card">
                    <?php if ($test->photo) { ?>
                        <img class="testimonial-avatar" src="<?php echo SPACART_URL; ?>/img/testimonials/<?php echo htmlspecialchars($test->photo); ?>" alt="">
                    <?php } else { ?>
                        <i class="material-icons large" style="color:#ccc;">account_circle</i>
                    <?php } ?>
                    <p class="testimonial-text">"<?php echo htmlspecialchars($test->content); ?>"</p>
                    <div class="spacart-review-stars">
                        <?php for ($s = 1; $s <= 5; $s++) { ?>
                            <i class="material-icons tiny"><?php echo $s <= $test->rating ? 'star' : 'star_border'; ?></i>
                        <?php } ?>
                    </div>
                    <p class="testimonial-name"><?php echo htmlspecialchars($test->customer_name); ?></p>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php } ?>
