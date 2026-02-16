Search keywords are helpful if you have specific products to display, so user came directly to your search page from google.<br /><br />
Don't forget to generate Google sitemap to include search keywords and then submit it once again where it is needed. Google parse sitemap automatically.
<br /><br />
<form method="post" enctype="multipart/form-data" name="fpform"{* class="noajax"*}>

<table cellpadding="3" cellspacing="1">

<tr>
 <td colspan="2"><h3>{lng[Import search keywords]}</td>
</tr>

<tr>
  <td width="100"><input type="file" name="file" /></td>
  <td>
  <button type="submit">{lng[Import]}</button>
  </td>
</tr>

</table>
</form>
<br />
<h3>What is in database already</h3>
<?php
if ($search_keywords) {
	foreach ($search_keywords as $v)
		echo $v.'<br />';

} else {
	echo 'No keywords';
}
?>