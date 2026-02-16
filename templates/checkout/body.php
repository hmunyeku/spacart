<div class="checkout-container">
<h1><a href="{$current_location}/cart" class="cart-link back-to-cart"><img src="/images/back_arrow.png" alt="" /></a>{lng[Checkout]}</h1>

<div align="left">
<table width="100%" cellspacing="0" class="checkout_products">
{foreach $products as $v}
{php $url = $v['cleanurl'] ? $v['cleanurl'].'.html' : 'product/'.$v['productid'];}
	<tr>
	 <td class="image"><a href="{$current_location}/{$url}">
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
	  <td width="100%" class="line" align="left">
{if $v['gift_card']}
<b>Gift Card</b>
{else}
<a href="{$current_location}/{$url}">{$v['name']}</a>
{/if}
{if $v['weight'] && $v['weight'] != '0.00'}
<br />
<small>{lng[Weight]}: {weight $v['weight']}</small>
{/if}
{if $v['product_options']}
<hr />
<b>{lng[Selected options]}:</b><br />
<table width="100%">
{foreach $v['product_options'] as $o}
<tr>
	<td valign="top" width="100">{if $o['fullname']}{$o['fullname']}{else}{$o['name']}{/if}:</td>
	<td>{$o['option']['name']}</td>
</tr>
{/foreach}
</table>
{/if}
 </td>
 <td class="line" nowrap align="right">
{if $v['gift_card']}
{price $v['amount']}
{else}
{price $v['price']} x {$v['quantity']} = {php $product_subtotal = $v['price'] * $v['quantity'];}{price $product_subtotal}
{/if}
 </td>
</tr>
{/foreach}
</table>

<br />
<hr />
{if !$login}
<br />
<div class="checkout-guest-mobile">
You can checkout as guest. Or <a href="/login">{lng[Login]}</a>, <a href="/register">{lng[Register]}</a>.
</div>

<div class="checkout-guest">
You can checkout as guest. Or <a href="/login" onclick="return login_popup();">{lng[Login]}</a>, <a href="/register" onclick="return register_popup();">{lng[Register]}</a>.
</div>
{/if}
<br />
{php $checkout_reason = func_check_checkout();}
{if $checkout_reason == 1}
<table id="checkout_table">
<tr>
 <td width="340" id="custom_details">
<form method="post" action="/checkout/user_form" id="checkout_user_form">
    <div class="group">
      <input type="text" name="posted_data[firstname]" required value="{php echo escape($userinfo['firstname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[First name]}</label>
    </div>

    <div class="group">
      <input type="text" name="posted_data[lastname]" required value="{php echo escape($userinfo['lastname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Last name]}</label>
    </div>
    <div class="group">
      <input type="text" name="posted_data[phone]" required value="{php echo escape($userinfo['phone'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Phone]}</label>
    </div>
    <div class="group">
      <input type="text" name="posted_data[email]" required value="{php echo escape($userinfo['email'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Email]}</label>
    </div>
{*
    <div class="group">
      <input type="password" name="password" required value="" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Password]}</label>
    </div>
*}
    <div class="group">
      <input type="text" name="posted_data[address]" required value="{php echo escape($userinfo['address'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Address]}</label>
    </div>

    <div class="group">
      <input type="text" name="posted_data[city]" required value="{php echo escape($userinfo['city'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[City]}</label>
    </div>

<script>
var states = {ldelim}{rdelim};
	user_state = "{php echo escape($userinfo['state'], 2);}",
	user_state_b = "{php echo escape($userinfo['b_state'], 2);}";
{foreach $countries as $v}
 {if $v['states']}
states.{$v['code']} = {states: []};
  {foreach $v['states'] as $k=>$s}
states.{$v['code']}.states[{$k}] = {code: "{$s['code']}", state: "{php echo escape($s['state'], 2);}"};
  {/foreach}
 {/if}
{/foreach}
</script>

    <div class="group group-select">
<label>{lng[State]}:</label>
<div>
{php $found = false;}
{foreach $countries as $v}
 {if $v['code'] == $userinfo['country'] && $v['states']}
  {php $found = true;}
<select name="posted_data[state]" id="state">
  {foreach $v['states'] as $s}
 <option value="{$s['code']}"{if $s['code'] == $userinfo['state']} selected{/if}>{$s['state']}</option>
   {/foreach}
</select>
 {/if}
{/foreach}

{if !$found}
<input type="text" name="posted_data[state]" id="state" value="{php echo escape($userinfo['state'], 2);}" />
{/if}
</div>
    </div>

    <div class="group group-select">
 <label>{lng[Country]}:</label>
<select name="posted_data[country]" id="country">
{foreach $countries as $v}
 <option value="{$v['code']}"{if $v['code'] == $userinfo['country'] || (!$userinfo['country'] && $v['code'] == 'US')} selected{/if}>{$v['country']}</option>
{/foreach}
</select>
    </div>

    <div class="group">
      <input type="text" name="posted_data[zipcode]" required value="{php echo escape($userinfo['zipcode'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Zip/Postal code]}</label>
    </div>

<label><input type="checkbox" id="same_address" name="same_address" value="1"{if $userinfo['same_address'] || !$userinfo['firstname']} checked{/if} /> {lng[Billing address is the same]}</label>
<br /><br />
<div class="billing_address {if $userinfo['same_address'] || !$userinfo['firstname']} display-none{else} display-block{/if}">
<h1 class="checkout-h1">{lng[Billing address]}</h1><br />
    <div class="group">
      <input type="text" name="posted_data[b_firstname]" required value="{php echo escape($userinfo['b_firstname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[First name]}</label>
    </div>

    <div class="group">
      <input type="text" name="posted_data[b_lastname]" required value="{php echo escape($userinfo['b_lastname'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Last name]}</label>
    </div>
    <div class="group">
      <input type="text" name="posted_data[b_phone]" required value="{php echo escape($userinfo['b_phone'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Phone]}</label>
    </div>
    <div class="group">
      <input type="text" name="posted_data[b_address]" required value="{php echo escape($userinfo['b_address'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Address]}</label>
    </div>

    <div class="group">
      <input type="text" name="posted_data[b_city]" required value="{php echo escape($userinfo['b_city'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[City]}</label>
    </div>
    <div class="group group-select">
<label>{lng[State]}:</label>
<div>
{php $found = false;}
{foreach $countries as $v}
 {if $v['code'] == $userinfo['b_country'] && $v['states']}
  {php $found = true;}
<select name="posted_data[b_state]" id="b_state">
  {foreach $v['states'] as $s}
 <option value="{$s['code']}"{if $s['code'] == $userinfo['b_state']} selected{/if}>{$s['b_state']}</option>
   {/foreach}
</select>
 {/if}
{/foreach}

{if !$found}
<input type="text" name="posted_data[b_state]" id="b_state" value="{php echo escape($userinfo['b_state'], 2);}" />
{/if}
</div>
    </div>

    <div class="group group-select">
 <label>{lng[Country]}:</label>
<select name="posted_data[b_country]" id="b_country">
{foreach $countries as $v}
 <option value="{$v['code']}"{if $v['code'] == $userinfo['b_country'] || (!$userinfo['b_country'] && $v['code'] == 'US')} selected{/if}>{$v['country']}</option>
{/foreach}
</select>
    </div>

    <div class="group">
      <input type="text" name="posted_data[b_zipcode]" required value="{php echo escape($userinfo['b_zipcode'], 2);}" />
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Zip/Postal code]}</label>
    </div>

</div>

<button>{lng[Continue]}</button>
</form>
</div>
 </td>
 <td width="260" id="place_order" class="opacity-03">
{include="checkout/right_part.php"}
 </td>
</tr>
</table>
{else}
{lng[Subtotal]}: {price $cart['subtotal']}<br /><br />
{$checkout_reason}
{/if}
</div>