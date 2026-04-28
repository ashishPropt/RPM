<?php
/**
 * PropTXChange — Environment / Configuration
 *
 * Loaded ONCE by index.php before anything else.
 * Defines all constants used by db.php, auth.php, and pages.
 *
 * HOW TO CONFIGURE:
 *   Option A — Server environment variables (recommended for production):
 *     export DB_HOST=localhost
 *     export DB_NAME=rpm_db
 *     export DB_USER=your_user
 *     export DB_PASS=your_password
 *
 *   Option B — Edit the fallback values below (local/dev only).
 *   !! Never commit real credentials to a public repository !!
 *
 * cPanel / shared hosting:
 *   DB_HOST is almost always 'localhost'
 *   DB_NAME, DB_USER, DB_PASS come from cPanel > MySQL Databases
 */

// Guard: only define once (safe to include from multiple places)
if (defined('APP_BOOTSTRAPPED')) return;
define('APP_BOOTSTRAPPED', true);

// ── MySQL connection ──────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rpm_db');
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD');

// ── Admin invite code ─────────────────────────────────────────
// Required to create an admin account via the website.
// Change this to something private before going live!
define('ADMIN_INVITE_CODE', getenv('ADMIN_INVITE_CODE') ?: 'CHANGE_THIS_SECRET');

// ── App meta ──────────────────────────────────────────────────
define('APP_NAME',    'PropTXChange');
define('APP_VERSION', '2.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');

// ── Session security settings ────────────────────────────────
// These must be set before session_start() is called.
// index.php calls session_start() BEFORE including this file, so
// these take effect on the NEXT request — which is fine.
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Lax');
}
