<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

// Trusted Types (DOM XSS sinks) - keep CSP policy minimal to avoid breaking existing resource loading.
if (!defined('__TYPECHO_ADMIN__') && !headers_sent()) {
    header("Content-Security-Policy: trusted-types default; require-trusted-types-for 'script'", false);
}

// 加载核心模块
require_once __DIR__ . '/inc/core.php';
require_once __DIR__ . '/inc/setttings.php';
require_once __DIR__ . '/inc/shortcodes.php';
require_once __DIR__ . '/inc/editor.php';
require_once __DIR__ . '/inc/comments.php';
require_once __DIR__ . '/inc/widgets.php';
require_once __DIR__ . '/inc/ajax.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/video.php';
require_once __DIR__ . '/inc/check-version.php';
require_once __DIR__ . '/inc/backup.php';