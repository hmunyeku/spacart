<?php
/**
 * SpaCart Email Template - Order Shipped
 * Variables: $order, $customer, $trackingNumber, $trackingUrl, $shopName, $shopUrl
 */
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">

<!-- Header -->
<tr><td style="background:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;padding:30px;text-align:center;">
    <h1 style="color:#ffffff;margin:0;font-size:24px;"><?php echo htmlspecialchars($shopName); ?></h1>
</td></tr>

<!-- Content -->
<tr><td style="padding:30px;">
    <h2 style="color:#333;margin-top:0;">Votre commande a été expédiée !</h2>
    <p>Bonjour <?php echo htmlspecialchars($customer->firstname); ?>,</p>
    <p>Bonne nouvelle ! Votre commande <strong><?php echo htmlspecialchars($order->ref); ?></strong> a été expédiée.</p>

    <?php if (!empty($trackingNumber)) { ?>
    <table style="width:100%;border-collapse:collapse;margin:20px 0;background:#e8f5e9;border-radius:4px;">
        <tr>
            <td style="padding:20px;text-align:center;">
                <p style="margin:0 0 10px;font-size:16px;font-weight:bold;">Numéro de suivi</p>
                <p style="margin:0;font-size:20px;letter-spacing:2px;"><?php echo htmlspecialchars($trackingNumber); ?></p>
                <?php if (!empty($trackingUrl)) { ?>
                <p style="margin:15px 0 0;"><a href="<?php echo htmlspecialchars($trackingUrl); ?>" style="background:#4caf50;color:#fff;padding:10px 25px;text-decoration:none;border-radius:4px;display:inline-block;">Suivre mon colis</a></p>
                <?php } ?>
            </td>
        </tr>
    </table>
    <?php } ?>

    <p>Vous recevrez votre colis dans les prochains jours.</p>

    <p style="text-align:center;margin:30px 0;">
        <a href="<?php echo $shopUrl; ?>#/invoice/<?php echo $order->id; ?>" style="background:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;color:#fff;padding:12px 30px;text-decoration:none;border-radius:4px;display:inline-block;">Voir ma commande</a>
    </p>

    <p>Cordialement,<br><strong><?php echo htmlspecialchars($shopName); ?></strong></p>
</td></tr>

<!-- Footer -->
<tr><td style="background:#f5f5f5;padding:20px;text-align:center;font-size:12px;color:#999;">
    <p>&copy; <?php echo date('Y').' '.htmlspecialchars($shopName); ?>. Tous droits réservés.</p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
