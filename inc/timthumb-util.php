<?php

function puock_timthumb_cache_dir(): string
{
    $themeDir = dirname(__DIR__);
    $dir = $themeDir . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'thumbnail';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir)) {
        return $dir;
    }

    $base = rtrim((string)sys_get_temp_dir(), '/\\');
    $fallback = $base . DIRECTORY_SEPARATOR . 'puock-timthumb-cache';
    if (!is_dir($fallback)) {
        @mkdir($fallback, 0755, true);
    }
    return $fallback;
}

function puock_timthumb_key_file_path(): string
{
    return puock_timthumb_cache_dir() . DIRECTORY_SEPARATOR . 'puock-timthumb-key.php';
}

function puock_timthumb_get_key(): string
{
    $file = puock_timthumb_key_file_path();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    if (is_file($file)) {
        $content = @file_get_contents($file);
        if (is_string($content) && $content !== '') {
            $content = preg_replace('/^<\\?php\\s+exit;\\s*\\?>\\s*/', '', $content);
            $content = trim((string)$content);
            if ($content !== '') {
                return $content;
            }
        }
    }

    try {
        $key = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $key = bin2hex((string)mt_rand() . (string)microtime(true));
        $key = substr($key, 0, 32);
    }

    @file_put_contents($file, "<?php exit; ?>\n" . $key, LOCK_EX);
    return $key;
}

function puock_timthumb_is_remote_url(string $url, string $siteUrl): bool
{
    $url = trim($url);
    if ($url === '' || preg_match('#^(data|blob):#i', $url)) return false;
    if (!preg_match('#^https?://#i', $url)) return false;

    $uHost = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
    $sHost = strtolower((string)(parse_url($siteUrl, PHP_URL_HOST) ?? ''));
    $uHost = preg_replace('/^www\\./i', '', $uHost);
    $sHost = preg_replace('/^www\\./i', '', $sHost);
    if ($uHost === '' || $sHost === '') return true;
    return $uHost !== $sHost;
}

function puock_timthumb_sign(string $src, int $w, int $h, int $zc = 1, int $q = 90, ?string $key = null): string
{
    $key = $key ?? puock_timthumb_get_key();
    $payload = $src . '|' . $w . '|' . $h . '|' . $zc . '|' . $q;
    return hash_hmac('sha256', $payload, $key);
}

function puock_timthumb_build_url(string $timthumbUrl, string $src, int $w, int $h, int $zc = 1, int $q = 90): string
{
    $timthumbUrl = trim($timthumbUrl);
    if ($timthumbUrl === '' || $src === '') return $src;

    $params = [
        'src' => $src,
        'w'   => $w,
        'h'   => $h,
        'zc'  => $zc,
        'q'   => $q,
    ];
    $params['sig'] = puock_timthumb_sign($params['src'], $params['w'], $params['h'], $params['zc'], $params['q']);

    $qs = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    return rtrim($timthumbUrl, '?') . (str_contains($timthumbUrl, '?') ? '&' : '?') . $qs;
}
