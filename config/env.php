<?php
/**
 * PropTXChange — Environment / Configuration
 *
 * Loaded by index.php after session ini settings have been applied
 * and session_start() has been called.
 *
 * This file ONLY defines constants — no ini_set() calls here.
 * Session security settings live in index.php where they run
 * before session_start(), which is the only valid time to set them.
 *
 * HOW TO CONFIGURE:
 *   Option A — Server environment variables (recommended for production):
 *     Set these in your hosting control panel or .htaccess:
 *       SetEnv DB_HOST     localhost
 *       SetEnv DB_NAME     rpm_db
 *       SetEnv DB_USER     your_user
 *       SetEnv DB_PASS     your_password
 *       SetEnv APP_ENV     production
 *
 *   Option B — Edit the fallback values below (local / dev only).
 *   !! Never commit real credentials to a public repository !!
 *
 * cPanel / shared hosting:
 *   DB_HOST is almost always 'localhost'
 *   DB_NAME, DB_USER, DB_PASS come from cPanel > MySQL Databases
 */

// Guard: prevent re-definition if this file is somehow included twice
if (defined('APP_BOOTSTRAPPED')) return;
define('APP_BOOTSTRAPPED', true);

// ── MySQL connection ──────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rpm_db');
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASSWORD');

// ── Admin registration invite code ───────────────────────────
// Anyone who knows this code can create an admin account.
// Change this to something private before going live!
define('ADMIN_INVITE_CODE', getenv('ADMIN_INVITE_CODE') ?: 'CHANGE_THIS_SECRET');

// ── App meta ──────────────────────────────────────────────────
define('APP_NAME',    'PropTXChange');
define('APP_VERSION', '2.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
