<?php
/**
 * SpaCart Email Template - Password Reset
 * Variables: $customer, $resetLink, $shopName, $shopUrl
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
    <h2 style="color:#333;margin-top:0;">Réinitialisation de votre mot de passe</h2>
    <p>Bonjour <?php echo htmlspecialchars($customer->firstname); ?>,</p>
    <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>

    <p style="text-align:center;margin:30px 0;">
        <a href="<?php echo htmlspecialchars($resetLink); ?>" style="background:<?php echo getDolGlobalString('SPACART_PRIMARY_COLOR', '#1a237e'); ?>;color:#fff;padding:14px 35px;text-decoration:none;border-radius:4px;display:inline-block;font-size:16px;">Réinitialiser mon mot de passe</a>
    </p>

    <p style="color:#666;font-size:13px;">Ce lien est valable pendant 1 heure. Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>

    <p style="color:#999;font-size:12px;word-break:break-all;">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br><?php echo htmlspecialchars($resetLink); ?></p>

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
