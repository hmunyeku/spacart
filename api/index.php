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
$_api_path_parsed = parse_url($requestUri, PHP_URL_PATH);

// Support both proxy URL and direct URL for API base path
// Build list of possible base paths: internal path + proxy path if configured
$_api_base_paths = array('/custom/spacart/api');
$_api_proxy_url = getDolGlobalString('SPACART_PUBLIC_URL');
if (!empty($_api_proxy_url)) {
    $_api_proxy_parsed = parse_url($_api_proxy_url);
    $_api_proxy_path = rtrim($_api_proxy_parsed['path'] ?? '', '/');
    if (!empty($_api_proxy_path)) {
        array_unshift($_api_base_paths, $_api_proxy_path . '/api');
    }
}

$path = '';
foreach ($_api_base_paths as $_abp) {
    $_bpos = strpos($_api_path_parsed, $_abp);
    if ($_bpos !== false) {
        $path = substr($_api_path_parsed, $_bpos + strlen($_abp));
        break;
    }
}
unset($_api_base_paths, $_api_proxy_url, $_api_proxy_parsed, $_api_proxy_path, $_abp, $_bpos);
$path = trim($path, '/');
$segments = $path ? explode('/', $path) : array();

$endpoint = $segments[0] ?? '';
$subEndpoint = $segments[1] ?? '';
$param = $segments[2] ?? '';

// CORS headers (SEC-HIGH: restricted origin - no wildcard)
header('Content-Type: application/json; charset=utf-8');
$_spacart_allowed_origins = array(
    'https://www.coexdis.com',
    'https://coexdis.com',
    'http://www.coexdis.com',
    'http://coexdis.com'
);
$_spacart_origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($_spacart_origin, $_spacart_allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $_spacart_origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // Default to same host (for direct/non-CORS requests)
    $_spacart_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    header('Access-Control-Allow-Origin: ' . $_spacart_scheme . '://' . $_SERVER['HTTP_HOST']);
}
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

        // ======== Blog Comments ========
        case 'blog':
            if ($subEndpoint === 'comment' && $method === 'POST') {
                $postId = (int) ($_POST['post_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $comment = trim($_POST['comment'] ?? '');
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);

                if (!$postId || !$comment) {
                    spacart_json_error('Post ID et commentaire requis');
                    break;
                }
                if (!$customerId && (!$name || !$email)) {
                    spacart_json_error('Nom et email requis');
                    break;
                }

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_blog_comment";
                $sql .= " (fk_blog, fk_customer, author_name, author_email, comment, status, date_creation)";
                $sql .= " VALUES (".$postId.", ".$customerId.", '".$db->escape($name)."',";
                $sql .= " '".$db->escape($email)."', '".$db->escape($comment)."', 0, NOW())";
                $db->query($sql);
                spacart_json_response(array('success' => true, 'message' => 'Commentaire soumis (en attente de modération)'));
            } elseif ($subEndpoint === 'comments') {
                $postId = (int) ($param ?: ($_GET['post_id'] ?? 0));
                $comments = array();
                if ($postId) {
                    $sql = "SELECT author_name, comment, date_creation FROM ".MAIN_DB_PREFIX."spacart_blog_comment WHERE fk_blog = ".$postId." AND status = 1 ORDER BY date_creation DESC";
                    $res = $db->query($sql);
                    if ($res) {
                        while ($obj = $db->fetch_object($res)) {
                            $comments[] = $obj;
                        }
                    }
                }
                spacart_json_response(array('success' => true, 'comments' => $comments));
            }
            break;

        // ======== News Comments ========
        case 'news':
            if ($subEndpoint === 'comment' && $method === 'POST') {
                $newsId = (int) ($_POST['news_id'] ?? 0);
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $comment = trim($_POST['comment'] ?? '');
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);

                if (!$newsId || !$comment) {
                    spacart_json_error('News ID et commentaire requis');
                    break;
                }
                if (!$customerId && (!$name || !$email)) {
                    spacart_json_error('Nom et email requis');
                    break;
                }

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_news_comment";
                $sql .= " (fk_news, fk_customer, author_name, author_email, comment, status, date_creation)";
                $sql .= " VALUES (".$newsId.", ".$customerId.", '".$db->escape($name)."',";
                $sql .= " '".$db->escape($email)."', '".$db->escape($comment)."', 0, NOW())";
                $db->query($sql);
                spacart_json_response(array('success' => true, 'message' => 'Commentaire soumis (en attente de modération)'));
            } elseif ($subEndpoint === 'comments') {
                $newsId = (int) ($param ?: ($_GET['news_id'] ?? 0));
                $comments = array();
                if ($newsId) {
                    $sql = "SELECT author_name, comment, date_creation FROM ".MAIN_DB_PREFIX."spacart_news_comment WHERE fk_news = ".$newsId." AND status = 1 ORDER BY date_creation DESC";
                    $res = $db->query($sql);
                    if ($res) {
                        while ($obj = $db->fetch_object($res)) {
                            $comments[] = $obj;
                        }
                    }
                }
                spacart_json_response(array('success' => true, 'comments' => $comments));
            }
            break;

        // ======== Password Reset ========
        case 'password':
            require_once SPACART_PATH.'/includes/func/func.user.php';

            if ($subEndpoint === 'reset' && $method === 'POST') {
                $action = $_POST['action'] ?? 'request';

                if ($action === 'request') {
                    $email = trim($_POST['email'] ?? '');
                    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        spacart_json_error('Email invalide');
                        break;
                    }

                    $sql = "SELECT rowid, firstname, lastname FROM ".MAIN_DB_PREFIX."spacart_customer WHERE email = '".$db->escape($email)."' AND active = 1";
                    $res = $db->query($sql);
                    if ($res && $db->num_rows($res)) {
                        $customer = $db->fetch_object($res);
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer SET reset_token = '".$db->escape($token)."', reset_token_expiry = '".$expiry."', tms = NOW() WHERE rowid = ".(int) $customer->rowid);

                        $shopUrl = getDolGlobalString('SPACART_SHOP_URL', DOL_MAIN_URL_ROOT.'/custom/spacart/public/');
                        $resetUrl = $shopUrl.'#/password?token='.$token.'&email='.urlencode($email);
                        $subject = 'Réinitialisation mot de passe';
                        $body = $customer->firstname.",\n\nCliquez ici pour réinitialiser votre mot de passe :\n".$resetUrl."\n\nCe lien expire dans 1 heure.";
                        spacart_send_mail($email, $subject, $body);
                    }
                    spacart_json_response(array('success' => true, 'message' => 'Si un compte existe, un email a été envoyé.'));

                } elseif ($action === 'confirm') {
                    $email = trim($_POST['email'] ?? '');
                    $token = trim($_POST['token'] ?? '');
                    $newPassword = $_POST['password'] ?? '';

                    if (!$email || !$token || strlen($newPassword) < 6) {
                        spacart_json_error('Données invalides');
                        break;
                    }

                    $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."spacart_customer WHERE email = '".$db->escape($email)."' AND reset_token = '".$db->escape($token)."' AND reset_token_expiry > NOW()";
                    $res = $db->query($sql);
                    if ($res && $db->num_rows($res)) {
                        $cust = $db->fetch_object($res);
                        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                        $db->query("UPDATE ".MAIN_DB_PREFIX."spacart_customer SET password = '".$db->escape($hash)."', reset_token = NULL, reset_token_expiry = NULL, tms = NOW() WHERE rowid = ".(int) $cust->rowid);
                        spacart_json_response(array('success' => true, 'message' => 'Mot de passe réinitialisé'));
                    } else {
                        spacart_json_error('Lien invalide ou expiré');
                    }
                }
            }
            break;

        // ======== Support Tickets ========
        case 'support':
            if ($subEndpoint === 'ticket' && $method === 'POST') {
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                $subject = trim($_POST['subject'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $name = trim($_POST['name'] ?? '');

                if (!$subject || !$message) {
                    spacart_json_error('Sujet et message requis');
                    break;
                }

                if ($customerId) {
                    require_once SPACART_PATH.'/includes/func/func.user.php';
                    $customer = spacart_load_customer($customerId);
                    if ($customer) {
                        $email = $customer->email;
                        $name = $customer->firstname.' '.$customer->lastname;
                    }
                }

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."spacart_support_ticket";
                $sql .= " (fk_customer, customer_name, customer_email, subject, status, date_creation, tms)";
                $sql .= " VALUES (".(int) $customerId.", '".$db->escape($name)."', '".$db->escape($email)."',";
                $sql .= " '".$db->escape($subject)."', 'open', NOW(), NOW())";
                $db->query($sql);
                $ticketId = $db->last_insert_id(MAIN_DB_PREFIX."spacart_support_ticket");

                if ($ticketId) {
                    $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."spacart_support_message";
                    $sql2 .= " (fk_ticket, sender_type, sender_name, message, date_creation)";
                    $sql2 .= " VALUES (".(int) $ticketId.", 'customer', '".$db->escape($name)."', '".$db->escape($message)."', NOW())";
                    $db->query($sql2);
                }

                spacart_json_response(array('success' => true, 'message' => 'Ticket créé', 'ticket_id' => $ticketId));
            } elseif ($subEndpoint === 'tickets') {
                $customerId = (int) ($_SESSION['spacart_customer_id'] ?? 0);
                if (!$customerId) {
                    spacart_json_error('Non connecté', 401);
                    break;
                }
                $tickets = array();
                $sql = "SELECT rowid, subject, status, date_creation FROM ".MAIN_DB_PREFIX."spacart_support_ticket WHERE fk_customer = ".$customerId." ORDER BY date_creation DESC";
                $res = $db->query($sql);
                if ($res) {
                    while ($obj = $db->fetch_object($res)) {
                        $tickets[] = $obj;
                    }
                }
                spacart_json_response(array('success' => true, 'tickets' => $tickets));
            }
            break;

        // ======== Products API (public listing) ========
        case 'products':
            require_once SPACART_PATH.'/includes/func/func.product.php';

            $page = max(1, (int) ($_GET['page'] ?? 1));
            $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
            $categoryId = (int) ($_GET['category_id'] ?? 0);
            $search = trim($_GET['q'] ?? '');
            $sort = $_GET['sort'] ?? 'date_desc';

            $filters = array();
            if ($categoryId) $filters['category_id'] = $categoryId;
            if ($search) $filters['search'] = $search;

            $result = spacart_get_products($filters, $sort, $page, $limit);

            spacart_json_response(array(
                'success' => true,
                'products' => $result['items'],
                'total' => $result['total'],
                'page' => $result['page'],
                'pages' => $result['pages']
            ));
            break;

        // ======== Language ========
        case 'language':
            if ($method === 'POST') {
                $lang = $db->escape($_POST['language'] ?? '');
                if ($lang) {
                    spacart_set_language($lang);
                    spacart_json_response(array('success' => true, 'language' => $lang));
                } else {
                    spacart_json_error('Language code required');
                }
            } else {
                spacart_json_response(array('language' => $_SESSION['spacart_language'] ?? 'fr_FR'));
            }
            break;

        // ======== Currency ========
        case 'currency':
            if ($method === 'POST') {
                $cur = $db->escape($_POST['currency'] ?? '');
                if ($cur) {
                    spacart_set_currency($cur);
                    $rate = spacart_get_currency_rate();
                    $symbol = spacart_get_currency_symbol();
                    spacart_json_response(array('success' => true, 'currency' => $cur, 'rate' => $rate, 'symbol' => $symbol));
                } else {
                    spacart_json_error('Currency code required');
                }
            } else {
                $currencies = spacart_get_currencies();
                spacart_json_response(array('currencies' => $currencies, 'current' => $_SESSION['spacart_currency'] ?? 'EUR'));
            }
            break;

        // ======== Theme ========
        case 'theme':
            if ($subEndpoint === 'save' && $method === 'POST') {
                $primary = $db->escape($_POST['primary_color'] ?? '');
                $secondary = $db->escape($_POST['secondary_color'] ?? '');
                if ($primary) {
                    $_SESSION['spacart_theme_color'] = $primary;
                }
                if ($secondary) {
                    $_SESSION['spacart_theme_color_2'] = $secondary;
                }
                spacart_json_response(array('success' => true));
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
