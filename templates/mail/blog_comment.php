{include="mail/header.php"}
{lng[Hello]},<br /><br />
<p>{lng[New blog comment has been posted]}</p>
<a href="{$current_location}/admin/blog/{$blog['blogid']}">{$current_location}/admin/blog/{$blog['blogid']}</a>
<br /><br />
{$signature}
{include="mail/footer.php"}