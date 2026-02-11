<?php
/**
 * SpaCart - API Router
 * Public REST API for AJAX operations
 */

define('SPACART_BOOT', true);
define('NOLOGIN', 1);
define('NOCSRFCHECK', 1);
define('NOIPCHECK', 1);

// Load Dolibarr environment
$res = @include '../../main.inc.php';
if (!$res) {
    $res = @include '../../../main.inc.php';
}
if (!$res) {
    die('Dolibarr environment not found');
}

define('SPACART_PATH', dirname(__DIR__));
define('SPACART_URL', DOL_URL_ROOT.'/custom/spacart');
define('SPACART_API_URL', SPACART_URL.'/api');

// Include functions
require_once SPACART_PATH.'/lib/spacart.lib.php';
require_once SPACART_PATH.'/includes/func/func.core.php';

// Start session
session_name('spacart_session');
session_start();

// Parse request
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/custom/spacart/api';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = substr($path, strpos($path, $basePath) + strlen($basePath));
$path = trim($path, '/');
$segments = $path ? explode('/', $path) : array();

$endpoint = $segments[0] ?? '';
$subEndpoint = $segments[1] ?? '';
$param = $segments[2] ?? '';

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, X-SpaCart-Token');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Route
try {
    switch ($endpoint) {

        // ======== Cart ========
        case 'cart':
            require_once SPACART_PATH.'/includes/func/func.cart.php';

            if ($subEndpoint === 'add' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $productId = (int) ($_POST['product_id'] ?? 0);
                $qty = max(1, (int) ($_POST['qty'] ?? 1));
                $variantId = (int) ($_POST['variant_id'] ?? 0);
                $options = array();
                foreach ($_POST as $k => $v) {
                    if (strpos($k, 'option_') === 0) $options[substr($k, 7)] = (int) $v;
                }
                $result = spacart_cart_add($cartId, $productId, $qty, $variantId, $options);
                $summary = spacart_get_cart_summary($cartId);
                $result['cart_count'] = $summary['count'];
                $result['cart_total'] = $summary['subtotal'];
                spacart_json_response($result);

            } elseif ($subEndpoint === 'update' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $itemId = (int) ($_POST['item_id'] ?? 0);
                $qty = (int) ($_POST['qty'] ?? 0);
                $result = spacart_cart_update_qty($cartId, $itemId, $qty);
                $summary = spacart_get_cart_summary($cartId);
                $result['cart_count'] = $summary['count'];
                $result['cart_total'] = $summary['subtotal'];
                spacart_json_response($result);

            } elseif ($subEndpoint === 'remove' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $itemId = (int) ($param ?: ($_POST['item_id'] ?? 0));
                $result = spacart_cart_remove_item($cartId, $itemId);
                $summary = spacart_get_cart_summary($cartId);
                $result['cart_count'] = $summary['count'];
                $result['cart_total'] = $summary['subtotal'];
                spacart_json_response($result);

            } elseif ($subEndpoint === 'coupon' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $result = spacart_cart_apply_coupon($cartId, trim($_POST['code'] ?? ''));
                $summary = spacart_get_cart_summary($cartId);
                $result['cart_count'] = $summary['count'];
                $result['cart_total'] = $summary['subtotal'];
                spacart_json_response($result);

            } elseif ($subEndpoint === 'giftcard' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $result = spacart_cart_apply_giftcard($cartId, trim($_POST['code'] ?? ''));
                $summary = spacart_get_cart_summary($cartId);
                $result['cart_count'] = $summary['count'];
                $result['cart_total'] = $summary['subtotal'];
                spacart_json_response($result);

            } else {
                $cartId = spacart_api_get_cart_id();
                $cart = spacart_load_cart($cartId);
                spacart_json_response(array('success' => true, 'cart' => $cart));
            }
            break;

        // ======== Customer ========
        case 'customer':
            require_once SPACART_PATH.'/includes/func/func.user.php';

            if ($subEndpoint === 'register' && $method === 'POST') {
                $result = spacart_register_customer($_POST);
                spacart_json_response($result);

            } elseif ($subEndpoint === 'login' && $method === 'POST') {
                $result = spacart_login_customer(
                    $_POST['email'] ?? '',
                    $_POST['password'] ?? '',
                    !empty($_POST['remember'])
                );
                spacart_json_response($result);

            } elseif ($subEndpoint === 'logout' && $method === 'POST') {
                $result = spacart_logout_customer();
                spacart_json_response($result);

            } elseif ($subEndpoint === 'profile') {
                $custId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                if (!$custId) {
                    spacart_json_error('Non connecté', 401);
                    break;
                }
                if ($method === 'POST' || $method === 'PUT') {
                    $result = spacart_update_profile($custId, $_POST);
                    spacart_json_response($result);
                } else {
                    $customer = spacart_load_customer($custId);
                    spacart_json_response(array('success' => true, 'customer' => $customer));
                }

            } elseif ($subEndpoint === 'orders') {
                $custId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                if (!$custId) { spacart_json_error('Non connecté', 401); break; }
                $customer = spacart_load_customer($custId);
                $orders = spacart_get_customer_orders($customer->fk_soc ?? 0);
                spacart_json_response(array('success' => true, 'orders' => $orders));

            } elseif ($subEndpoint === 'wishlist') {
                $custId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                if (!$custId) {
                    spacart_json_response(array('success' => false, 'login_required' => true));
                    break;
                }
                if ($method === 'POST') {
                    $productId = (int) ($_POST['product_id'] ?? 0);
                    $result = spacart_toggle_wishlist($custId, $productId);
                    spacart_json_response($result);
                } else {
                    $wishlist = spacart_get_wishlist($custId);
                    spacart_json_response(array('success' => true, 'wishlist' => $wishlist));
                }

            } else {
                spacart_json_error('Endpoint not found', 404);
            }
            break;

        // ======== Checkout ========
        case 'checkout':
            require_once SPACART_PATH.'/includes/func/func.cart.php';
            require_once SPACART_PATH.'/includes/func/func.user.php';
            require_once SPACART_PATH.'/includes/func/func.order.php';

            if ($subEndpoint === 'validate' && $method === 'POST') {
                $cartId = spacart_api_get_cart_id();
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                $result = spacart_create_order($cartId, $customerId, $_POST);

                if ($result['success']) {
                    $paymentMethod = $_POST['payment_method'] ?? 'bank_transfer';

                    if ($paymentMethod === 'stripe') {
                        // Create Stripe PaymentIntent
                        $stripeResult = spacart_create_stripe_intent($result['order_id'], $cartId);
                        if ($stripeResult) {
                            $result['stripe_client_secret'] = $stripeResult;
                        }
                    } elseif ($paymentMethod === 'paypal') {
                        // Generate PayPal redirect
                        $result['payment_redirect'] = spacart_create_paypal_payment($result['order_id']);
                    }
                    // bank_transfer and cod: no redirect needed
                }

                spacart_json_response($result);

            } else {
                spacart_json_error('Endpoint not found', 404);
            }
            break;

        // ======== Shipping ========
        case 'shipping':
            require_once SPACART_PATH.'/includes/func/func.order.php';

            if ($subEndpoint === 'methods') {
                $methods = spacart_get_shipping_methods();
                spacart_json_response(array('success' => true, 'methods' => $methods));

            } elseif ($subEndpoint === 'calculate') {
                require_once SPACART_PATH.'/includes/func/func.cart.php';
                $cartId = spacart_api_get_cart_id();
                $cart = spacart_load_cart($cartId);
                $methodId = (int) ($_GET['method_id'] ?? 0);
                $countryId = (int) ($_GET['country_id'] ?? 1);

                $cost = spacart_calculate_shipping_rate($methodId, $countryId, $cart ? $cart->subtotal : 0, 0);

                // Update cart shipping cost
                if ($cart) {
                    $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_cart SET shipping_cost = ".(float) $cost.", shipping_method = ".$methodId.", tms = NOW() WHERE rowid = ".(int) $cartId);
                    spacart_recalculate_cart($cartId);
                    $cart = spacart_load_cart($cartId);
                }

                spacart_json_response(array(
                    'success' => true,
                    'shipping_cost' => $cost,
                    'total' => $cart ? $cart->total : 0
                ));
            }
            break;

        // ======== Reviews ========
        case 'reviews':
            require_once SPACART_PATH.'/includes/func/func.product.php';

            if ($method === 'POST') {
                $productId = (int) ($_POST['product_id'] ?? 0);
                $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
                $title = trim($_POST['title'] ?? '');
                $comment = trim($_POST['comment'] ?? '');
                $customerName = trim($_POST['customer_name'] ?? 'Anonyme');
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);

                if (!$productId || !$comment) {
                    spacart_json_error('Produit et commentaire requis');
                    break;
                }

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_review";
                $sql .= " (fk_product, fk_customer, customer_name, rating, title, comment, status, date_creation)";
                $sql .= " VALUES (".$productId.", ".$customerId.", '".$db->escape($customerName)."',";
                $sql .= " ".$rating.", '".$db->escape($title)."', '".$db->escape($comment)."', 0, NOW())";
                $db->query($sql);

                spacart_json_response(array('success' => true, 'message' => 'Avis soumis (en attente de modération)'));

            } else {
                $productId = (int) ($subEndpoint ?: 0);
                $reviews = spacart_get_product_reviews($productId);
                spacart_json_response(array('success' => true, 'reviews' => $reviews));
            }
            break;

        // ======== Newsletter ========
        case 'newsletter':
            if ($method === 'POST') {
                $email = trim($_POST['email'] ?? '');
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    spacart_json_error('Email invalide');
                    break;
                }
                $sqlCheck = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_subscriber WHERE email = '".$db->escape($email)."'";
                $resCheck = $db->query($sqlCheck);
                if ($resCheck && $db->num_rows($resCheck)) {
                    spacart_json_response(array('success' => true, 'message' => 'Vous êtes déjà inscrit'));
                } else {
                    $db->query("INSERT INTO ".MAIN_DB_PREFIX."spacart_subscriber (email, active, date_creation) VALUES ('".$db->escape($email)."', 1, NOW())");
                    spacart_json_response(array('success' => true, 'message' => 'Inscription réussie !'));
                }
            }
            break;

        // ======== Contact / Send to friend ========
        case 'contact':
            if ($method === 'POST') {
                $action = $_POST['action'] ?? '';
                if ($action === 'send_to_friend') {
                    $friendEmail = trim($_POST['email'] ?? '');
                    $productId = (int) ($_POST['product_id'] ?? 0);
                    if ($friendEmail && $productId) {
                        require_once SPACART_PATH.'/includes/func/func.product.php';
                        $product = spacart_get_product($productId);
                        if ($product) {
                            $subject = 'Un ami vous recommande : '.$product->label;
                            $body = 'Découvrez ce produit : '.$product->label."\n\n";
                            $body .= 'Voir le produit : '.DOL_MAIN_URL_ROOT.'/custom/spacart/public/#/product/'.$productId;
                            spacart_send_mail($friendEmail, $subject, $body);
                            spacart_json_response(array('success' => true, 'message' => 'Email envoyé !'));
                        } else {
                            spacart_json_error('Produit non trouvé');
                        }
                    } else {
                        spacart_json_error('Email et produit requis');
                    }
                }
            }
            break;

        // ======== Webhooks ========
        case 'webhooks':
            if ($subEndpoint === 'stripe') {
                require_once SPACART_PATH.'/pages/stripe.php';
            } elseif ($subEndpoint === 'paypal') {
                require_once SPACART_PATH.'/pages/paypal.php';
            }
            break;

        default:
            spacart_json_error('Endpoint not found', 404);
    }
} catch (Exception $e) {
    spacart_json_error('Internal error: '.$e->getMessage(), 500);
}

/**
 * Helper: get cart ID from session
 */
function spacart_api_get_cart_id()
{
    $cartId = (int) ($_SESSION['spacart_cart_id'] ?? 0);
    if (!$cartId) {
        $sessionId = $_SESSION['spacart_token'] ?? session_id();
        $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
        $cart = spacart_get_or_create_cart($sessionId, $customerId);
        if ($cart) {
            $cartId = $cart->rowid;
            $_SESSION['spacart_cart_id'] = $cartId;
        }
    }
    return $cartId;
}

/**
 * Create Stripe PaymentIntent using Dolibarr's native Stripe module
 *
 * @param int $orderId  Dolibarr order ID
 * @param int $cartId   SpaCart cart ID
 * @return string|null  PaymentIntent client_secret or null on error
 */
function spacart_create_stripe_intent($orderId, $cartId)
{
    global $db, $conf, $user;

    // Check Stripe is configured in Dolibarr
    if (!isModEnabled('stripe')) return null;

    require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
    require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

    $order = new Commande($db);
    $order->fetch($orderId);
    if (!$order->id) return null;

    // Use technical user for Stripe operations
    $techUserId = getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1);
    $techUser = new User($db);
    $techUser->fetch($techUserId);

    $stripe = new Stripe($db);
    $status = getDolGlobalString('STRIPE_LIVE') ? 1 : 0;
    $stripeacc = $stripe->getStripeAccount($status);

    // Get or create Stripe customer for this third-party
    $stripeCustomer = null;
    if ($order->fk_soc) {
        $soc = new Societe($db);
        $soc->fetch($order->fk_soc);
        $stripeCustomer = $stripe->customerStripe($soc, $stripeacc, $status, 1);
    }

    $currency = strtolower($conf->currency);
    $amount = $order->total_ttc;
    $tag = $order->ref;
    $description = 'Commande '.$order->ref;

    $paymentintent = $stripe->getPaymentIntent(
        $amount,
        $currency,
        $tag,
        $description,
        $order,
        $stripeCustomer ? $stripeCustomer->id : null,
        $stripeacc,
        $status,
        0,        // usethirdpartyemailforreceiptemail
        'automatic',
        false,    // confirmnow
        null,     // payment_method
        0,        // off_session
        1,        // noidempotency_key
        0         // did
    );

    if ($paymentintent && $paymentintent->client_secret) {
        // Store PI reference in order notes
        $order->note_private = ($order->note_private ? $order->note_private."\n" : '')
            .'stripe_payment_intent='.$paymentintent->id;
        $order->update_note($order->note_private, '_private');

        return $paymentintent->client_secret;
    }

    return null;
}

/**
 * Create PayPal payment redirect using Dolibarr's native PayPal module
 *
 * @param int $orderId  Dolibarr order ID
 * @return string|null  PayPal redirect URL or null on error
 */
function spacart_create_paypal_payment($orderId)
{
    global $db, $conf;

    // Check PayPal is configured in Dolibarr
    if (!isModEnabled('paypal')) return null;

    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

    $order = new Commande($db);
    $order->fetch($orderId);
    if (!$order->id) return null;

    // Use Dolibarr's public payment page with order source
    $paymentUrl = DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php'
        .'?source=order'
        .'&ref='.urlencode($order->ref)
        .'&amount='.urlencode($order->total_ttc)
        .'&currency='.urlencode($conf->currency)
        .'&tag='.urlencode('spacart_'.$order->ref);

    return $paymentUrl;
}
