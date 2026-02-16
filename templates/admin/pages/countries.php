<table cellpadding="2" cellspacing="1" class="countries lines-table">

<tr>
  <th>{lng[Code]}</th>
  <th>{lng[Country]}</th>
  <th>{lng[States]}</th>
</tr>

<?php
foreach ($countries as $v) {
	echo '<tr>
  <td width="5%" align="center">'.$v['code'].'</td>
  <td>'.$v['country'].'</td>
  <td align="left"><a href="/admin/countries/'.$v['code'].'">{lng[Manage]}</a> '.($v['states'] ? '('.$v['states'].')' : '').'</td>
</tr>';
}
?>

</table>