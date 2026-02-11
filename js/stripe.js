/**
 * SpaCart - Stripe Elements integration
 * Uses Dolibarr's native Stripe module (publishable key from Dolibarr config)
 */
(function() {
    'use strict';

    var SC = window.SpaCart;
    var config = window.SpaCartConfig || {};

    if (!SC) return;

    SC.stripe = SC.stripe || {};

    var stripe = null;
    var elements = null;
    var cardElement = null;

    SC.stripe.init = function() {
        // Use Dolibarr's Stripe publishable key (set via checkout template)
        var pk = config.stripePk || config.stripeKey || '';
        if (!pk || typeof Stripe === 'undefined') {
            console.log('SpaCart Stripe: not available (no key or Stripe.js not loaded)');
            return;
        }

        stripe = Stripe(pk);
        SC.stripe.instance = stripe;
    };

    SC.stripe.mountCard = function() {
        if (!stripe) SC.stripe.init();
        if (!stripe) return;

        var container = document.getElementById('card-element');
        if (!container) return;

        elements = stripe.elements();
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#333',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': { color: '#999' }
                },
                invalid: {
                    color: '#ff5252',
                    iconColor: '#ff5252'
                }
            },
            hidePostalCode: true
        });

        cardElement.mount('#card-element');

        cardElement.on('change', function(event) {
            var errorEl = document.getElementById('card-errors');
            if (errorEl) {
                errorEl.textContent = event.error ? event.error.message : '';
            }
        });
    };

    SC.stripe.unmountCard = function() {
        if (cardElement) {
            cardElement.unmount();
            cardElement = null;
        }
    };

    SC.stripe.processPayment = function(clientSecret, orderId) {
        if (!stripe) {
            SC.util.toast('Stripe non initialisé', 'error');
            return;
        }

        SC.util.showLoader();

        var confirmData = {};
        if (cardElement) {
            confirmData.payment_method = { card: cardElement };
        }

        stripe.confirmCardPayment(clientSecret, confirmData).then(function(result) {
            SC.util.hideLoader();
            if (result.error) {
                SC.util.toast(result.error.message, 'error');
            } else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                SC.util.toast('Paiement confirmé !', 'success');
                SC.cart.updateBadge(0, 0);
                SC.router.navigate('/stripe/' + orderId + '?status=success');
            } else {
                SC.util.toast('Paiement en cours de traitement...', 'warning');
                // Redirect to order page - webhook will confirm later
                SC.router.navigate('/invoice/' + orderId);
            }
        }).catch(function(err) {
            SC.util.hideLoader();
            SC.util.toast(err.message || 'Erreur de paiement', 'error');
        });
    };

    SC.stripe.isAvailable = function() {
        return stripe !== null;
    };

    // Watch for payment method selection to show/hide card element
    document.addEventListener('change', function(e) {
        if (e.target.name === 'payment_method') {
            var cardContainer = document.getElementById('spacart-stripe-card');
            if (cardContainer) {
                if (e.target.value === 'stripe') {
                    cardContainer.style.display = 'block';
                    SC.stripe.mountCard();
                } else {
                    cardContainer.style.display = 'none';
                    SC.stripe.unmountCard();
                }
            }
        }
    });

})();
