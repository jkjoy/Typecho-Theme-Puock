<?php
// Theme-level TimThumb config. Loaded automatically by timthumb.php.

require_once __DIR__ . '/inc/timthumb-util.php';

// Cache directory: keep inside theme to avoid open_basedir restrictions.
$pkTimthumbCacheDir = puock_timthumb_cache_dir();
if (!is_dir($pkTimthumbCacheDir)) {
    @mkdir($pkTimthumbCacheDir, 0755, true);
}
if (!defined('FILE_CACHE_DIRECTORY')) define('FILE_CACHE_DIRECTORY', $pkTimthumbCacheDir);

// External fetching is enabled, but remote URLs must be signed (see timthumb.php modifications).
if (!defined('ALLOW_EXTERNAL')) define('ALLOW_EXTERNAL', true);
if (!defined('ALLOW_ALL_EXTERNAL_SITES')) define('ALLOW_ALL_EXTERNAL_SITES', false);

// Keep the allowlist empty by default; signed requests bypass it.
if (!isset($ALLOWED_SITES) || !is_array($ALLOWED_SITES)) $ALLOWED_SITES = [];

if (!defined('PUOCK_TIMTHUMB_REQUIRE_SIG')) define('PUOCK_TIMTHUMB_REQUIRE_SIG', true);
if (!defined('PUOCK_TIMTHUMB_KEY')) define('PUOCK_TIMTHUMB_KEY', puock_timthumb_get_key());
