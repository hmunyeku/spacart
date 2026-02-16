{if $view == "detail"}

<div class="orders-page orders-detail">
<div class="orders-back">
<a href="{$current_location}/orders">&larr; {lng[Retour a mes commandes]}</a>
</div>

<h1>{lng[Commande]} #{$order['orderid']}</h1>

<div class="order-summary-card">
<div class="order-summary-row">
 <div class="order-summary-item">
  <span class="order-label">{lng[Date]}</span>
  <span class="order-value">{php echo date($datetime_format, $order['date']);}</span>
 </div>
 <div class="order-summary-item">
  <span class="order-label">{lng[Statut]}</span>
  <span class="order-value">
   {if $order['status'] == 1}<span class="order-badge badge-queued">{$order_statuses[$order['status']]}</span>
   {elseif $order['status'] == 2}<span class="order-badge badge-paid">{$order_statuses[$order['status']]}</span>
   {elseif $order['status'] == 3}<span class="order-badge badge-completed">{$order_statuses[$order['status']]}</span>
   {elseif $order['status'] == 4}<span class="order-badge badge-declined">{$order_statuses[$order['status']]}</span>
   {elseif $order['status'] == 5}<span class="order-badge badge-failed">{$order_statuses[$order['status']]}</span>
   {elseif $order['status'] == 6}<span class="order-badge badge-shipped">{$order_statuses[$order['status']]}</span>
   {else}<span class="order-badge">{$order_statuses[$order['status']]}</span>
   {/if}
  </span>
 </div>
 <div class="order-summary-item">
  <span class="order-label">{lng[Total]}</span>
  <span class="order-value order-total-value">{price $order['total']}</span>
 </div>
</div>

{if $order['payment_method']}
<div class="order-summary-row">
 <div class="order-summary-item">
  <span class="order-label">{lng[Paiement]}</span>
  <span class="order-value">{$order['payment_method']}{if $order['payment_details']} <small>({$order['payment_details']})</small>{/if}</span>
 </div>
 {if $order['shipping_method']}
 <div class="order-summary-item">
  <span class="order-label">{lng[Livraison]}</span>
  <span class="order-value">{if $order['local_pickup']}{lng[Retrait sur place]}{else}{$order['shipping_method']['shipping']}{if $order['shipping_method']['shipping_time']} ({$order['shipping_method']['shipping_time']}){/if}{/if}</span>
 </div>
 {/if}
</div>
{/if}

{if $order['tracking']}
<div class="order-summary-row order-tracking-row">
 <div class="order-summary-item">
  <span class="order-label">{lng[Numero de suivi]}</span>
  <span class="order-value">{$order['tracking']}</span>
 </div>
 {if $order['tracking_url']}
 <div class="order-summary-item">
  <a href="{$order['tracking_url']}" target="_blank" class="order-track-btn">{lng[Suivre le colis]} &rarr;</a>
 </div>
 {/if}
</div>
{/if}
</div>

<h2>{lng[Produits]}</h2>
<div class="order-products-list">
{foreach $products as $v}
{php $url = $v['cleanurl'] ? $v['cleanurl'] . '.html' : 'product/' . $v['productid'];}
<div class="order-product-row">
 <div class="order-product-image">
{if $v['variant_photo']}
<?php
$image = $v['variant_photo'];
$image['new_width'] = 80;
$image['new_height'] = 80;
include SITE_ROOT . '/includes/variant_image.php';
?>
{elseif $v['photo']}
<?php
$image = $v['photo'];
$image['new_width'] = 80;
$image['new_height'] = 80;
include SITE_ROOT . '/includes/image.php';
?>
{/if}
 </div>
 <div class="order-product-info">
  <div class="order-product-name">
   {if $v['gift_card']}
    <b>{lng[Carte Cadeau]}</b>
   {else}
    <a href="{$current_location}/{$url}">{$v['name']}</a>
   {/if}
  </div>
  {if $v['product_options']}
  <div class="order-product-options">
   {foreach $v['product_options'] as $o}
    <span class="order-option">{if $o['fullname']}{$o['fullname']}{else}{$o['name']}{/if}: {$o['option']['name']}</span>
   {/foreach}
  </div>
  {/if}
 </div>
 <div class="order-product-qty">
  {if !$v['gift_card']}x{$v['quantity']}{/if}
 </div>
 <div class="order-product-price">
  {if $v['gift_card']}
   {price $v['price']}
  {else}
   {php $product_subtotal = $v['price'] * $v['quantity'];}
   {price $product_subtotal}
   {if $v['quantity'] > 1}<br /><small>{price $v['price']} / {lng[unite]}</small>{/if}
  {/if}
 </div>
</div>
{/foreach}
</div>

<div class="order-totals">
<table>
<tr>
 <td class="totals-label">{lng[Sous-total]}</td>
 <td class="totals-value">{price $order['subtotal']}</td>
</tr>
{if $order['coupon']}
<tr>
 <td class="totals-label">{lng[Coupon]} ({$order['coupon']})</td>
 <td class="totals-value">-{price $order['coupon_discount']}</td>
</tr>
{php $discounted_subtotal = $order['subtotal'] - $order['coupon_discount'];}
<tr>
 <td class="totals-label">{lng[Sous-total remise]}</td>
 <td class="totals-value">{price $discounted_subtotal}</td>
</tr>
{/if}
<tr>
 <td class="totals-label">{lng[Livraison]}</td>
 <td class="totals-value">{price $order['shipping']}</td>
</tr>
{if $order['tax_details']['tax_name']}
<tr>
 <td class="totals-label">{lng[Taxe]} ({$order['tax_details']['tax_name']})</td>
 <td class="totals-value">{price $order['tax']}</td>
</tr>
{/if}
{if $order['gc_discount'] > 0}
<tr>
 <td class="totals-label">{lng[Carte Cadeau]}</td>
 <td class="totals-value">-{price $order['gc_discount']}</td>
</tr>
{/if}
<tr class="totals-total">
 <td class="totals-label">{lng[Total]}</td>
 <td class="totals-value">{price $order['total']}</td>
</tr>
</table>
</div>

<div class="order-addresses">
<div class="order-address-block">
<h3>{lng[Adresse de livraison]}</h3>
<p>{$order['firstname']} {$order['lastname']}</p>
<p>{$order['address']}</p>
<p>{$order['zipcode']} {$order['city']}</p>
{if $order['statename']}<p>{$order['statename']}</p>{/if}
<p>{$order['countryname']}</p>
{if $order['phone']}<p>{lng[Tel]}: {$order['phone']}</p>{/if}
{if $order['email']}<p>{$order['email']}</p>{/if}
</div>
<div class="order-address-block">
<h3>{lng[Adresse de facturation]}</h3>
<p>{$order['b_firstname']} {$order['b_lastname']}</p>
<p>{$order['b_address']}</p>
<p>{$order['b_zipcode']} {$order['b_city']}</p>
{if $order['b_statename']}<p>{$order['b_statename']}</p>{/if}
<p>{$order['b_countryname']}</p>
{if $order['b_phone']}<p>{lng[Tel]}: {$order['b_phone']}</p>{/if}
</div>
</div>

{if $order['notes']}
<div class="order-notes">
<h3>{lng[Commentaires]}</h3>
<p>{$order['notes']}</p>
</div>
{/if}

<div class="order-actions">
<a href="{$current_location}/invoice/{$order['orderid']}" class="order-action-btn">{lng[Voir la facture]}</a>
<a href="{$current_location}/invoice/{$order['orderid']}/print" target="_blank" class="order-action-btn">{lng[Imprimer]}</a>
<a href="{$current_location}/invoice_pdf/{$order['orderid']}" target="_blank" class="order-action-btn">{lng[Telecharger PDF]}</a>
</div>

</div>

{else}

<div class="orders-page orders-list">
<h1>{lng[Mes commandes]}</h1>

{if $orders}

<div class="orders-table-wrap">
<table class="orders-table">
<thead>
<tr>
 <th>{lng[Commande]}</th>
 <th>{lng[Date]}</th>
 <th>{lng[Statut]}</th>
 <th>{lng[Total]}</th>
 <th>{lng[Paiement]}</th>
 <th class="orders-th-actions"></th>
</tr>
</thead>
<tbody>
{foreach $orders as $o}
<tr>
 <td data-label="{lng[Commande]}"><a href="{$current_location}/orders/{$o['orderid']}">#{$o['orderid']}</a></td>
 <td data-label="{lng[Date]}">{php echo date($datetime_format, $o['date']);}</td>
 <td data-label="{lng[Statut]}">
  {if $o['status'] == 1}<span class="order-badge badge-queued">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 2}<span class="order-badge badge-paid">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 3}<span class="order-badge badge-completed">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 4}<span class="order-badge badge-declined">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 5}<span class="order-badge badge-failed">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 6}<span class="order-badge badge-shipped">{$order_statuses[$o['status']]}</span>
  {else}<span class="order-badge">{$order_statuses[$o['status']]}</span>
  {/if}
 </td>
 <td data-label="{lng[Total]}">{price $o['total']}</td>
 <td data-label="{lng[Paiement]}">{if $o['payment_name']}{$o['payment_name']}{else}-{/if}</td>
 <td class="orders-td-actions"><a href="{$current_location}/orders/{$o['orderid']}" class="order-view-btn">{lng[Voir]}</a></td>
</tr>
{/foreach}
</tbody>
</table>
</div>

<div class="orders-cards-mobile">
{foreach $orders as $o}
<a href="{$current_location}/orders/{$o['orderid']}" class="order-card-mobile">
 <div class="order-card-header">
  <span class="order-card-id">#{$o['orderid']}</span>
  {if $o['status'] == 1}<span class="order-badge badge-queued">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 2}<span class="order-badge badge-paid">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 3}<span class="order-badge badge-completed">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 4}<span class="order-badge badge-declined">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 5}<span class="order-badge badge-failed">{$order_statuses[$o['status']]}</span>
  {elseif $o['status'] == 6}<span class="order-badge badge-shipped">{$order_statuses[$o['status']]}</span>
  {else}<span class="order-badge">{$order_statuses[$o['status']]}</span>
  {/if}
 </div>
 <div class="order-card-body">
  <span class="order-card-date">{php echo date($datetime_format, $o['date']);}</span>
  <span class="order-card-total">{price $o['total']}</span>
 </div>
</a>
{/foreach}
</div>

{else}

<div class="orders-empty">
<p>{lng[Vous n avez aucune commande pour le moment.]}</p>
<a href="{$current_location}" class="orders-shop-btn">{lng[Continuer mes achats]}</a>
</div>

{/if}
</div>

{/if}
