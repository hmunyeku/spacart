<?php
/* Copyright (C) 2024-2026  CoexDis <contact@coexdis.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       backoffice/index.php
 * \ingroup    spacart
 * \brief      SpaCart Admin Backoffice - Main Entry Point & Router
 */

require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/functions.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    spacartAdminLogout();
    header('Location: login.php');
    exit;
}

// Require authentication (redirects to login.php if not logged in)
spacartAdminRequireAuth();
$admin_user = spacartAdminCurrentUser();

// Route to the requested page
$page = isset($_GET['page']) ? preg_replace('/[^a-z0-9_]/', '', $_GET['page']) : 'dashboard';

// Map of valid pages
$valid_pages = array(
    'dashboard', 'statistics', 'predictions',
    'orders', 'order_view', 'invoices',
    'products', 'product_edit', 'categories', 'brands', 'reviews',
    'customers', 'customer_view',
    'coupons', 'giftcards',
    'shipping', 'taxes', 'countries',
    'pages_cms', 'blog', 'news', 'testimonials', 'banners',
    'settings', 'theme', 'homepage', 'languages', 'currencies',
    'admin_users', 'subscribers'
);

if (!in_array($page, $valid_pages)) {
    $page = 'dashboard';
}

$page_file = __DIR__.'/pages/'.$page.'.php';
if (!file_exists($page_file)) {
    $page = 'dashboard';
    $page_file = __DIR__.'/pages/dashboard.php';
}

// Set current page for sidebar highlighting
$current_page = $page;

// Include the page (it will include header/footer itself)
require_once $page_file;
