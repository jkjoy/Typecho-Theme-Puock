<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 全部标签按字母书序排列
 */
// 引入 Composer 自动加载
require __DIR__ . '/vendor/autoload.php';
use Overtrue\Pinyin\Pinyin;

function getFirstChar($str) {
    if (empty($str)) return '#';

    $pinyin = new Pinyin();
    $firstChar = mb_substr($str, 0, 1, 'UTF-8');

    // 数字
    if (is_numeric($firstChar)) {
        return '0';
    }

    // 英文字母
    if (preg_match('/^[a-zA-Z]$/', $firstChar)) {
        return strtoupper($firstChar);
    }

    // 中文转拼音首字母
    $abbr = $pinyin->abbr($firstChar, '');
    return strtoupper($abbr[0] ?? '#');
}

/**
 * 判断是否包含index.php
 */
function get_correct_url($path) {
    // 获取当前请求的URI
    $requestUri = $_SERVER['REQUEST_URI'];
    
    // 检查是否包含index.php
    $isIndexPhp = strpos($requestUri, '/index.php/') !== false;
    
    // 获取站点URL
    $siteUrl = Helper::options()->siteUrl;
    
    // 如果是/index.php/结构
    if ($isIndexPhp) {
        return $siteUrl . 'index.php' . $path;
    }
    
    return $siteUrl . ltrim($path, '/');
}

/**
 * 友好时间显示函数
 * @param $timestamp
 * @return string
 */
function friendly_date($timestamp) {
    $time = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    $diff = time() - $time;
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . '月前';
    } else {
        return date('Y-m-d H:i:s', $time);
    }
}

/**
 * 关闭评论反垃圾保护
 * 评论层级突破999
 * 关闭检查评论来源URL与文章链接是否一致判断
 * 最新评论显示在前
 */
function themeInit($archive)
{
    // 站外链接跳转提醒
    // - /go/<token>
    // - /index.php/go/<token>
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $requestPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?: '');
    $sitePath = (string)(parse_url((string)Helper::options()->siteUrl, PHP_URL_PATH) ?: '');
    $sitePath = rtrim($sitePath, '/');
    if ($sitePath !== '' && str_starts_with($requestPath, $sitePath)) {
        $requestPath = substr($requestPath, strlen($sitePath));
        if ($requestPath === '') $requestPath = '/';
    }
    $requestPath = '/' . ltrim($requestPath, '/');

    if (preg_match('#^/(?:index\\.php/)?go/([A-Za-z0-9_-]+)(?:/)?$#', $requestPath, $m)) {
        $GLOBALS['puock_goto_active'] = true;
        $GLOBALS['puock_goto_title'] = '即将离开本站';
        puock_handle_goto_request($archive, $m[1]);
        exit;
    }

    if (isset($_GET['goto']) && $_GET['goto'] !== '') {
        $GLOBALS['puock_goto_active'] = true;
        $GLOBALS['puock_goto_title'] = '即将离开本站';
        puock_handle_goto_request($archive, null);
        exit;
    }

    Helper::options()->commentsAntiSpam = false; 
    Helper::options()->commentsMaxNestingLevels = 999;
    Helper::options()->commentsOrder = 'DESC';
    Helper::options()->commentsCheckReferer = false;

    // 站外链接跳转提醒（文章/页面/评论）
    if (class_exists('Typecho_Plugin')) {
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = 'puock_filter_external_links_to_go';
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = 'puock_filter_external_links_to_go';
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = 'puock_filter_external_links_to_go';
    }
}

function puock_go_base64url_encode($raw)
{
    $encoded = base64_encode((string)$raw);
    return rtrim(strtr($encoded, '+/', '-_'), '=');
}

function puock_go_base64url_decode($token)
{
    $token = (string)$token;
    $token = strtr($token, '-_', '+/');
    $padLen = strlen($token) % 4;
    if ($padLen) {
        $token .= str_repeat('=', 4 - $padLen);
    }
    $decoded = base64_decode($token, true);
    return $decoded === false ? '' : $decoded;
}

function puock_go_encode_url($url)
{
    return puock_go_base64url_encode((string)$url);
}

function puock_go_decode_url($token)
{
    return puock_go_base64url_decode((string)$token);
}

function puock_go_page_base_url()
{
    static $cached = null;
    if (is_string($cached)) {
        return $cached;
    }
    if (function_exists('get_correct_url')) {
        $cached = (string)get_correct_url('/go/');
        return $cached;
    }

    $siteUrl = (string)Helper::options()->siteUrl;
    $siteUrl = $siteUrl !== '' ? rtrim($siteUrl, '/') . '/' : '/';
    $cached = $siteUrl . 'go/';
    return $cached;
}

function puock_go_build_url($targetUrl)
{
    $base = puock_go_page_base_url();
    $token = puock_go_encode_url((string)$targetUrl);
    $base = rtrim((string)$base, '/') . '/';
    return $base . rawurlencode($token);
}

function puock_normalize_host($host)
{
    $host = strtolower(trim((string)$host));
    $host = preg_replace('/^www\\./i', '', $host);
    return $host;
}

function puock_is_external_url($url)
{
    $url = trim((string)$url);
    if ($url === '') return false;

    if (preg_match('#^(mailto|tel|javascript|data):#i', $url)) return false;
    if (str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, '?') || str_starts_with($url, './') || str_starts_with($url, '../')) {
        return false;
    }

    if (str_starts_with($url, '//')) {
        $url = 'https:' . $url;
    }

    $parts = @parse_url($url);
    if (!is_array($parts)) return false;

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if (!in_array($scheme, ['http', 'https'], true)) return false;

    $host = (string)($parts['host'] ?? '');
    if ($host === '') return false;

    $siteHost = parse_url((string)Helper::options()->siteUrl, PHP_URL_HOST) ?: '';
    if ($siteHost === '') return true;

    $hostNorm = puock_normalize_host($host);
    $siteHostNorm = puock_normalize_host($siteHost);

    if ($hostNorm === $siteHostNorm) return false;
    if ($siteHostNorm !== '' && str_ends_with($hostNorm, '.' . $siteHostNorm)) return false;
    if ($hostNorm !== '' && str_ends_with($siteHostNorm, '.' . $hostNorm)) return false;

    return true;
}

function puock_rewrite_external_links_to_go($html)
{
    if (!is_string($html) || $html === '') return $html;

    return preg_replace_callback(
        '~<a\\b([^>]*?\\bhref\\s*=\\s*)(?:(["\'])(.*?)\\2|([^\\s"\'>]+))([^>]*)>~i',
        function ($m) {
            $quote = (string)($m[2] ?? '');
            $hrefRaw = (string)($m[3] !== '' ? $m[3] : ($m[4] ?? ''));
            $href = trim(html_entity_decode($hrefRaw, ENT_QUOTES, 'UTF-8'));
            if (!puock_is_external_url($href)) {
                return $m[0];
            }

            $goHref = puock_go_build_url($href);
            $goHref = htmlspecialchars($goHref, ENT_QUOTES, 'UTF-8');
            $quote = ($quote === '"' || $quote === "'") ? $quote : '"';

            $attrsTail = (string)$m[5];
            if (preg_match('/\\btarget\\s*=\\s*(["\'])(.*?)\\1/i', $attrsTail)) {
                $attrsTail = preg_replace('/\\btarget\\s*=\\s*(["\'])(.*?)\\1/i', 'target="_blank"', $attrsTail, 1);
            } else {
                $attrsTail .= ' target="_blank"';
            }

            // Ensure rel contains safe tokens
            if (preg_match('/\\brel\\s*=\\s*(["\'])(.*?)\\1/i', $attrsTail, $rm)) {
                $relQuote = $rm[1];
                $relVal = trim((string)$rm[2]);
                $tokens = preg_split('/\\s+/', $relVal, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $need = ['noopener', 'noreferrer', 'nofollow'];
                foreach ($need as $t) {
                    if (!in_array($t, $tokens, true)) $tokens[] = $t;
                }
                $newRel = implode(' ', $tokens);
                $attrsTail = preg_replace('/\\brel\\s*=\\s*(["\'])(.*?)\\1/i', 'rel=' . $relQuote . $newRel . $relQuote, $attrsTail, 1);
            } else {
                $attrsTail .= ' rel="noopener noreferrer nofollow"';
            }

            return '<a' . $m[1] . $quote . $goHref . $quote . $attrsTail . '>';
        },
        $html
    );
}

function puock_filter_external_links_to_go($content, $widget, $lastResult)
{
    $content = empty($lastResult) ? $content : $lastResult;
    return puock_rewrite_external_links_to_go($content);
}

function puock_handle_goto_request($archive, $tokenFromPath = null)
{
    $options = Helper::options();
    $GLOBALS['puock_goto_active'] = true;
    $GLOBALS['puock_goto_title'] = '即将离开本站';
    // themeInit 阶段 $archive->options 可能尚未注入，导致 header.php 内 $this->options 为空
    try {
        if (is_object($archive) && (!isset($archive->options) || $archive->options === null)) {
            $archive->options = $options;
        }
    } catch (Throwable $e) {
    }

    $rawToken = $tokenFromPath !== null ? (string)$tokenFromPath : (isset($_GET['goto']) ? (string)$_GET['goto'] : '');
    $rawUrl = isset($_GET['url']) ? (string)$_GET['url'] : '';

    $targetUrl = '';
    if ($rawToken !== '') {
        $targetUrl = (string)puock_go_decode_url($rawToken);
    } elseif ($rawUrl !== '') {
        $targetUrl = trim(html_entity_decode($rawUrl, ENT_QUOTES, 'UTF-8'));
        $targetUrl = rawurldecode($targetUrl);
    }

    $targetUrl = trim((string)$targetUrl);
    $targetUrl = preg_replace('/[\\x00-\\x1F\\x7F]+/u', '', $targetUrl);

    $isValid = false;
    $targetHost = '';
    if ($targetUrl !== '') {
        if (str_starts_with($targetUrl, '//')) {
            $scheme = parse_url((string)Helper::options()->siteUrl, PHP_URL_SCHEME) ?: 'https';
            $targetUrl = $scheme . ':' . $targetUrl;
        }

        $parts = @parse_url($targetUrl);
        $scheme = is_array($parts) ? strtolower((string)($parts['scheme'] ?? '')) : '';
        $targetHost = is_array($parts) ? (string)($parts['host'] ?? '') : '';

        if (in_array($scheme, ['http', 'https'], true) && filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            $isValid = true;
        }
    }

    $siteHost = parse_url((string)$options->siteUrl, PHP_URL_HOST) ?: '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $backUrl = (string)$options->siteUrl;
    if ($referer !== '') {
        $refererHost = parse_url((string)$referer, PHP_URL_HOST) ?: '';
        if ($refererHost !== '' && $siteHost !== '' && strtolower($refererHost) === strtolower($siteHost)) {
            $backUrl = $referer;
        }
    }

    $archive->need('header.php');
    ?>
    <div id="breadcrumb" class="animated fadeInUp">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a class="a-link" href="<?php $options->siteUrl(); ?>">首页</a></li>
                <li class="breadcrumb-item active" aria-current="page">链接跳转</li>
            </ol>
        </nav>
    </div>

    <div id="page-empty">
        <div id="page" class="row row-cols-1">
            <div id="post-main" class="col-lg-12 col-md-12">
                <div class="p-block">
                    <h1 id="post-title" class="mb-0 puock-text t-xxl">即将离开本站</h1>
                    <div class="mt20 puock-text entry-content">
                        <?php if (!$isValid): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-circle"></i>&nbsp;链接无效或缺少参数
                            </div>
                            <p class="mt10">
                                <a class="btn btn-primary btn-ssm" href="<?php $options->siteUrl(); ?>">返回首页</a>
                            </p>
                        <?php else: ?>
                            <div class="alert alert-primary alert-outline">
                                <span class="c-sub fs14">
                                    <i class="fa fa-circle-exclamation me-1"></i>
                                    你将前往站外地址，请注意账号与财产安全。
                                </span>
                            </div>
                            <p class="t-sm c-sub mt10">目标站点：<?php echo htmlspecialchars($targetHost ?: $targetUrl, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="t-sm c-sub mt10" style="word-break: break-all;">完整地址：<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="mt20">
                                <a class="btn btn-primary btn-ssm"
                                   href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                   rel="noopener noreferrer nofollow">继续访问</a>
                                <a class="btn btn-outline-secondary btn-ssm ml-1"
                                   href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                   rel="nofollow">返回上一页</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $archive->need('footer.php');
}