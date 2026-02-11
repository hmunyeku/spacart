<div class="container spacart-page">
    <h4>{$page_title}</h4>

    {if $single_article}
    <!-- Single news article -->
    <div class="row">
        <div class="col s12 m10 offset-m1">
            <article class="spacart-blog-single">
                {if $article->image}
                <div class="spacart-blog-hero" style="margin-bottom:20px;">
                    <img src="{$article->image}" alt="{$article->title}" class="responsive-img" style="width:100%;max-height:400px;object-fit:cover;border-radius:4px;">
                </div>
                {/if}
                <h4>{$article->title}</h4>
                <p class="grey-text"><i class="material-icons tiny">event</i> {$article->date_creation|date}</p>
                <div class="spacart-blog-content">{$article->content}</div>

                <!-- Comments -->
                <div class="section" style="margin-top:30px;">
                    <h5>{$comments_count} commentaire(s)</h5>
                    <div class="divider"></div>

                    {foreach $comments as $comment}
                    <div class="spacart-comment" style="padding:15px 0;border-bottom:1px solid #eee;">
                        <strong>{$comment->author_name}</strong>
                        <span class="grey-text right">{$comment->date_creation|date}</span>
                        <p>{$comment->content}</p>
                    </div>
                    {/foreach}

                    <div class="card-panel" style="margin-top:20px;">
                        <h6>Laisser un commentaire</h6>
                        <form class="spacart-comment-form" data-type="news" data-id="{$article->rowid}">
                            <div class="row">
                                <div class="input-field col s12 m6">
                                    <input type="text" name="author_name" id="nc_name" required>
                                    <label for="nc_name">Votre nom</label>
                                </div>
                                <div class="input-field col s12 m6">
                                    <input type="email" name="author_email" id="nc_email">
                                    <label for="nc_email">Email (optionnel)</label>
                                </div>
                            </div>
                            <div class="input-field">
                                <textarea name="content" id="nc_content" class="materialize-textarea" required></textarea>
                                <label for="nc_content">Votre commentaire</label>
                            </div>
                            <button type="submit" class="btn waves-effect" style="background:{$primary_color}">Envoyer</button>
                        </form>
                    </div>
                </div>
            </article>
        </div>
    </div>

    {else}
    <!-- News listing -->
    <div class="row">
        {foreach $articles as $article}
        <div class="col s12 m6 l4">
            <div class="card spacart-blog-card">
                {if $article->image}
                <div class="card-image">
                    <img src="{$article->image}" alt="{$article->title}" style="height:200px;object-fit:cover;">
                </div>
                {/if}
                <div class="card-content">
                    <span class="card-title">{$article->title}</span>
                    <p class="grey-text" style="margin-bottom:10px;"><i class="material-icons tiny">event</i> {$article->date_creation|date}</p>
                    <p>{$article->excerpt}</p>
                </div>
                <div class="card-action">
                    <a href="#/news/{$article->rowid}" class="spa-link" style="color:{$primary_color}">Lire la suite</a>
                    {if $article->nb_comments > 0}
                    <span class="grey-text right"><i class="material-icons tiny">comment</i> {$article->nb_comments}</span>
                    {/if}
                </div>
            </div>
        </div>
        {/foreach}
    </div>

    {if $total_pages > 1}
        {include="common/pagination"}
    {/if}

    {if empty($articles)}
    <div class="center-align" style="padding:40px;">
        <i class="material-icons large grey-text">article</i>
        <p class="grey-text">Aucune actualité pour le moment.</p>
    </div>
    {/if}
    {/if}
</div>
