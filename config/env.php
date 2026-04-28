<?php
/**
 * PropTXChange — Environment Configuration (MySQL)
 *
 * HOW TO SET YOUR CREDENTIALS:
 *   Option A (recommended for production):
 *     Set real server environment variables — this file reads them automatically.
 *
 *   Option B (local dev only):
 *     Edit the fallback values below directly.
 *     !! Never commit real credentials to a public repo !!
 *
 * For cPanel / shared hosting:
 *   DB_HOST is usually 'localhost'
 *   DB_NAME, DB_USER, DB_PASS come from cPanel > MySQL Databases
 */

// ── MySQL credentials ─────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rpm_db');
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD');

// ── Admin registration invite code ───────────────────────────
// Anyone who knows this code can register an admin account.
// Change this to something private before going live!
define('ADMIN_INVITE_CODE', getenv('ADMIN_INVITE_CODE') ?: 'CHANGE_THIS_SECRET');

// ── App config ────────────────────────────────────────────────
define('APP_NAME',    'PropTXChange');
define('APP_VERSION', '2.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');

// ── Session security ──────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', '1');
}
