<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/login.php
 * \ingroup    spacart
 * \brief      SpaCart Admin - Login page (standalone, no sidebar/header)
 */

require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

// Already logged in? Redirect to dashboard
if (spacartAdminCheck()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!spacartAdminCheckCSRF()) {
        $error = 'Session expiree, veuillez reessayer';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);
        $email_value = $email;

        $result = spacartAdminLogin($email, $password, $remember);
        if (!empty($result['success'])) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'] ?? 'Identifiants incorrects';
        }
    }
}

$csrf_token = spacartAdminGetCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SpaCart Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">

<!-- Floating background shapes -->
<div class="bg-shape bg-shape-1"></div>
<div class="bg-shape bg-shape-2"></div>
<div class="bg-shape bg-shape-3"></div>
<div class="bg-shape bg-shape-4"></div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">
                <i class="bi bi-cart4"></i>
            </div>
            <h1>SpaCart</h1>
            <p>Panneau d'administration</p>
        </div>

        <?php if ($error): ?>
            <div class="login-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="on" id="loginForm">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="email">Adresse email</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($email_value); ?>"
                           placeholder="admin@exemple.com" required autofocus>
                    <i class="bi bi-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password"
                           placeholder="Votre mot de passe" required>
                    <i class="bi bi-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Afficher le mot de passe">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Se souvenir de moi</label>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">Se connecter</button>
        </form>
    </div>

    <div class="login-footer">
        Propulse par SpaCart &middot; CoexDis
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
function togglePassword() {
    var input = document.getElementById('password');
    var icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    btn.classList.add('btn-loading');
    btn.disabled = true;
});

// Dark mode support for login page
(function() {
    var stored = null;
    try { stored = localStorage.getItem('spacart_dark_mode'); } catch(e) {}
    if (stored === 'dark' || (!stored && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();
</script>
</body>
</html>
