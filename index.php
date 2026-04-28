<?php
/**
 * PropTXChange — Front Controller
 * Single entry point. Loads everything in the correct order.
 */

// 1. Session ini settings MUST come before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// 2. Start session
session_start();

// 3. Config (defines DB_* constants and APP_ENV)
require_once __DIR__ . '/config/config.php';

// 4. Core classes
require_once __DIR__ . '/core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/flash.php';

// 5. Route
$page = $_GET['page'] ?? 'home';
$allowed = ['home', 'login', 'register', 'admin', 'tenant', 'logout'];
if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

// 6. Logout handled here so it works from any page
if ($page === 'logout') {
    AppAuth::logout();
    flash('info', 'You have been signed out.');
    redirect('login');
}

// 7. Render
require __DIR__ . '/core/header.php';
require __DIR__ . '/pages/' . $page . '.php';
require __DIR__ . '/core/footer.php';
