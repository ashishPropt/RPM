<?php
/**
 * PropTXChange — Environment Configuration (MySQL)
 *
 * HOW TO SET YOUR CREDENTIALS:
 *   Option A (recommended for production):
 *     Set server environment variables and this file reads them.
 *
 *   Option B (local dev only):
 *     Edit the fallback values below.
 *     !! Never commit real credentials to a public repo !!
 *
 * For cPanel / shared hosting:
 *   DB_HOST is usually 'localhost'
 *   DB_NAME, DB_USER, DB_PASS are set in cPanel > MySQL Databases
 */

// ── MySQL credentials ─────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rpm_db');
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD');

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
