var register_form = [];
register_form['firstname'] = "{lng[Firstname]}";
register_form['lastname'] = "{lng[Lastname]}";
register_form['email'] = "{lng[E-mail]}";
register_form['password'] = "{lng[Password]}";

register_form['address'] = "{lng[Address]}";
register_form['city'] = "{lng[City]}";
register_form['zipcode'] = "{lng[Zip/Postal code]}";
register_form['phone'] = "{lng[Phone]}";

(function($) {
"use strict";
  $(document).ready(function() {
	if (page == 'checkout') {		checkout_actions();
		coupon_actions();
		checkout_changes();
		$('#place_order *').attr('disabled', true);
	}
  });
})($);