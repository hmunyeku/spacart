<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="spacart-page-title">{$page_title}</title>
    <meta name="description" content="{$config['company_slogan']}">

    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <!-- SpaCart CSS -->
    <link rel="stylesheet" href="<?php echo SPACART_URL; ?>/css/spacart.css">

    <!-- Dynamic theme colors -->
    <style>
        :root {
            --spacart-primary: {$config['theme_color']};
            --spacart-primary-dark: {$config['theme_color_2']};
        }
        .btn, .btn-large, nav, .sidenav .collapsible-header:hover,
        .collection .collection-item.active, .pagination li.active {
            background-color: var(--spacart-primary) !important;
        }
        a { color: var(--spacart-primary); }
        a:hover { color: var(--spacart-primary-dark); }
        nav .brand-logo { color: #fff; }
    </style>

    <!-- Config for JS -->
    <script>
        window.SpaCartConfig = {
            baseUrl: '<?php echo SPACART_PUBLIC_URL; ?>',
            apiUrl: '<?php echo SPACART_API_URL; ?>',
            assetsUrl: '<?php echo SPACART_URL; ?>',
            currency: '<?php echo $config["currency"]; ?>',
            currencySymbol: '<?php echo $config["currency_symbol"]; ?>',
            stripeKey: '<?php echo $config["stripe_pk"]; ?>',
            sessionToken: '<?php echo $session_token; ?>',
            isLoggedIn: <?php echo $is_logged_in ? 'true' : 'false'; ?>,
            cartCount: <?php echo (int) $cart['count']; ?>,
            cartTotal: <?php echo (float) $cart['total']; ?>
        };
    </script>
</head>
<body>
    <!-- SPA Loading overlay -->
    <div id="spacart-loader" class="spacart-loader" style="display:none;">
        <div class="preloader-wrapper active">
            <div class="spinner-layer" style="border-color: var(--spacart-primary);">
                <div class="circle-clipper left"><div class="circle"></div></div>
                <div class="gap-patch"><div class="circle"></div></div>
                <div class="circle-clipper right"><div class="circle"></div></div>
            </div>
        </div>
    </div>

    {include="head.php"}

    <!-- Main SPA Content Container -->
    <main id="spacart-main" class="spacart-main">
        <div class="container">
            <!-- Breadcrumbs -->
            <div id="spacart-breadcrumbs" class="spacart-breadcrumbs"></div>

            <!-- Page Content (loaded via AJAX for SPA) -->
            <div id="spacart-content" class="spacart-content">
                {$initial_content|raw}
            </div>
        </div>
    </main>

    {include="foot.php"}

    <!-- Quick View Modal -->
    <div id="spacart-quickview-modal" class="modal modal-fixed-footer">
        <div class="modal-content" id="spacart-quickview-content"></div>
        <div class="modal-footer">
            <a href="#!" class="modal-close btn-flat">Fermer</a>
        </div>
    </div>

    <!-- Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <!-- SpaCart SPA Engine -->
    <script src="<?php echo SPACART_URL; ?>/js/spacart.js"></script>

    {if !empty($config['tawkto_id'])}
    <!-- Tawk.to Live Chat -->
    <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        (function(){var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;s1.src='https://embed.tawk.to/{$config["tawkto_id"]}';s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');s0.parentNode.insertBefore(s1,s0);})();
    </script>
    {/if}

    {if !empty($config['analytics_id'])}
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={$config['analytics_id']}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{$config["analytics_id"]}');
    </script>
    {/if}
</body>
</html>
