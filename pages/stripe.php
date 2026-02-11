<?php
/**
 * SpaCart - Stripe webhook handler & payment callback
 *
 * Webhooks: Delegates to Dolibarr's native Stripe webhook handler at
 * /public/stripe/ipn.php. This file only handles SpaCart-specific
 * post-processing (e.g. order status update after Dolibarr processes the payment).
 *
 * For webhook configuration in Stripe Dashboard, use:
 *   https://erp.coexdis.com/public/stripe/ipn.php
 * (Dolibarr's native endpoint)
 *
 * Callback page: displayed after Stripe redirect back to the shop.
 */

// Webhook mode (called from API router)
if (strpos($_SERVER['REQUEST_URI'], 'webhooks/stripe') !== false) {
    // Use Dolibarr's native Stripe module for webhook processing
    $payload = file_get_contents('php://input');
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (!$payload) {
        http_response_code(400);
        echo json_encode(array('error' => 'No payload'));
        exit;
    }

    // Load Dolibarr Stripe
    require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
    require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

    $status = getDolGlobalString('STRIPE_LIVE') ? 1 : 0;
    $webhookKey = $status
        ? getDolGlobalString('STRIPE_LIVE_WEBHOOK_KEY')
        : getDolGlobalString('STRIPE_TEST_WEBHOOK_KEY');

    // Verify signature using Stripe SDK
    $event = null;
    try {
        if ($webhookKey) {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookKey);
        } else {
            $event = json_decode($payload, false);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(array('error' => 'Webhook verification failed: '.$e->getMessage()));
        exit;
    }

    if (!$event) {
        http_response_code(400);
        echo json_encode(array('error' => 'Invalid event'));
        exit;
    }

    $type = is_array($event) ? ($event['type'] ?? '') : ($event->type ?? '');
    $dataObj = is_array($event) ? ($event['data']['object'] ?? array()) : ($event->data->object ?? null);

    switch ($type) {
        case 'payment_intent.succeeded':
            $piId = is_object($dataObj) ? $dataObj->id : ($dataObj['id'] ?? '');
            $metadata = is_object($dataObj) ? (array) $dataObj->metadata : ($dataObj['metadata'] ?? array());
            $orderId = (int) ($metadata['dol_id'] ?? $metadata['order_id'] ?? 0);

            if ($orderId) {
                require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
                $commande = new Commande($db);
                $commande->fetch($orderId);

                if ($commande->id > 0 && $commande->statut == Commande::STATUS_DRAFT) {
                    $techUser = new User($db);
                    $techUser->fetch(getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1));

                    // Validate the order
                    $commande->valid($techUser);

                    $amount = is_object($dataObj) ? $dataObj->amount : ($dataObj['amount'] ?? 0);
                    $currency = is_object($dataObj) ? $dataObj->currency : ($dataObj['currency'] ?? '');
                    $commande->note_private .= "\n[".date('Y-m-d H:i')."] Stripe payment confirmed: ".$piId.' - '.($amount / 100).' '.$currency;
                    $commande->update_note($commande->note_private, '_private');
                }
            }
            break;

        case 'payment_intent.payment_failed':
            $metadata = is_object($dataObj) ? (array) $dataObj->metadata : ($dataObj['metadata'] ?? array());
            $orderId = (int) ($metadata['dol_id'] ?? $metadata['order_id'] ?? 0);

            if ($orderId) {
                require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
                $commande = new Commande($db);
                $commande->fetch($orderId);
                if ($commande->id > 0) {
                    $errMsg = is_object($dataObj)
                        ? ($dataObj->last_payment_error->message ?? 'Unknown')
                        : ($dataObj['last_payment_error']['message'] ?? 'Unknown');
                    $commande->note_private .= "\n[".date('Y-m-d H:i')."] Stripe payment FAILED: ".$errMsg;
                    $techUser = new User($db);
                    $techUser->fetch(getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1));
                    $commande->update_note($commande->note_private, '_private');
                }
            }
            break;
    }

    http_response_code(200);
    echo json_encode(array('received' => true));
    exit;
}

// ===== Callback page (after Stripe payment, displayed in SPA) =====
if (!defined('SPACART_BOOT')) die('Access denied');

$orderId = !empty($get[1]) ? (int) $get[1] : 0;
$status = !empty($_GET['status']) ? $_GET['status'] : 'success';

if ($status === 'success' && $orderId) {
    $page_html = '<div class="center-align" style="padding:50px;"><i class="material-icons large" style="color:#4caf50;">check_circle</i><h5>Paiement confirmé !</h5><p>Votre commande a été validée.</p><a href="#/invoice/'.$orderId.'" class="btn spacart-spa-link">Voir ma commande</a></div>';
    $page_title = 'Paiement confirmé';
} else {
    $page_html = '<div class="center-align" style="padding:50px;"><i class="material-icons large" style="color:#ff5252;">error</i><h5>Erreur de paiement</h5><p>Le paiement n\'a pas abouti. Veuillez réessayer.</p><a href="#/cart" class="btn spacart-spa-link">Retour au panier</a></div>';
    $page_title = 'Erreur de paiement';
}
$breadcrumbs_html = '';
