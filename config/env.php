<?php
/**
 * PropTXChange — Environment Configuration
 *
 * HOW TO SET YOUR CREDENTIALS:
 *   Option A (recommended): Set real server environment variables,
 *   then this file reads them automatically.
 *
 *   Option B (local dev only): Replace the placeholder strings below.
 *   !! Never commit real keys to a public repo !!
 *
 * Supabase credentials are found in:
 *   Supabase Dashboard > Project Settings > API
 */

// Option A: read from server environment (recommended for production)
$_supabase_url = getenv('SUPABASE_URL');
$_supabase_key = getenv('SUPABASE_ANON_KEY');

// Option B: fallback to hardcoded values (local dev only)
if (!$_supabase_url) {
    $_supabase_url = 'https://YOUR_PROJECT_ID.supabase.co';
}
if (!$_supabase_key) {
    $_supabase_key = 'YOUR_SUPABASE_ANON_KEY';
}

define('SUPABASE_URL',      $_supabase_url);
define('SUPABASE_ANON_KEY', $_supabase_key);

// App-level config
define('APP_NAME',    'PropTXChange');
define('APP_VERSION', '2.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
