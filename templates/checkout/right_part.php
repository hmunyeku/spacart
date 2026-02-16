<form id="checkoutform" method="POST" stripeid="{$stripe_id}">
<input type="hidden" name="stripe_token" id="stripe_token" />
<input type="hidden" name="order_total" id="order_total" value="<?php echo price_format($cart['total']); ?>" />
{if $cart['need_shipping']}
<h3>{lng[Shipping method]}</h3>
{if $shipping_methods}
<select name="shippingid" onchange="javascript: recalculate_shipping(this.value);"{if $cart['shippingid'] == 'L'} class="hidden"{/if}>
{foreach $shipping_methods as $v}
<option value="{$v['shippingid']}"{if $cart['shippingid'] == $v['shippingid']} selected{/if}>{$v['shipping']}{if $v['shipping_time']} ({$v['shipping_time']}){/if} - {price $v['rate']}</option>
{/foreach}
</select>
{elseif !$userinfo['firstname']}
{lng[Please enter your address]}
{elseif !$cart['local_pickup']}
{lng[No shipping methods to your location available]}
{/if}

{if $cart['local_pickup']}
<br />
<label><input type="checkbox" name="local_pickup" id="local_pickup" value="1"{if $cart['shippingid'] == 'L'} checked{/if} />{lng[Local pickup(free shipping)]}</label>
<div class="choose-warehouse{if $cart['shippingid'] != 'L'} hidden{/if}"><h2>{lng[Choose warehouse]}</h2>
<table>
{foreach $cart['warehouses'] as $k=>$w}
<tr{if !$k} class="active"{/if}>
 <td width="10"><input type="radio" id="wid-{$w['wid']}" name="wid" value="{$w['wid']}"{if !$k} checked{/if} /></td>
 <td><label for="wid-{$w['wid']}">{$w['title']}, {$w['address']}</label> <a href="http://maps.google.com/?q={php echo escape($w['address'], 2);}" target="_blank">{lng[Open in map]}</a></td>
</tr>
{/foreach}
</table>
</div>
<br /><br />
{/if}
{/if}

<h3>{lng[Payment method]}</h3>
{if $cart['total'] == "0.00"}
Free
{else}
<select name="paymentid" id="paymentid">
{foreach $payment_methods as $v}
<option value="{$v['paymentid']}">{$v['name']}</option>
{/foreach}
</select>
{/if}
{if $client_token}
  <div id="cc-info" class="display-none">
    <input hidden name="payment_method_nonce" id="payment-method-nonce">
<table>
<tr>
 <td colspan="2">
    <label>{lng[Credit Card #]}</label>
    <div class="height-50p" id="card_number"></div>
    <div id="card-image"></div>
 </td>
</tr>
<tr>
 <td><label>{lng[Expiration]}</label>
      <div class="cc-input" id="ex_date">
 </td>
 <td><label>{lng[CVV]}</label>
      <div class="cc-input" id="cv_code"></div>
 </td>
</tr>
</table>
</div>
{/if}

<br /><br />
    <div class="group">
      <textarea class="width-95p" cols="30" rows="4" name="notes"></textarea>
      <span class="highlight"></span>
      <span class="bar"></span>
      <label>{lng[Your comments]}</label>
    </div>

<br /><br />
<div align="right">
<table class="subtotal" cellspacing="0" cellpadding="0" width="100%">
<tr>
 <td align="right">{lng[Subtotal]}:</td>
 <td width="50" align="right">{price $cart['subtotal']}</td>
</tr>
{if $cart['coupon']}
<tr>
 <td align="right">{lng[Coupon discount]}({$cart['coupon']['coupon']}) <span class="remove_coupon">(x)</span>:</td>
 <td align="right">{price $cart['coupon_discount']}</td>
</tr>
{*{php $discounted_subtotal = $cart['subtotal'] - $cart['coupon_discount'];}*}
<tr>
 <td align="right">{lng[Discounted subtotal]}:</td>
 <td width="50" align="right">{price $cart['discounted_subtotal']}</td>
</tr>
{/if}
<tr>
 <td align="right">{lng[Shipping]}:</td>
 <td align="right">{price $cart['shipping_cost']}</td>
</tr>
{if $cart['tax_details']}
<tr>
 <td align="right">{lng[Tax]}({$cart['tax_details']['tax_name']}):</td>
 <td align="right">{price $cart['tax']}</td>
</tr>
{/if}
{if $cart['gift_card']}
<tr>
 <td align="right">{lng[Paid with Gift Card]} <span class="remove_gc">(x)</span>:</td>
 <td align="right">{price $cart['gc_discount']}</td>
</tr>
{/if}
<tr class="totals">
 <td align="right">{lng[Total]}:</td>
 <td align="right">{price $cart['total']}</td>
</tr>
</table>
</div>
{if !$cart['coupon']}<div class="apply_coupon">{lng[Have a discount coupon?]}</div>
{/if}
{if !$cart['gift_card']}
<br />
<div class="apply_gc">{lng[Have a Gift Card?]}</div>
{/if}
<br />
<div class="register_error"></div>
{if $shipping_methods || (!$cart['need_shipping'] && $get['1'] == 'user_form')}
<center>
<button type="button" id="card-submit">{lng[Place order]}</button>
</center>
{/if}
</form>

{if $client_token}
<script>
var form = document.querySelector('#checkoutform');
var submit = document.querySelector('#card-submit');
var client_token = "{$client_token}",
	threeDSecure = '';

braintree.client.create({
  authorization: client_token
}, function (err, clientInstance) {
  if (err) {
    console.error(err);
    return;
  }


  braintree.hostedFields.create({
    client: clientInstance,
    styles: {
      'input': {
        'color': '#282c37',
        'font-size': '16px',
        'transition': 'color 0.1s',
        'line-height': '3'
      },
      'input.invalid': {
        'color': '#E53A40'
      },
      '::-webkit-input-placeholder': {
        'color': 'rgba(0,0,0,0.6)'
      },
      ':-moz-placeholder': {
        'color': 'rgba(0,0,0,0.6)'
      },
      '::-moz-placeholder': {
        'color': 'rgba(0,0,0,0.6)'
      },
      ':-ms-input-placeholder': {
        'color': 'rgba(0,0,0,0.6)'
      }

    },
    fields: {
      number: {
        selector: '#card_number',
        placeholder: '1111 1111 1111 1111'
      },
      cvv: {
        selector: '#cv_code',
        placeholder: '123'
      },
      expirationDate: {
        selector: '#ex_date',
        placeholder: '10 / 2022'
      }
    }
  }, function (err, hostedFieldsInstance) {
    if (err) {
      console.error(err);
      return;
    }

    hostedFieldsInstance.on('validityChange', function (event) {
      var formValid = Object.keys(event.fields).every(function (key) {
        return event.fields[key].isValid;
      });

      if (formValid) {
        $('#button-pay').addClass('show-button');
      } else {
        $('#button-pay').removeClass('show-button');
      }
    });

    hostedFieldsInstance.on('empty', function (event) {
      $('header').removeClass('header-slide');
      $('#card-image').removeClass();
      $(form).removeClass();
    });

    hostedFieldsInstance.on('cardTypeChange', function (event) {
      if (event.cards.length === 1) {
        $(form).removeClass().addClass(event.cards[0].type);
        $('#card-image').removeClass().addClass(event.cards[0].type);
        $('header').addClass('header-slide');

        if (event.cards[0].code.size === 4) {
          hostedFieldsInstance.setAttribute({
            field: 'cvv',
            attribute: 'placeholder',
            value: '1234'
          });
        }
      } else {
        hostedFieldsInstance.setAttribute({
          field: 'cvv',
          attribute: 'placeholder',
          value: '123'
        });
      }
    });

	hostedFieldsInstance_obj = hostedFieldsInstance;
{*
    braintree.threeDSecure.create({
      authorization: client_token,
      version: 2
	}, function (createError, threeDSecure_obj) {		threeDSecure = threeDSecure_obj;

    });
*}
  });
});
</script>
{/if}