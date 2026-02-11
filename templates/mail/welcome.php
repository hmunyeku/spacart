<?php
/**
 * SpaCart Email Template - Welcome / Registration
 * Variables: $customer, $shopName, $shopUrl
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
    <h2 style="color:#333;margin-top:0;">Bienvenue <?php echo htmlspecialchars($customer->firstname); ?> !</h2>
    <p>Votre compte a été créé avec succès sur <strong><?php echo htmlspecialchars($shopName); ?></strong>.</p>

    <p>Vous pouvez désormais :</p>
    <ul style="color:#555;line-height:1.8;">
        <li>Passer des commandes rapidement</li>
        <li>Suivre vos commandes en cours</li>
        <li>Gérer vos adresses de livraison</li>
        <li>Créer votre liste de souhaits</li>
        <li>Consulter votre historique d'achats</li>
    </ul>

    <p style="text-align:center;margin:30px 0;">
        <a href="<?php echo $shopUrl; ?>#/profile" style="background:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;color:#fff;padding:12px 30px;text-decoration:none;border-radius:4px;display:inline-block;">Accéder à mon compte</a>
    </p>

    <p style="text-align:center;margin:20px 0;">
        <a href="<?php echo $shopUrl; ?>" style="color:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;text-decoration:none;">Découvrir la boutique</a>
    </p>

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
