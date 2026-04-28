<?php
session_start();

require_once 'config/env.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$allowed_pages = ['home', 'admin', 'tenant', 'features', 'login', 'register'];

if (!in_array($page, $allowed_pages)) {
    $page = 'home';
}

include 'includes/header.php';

switch ($page) {
    case 'home':     include 'pages/home.php';     break;
    case 'admin':    include 'pages/admin.php';    break;
    case 'tenant':   include 'pages/tenant.php';   break;
    case 'features': include 'pages/features.php'; break;
    case 'login':    include 'pages/login.php';    break;
    case 'register': include 'pages/register.php'; break;
}

include 'includes/footer.php';
?>
