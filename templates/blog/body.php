<!-- Blog / News Listing -->

<h5 style="margin-top:15px;">
    <i class="material-icons left"><?php echo $type === 'news' ? 'newspaper' : 'article'; ?></i>
    <?php echo $type === 'news' ? 'Actualités' : 'Blog'; ?>
</h5>

<?php if (!empty($articles)) { ?>
<div class="row">
    <?php foreach ($articles as $article) { ?>
    <div class="col l4 m6 s12">
        <div class="card spacart-blog-card">
            <?php if ($article->image) { ?>
            <div class="card-image">
                <img src="<?php echo SPACART_URL; ?>/img/blog/<?php echo htmlspecialchars($article->image); ?>" alt="<?php echo htmlspecialchars($article->title); ?>">
            </div>
            <?php } ?>
            <div class="card-content">
                <div class="spacart-blog-meta">
                    <i class="material-icons tiny">calendar_today</i> <?php echo date('d/m/Y', strtotime($article->date_creation)); ?>
                    <?php if ($article->author) { ?> | <?php echo htmlspecialchars($article->author); ?><?php } ?>
                    <?php if (!empty($article->comment_count)) { ?> | <i class="material-icons tiny">comment</i> <?php echo $article->comment_count; ?><?php } ?>
                </div>
                <span class="card-title" style="font-size:1.1rem;"><?php echo htmlspecialchars($article->title); ?></span>
                <p class="spacart-blog-excerpt"><?php echo htmlspecialchars($article->excerpt); ?></p>
            </div>
            <div class="card-action">
                <a href="#/<?php echo $type; ?>/<?php echo $article->rowid; ?>" class="spacart-spa-link">Lire la suite</a>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<?php if ($total_pages > 1) { ?>
    <?php include SPACART_TPL_PATH.'/common/pagination.php'; ?>
<?php } ?>

<?php } else { ?>
<div class="spacart-empty-state">
    <i class="material-icons large grey-text">article</i>
    <p>Aucun article pour le moment</p>
</div>
<?php } ?>
