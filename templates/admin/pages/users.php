<a href="/admin/user/new">{lng[Create new user account]}</a><br /><br />
<form action="{$current_location}/admin/users/search" method="post" name="users_form">
<input type="hidden" name="mode" value="" />

<?php
if ($get['2'] == 'search') {
?>
<a href="{$current_location}/admin/users" class="search_again">{lng[Search again]}</a>
<h3>{lng[Users search results]}</h3>
<?php
if ($users) {
?>
<?php
if ($total_pages > 2) {
?>
{include="common/navigation.php"}
<?php
	echo '<br />';
}
?>

<table cellpadding="2" class="users_list lines-table">
<tr>
 <th width="10">&nbsp;</th>
 <th width="40%">{lng[User name]}</th>
 <th width="40%">{lng[User email]}</th>
 <th width="10%">{lng[User role]}</th>
 <th width="10%">{lng[Status]}</th>
</tr>
<?php
foreach ($users as $k=>$v) {
?>
<tr>
 <td><input type="checkbox" name="to_delete[<?php echo $v['id']; ?>]" /></td>
 <td><a href="{$current_location}/admin/user/<?php echo $v['id']; ?>"><?php echo $v['firstname'].' '.$v['lastname']; ?></a></td>
 <td><?php echo $v['email']; ?></a></td>
 <td align="center"><?php if ($v['usertype'] == 'A') {?>{lng[Administrator]}<?php} else {?>{lng[Customer]}<?php} ?></a></td>
 <td>
<select name="status[<?php echo $v['id']; ?>]">
<option value="1"<?php if ($v['status'] == 1) echo ' selected="selected"'; ?>>{lng[Active]}</option>
<option value="0"<?php if (empty($v['status'])) echo ' selected="selected"'; ?>>{lng[Not active]}</option>
</select>
 </td>
</tr>
<?php
}
?>
</table>
<div class="fixed_save_button">
<button type="button" onclick="submitForm(document.users_form, 'update');">{lng[Update]}</button>
&nbsp;
<button type="button" onclick="submitForm(document.users_form, 'delete');">{lng[Delete selected]}</button>
</div>
<?php
} else {
?>
{lng[No users found]}
<?php
}
} else {
?>
<h3>{lng[Users management]}</h3><br />
<table cellpadding="2" cellspacing="1" class="users_management">
<tr>
 <td class="name">{lng[Search for]}</td>
 <td><input type="text" name="substring" value="<?php echo escape($users_search['substring']); ?>" size="80" /></td>
</tr>

<tr>
 <td class="name">{lng[Search in]}</td>
 <td>
<label><input type="checkbox" name="firstname" value="1"<?php if ($users_search['firstname']) echo ' checked="checked"'; ?> /> {lng[First name]}</label>
<label><input type="checkbox" name="lastname" value="1"<?php if ($users_search['lastname']) echo ' checked="checked"'; ?> /> {lng[Last name]}</label>
<label><input type="checkbox" name="email" value="1"<?php if ($users_search['email']) echo ' checked="checked"'; ?> /> {lng[Email]}</label>
 </td>
</tr>

<tr>
 <td class="name">{lng[Status]}</td>
 <td>
<select name="status">
<option value="">{lng[All]}</option>
<option value="1"<?php if ($users_search['status'] == 1) echo ' selected="selected"'; ?>>{lng[Active]}</option>
<option value="0"<?php if ($users_search['status'] == '0') echo ' selected="selected"'; ?>>{lng[Not active]}</option>
</select>
 </td>
</tr>

<tr>
 <td class="name">{lng[Role]}</td>
 <td>
<select name="usertype">
<option value="">{lng[All]}</option>
<option value="C"<?php if ($users_search['usertype'] == 'C') echo ' selected="selected"'; ?>>{lng[Customer]}</option>
<option value="A"<?php if ($users_search['usertype'] == 'A') echo ' selected="selected"'; ?>>{lng[Administrator]}</option>
</select>
 </td>
</tr>

<tr>
 <td class="name">{lng[Membership]}</td>
 <td>
<select name="membershipid">
<option value="0">{lng[No membership]}</option>
<?php
if ($memberships)
	foreach ($memberships as $m) {
		echo '<option value="'.$m['membershipid'].'"'.($users_search['membershipid'] == $m['membershipid'] ? ' selected="selected"' : '').'>'.$m['membership'].'</option>';
	}
?>
</select>
 </td>
</td>

<tr>
 <td class="name">{lng[Pending membership]}</td>
 <td>
<input type="checkbox" name="pending_membership" value="Y"<?php if ($users_search['pending_membership'] == 'Y') echo ' checked="checked"'; ?> />
 </td>
</tr>

<tr>
  <td></td>
  <td><br /><button type="submit">{lng[Search]}</button></td>
</tr>

</table>
</form>
<?php
}
?>
