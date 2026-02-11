<?php
/**
 * SpaCart - PayPal callback & IPN handler
 *
 * PayPal payments are processed through Dolibarr's native payment page:
 *   /public/payment/newpayment.php?source=order&ref=...
 *
 * The IPN (Instant Payment Notification) is handled by Dolibarr natively.
 * This file only provides a SpaCart-specific fallback webhook and callback pages.
 */

// IPN Webhook mode (fallback - prefer Dolibarr's native IPN handler)
if (strpos($_SERVER['REQUEST_URI'], 'webhooks/paypal') !== false) {
    $raw = file_get_contents('php://input');
    parse_str($raw, $ipnData);

    // Determine PayPal sandbox mode from Dolibarr config
    $isSandbox = getDolGlobalString('PAYPAL_API_SANDBOX');
    $verifyUrl = $isSandbox
        ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
        : 'https://ipnpb.paypal.com/cgi-bin/webscr';

    $verifyData = 'cmd=_notify-validate&'.$raw;

    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $verifyData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    curl_close($ch);

    if (strcmp($response, 'VERIFIED') === 0) {
        $paymentStatus = $ipnData['payment_status'] ?? '';

        // Extract order ref from tag field (format: spacart_CO1234-5678)
        $tag = $ipnData['custom'] ?? ($ipnData['item_number'] ?? '');
        $orderRef = str_replace('spacart_', '', $tag);

        if ($paymentStatus === 'Completed' && !empty($orderRef)) {
            require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

            $commande = new Commande($db);
            // Try fetching by ref first, then by ID
            if (is_numeric($orderRef)) {
                $commande->fetch((int) $orderRef);
            } else {
                $commande->fetch(0, $orderRef);
            }

            if ($commande->id > 0) {
                $techUser = new User($db);
                $techUser->fetch(getDolGlobalInt('SPACART_TECHNICAL_USER_ID', 1));

                if ($commande->statut == Commande::STATUS_DRAFT) {
                    $commande->valid($techUser);
                }

                $commande->note_private .= "\n[".date('Y-m-d H:i').'] PayPal payment confirmed: '
                    .($ipnData['txn_id'] ?? '').' - '
                    .($ipnData['mc_gross'] ?? '').' '
                    .($ipnData['mc_currency'] ?? '');
                $commande->update_note($commande->note_private, '_private');
            }
        }
    }

    http_response_code(200);
    echo 'OK';
    exit;
}

// ===== Callback page (after PayPal redirect, displayed in SPA) =====
if (!defined('SPACART_BOOT')) die('Access denied');

$orderId = !empty($_GET['order_id']) ? (int) $_GET['order_id'] : (!empty($get[1]) ? (int) $get[1] : 0);
$status = !empty($_GET['status']) ? $_GET['status'] : 'success';

if ($status === 'cancel') {
    $page_html = '<div class="center-align" style="padding:50px;"><i class="material-icons large" style="color:#ff9800;">info</i><h5>Paiement annulé</h5><p>Vous avez annulé le paiement PayPal.</p><a href="#/cart" class="btn spacart-spa-link">Retour au panier</a></div>';
    $page_title = 'Paiement annulé';
} elseif ($orderId) {
    $page_html = '<div class="center-align" style="padding:50px;"><i class="material-icons large" style="color:#4caf50;">check_circle</i><h5>Paiement en cours de validation</h5><p>Votre paiement PayPal est en cours de vérification.</p><a href="#/invoice/'.$orderId.'" class="btn spacart-spa-link">Voir ma commande</a></div>';
    $page_title = 'Paiement PayPal';
} else {
    $page_html = '<div class="center-align" style="padding:50px;"><i class="material-icons large grey-text">payment</i><h5>PayPal</h5><a href="#/" class="btn spacart-spa-link">Retour à l\'accueil</a></div>';
    $page_title = 'PayPal';
}
$breadcrumbs_html = '';
