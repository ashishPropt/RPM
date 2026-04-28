<?php
/**
 * PropTXChange — Front Controller
 *
 * This is the ONLY file that bootstraps the application.
 * All core includes happen here in the correct order so that
 * every page has access to session, config, DB, auth, and flash.
 */

// 1. Start session first — must happen before ANY output
session_start();

// 2. Load config (defines DB_HOST, DB_NAME etc. and APP constants)
require_once __DIR__ . '/config/env.php';

// 3. Load database client (defines MySQLDB class + db() helper)
require_once __DIR__ . '/includes/db.php';

// 4. Load auth (depends on db.php already being loaded)
require_once __DIR__ . '/includes/auth.php';

// 5. Load flash messages helper
require_once __DIR__ . '/includes/flash.php';

// 6. Route the page
$page          = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'admin', 'tenant', 'features', 'login', 'register'];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}

// 7. Render header (nav reads session state via AppAuth static methods)
include __DIR__ . '/includes/header.php';

// 8. Render the requested page
switch ($page) {
    case 'home':     include __DIR__ . '/pages/home.php';     break;
    case 'admin':    include __DIR__ . '/pages/admin.php';    break;
    case 'tenant':   include __DIR__ . '/pages/tenant.php';   break;
    case 'features': include __DIR__ . '/pages/features.php'; break;
    case 'login':    include __DIR__ . '/pages/login.php';    break;
    case 'register': include __DIR__ . '/pages/register.php'; break;
}

// 9. Render footer
include __DIR__ . '/includes/footer.php';
