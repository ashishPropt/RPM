<?php
/**
 * PropTXChange — Front Controller
 *
 * Correct bootstrap order:
 *   1. Session ini settings  — MUST come before session_start()
 *   2. session_start()
 *   3. Config constants (DB credentials, app meta)
 *   4. DB client
 *   5. Auth helper
 *   6. Flash messages
 *   7. Header / nav
 *   8. Page
 *   9. Footer
 */

// ── 1. Session security ini settings ─────────────────────────
// These MUST be set before session_start() — once the session
// is active, ini_set() on session.* settings throws a warning.
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
// cookie_secure and cookie_samesite are set below after APP_ENV is defined

// ── 2. Start the session ──────────────────────────────────────
session_start();

// ── 3. Load config (DB credentials, ADMIN_INVITE_CODE, APP_ENV) ──
require_once __DIR__ . '/config/env.php';

// Now that APP_ENV is defined, apply the production-only settings.
// These won't affect the current request's already-started session cookie,
// but they will be correct for every subsequent request — which is fine
// on shared hosting where you can't set php.ini globally.
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure',   '1');
    ini_set('session.cookie_samesite', 'Lax');
}

// ── 4. Database client ────────────────────────────────────────
require_once __DIR__ . '/includes/db.php';

// ── 5. Auth helper ────────────────────────────────────────────
require_once __DIR__ . '/includes/auth.php';

// ── 6. Flash messages ─────────────────────────────────────────
require_once __DIR__ . '/includes/flash.php';

// ── 7. Route ──────────────────────────────────────────────────
$page          = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'admin', 'tenant', 'features', 'login', 'register'];

if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}

// ── 8. Render ─────────────────────────────────────────────────
include __DIR__ . '/includes/header.php';

switch ($page) {
    case 'home':     include __DIR__ . '/pages/home.php';     break;
    case 'admin':    include __DIR__ . '/pages/admin.php';    break;
    case 'tenant':   include __DIR__ . '/pages/tenant.php';   break;
    case 'features': include __DIR__ . '/pages/features.php'; break;
    case 'login':    include __DIR__ . '/pages/login.php';    break;
    case 'register': include __DIR__ . '/pages/register.php'; break;
}

include __DIR__ . '/includes/footer.php';
