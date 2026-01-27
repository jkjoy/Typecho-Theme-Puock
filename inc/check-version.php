<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 自动检查主题更新
 */
function get_theme_version()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $indexFile = dirname(__DIR__) . '/index.php';
    if (!is_file($indexFile)) {
        $cached = '';
        return $cached;
    }

    // 只读取开头部分即可（版本信息一般在文件头注释里）
    $head = @file_get_contents($indexFile, false, null, 0, 4096);
    if ($head === false) {
        $cached = '';
        return $cached;
    }

    if (preg_match('/@version\\s+([^\\s\\*]+)/i', $head, $m)) {
        $cached = trim($m[1]);
        return $cached;
    }

    $cached = '';
    return $cached;
}

function themeAutoUpgradeNotice()
{
    // 1. 当前主题版本（从 index.php 文件头注释读取）
    $current_version = get_theme_version();
    if (!$current_version) {
        $current_version = '0.0.0';
    }

    // 2. 定义 GitHub API 地址
    $api_url = 'https://api.github.com/repos/jkjoy/typecho-theme-puock/releases/latest';

    // 3. 设置缓存，避免每次请求都调用 API，减轻服务器压力
    $cache_dir = __TYPECHO_ROOT_DIR__ . '/usr/cache';
    $cache_file = $cache_dir . '/theme_version.json';
    $cache_time = 12 * 3600; // 缓存12小时

    // 确保缓存目录存在
    if (!file_exists($cache_dir)) {
        @mkdir($cache_dir, 0755, true);
    }

    $latest_version = null;
    
    // 检查缓存文件是否存在且未过期
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if ($cache_data && isset($cache_data['tag_name'])) {
            $latest_version = $cache_data['tag_name'];
        }
    } else {
        // 缓存过期或不存在，重新请求 API
        $ctx = stream_context_create([
            'http' => [
                'header' => 'User-Agent: Typecho-Theme-Updater', // GitHub API 要求有 User-Agent
                'timeout' => 10 // 设置超时时间
            ]
        ]);
        
        $response = @file_get_contents($api_url, false, $ctx);

        if ($response) {
            $release_data = json_decode($response, true);
            if (isset($release_data['tag_name'])) {
                $latest_version = $release_data['tag_name'];
                // 更新缓存文件
                $result = file_put_contents($cache_file, json_encode(['tag_name' => $latest_version, 'time' => time()]));
                // 如果缓存写入失败，记录错误但不影响显示
                if (!$result) {
                    error_log('Failed to write upgrade cache to ' . $cache_file);
                }
            }
        } else {
            // API请求失败，记录错误
            error_log('Failed to fetch release data from ' . $api_url);
            // 如果有旧缓存，使用旧缓存数据
            if (file_exists($cache_file)) {
                $cache_data = json_decode(file_get_contents($cache_file), true);
                if ($cache_data && isset($cache_data['tag_name'])) {
                    $latest_version = $cache_data['tag_name'];
                }
            }
        }
    }
    // 4. 如果获取到了最新版本，则进行比较
    $current_compare = ltrim((string)$current_version, "vV");
    $latest_compare = $latest_version ? ltrim((string)$latest_version, "vV") : null;
    if ($latest_compare && version_compare($current_compare, $latest_compare, '<')) {
        
        $notice_html = '
        <span class="themeConfig"><h3>主题更新</h3>
            <div class="info">发现新版本 ' . $latest_version . '，您当前使用的是 ' . $current_version . '。建议立即更新以获得最新功能和安全性修复。
                <a href="https://github.com/jkjoy/typecho-theme-puock/releases/latest" target="_blank">查看更新</a>
                <a href="https://github.com/jkjoy/typecho-theme-puock/releases" target="_blank">立即下载</a>
            </div>';
        echo $notice_html;
    }
}