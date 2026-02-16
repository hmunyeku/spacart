{if !$pdf_invoice}
{if $get['2'] != 'print' && !$is_mail}{if $userinfo['usertype'] == 'A' && $order['gift_card']}
<b>Gift Card order: {$order['gift_card']}</b><br /><br />
{/if}
 {if $get['2'] == 'success'}<div class="checkout-success-message">{lng[Your order placed successfully.]}</div>
 {elseif $get['2'] == 'failed'}<div class="checkout-fail-message">{lng[We are unable to process your order.]}</div>
 {/if}
<div class="print-invoice-button">
<a href="/invoice/{$order['orderid']}/print" target="_blank" onclick="javascript: return print_invoice($(this));">{lng[Print invoice]}</a>
<a href="/invoice_pdf/{$order['orderid']}" target="_blank">{lng[Download PDF invoice]}</a>
<br /><br />
</div>{/if}
{/if}
<div style="width: 650px; margin: 0 auto; background: #fff; padding: 10px; {if $get['2'] != 'print' && !$is_mail}border: 1px solid #ccc;{/if}" class="invoice">
<h1 style="text-align: center;">{lng[Invoice]}</h1>
{*
{if $pdf_invoice}
<table width="100%">
<tr>
 <td width="50%" style="font-size: 16px;">{lng[Order]} #{$order['orderid']} ({$order_statuses[$order['status']]})</td>
 <td align="right" style="font-size: 16px;">{php echo date($datetime_format, $order['date']);}</td>
 </tr>
</table>
{else}
{/if}
*}
<table class="invoice-table" style="width: 100%; margin: 10px auto 10px auto;">
<tr>
 <td valign="top" width="50%" id="invoice-table-td-1" style="vertical-align: top;">
<h2 style="margin: 0; padding: 10px 0; text-align: center;">{lng[Order]} #{$order['orderid']} ({$order_statuses[$order['status']]})</h1></h2>
<div>
{php echo date($datetime_format, $order['date']);}
</div>
{if $order['shipping_method']}
<span style="text-align:right; float: none;" class="delivery-method">{if $order['local_pickup']}{lng[Local pickup]}:{if $admin_display} {$order['warehouse']['wcode']},{/if} {$order['warehouse']['title']} ({$order['warehouse']['address']}){else}{lng[Delivery method]}: {$order['shipping_method']['shipping']}{if $order['shipping_method']['shipping_time']} ({$order['shipping_method']['shipping_time']}){/if}{/if}</span><br />
{/if}
{if $order['payment_method']}
{lng[Payment method]}: {$order['payment_method']}{if $order['payment_details']} ({$order['payment_details']}){/if}
{/if}
{if $order['tracking']}
<br />
{lng[Tracking number]}: {$order['tracking']}
{/if}
{if $order['tracking_url']}
<br />
<a href="{$order['tracking_url']}" target="_blank">{lng[Track order]}</a>
{/if}
 </td>
 <td valign="top" id="invoice-table-td-2" style="vertical-align: top;">
<h2 style="margin: 0; padding: 10px 0; text-align: center;">{lng[Company information]}</h2>
<b>{$config['Company']['company_name']}</b><br />
<a href="{$http_location}" target="_blank">{$http_location}</a><br /><br />
{$config['Company']['location_address']}, {$config['Company']['location_city']}<br />
{$config['Company']['location_zipcode']}, {$config['Company']['location_statename']}<br />
{$config['Company']['location_countryname']}<br />
{if $config['Company']['company_phone']}CALL US: {$config['Company']['company_phone']}<br />{/if}
{if $config['Company']['company_phone_2']}International: {$config['Company']['company_phone_2']}<br />{/if}
{if $config['Company']['company_fax']}Fax: {$config['Company']['company_fax']}<br />{/if}
{if $config['Company']['orders_department']}Email: {$config['Company']['orders_department']}{/if}
 </td>
</tr>
</table>
<table class="invoice-table" style="width: 100%; margin: 10px auto 10px auto;">
<tr>
 <td valign="top" width="50%" id="invoice-table-td-1" style="vertical-align: top;">
 <h2 style="margin: 0; padding: 10px 0; text-align: center;">{lng[Shipping address]}</h2>
<table width="100%">
<tr>
 <td><b>{lng[First name]}:</b></td>
 <td>{$order['firstname']}</td>
</tr>
<tr>
 <td><b>{lng[Last name]}:</b></td>
 <td>{$order['lastname']}</td>
</tr>
<tr>
 <td><b>{lng[Address]}:</b></td>
 <td>{$order['address']}</td>
</tr>
<tr>
 <td><b>{lng[City]}:</b></td>
 <td>{$order['city']}</td>
</tr>
<tr>
 <td><b>{lng[State]}:</b></td>
 <td>{$order['statename']}</td>
</tr>
<tr>
 <td><b>{lng[Country]}:</b></td>
 <td>{$order['countryname']}</td>
</tr>
<tr>
 <td><b>{lng[Zip/Postal code]}:</b></td>
 <td>{$order['zipcode']}</td>
</tr>
<tr>
 <td><b>{lng[Phone]}:</b></td>
 <td>{$order['phone']}</td>
</tr>
<tr>
 <td><b>{lng[E-mail]}:</b></td>
 <td>{$order['email']}</td>
</tr>
</table>

 </td>
 <td valign="top" id="invoice-table-td-2" style="vertical-align: top;">
 <h2 style="margin: 0; padding: 10px 0; text-align: center;">{lng[Billing address]}</h2>
<table width="100%">
<tr>
 <td><b>{lng[First name]}:</b></td>
 <td>{$order['b_firstname']}</td>
</tr>
<tr>
 <td><b>{lng[Last name]}:</b></td>
 <td>{$order['b_lastname']}</td>
</tr>
<tr>
 <td><b>{lng[Address]}:</b></td>
 <td>{$order['b_address']}</td>
</tr>
<tr>
 <td><b>{lng[City]}:</b></td>
 <td>{$order['b_city']}</td>
</tr>
<tr>
 <td><b>{lng[State]}:</b></td>
 <td>{$order['b_statename']}</td>
</tr>
<tr>
 <td><b>{lng[Country]}:</b></td>
 <td>{$order['b_countryname']}</td>
</tr>
<tr>
 <td><b>{lng[Zip/Postal code]}:</b></td>
 <td>{$order['b_zipcode']}</td>
</tr>
<tr>
 <td><b>{lng[Phone]}:</b></td>
 <td>{$order['b_phone']}</td>
</tr>
</table>
 </td>
</tr>
</table>

<h3>{lng[Products]}</h3>
<table width="100%">
{foreach $products as $v}
{if $admin_display}
 {php $url = '/admin/products/'.$v['productid'];}
{else}
 {php $url = $v['cleanurl'] ? $v['cleanurl'] .'.html' : 'product/'.$v['productid'];}
{/if}
	<tr>
	 <td class="image hideonmobile" valign="top" style="padding-right: 10px;"><a href="{$http_location}/{$url}">
{if $v['variant_photo']}
<?php
$image = $v['variant_photo'];
$image['new_width'] = 100;
$image['new_height'] = 100;
include SITE_ROOT . '/includes/variant_image.php';
?>
{elseif $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 100;
$image['new_height'] = 100;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
	  </a></td>
	  <td{if $pdf_invoice} width="60%"{else} width="100%"{/if} valign="top">
{if $v['gift_card']}
<b>{lng[Gift Card]}</b>
{if $order['gc_generated']}
{if $userinfo['id'] == $order['userid']}
<br />
{lng[Gift Card key phrase]}: {$v['gift_card']}
{/if}
{/if}
{else}
<a href="{$http_location}/{$url}">{$v['name']}</a>
{*
{if $v['weight']}
<br />
<small>{lng[Weight]}: {weight $v['weight']}</small>
{/if}
*}
{if $v['product_options']}
<hr />
<b>{lng[Selected options]}:</b><br />
<table width="100%">
 {foreach $v['product_options'] as $o}
<tr>
 <td valign="top" width="150">{if $o['fullname']}{$o['fullname']}{else}{$o['name']}{/if}:</td>
 <td>{$o['option']['name']}</td>
</tr>
 {/foreach}
</table>
{/if}
{/if}
 </td>
{php $product_subtotal = $v['price'] * $v['quantity'];}
 <td{if $pdf_invoice} width="25%"{else} nowrap{/if} align="right" valign="top">
{if $v['gift_card']}
{price $v['price']}
{else}
{price $v['price']} x {$v['quantity']} = {price $product_subtotal}
{/if}
 </td>
 {if $admin_display && !$order['local_pickup'] && $warehouse_enabled}
 <td nowrap><span class="update-whs" data-itemid="{$v['itemid']}">{lng[Update warehouses]}</span></td>
 {/if}
	</tr>
 {/foreach}
</table>

{if $admin_display}
{foreach $products as $v}
<div class="warehouses" id="warehouses-{$v['itemid']}" data-itemid="{$v['itemid']}">
{lng[Ordered amount]}: {$v['quantity']}
<form method="POST">
<input type="hidden" name="itemid" value="{$v['itemid']}" />
<div class="wh-list">
<table width="100%">
<tr>
 <th>{lng[Warehouse]}</th>
 <th>{lng[Qty]}</th>
</tr>
{foreach $v['warehouses'] as $w}
<tr>
 <td width="100%">{$w['wcode']}</td>
 <td nowrap><input type="text" size="3" name="update_wh[{$v['itemid']}][{$w['wid']}]" value="{$w['spent']}" /> ({$w['avail']})</td>
</tr>
{/foreach}
</table>
</div>
<br />
<button class="save">{lng[Save]}</button>
<button type="button" class="cancel">{lng[Cancel]}</button>
</form>
</div>
{/if}
{/if}

<div align="right" style="text-align: right;">
<table width="100%">
<tr>
 <td width="50%"></td>
 <td align="right">
<table class="subtotal" style="border-top: 1px solid black;" cellspacing="0" cellpadding="0">
<tr>
 <td width="250" align="right">{lng[Subtotal]}:</td>
 <td width="70" align="right">{price $order['subtotal']}</td>
</tr>
{if $order['coupon']}
{if $order['coupon']}
<tr>
 <td align="right">{lng[Coupon discount]}({$order['coupon']}):</td>
 <td align="right">{price $order['coupon_discount']}</td>
</tr>
{/if}
{php $discounted_subtotal = $order['subtotal'] - $order['coupon_discount'];}
<tr>
 <td align="right">{lng[Discounted subtotal]}:</td>
 <td width="50" align="right">{price $discounted_subtotal}</td>
</tr>
{/if}

<tr>
 <td align="right">{lng[Shipping]}:</td>
 <td align="right">{price $order['shipping']}</td>
</tr>
{if $order['tax_details']['tax_name']}
<tr class="totals">
 <td align="right">{lng[Tax]}({$order['tax_details']['tax_name']}):</td>
 <td align="right">{price $order['tax']}</td>
</tr>
{/if}
{if $order['gc_discount'] > 0}
<tr>
 <td align="right">{lng[Gift Card]}:</td>
 <td align="right">{price $order['gc_discount']}</td>
</tr>
{/if}
<tr class="totals">
 <td align="right">{lng[Total]}:</td>
 <td align="right">{price $order['total']}</td>
</tr>
</table>
 </td>
</tr>
</table>
</div>

{if $order['notes']}
<br />
{lng[Comments]}:
<hr />
{$order['notes']}
{/if}
<h2 style="padding: 20px 0; margin: 0; text-align: center;">{lng[Thank you for your ordering]}</h2>
</div>
