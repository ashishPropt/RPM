<?php
/**
 * PropTXChange — Configuration
 *
 * Fill in your MySQL credentials below.
 * For production, set these as server environment variables instead.
 */
if (defined('APP_LOADED')) return;
define('APP_LOADED', true);

// --- MySQL ---
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'rpm_db');          // <-- your database name
define('DB_USER', getenv('DB_USER') ?: 'YOUR_DB_USER');    // <-- your db username
define('DB_PASS', getenv('DB_PASS') ?: 'YOUR_DB_PASS');    // <-- your db password

// --- Admin registration code ---
// Anyone with this code can create an admin account.
// Change this before going live!
define('ADMIN_CODE', getenv('ADMIN_CODE') ?: 'CHANGE_ME_2025');

// --- App ---
define('APP_NAME', 'PropTXChange');
define('APP_ENV',  getenv('APP_ENV') ?: 'development');    // 'production' on live server
define('BASE_URL', getenv('BASE_URL') ?: 'index.php');     // used by redirect()
