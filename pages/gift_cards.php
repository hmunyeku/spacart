<?php
/**
 * SpaCart Page - Gift Cards (public page to check balance)
 */

$pageTitle = $spacartLangs['gift_cards'] ?? 'Cartes cadeaux';
$breadcrumbs = array(array('label' => $pageTitle));

$action = $_POST['action'] ?? '';
$giftcardInfo = null;

if ($action === 'check_balance') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    if (!empty($code)) {
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."spacart_giftcard WHERE code = '".$db->escape($code)."' AND active = 1";
        $res = $db->query($sql);
        if ($res && $db->num_rows($res) > 0) {
            $giftcardInfo = $db->fetch_object($res);
            if ($giftcardInfo->expires_at && strtotime($giftcardInfo->expires_at) < time()) {
                $giftcardInfo->expired = true;
            }
        } else {
            $giftcardInfo = false; // Not found
        }
    }
}

$data = array(
    'page_title' => $pageTitle,
    'giftcard_info' => $giftcardInfo,
    'code_checked' => ($action === 'check_balance'),
    'code_value' => $code ?? '',
);

spacart_render_page('gift_cards', $data, $pageTitle, $breadcrumbs);
