<a href="<?php echo $current_location; ?>/admin/subscribtions/<?php echo $get['2']; ?>/new">{lng[Add new]}</a>
<br /><br />
{lng[Total subscribers]}: <?php echo $subscribers ? $subscribers : '0';?> <a href="/admin/subscribtions/<?php echo $get['2']; ?>/export">{lng[Export]}</a>
<br /><br />
<form method="post" name="newsform">
<input type="hidden" name="mode" value="delete" />

<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<?php
	echo '<br />';
}
?>

<a href="javascript: void(0);" onclick="javascript: check_all(document.newsform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.newsform, 'to_delete', false);">{lng[Uncheck all]}</a>

<table cellpadding="3" cellspacing="1" width="600" class="lines-table">
<tr>
	<th width="10">&nbsp;</th>
	<th width="70%">{lng[Subject]}</th>
	<th width="30%">{lng[Date]}</th>
</tr>

<?php
foreach ($letters as $l) {
	echo '<tr>
	<td><input type="checkbox" name="to_delete['.$l['id'].']" value="Y" /></td>
	<td><a href="'.$current_location.'/admin/subscribtions/'.$get['2'].'/'.$l['id'].'">'.$l['subject'].'</a></td>
	<td align="center">'.date($datetime_format, $l['date'])."</td>
</tr>";
}
?>

</table>
<div class="fixed_save_button">
<button type="button" onclick="javascript: if (confirmed || confirm('{lng[This operation will delete selected letters.|escape]}', $(this))) submitForm(this, 'delete');">{lng[Delete selected]}</button>
</div>
</form>