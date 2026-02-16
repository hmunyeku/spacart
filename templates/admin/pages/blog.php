<?php
if ($blogs) {
?>
<a href="{$current_location}/admin/blog/new">{lng[Add new]}</a>
<br /><br />
<form method="post" name="blogsform">
<input type="hidden" name="mode" value="update" />

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<?php
	echo '<br />';
}
?>

<a href="javascript: void(0);" onclick="javascript: check_all(document.blogsform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.blogsform, 'to_delete', false);">{lng[Uncheck all]}</a>

<table cellpadding="3" cellspacing="1" width="600" class="lines-table">
<tr>
	<th width="10">&nbsp;</th>
	<th width="60%">{lng[Title]}</th>
	<th width="5%">{lng[Active]}</th>
	<th width="35%">{lng[Comments]}</th>
</tr>

<?php
foreach ($blogs as $b) {
	echo '<tr>
	<td><input type="checkbox" name="to_delete['.$b['blogid'].']" value="Y" /></td>
	<td><a href="'.$current_location.'/admin/blog/'.$b['blogid'].'">'.$b['title'].'</a></td>
	<td><input type="checkbox" name="to_update['.$b['blogid'].'][active]" value="Y"'.($b['active'] == 'Y' ? ' checked="checked"' : '').' /></td>
	<td align="center"><a href="'.$current_location.'/admin/blog/'.$b['blogid'].'">'.$b['comments']."</a></td>
</tr>";
}
?>
</table>
<div class="fixed_save_button">
<button type="button" onclick="javascript: submitForm(this, 'update');">{lng[Update]}</button> &nbsp;
<button type="button" onclick="javascript: if (confirmed || confirm('{lng[This operation will delete selected blogs with all messages.|escape]}', $(this))) submitForm(this, 'delete');">{lng[Delete selected]}</button>
</div>
</form>

<h3>{lng[Authors]}</h3>
<?php
	foreach ($authors as $id=>$v)
		echo '<a href="'.$current_location.'/admin/blog/?author='.$id.'">'.$v['1'].'('.$v['0'].')</a><br />';
} else {
?>
<script src="{$current_location}/ckeditor/ckeditor.js"></script>
<form method="post" name="blogform" enctype="multipart/form-data">

{if $get['2'] == 'new'}
<h3>{lng[New blog|escape]}</h3>
{else}
<h3>{$blog['title']}</h3>
{/if}

<table cellpadding="3" cellspacing="1" width="90%" class="normal-table">
<?php
if ($blog['date']) {
?>
<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Author]}</div>
<a href="<?php echo $current_location.'/admin/user/'.$blog['author']; ?>"><?php echo $blog['firstname'].' '.$blog['lastname']; ?></a></td>
</tr>
<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Added]}</div>
<?php echo date($date_format, $blog['date']); ?></td>
</tr>
<?php
}
?>

<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Image]}</div>
<?php
if ($blog['image']) {
	$image = $blog['image'];
	$image['new_width'] = 400;
	$image['new_height'] = 100;
	$image['link'] = 'Y';
	$image['blank'] = 'Y';
	include SITE_ROOT . '/includes/blog_image.php';
?>
<br />
 <a href="<?php echo $current_location.'/admin/blog/'.$blog['blogid'].'/?mode=delete_image'; ?>">{lng[Delete image]}</a><br />
<?php
}
?>
<input type="file" name="userfile" />
 </td>
</tr>

<tr>
 <td><b>{lng[Title]}</b></td>
 <td><input type="text" name="title" size="40" value="<?php echo escape($blog['title'], 2); ?>" onchange="javascript: if (this.form.cleanurl.value == '') copy_clean_url(this, this.form.cleanurl);" /></td>
</tr>

<tr>
 <td><b>{lng[Clean URL]}</b></td>
 <td><input type="text" name="cleanurl" size="40" value="<?php echo escape($blog['cleanurl'], 2); ?>" /></td>
</tr>

<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Short description]}</div>
		<textarea class="ckeditor" id="ck_editor" cols="65" rows="7" name="descr"><?php echo $blog['descr']; ?></textarea>
 </td>
</tr>

<tr>
 <td valign="top" class="hide-td-for-mdl"></td>
 <td><div class="select-title">{lng[Detailed description]}</div>
	<script>
		var editor;
		// The instanceReady event is fired, when an instance of CKEditor has finished
		// its initialization.
		CKEDITOR.on( 'instanceReady', function( ev ) {
			editor = ev.editor;
		    $('*').removeAttr("title");
		});
	</script>
		<textarea class="ckeditor" id="ck_editor_2" cols="65" rows="7" name="fulldescr"><?php echo $blog['fulldescr']; ?></textarea>
 </td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta title]}</b></td>
 <td><input type="text" size="80" name="meta_title" value="<?php echo $blog['meta_title']; ?>" /></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta keywords]}</b></td>
 <td><textarea cols="80" rows="5" name="meta_keywords"><?php echo $blog['meta_keywords']; ?></textarea></td>
</tr>

<tr>
 <td valign="top"><b>{lng[Meta description]}</b></td>
 <td><textarea cols="80" rows="5" name="meta_descr"><?php echo $blog['meta_descr']; ?></textarea></td>
</tr>

<tr>
 <td><b>{lng[Active]}</b></td>
 <td><input type="checkbox" name="active" value="Y"<?php echo ((!$blog || $blog['active'] == 'Y') ? ' checked="checked"' : ''); ?> /></td>
</tr>

</table>
<div class="fixed_save_button">
<button type="submit"><?php if ($get['2'] == 'new') { ?>{lng[Add|escape]}<?php } else { ?>{lng[Save|escape]}<?php } ?></button>
</div>
<?php
if ($blog) {
?>
<br />
<h3>{lng[Blog comments]}</h3>

<?php
if ($comments) {
?>

<input type="hidden" name="commentid" value="" />

{include="admin/common/navigation.php"}

<table cellpadding="3" cellspacing="1" width="100%">
<?php
	foreach ($comments as $i) {
		echo '<tr>
  <td>
<div class="float-right">
<a href="javascript: void(0);" onclick="javascript: if (confirm(\'{lng[Delete this comment?]}\', \'\', \''.$current_location.'/admin/blog/'.$get['2'].'/?deleteid='.$i['commentid'].'\'));">{lng[Delete]}</a> / <a href="javascript: void(0);" onclick="javascript: edit_comment(\''.$i['commentid'].'\');">{lng[Edit]}</a> / ';
	if ($i['active'] == "Y")
		echo '<a class="color-red" href="'.$current_location.'/admin/blog/'.$get['2'].'/?declineid='.$i['commentid'].'">{lng[Decline]}</a>';
	else
		echo '<a class="blog-color-2" href="'.$current_location.'/admin/blog/'.$get['2'].'/?approveid='.$i['commentid'].'">{lng[Approve]}</a>';
	echo '&nbsp;&nbsp;
</div>
<div class="blog-comment">
<div class="float-right">
'.date($datetime_format, $i['date']).' / '.$i['ip'].'
&nbsp;
</div>
&nbsp;'.($i['name'] != '' ? $i['name'] : '<a href="'.$current_location.'/admin/user/'.$i['userid'].'">'.$i['firstname'].' '.$i['lastname'].'</a>').'
</div>
<span id="comment_'.$i['commentid'].'" class="hidden">'.$i['message'].'</span>
'.$i['bb_message'].'
  </td>
</tr>';
	}

	echo '</table>';
} else {
?>
<br /><center>{lng[No blog comments yet]}</center>
<?php
}
?>

<br /><br />
<h3>{lng[Add new/edit]}</h3>
<a onmouseover="javascript: bbhelp('b');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[b]','[/b]');"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/b.png" tabindex="-1"></a>
<a onmouseover="javascript: bbhelp('i');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[i]','[/i]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/i.png" tabindex="-2"></a>
<a onmouseover="javascript: bbhelp('u');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[u]','[/u]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/u.png" tabindex="-3"></a>
<a onmouseover="javascript: bbhelp('s');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[s]','[/s]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/s.png" tabindex="-3"></a>
<a onmouseover="javascript: bbhelp('url');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[url]','[/url]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/url.png" tabindex="-4"></a>
<a onmouseover="javascript: bbhelp('email');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[email]','[/email]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/email.png" tabindex="-5"></a>
<a onmouseover="javascript: bbhelp('img');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[img]','[/img]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/img.png" tabindex="-6"></a>
<a onmouseover="javascript: bbhelp('list');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[list][*]','[/*][/list]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/list.png" tabindex="-7"></a>
<a onmouseover="javascript: bbhelp('li');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[*]','[/*]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/li.png" tabindex="-8"></a>
<a onmouseover="javascript: bbhelp('quote');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[quote]','[/quote]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/quote.png" tabindex="-9"></a>
<a onmouseover="javascript: bbhelp('code');" onmouseout="javascript: bbhelp();" href="javascript: bbcode('[code]','[/code]')"><img alt="" src="<?php echo $current_location.'/images'; ?>/bbcodes/code.png" tabindex="-10"></a>

&nbsp;
<input size="40" id="helptext" readonly>
<br />

<textarea name="comment" cols="80" rows="15"></textarea>
<br /><br />
<button type="submit" name="btn">{lng[Add]}</button>
<br /><br />

<?php
}
?>

</form>

<script>
function edit_comment(id) {
	var text = document.getElementById('comment_'+id).innerHTML;
	document.blogform.btn.value="{lng[Save|js]}";
	document.blogform.commentid.value = id;

	text = str_replace("&gt;", ">", text);
	text = str_replace("&lt;", "<", text);

	document.blogform.comment.value = text;
}
</script>
<?php
}
?>