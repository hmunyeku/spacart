<style>
table td {
	vertical-align: middle;
}
</style>
<h3>{lng[Search for reviews]}</h3>
<form method="POST" href="/admin/reviews?mode=search">
<input type="hidden" name="mode" value="search">
<table width="100%" align="center" cellpadding="2" cellspacing="1">
        <tr>
                <td align="right">{lng[Status]}</td>
                <td><select name="status">
                        <option value="0">{lng[Pending]}</option>
                        <option{if $search_data['status'] == "1"} selected{/if} value="1">{lng[Approved]}</option>
                        <option{if $search_data['status'] == "2"} selected{/if} value="2">{lng[Declined]}</option>
                        <option{if $search_data['status'] == "" && $search_data} selected{/if} value="">{lng[All]}</option>
                        </select>
                </td>
        <tr>
                <td width="170" align="right">{lng[IP Address]}</td>
                <td><input type="text" size="32" name="remote_ip" value="{$search_data['remote_ip']}"></td>
        </tr>
        <tr>
                <td align="right">{lng[Name]}</td>
                <td><input type="text" size="32" name="name" value="{$search_data['name']}"></td>
        </tr>
        <tr>
                <td align="right">{lng[Message]}</td>
                <td><input type="text" size=80 name="message" value="{$search_data['message']}"></td>
        </tr>
        <tr>
                <td align="right">{lng[Product ID]}</td>
                <td><input type="text" size="32" name="productid" value="{$search_data['productid']}"></td>
        </tr>
        <tr>
                <td align="right">{lng[SKU]}</td>
                <td><input type="text" size="32" name="sku" value="{$search_data['sku']}"></td>
        </tr>
        <tr>
                <td align="right">{lng[Rating]}</td>
                <td>
                                <select name="rating">
                                <option value="">{lng[All]}</option>
                                <option value="5"{if $search_data['rating'] == "5"} selected{/if}>5</option>
                                <option value="4"{if $search_data['rating'] == "4"} selected{/if}>4</option>
                                <option value="3"{if $search_data['rating'] == "3"} selected{/if}>3</option>
                                <option value="2"{if $search_data['rating'] == "2"} selected{/if}>2</option>
                                <option value="1"{if $search_data['rating'] == "1"} selected{/if}>1</option>
                                </select>
                </td>
        </tr>
        <tr>
                <td></td>
                <td><button>{lng[Search]}</button></td>
        </tr>
</table>
</form>

{if $reviews}
        <br />
<h3>Search results</h3>

{if $total_pages > 2}
{include="common/navigation.php"}
<br />
{/if}

<form action="/admin/reviews" method="post" name="reviewsform">
        <input type="hidden" name="mode" value="edit">
<a href="javascript: void(0);" onclick="javascript: check_all(document.reviewsform, 'to_delete', true);">{lng[Check all]}</a> / <a href="javascript: void(0);" onclick="javascript: check_all(document.reviewsform, 'to_delete', false);">{lng[Uncheck all]}</a>
        <table border="0" cellpadding="5" cellspacing="0" class="lines-table">
                <tr>
                        <th></th>
                        <th>{lng[Status]}</th>
                        <th>{lng[Rating]}</th>
                        <th>{lng[IP]}</th>
                        <th>{lng[Name]}</th>
                        <th>{lng[Message]}</th>
                        <th nowrap>{lng[Product ID/SKU]}</th>
                </tr>
{foreach $reviews as $r}
                <tr>
                        <td valign="top" width="5%"><input type="checkbox" name="to_delete[{$r['id']}]" value="{$r['id']}"></td>
                        <td valign="top" width="5%">
                                <select name="to_update[{$r['id']}][status]">
                                <option value="0" >{lng[Pending]}</option>
                                <option value="1" {if $r['status'] == "1"}selected{/if}>{lng[Approved]}</option>
                                <option value="2" {if $r['status'] == "2"}selected{/if}>{lng[Declined]}</option>
                                </select>
                        </td>
                        <td valign="top" width="5%">
                                <select name="to_update[{$r['id']}][rating]">
                                <option value="5"{if $r['rating'] == "5"} selected{/if}>5</option>
                                <option value="4"{if $r['rating'] == "4"} selected{/if}>4</option>
                                <option value="3"{if $r['rating'] == "3"} selected{/if}>3</option>
                                <option value="2"{if $r['rating'] == "2"} selected{/if}>2</option>
                                <option value="1"{if $r['rating'] == "1"} selected{/if}>1</option>
                                </select>
                        </td>
                        <td valign="top" width="15%">{$r['remote_ip']}</td>
                        <td valign="top" width="15%">{$r['name']}</td>
                        <td valign="top" width="45%"><textarea cols="40" rows="3" name="to_update[{$r['id']}][message]">{$r['message']}</textarea></td>
                        <td valign="top" width="10%"><a href="/admin/products/{$r['productid']}" target="_blank">{$r['productid']}/{$r['sku']}</a></td>
                </tr>
{/foreach}
                <tr>
                        <td colspan="9"><br />
<div class="fixed_save_button">
<button type="button" onclick="javascript: submitForm(document.reviewsform, 'update');">{lng[Update]}</button>
<button type="button" onclick="javascript: if (confirmed || confirm('{lng[This operation will delete selected reviews.|escape]}', $(this))) submitForm(this, 'delete');">{lng[Delete selected]}</button>
</div>
                        </td>
                </tr>
        </table>
        </form>

{if $total_pages > 2}
{include="common/navigation.php"}
<br />
{/if}
{/if}