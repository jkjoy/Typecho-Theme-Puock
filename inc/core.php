<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/** 头像镜像加速全局设置
 * @param $email
 * @param $size
 * @param $default
 * @return string
*/
$options = Typecho_Widget::widget('Widget_Options');
$gravatarPrefix = empty($options->cnavatar) ? 'https://cravatar.cn/avatar/' : $options->cnavatar;
define('__TYPECHO_GRAVATAR_PREFIX__', $gravatarPrefix);

/*
* 文章浏览数统计
*/
function get_post_view($archive) {
    $cid = $archive->cid;
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `views` INT(10) DEFAULT 0;');
        echo 0;
        return;
    }
    $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
    if ($archive->is('single')) {
        $views = Typecho_Cookie::get('extend_contents_views');
        if (empty($views)) {
            $views = array();
        } else {
            $views = explode(',', $views);
        }
        if (!in_array($cid, $views)) {
            $currentViews = isset($row['views']) ? (int)$row['views'] : 0;
            $db->query($db->update('table.contents')->rows(array('views' => $currentViews + 1))->where('cid = ?', $cid));
            array_push($views, $cid);
            $views = implode(',', $views);
            Typecho_Cookie::set('extend_contents_views', $views); //记录查看cookie
            
        }
    }
    echo $row['views'] ?? 0;
}

/*
* 点赞数统计
*/
// 点赞显示函数
function get_post_like($archive) {
    $cid = $archive->cid;
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    if (!array_key_exists('likes', $db->fetchRow($db->select()->from('table.contents')))) {
        $db->query('ALTER TABLE `' . $prefix . 'contents` ADD `likes` INT(10) DEFAULT 0;');
        echo 0;
        return;
    }
    $row = $db->fetchRow($db->select('likes')->from('table.contents')->where('cid = ?', $cid));
    echo $row['likes'] ?? 0;
}

/**
 * 随机封面
 */
function getPostCover($content, $cid, $fields = null) {
    // 优先使用自定义封面字段
    if ($fields && !empty($fields->cover)) {
        return $fields->cover;
    }
    // 从内容中提取第一张图片（兼容 src / data-src 等，兼容带 query / 不带后缀）
    $content = (string)$content;

    // HTML <img ...>
    if (preg_match('/<img\\b[^>]*>/i', $content, $mTag)) {
        $tag = $mTag[0];
        $candidates = ['data-src', 'data-original', 'data-lazy-src', 'data-srcset', 'src'];
        foreach ($candidates as $attr) {
            if (preg_match('/\\b' . preg_quote($attr, '/') . '\\s*=\\s*(["\\\'])(.*?)\\1/i', $tag, $m)) {
                $val = html_entity_decode(trim((string)$m[2]), ENT_QUOTES | ENT_HTML5);
                if ($val !== '' && !preg_match('#^(data|blob):#i', $val)) {
                    // srcset 取第一个
                    if ($attr === 'data-srcset') {
                        $parts = preg_split('/\\s*,\\s*/', $val);
                        $val = trim((string)($parts[0] ?? ''));
                        $val = preg_split('/\\s+/', $val)[0] ?? $val;
                        $val = trim((string)$val);
                    }
                    return $val;
                }
            }
            if (preg_match('/\\b' . preg_quote($attr, '/') . '\\s*=\\s*([^\\s>]+)/i', $tag, $m2)) {
                $val = trim((string)$m2[1], "\"' ");
                $val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5);
                if ($val !== '' && !preg_match('#^(data|blob):#i', $val)) {
                    return $val;
                }
            }
        }
    }

    // Markdown ![alt](url)
    if (preg_match('/!\\[[^\\]]*\\]\\(([^\\)\\s]+)(?:\\s+\\\"[^\\\"]*\\\")?\\)/', $content, $mMd)) {
        $val = html_entity_decode(trim((string)$mMd[1]), ENT_QUOTES | ENT_HTML5);
        if ($val !== '' && !preg_match('#^(data|blob):#i', $val)) {
            return $val;
        }
    }

    // 没有图片则用随机封面
    $coverNumber = ($cid % 8) + 1;
    return Helper::options()->themeUrl . '/assets/img/random/' . $coverNumber . '.jpg';
}

function getPostThumb($content, $cid, $fields = null, $w = 400, $h = 300, $zc = 1, $q = 85)
{
    $src = (string)getPostCover($content, $cid, $fields);
    $src = trim($src);
    if ($src === '') return $src;

    // Normalize to absolute URL for timthumb.
    if (!preg_match('#^(data|blob):#i', $src) && !preg_match('#^https?://#i', $src)) {
        $site = (string)Helper::options()->siteUrl;
        $site = $site !== '' ? rtrim($site, '/') . '/' : '';
        $scheme = (string)(parse_url($site, PHP_URL_SCHEME) ?: ($_SERVER['REQUEST_SCHEME'] ?? 'https'));
        if (str_starts_with($src, '//')) {
            $src = $scheme . ':' . $src;
        } elseif (str_starts_with($src, '/')) {
            $src = rtrim($site, '/') . $src;
        } elseif (preg_match('#^[a-z0-9.-]+\\.[a-z]{2,}(/|$)#i', $src)) {
            $src = $scheme . '://' . $src;
        } else {
            $src = rtrim($site, '/') . '/' . ltrim($src, '/');
        }
    }

    if (function_exists('puock_timthumb_build_url')) {
        // Don't use ?? here: Typecho options may expose URLs via magic getters and isset() can be false.
        $themeBase = trim((string)Helper::options()->themeUrl);
        $timthumb = rtrim($themeBase, '/') . '/timthumb.php';
        if (!preg_match('#^https?://#i', $timthumb)) {
            $site = rtrim((string)Helper::options()->siteUrl, '/');
            if ($site !== '' && str_starts_with($timthumb, '/')) {
                $timthumb = $site . $timthumb;
            } elseif ($site !== '') {
                $timthumb = $site . '/' . ltrim($timthumb, '/');
            }
        }
        return puock_timthumb_build_url($timthumb, $src, (int)$w, (int)$h, (int)$zc, (int)$q);
    }

    return $src;
}
