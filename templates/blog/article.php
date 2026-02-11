<!-- Single Article -->

<div class="row" style="margin-top:15px;">
    <div class="col l8 offset-l2 m12 s12">

        <?php if ($article->image) { ?>
        <img class="responsive-img" src="<?php echo SPACART_URL; ?>/img/blog/<?php echo htmlspecialchars($article->image); ?>" alt="<?php echo htmlspecialchars($article->title); ?>" style="width:100%;border-radius:8px;margin-bottom:20px;">
        <?php } ?>

        <h4><?php echo htmlspecialchars($article->title); ?></h4>

        <div class="spacart-blog-meta" style="margin-bottom:20px;">
            <i class="material-icons tiny">calendar_today</i> <?php echo date('d/m/Y', strtotime($article->date_creation)); ?>
            <?php if ($article->author) { ?>
                | <i class="material-icons tiny">person</i> <?php echo htmlspecialchars($article->author); ?>
            <?php } ?>
        </div>

        <div class="spacart-page-content">
            <?php echo $article->content; ?>
        </div>

        <div class="divider" style="margin:30px 0;"></div>

        <!-- Comments -->
        <h5>Commentaires (<?php echo count($article->comments); ?>)</h5>

        <?php if (!empty($article->comments)) { ?>
            <?php foreach ($article->comments as $comment) { ?>
            <div class="spacart-comment-item">
                <strong><?php echo htmlspecialchars($comment->author_name); ?></strong>
                <span class="spacart-comment-meta"> - <?php echo date('d/m/Y H:i', strtotime($comment->date_creation)); ?></span>
                <p><?php echo nl2br(htmlspecialchars($comment->content)); ?></p>
            </div>
            <?php } ?>
        <?php } else { ?>
            <p class="grey-text">Aucun commentaire. Soyez le premier !</p>
        <?php } ?>

        <!-- Comment form -->
        <div style="margin-top:20px;">
            <h6>Laisser un commentaire</h6>
            <form id="spacart-comment-form" data-type="<?php echo $type; ?>">
                <input type="hidden" name="article_id" value="<?php echo $article->rowid; ?>">
                <div class="row" style="margin-bottom:0;">
                    <div class="input-field col s6">
                        <input type="text" name="author_name" id="comment-name" required>
                        <label for="comment-name">Votre nom</label>
                    </div>
                    <div class="input-field col s6">
                        <input type="email" name="author_email" id="comment-email">
                        <label for="comment-email">Email (optionnel)</label>
                    </div>
                </div>
                <div class="input-field">
                    <textarea name="content" id="comment-content" class="materialize-textarea" required></textarea>
                    <label for="comment-content">Votre commentaire</label>
                </div>
                <button type="submit" class="btn">Publier</button>
            </form>
        </div>

        <div style="margin-top:20px;">
            <a href="#/<?php echo $type; ?>" class="btn btn-flat spacart-spa-link">
                <i class="material-icons left">arrow_back</i> Retour
            </a>
        </div>
    </div>
</div>
