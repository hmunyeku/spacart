<?php
/**
 * SpaCart Email Template - Order Confirmation
 * Variables: $order, $customer, $lines, $shopName, $shopUrl
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
    <h2 style="color:#333;margin-top:0;">Confirmation de commande</h2>
    <p>Bonjour <?php echo htmlspecialchars($customer->firstname); ?>,</p>
    <p>Merci pour votre commande ! Voici le récapitulatif :</p>

    <table style="width:100%;border-collapse:collapse;margin:20px 0;">
        <tr style="background:#f5f5f5;">
            <td style="padding:10px;font-weight:bold;">Référence</td>
            <td style="padding:10px;"><?php echo htmlspecialchars($order->ref); ?></td>
        </tr>
        <tr>
            <td style="padding:10px;font-weight:bold;">Date</td>
            <td style="padding:10px;"><?php echo date('d/m/Y H:i', strtotime($order->date_creation)); ?></td>
        </tr>
    </table>

    <!-- Order lines -->
    <table style="width:100%;border-collapse:collapse;margin:20px 0;">
        <tr style="background:#333;color:#fff;">
            <th style="padding:10px;text-align:left;">Produit</th>
            <th style="padding:10px;text-align:center;">Qté</th>
            <th style="padding:10px;text-align:right;">Prix</th>
        </tr>
        <?php if (!empty($lines)) { foreach ($lines as $line) { ?>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:10px;"><?php echo htmlspecialchars($line->description ?: $line->product_label); ?></td>
            <td style="padding:10px;text-align:center;"><?php echo $line->qty; ?></td>
            <td style="padding:10px;text-align:right;"><?php echo spacartFormatPrice($line->total_ttc); ?></td>
        </tr>
        <?php } } ?>
        <tr style="background:#f5f5f5;font-weight:bold;">
            <td colspan="2" style="padding:10px;text-align:right;">Total</td>
            <td style="padding:10px;text-align:right;"><?php echo spacartFormatPrice($order->total_ttc); ?></td>
        </tr>
    </table>

    <?php if (!empty($order->note_public)) { ?>
    <p><strong>Informations de livraison :</strong><br><?php echo nl2br(htmlspecialchars($order->note_public)); ?></p>
    <?php } ?>

    <p style="text-align:center;margin:30px 0;">
        <a href="<?php echo $shopUrl; ?>#/invoice/<?php echo $order->id; ?>" style="background:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;color:#fff;padding:12px 30px;text-decoration:none;border-radius:4px;display:inline-block;">Voir ma commande</a>
    </p>

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
    <p>Cordialement,<br><strong><?php echo htmlspecialchars($shopName); ?></strong></p>
</td></tr>

<!-- Footer -->
<tr><td style="background:#f5f5f5;padding:20px;text-align:center;font-size:12px;color:#999;">
    <p>&copy; <?php echo date('Y').' '.htmlspecialchars($shopName); ?>. Tous droits réservés.</p>
    <p><a href="<?php echo $shopUrl; ?>" style="color:#999;"><?php echo $shopUrl; ?></a></p>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
