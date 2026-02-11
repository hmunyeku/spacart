<!-- Testimonials Page -->

<h5 style="margin-top:15px;">
    <i class="material-icons left">format_quote</i>Témoignages clients
</h5>

<?php if (!empty($testimonials)) { ?>
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
            <small class="grey-text"><?php echo date('d/m/Y', strtotime($test->date_creation)); ?></small>
        </div>
    </div>
    <?php } ?>
</div>
<?php } else { ?>
<div class="spacart-empty-state">
    <i class="material-icons large grey-text">format_quote</i>
    <p>Aucun témoignage pour le moment</p>
</div>
<?php } ?>
