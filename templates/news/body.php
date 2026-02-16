<div class="news-content">
{*
<form method="POST" name="nform" onsubmit="javascript: if (document.nform.substring.value != 'Search') return true; else return false;">
<div class="news-search"><input value="<?php echo $news_substring ? $news_substring : 'Search'; ?>" onfocus="javascript: if (this.value == 'Search') this.value = '';" onblur="javascript: if (this.value == '') this.value = 'Search';" type="text" name="substring" /><input type="image" src="/images/spacer.gif" alt="" /></div>
</form>
*}
<h1>{lng[News]}</h1>

<?php
if ($news) {
?>

<div class="news">
<div class="date"><?php echo date($date_format, $news['date']); ?></div>
<h1 class="title"><?php echo $news['title']; ?></h1>
<?php
	if ($news['imageid']) {
		echo '<div class="image">';
		$image = $news;
		$image['new_width'] = 700;
		$image['new_height'] = 400;
		include SITE_ROOT . '/includes/news_image.php';
		echo '</div>';
	}
?>
<div class="fulldescr">{$news['fulldescr']}</div>
<div class="back"><a href="{$current_location}/news" class="ajax_link">{lng[Back to News mainpage]}</a></div>
</div>

<?php
} else {
?>
<?php
	if (!$newss) {
		echo '<br /><br />No news found<br />';
		return;
	}

	if ($news_substring)
		echo '<br /><a href="{$current_location}/news?nosearch=1">Reset search condition</a><br /><br />';

?>

{if $newss}
<div class="news-list">
<br />
{foreach $newss as $b}
{php $url = $current_location.'/news/'.($b['cleanurl'] ? $b['cleanurl'].'.html' : $b['newsid']);}
<a class="ajax_link" href="{$url}"><h5>{$b['title']} (<span class="date"><?php echo date($date_format, $b['date']); ?></span>)</h5></a>
<div class="news-item">
<?php
	if ($b['imageid']) {
		echo '<a class="ajax_link" href="'.$url.'">';
		$image = $b;
		$image['new_width'] = 250;
		$image['new_height'] = 200;
		include SITE_ROOT . '/includes/news_image.php';
		echo '</a>';
	}
?>
<div class="short-descr message-box">{$b['descr']}</div>
<br />
<a class="news-more ajax_link link-button" href="{$url}">{lng[See full article]}</a>
</div>
<div class="clear"></div>
{/foreach}
</div>
{/if}

<?php
	if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<br />
<?php
	}
}
?>
</div>
<div class="clear"></div>