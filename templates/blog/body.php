{if $blog}

{if $config['General']['recaptcha_key']}
<script src='https://www.google.com/recaptcha/api.js'></script>
{/if}

<div class="blog">
<div class="actions">
{if $comments}
<img src="{$current_location}/images/comments.gif" alt="" /> <a href="{$_REQUEST_URI}#comments">{lng[Go to comments|escape]}</a>&nbsp;
{/if}
<img src="{$current_location}/images/leave_comment.gif" alt="" /> <a href="{$REQUEST_URI}#leave_comment">{lng[Leave a comment]}</a>
</div>
<h1>{$blog['title']}</h1>
<br />
<img src="{$current_location}/images/blog_date.gif" alt="" /> {php echo date($datetime_format, $blog['date']);}
{if $blog['imageid']}
<div class="image">
<?php
	$image = $blog;
	$image['new_width'] = 750;
	$image['new_height'] = 400;
	include SITE_ROOT . '/includes/blog_image.php';
?>
</div>
{/if}
<div class="fulldescr">{$blog['fulldescr']}</div>

<?php
	if ($comments) {
?>
<a name="comments"></a>
<h3><?php echo $blog['comments'] ? $blog['comments'] : "0"; ?> {lng[Blog comments]}</h3>

<div align="right">{include="common/navigation.php"}</div>

<div class="blog_comments">
{foreach $comments as $k=>$v}
<div class="author">
<div class="quote">
<img src="{$current_location}/images/quote.gif" alt="" /> <a href="javascript: void(0);" onclick="javascript: quote_comment('{$v['commentid']}', '{php $name = $v['name'] ? $v['name'] : $v['firstname'].' '.$v['lastname']; $name = escape($name, 1); echo escape($name, 2);}');">{lng[Quote]}</a>
</div>
{lng[Comment from]} <b>{php echo $v['name'] ? $v['name'] : $v['firstname'].' '.$v['lastname'];}</b>. {lng[Commented on]} {php echo date($datetime_format, $v['date']);}.
</div>
<span id="comment_{$v['commentid']}" class="hidden">{$v['message']}</span>
<div class="comment">
{$v['bb_message']}
</div>
{/foreach}
</div>
{else}
<br /><center>{lng[No comments yet]}</center><br /><br />
{/if}

<a name="leave_comment"></a>
<h3>{lng[Leave a comment]}</h3>

{if !$login && $config['Blog']['blog_guests'] != "Y"}
<br />
<center>{lng[You should be a registered user to leave a comment.]}</center>
<br /><br />
{else}
<form method="post" name="blogform">
<input type="hidden" name="blogid" value="<?php echo $blog['blogid']; ?>">
<a onmouseover="javascript: bbhelp('b');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[b]','[/b]');"><img alt="" src="{$current_location}/images/bbcodes/b.png" tabindex="-1"></a>
<a onmouseover="javascript: bbhelp('i');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[i]','[/i]')"><img alt="" src="{$current_location}/images/bbcodes/i.png" tabindex="-2"></a>
<a onmouseover="javascript: bbhelp('u');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[u]','[/u]')"><img alt="" src="{$current_location}/images/bbcodes/u.png" tabindex="-3"></a>
<a onmouseover="javascript: bbhelp('s');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[s]','[/s]')"><img alt="" src="{$current_location}/images/bbcodes/s.png" tabindex="-3"></a>
<a onmouseover="javascript: bbhelp('url');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[url]','[/url]')"><img alt="" src="{$current_location}/images/bbcodes/url.png" tabindex="-4"></a>
<a onmouseover="javascript: bbhelp('email');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[email]','[/email]')"><img alt="" src="{$current_location}/images/bbcodes/email.png" tabindex="-5"></a>
<a onmouseover="javascript: bbhelp('img');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[img]','[/img]')"><img alt="" src="{$current_location}/images/bbcodes/img.png" tabindex="-6"></a>
<a onmouseover="javascript: bbhelp('list');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[list][*]','[/*][/list]')"><img alt="" src="{$current_location}/images/bbcodes/list.png" tabindex="-7"></a>
<a onmouseover="javascript: bbhelp('li');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[*]','[/*]')"><img alt="" src="{$current_location}/images/bbcodes/li.png" tabindex="-8"></a>
<a onmouseover="javascript: bbhelp('quote');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[quote]','[/quote]')"><img alt="" src="{$current_location}/images/bbcodes/quote.png" tabindex="-9"></a>
<a onmouseover="javascript: bbhelp('code');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[code]','[/code]')"><img alt="" src="{$current_location}/images/bbcodes/code.png" tabindex="-10"></a>

&nbsp;
<input size="40" id="helptext" readonly>
<br />

<textarea name="comment" cols="80" rows="15">{$new_comment['comment']}</textarea>
<br /><br />

{if !$login}
{lng[Name]} <font color='#880000'>*</font>: <input type="text" size="30" name="new_name" value="{php echo escape($new_comment['new_name'], 2);}">
<br />
{if $config['General']['recaptcha_key']}
<br />
<div class="g-recaptcha" data-sitekey="{$config['General']['recaptcha_key']}"></div>
{/if}
<br />
{/if}

<button type="button" onclick="javascript: new_comment();">{lng[Add]}</button>
<br /><br />
</form>
<?php
	}
?>
{elseif $blogs}
<h1>{lng[Our blog]}</h1><br />
{if $total_pages > 2}
<div align="right">{include="common/navigation.php"}</div>
<br />
{/if}
<div class="blogs news-item news-item-page">
{foreach $blogs as $b}
{php $url = $current_location.'/blog/'.($b['cleanurl'] ? $b['cleanurl'].'.html' : $b['blogid']);}
<div class="author">
<i>{$b['firstname']} {$b['lastname']}, {php echo $b['comments'] ? $b['comments'] : '0';} {lng[Comments]}</i>
<h2><a href="{$url}" class="ajax_link simple-button"><?php echo $b['title']; ?></a></h2>
</div>
<div class="image">
{if $b['imageid']}
<a href="{$url}" class="ajax_link">
<?php
		$image = $b;
		$image['new_width'] = 250;
		$image['new_height'] = 250;
		include SITE_ROOT . '/includes/blog_image.php';
?>
</a>
{/if}
</div>
<div class="descr">
<div class="message-box">{$b['descr']}</div>
<div align="right">
<a class="ajax_link link-button" href="{$url}">{lng[Read blog]}</a>&nbsp;
<a href="{$url}#leave_comment" class="link-button">{lng[Leave a comment]}</a>
</div>
</div>
<div class="clear"></div>
<br />
{/foreach}
</div>
{if $total_pages > 2}
<div align="right">{include="common/navigation.php"}</div>
<br />
{/if}
{else}
<br />
<center>{lng[No topics yet]}</center>
{/if}
