<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 解析表情短代码为图片
 * @param string $content
 * @return string
 */
function parse_smiley_shortcode($content) {
    $smileys = [
        ':?:' => 'doubt.png',
        ':razz:' => 'razz.png',
        ':sad:' => 'sad.png',
        ':evil:' => 'evil.png',
        ':naughty:' => 'naughty.png',
        ':!:' => 'scare.png',
        ':smile:' => 'smile.png',
        ':oops:' => 'oops.png',
        ':neutral:' => 'neutral.png',
        ':cry:' => 'cry.png',
        ':mrgreen:' => 'mrgreen.png',
        ':grin:' => 'grin.png',
        ':eek:' => 'eek.png',
        ':shock:' => 'shock.png',
        ':???:' => 'bz.png',
        ':cool:' => 'cool.png',
        ':lol:' => 'lol.png',
        ':mad:' => 'mad.png',
        ':twisted:' => 'twisted.png',
        ':roll:' => 'roll.png',
        ':wink:' => 'wink.png',
        ':idea:' => 'idea.png',
        ':despise:' => 'despise.png',
        ':celebrate:' => 'celebrate.png',
        ':watermelon:' => 'watermelon.png',
        ':xmas:' => 'xmas.png',
        ':warn:' => 'warn.png',
        ':rainbow:' => 'rainbow.png',
        ':loveyou:' => 'loveyou.png',
        ':love:' => 'love.png',
        ':beer:' => 'beer.png',
    ];
    $themeUrl = Helper::options()->themeUrl . '/assets/img/smiley/';
    foreach ($smileys as $code => $img) {
        $imgTag = '<img class="smiley-img" src="' . $themeUrl . $img . '" alt="' . $code . '" title="表情" style="width:16px;height:16px;vertical-align:middle;" />';
        $content = str_replace($code, $imgTag, $content);
    }
    return $content;
}

/**
 * 短代码实现
 */
function get_article_info($atts)
{
    $default_atts = array('id' => '');
    $atts = array_merge($default_atts, $atts);
    $db = Typecho_Db::get();
    if (!empty($atts['id'])) {
        $post = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $atts['id'])->limit(1));
    } else {
        return '请提供文章ID';
    }
    if (!$post) {
        return '未找到文章';
    }

    // 生成正确的文章链接
    $options = Helper::options();

    // 使用 Typecho 路由系统生成永久链接
    if (class_exists('\\Typecho\\Router')) {
        // Typecho 1.3.0 新版本
        $permalink = \Typecho\Router::url('post', $post, $options->index);
    } else {
        // 旧版本 Typecho
        $permalink = Typecho_Router::url('post', $post, $options->index);
    }

    $title = isset($post['title']) ? htmlspecialchars($post['title']) : '无标题';
    $summary = get_article_summary($post);

    $output = '<blockquote class="article-quote">';
    $output .= '<div class="t-lg t-line-1">';
    $output .= '<a class="a-link" title="' . $title . '" href="' . $permalink . '" target="_blank">' . $title . '</a>';
    $output .= '</div>';
    $output .= '<div class="t-md c-sub text-2line">' . htmlspecialchars($summary) . '</div>';
    $output .= '</blockquote>';

    return $output;
}

class ContentFilter  {
    /**
     * 从短代码内部提取 URL（兼容被 Markdown 自动转为 <a> 的情况）。
     *
     * @param string $raw
     * @return string
     */
    private static function extractUrl($raw)
    {
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        $raw = trim(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'));
        $stripped = trim(strip_tags($raw));

        // HTML 链接：<a href="...">...</a>
        if (preg_match('/<a\\s+[^>]*href=(["\'])(.*?)\\1/i', $raw, $m)) {
            $href = trim($m[2]);
            // Markdown 自动链接在遇到括号等字符时，可能会把后缀拆到 </a> 外，strip_tags 后能还原成完整 URL
            if ($stripped !== '' && preg_match('#^https?://\\S+$#i', $stripped)) {
                return $stripped;
            }
            return $href;
        }

        // Markdown 链接：[text](url)
        if (preg_match('/\\[[^\\]]*\\]\\(([^)\\s]+)(?:\\s+\"[^\"]*\")?\\)/', $raw, $m)) {
            return trim($m[1]);
        }

        if ($stripped === '') {
            return '';
        }
        return trim($stripped, " \t\n\r\0\x0B\"'");
    }

    /**
     * 临时保护（非 GitHub）短代码，避免在短代码内部误处理 URL（如 GitHub 卡片识别与替换）。
     *
     * @param string $content
     * @param array $protected 输出：占位符 => 原始内容
     * @return string
     */
    private static function protectNonGithubShortcodes($content, &$protected)
    {
        $protected = [];
        $prefix = '<!--PUOCK_SC_' . md5(uniqid('', true)) . '_';
        $suffix = '-->';

        $store = function ($raw) use (&$protected, $prefix, $suffix) {
            $key = $prefix . count($protected) . $suffix;
            $protected[$key] = $raw;
            return $key;
        };

        // 先保护成对短代码块：[tag ...]...[/tag]
        $blockPattern = '/\[(?!\/?github(?:=|\s|\]))([a-zA-Z][\\w-]*)(?:\\s+[^\\]]*)?\\][\\s\\S]*?\\[\\/\\1\\]/i';
        for ($i = 0; $i < 50; $i++) {
            $content = preg_replace_callback(
                $blockPattern,
                function ($matches) use ($store) {
                    return $store($matches[0]);
                },
                $content,
                -1,
                $count
            );
            if (empty($count)) {
                break;
            }
        }

        // 再保护剩余的短代码标签：[tag ...] 或 [/tag]
        $tagPattern = '/\[(?!\/?github(?:=|\s|\]))\\/?[a-zA-Z][\\w-]*(?:\\s+[^\\]]*)?\\]/i';
        $content = preg_replace_callback(
            $tagPattern,
            function ($matches) use ($store) {
                return $store($matches[0]);
            },
            $content
        );

        return $content;
    }

    /**
     * 恢复 protectNonGithubShortcodes 产生的占位符。
     *
     * @param string $content
     * @param array $protected
     * @return string
     */
    private static function restoreProtectedShortcodes($content, $protected)
    {
        if (empty($protected)) {
            return $content;
        }
        return str_replace(array_keys($protected), array_values($protected), $content);
    }

    /**
     * 过滤内容中的短代码
     *
     * @param string $content 内容
     * @param object $widget 小工具对象
     * @param array $lastResult 上一次结果
     * @return string 处理后的内容
     */
    public static function filterContent($content, $widget = null, $lastResult = null)
    {
        // Step 1: 保护代码块
        $codeBlocks = [];
        $content = preg_replace_callback(
            '/<pre><code[^>]*>[\s\S]*?<\/code><\/pre>/i', // 匹配已转换的代码块
            function ($matches) use (&$codeBlocks) {
                $placeholder = '<!--CODEBLOCK_' . count($codeBlocks) . '-->';
                $codeBlocks[$placeholder] = $matches[0];
                return $placeholder;
            },
            $content
        );
        // 同时保护原始的markdown代码块
        $content = preg_replace_callback(
            '/```[\s\S]*?```/m', // 匹配 Markdown 代码块（包括多行）
            function ($matches) use (&$codeBlocks) {
                $placeholder = '<!--CODEBLOCK_' . count($codeBlocks) . '-->';
                $codeBlocks[$placeholder] = $matches[0]; // 存储原始代码块内容
                return $placeholder;
            },
            $content
        );

        // Step 2: 执行现有的短代码处理
        // GitHub 短代码替换
        $content = preg_replace_callback('/\[github=([\w\-\.]+\/[\w\-\.]+)\]/i', function($matches) {
            $repo = htmlspecialchars($matches[1]);
            return '<div class="github-card text-center" data-repo="' . $repo . '"><div class="spinner-grow text-primary"></div></div>';
        }, $content);

        // GitHub 直链转换需排除其它短代码内部的 URL，避免短代码冲突（例如“新窗口打开链接”等短代码中包含 GitHub URL）
        $protectedShortcodes = [];
        $githubScanContent = self::protectNonGithubShortcodes($content, $protectedShortcodes);
        $githubScanContent = preg_replace_callback(
            '#https://github\.com/([\w\-\.]+/[\w\-\.]+)(?=[\s\.,;:!\?\)\]\}\"\'\n]|$)#i',
            function($matches) {
                $repo = htmlspecialchars($matches[1]);
                return '<div class="github-card text-center" data-repo="' . $repo . '"><div class="spinner-grow text-primary"></div></div>';
            },
            $githubScanContent
        );
        $content = self::restoreProtectedShortcodes($githubScanContent, $protectedShortcodes);

        // alert 类短代码
        $alertShortcodes = [
            'success' => 'success',
            'primary' => 'primary',
            'danger'  => 'danger',
            'warning' => 'warning',
            'info'    => 'info',
            'dark'    => 'dark',
        ];
        foreach ($alertShortcodes as $shortcode => $class) {
            // 先处理被 Markdown 包裹在 <p> 标签中的短代码
            $content = preg_replace(
                '/<p>\s*\[' . $shortcode . '\](.*?)\[\/' . $shortcode . '\]\s*<\/p>/is',
                '<div class="alert alert-' . $class . '">$1</div>',
                $content
            );
            // 再处理普通的短代码（向后兼容）
            $content = preg_replace(
                '/\[' . $shortcode . '\](.*?)\[\/' . $shortcode . '\]/is',
                '<div class="alert alert-' . $class . '">$1</div>',
                $content
            );
        }

        // article 短代码
        $content = preg_replace_callback('/\[article\s+([^\]]+)\]/', function($matches) {
            $atts = self::parse_atts($matches[1]);
            return get_article_info($atts);
        }, $content);

        // 懒加载图片替换
        $themeUrl = Helper::options()->themeUrl;
        $loadSvg = $themeUrl . '/assets/img/load.svg';
        $title = isset($widget->title) ? htmlspecialchars($widget->title) : '';
        $content = preg_replace_callback(
            '/<img\s+[^>]*src=["\"]([^"\"]+\.(?:jpg|jpeg|png|webp))["\"][^>]*>/i',
            function($matches) use ($title, $loadSvg) {
                $imgUrl = $matches[1];
                return '<img title="' . $title . '" alt="' . $title . '" decoding="async" data-src="' . $imgUrl . '" data-lazy="true" src="' . $loadSvg . '" />';
            },
            $content
        );

        // collapse 折叠面板短代码
        static $collapseIndex = 0;
        $content = preg_replace_callback(
            '/\[collapse\s+title=(?:\'([^\']*)\'|\"([^\"]*)\")\](.*?)\[\/collapse\]/is',
            function($matches) use (&$collapseIndex) {
                $title = $matches[1] !== '' ? $matches[1] : $matches[2];
                $body = $matches[3];
                $collapseIndex++;
                $uniqid = 'collapse-' . mt_rand(100,999) . '-' . $collapseIndex;
                return '<div class="pk-sc-collapse"><a class="btn btn-primary btn-sm" data-bs-toggle="collapse" href="#' . $uniqid . '" role="button" aria-expanded="false" aria-controls="' . $uniqid . '"><i class="fa fa-angle-up"></i>&nbsp;' . htmlspecialchars($title) . '</a></div><div class="collapse" id="' . $uniqid . '">' . $body . '</div>';
            },
            $content
        );

        // download 短代码
        $content = preg_replace_callback(
            '/\[download\s+file=(?:\'([^\']*)\'|\"([^\"]*)\")\s+size=(?:\'([^\']*)\'|\"([^\"]*)\")\](.*?)\[\/download\]/is',
            function($matches) {
                $file = $matches[1] !== '' ? $matches[1] : $matches[2];
                $size = $matches[3] !== '' ? $matches[3] : $matches[4];
                $url = self::extractUrl($matches[5]);
                return '<div class="p-block p-down-box">'
                    . "<div class='mb15'><i class='fa fa-file-zipper'></i>&nbsp;<span> 文件名称：" . htmlspecialchars($file) . "</span></div>"
                    . "<div class='mb15'><i class='fa fa-download'></i>&nbsp;<span> 文件大小：" . htmlspecialchars($size) . "</span></div>"
                    . "<div class='mb15'><i class='fa-regular fa-bell'></i>&nbsp;<span> 下载声明：本站部分资源来自于网络收集，若侵犯了你的隐私或版权，请及时联系我们删除有关信息。</span></div>"
                    . "<div><i class='fa fa-link'></i><span> 下载地址：<a href='" . htmlspecialchars($url) . "' target='_blank'>点此下载</a> </span></div></p></div>";
            },
            $content
        );

        // reply 短代码
        $content = preg_replace_callback(
            '/\[reply\](.*?)\[\/reply\]/is',
            function($matches) use ($widget) {
                $show = false;
                // 兼容 Typecho 1.3.0 的 Widget 类检查
                $isArchive = (class_exists('Widget_Archive') && $widget instanceof Widget_Archive) || 
                           (class_exists('\Typecho\Widget\Archive') && $widget instanceof \Typecho\Widget\Archive);
                
                if ($isArchive && method_exists($widget, 'is') && $widget->is('single')) {
                    $user = \Typecho\Widget::widget('Widget_User');
                    $db = Typecho_Db::get();
                    if (isset($user->hasLogin) && $user->hasLogin) {
                        $hasComment = $db->fetchRow($db->select()->from('table.comments')
                            ->where('cid = ?', isset($widget->cid) ? $widget->cid : 0)
                            ->where('mail = ?', isset($user->mail) ? $user->mail : '')
                            ->where('status = ?', 'approved')
                        );
                        if ($hasComment) $show = true;
                    } else {
                        $ip = '';
                        if (method_exists($widget, 'request') && $widget->request) {
                            $ip = method_exists($widget->request, 'getIp') ? $widget->request->getIp() : '';
                        }
                        $hasComment = $db->fetchRow($db->select()->from('table.comments')
                            ->where('cid = ?', isset($widget->cid) ? $widget->cid : 0)
                            ->where('status = ?', 'approved')
                            ->where('ip = ?', $ip)
                        );
                        if ($hasComment) $show = true;
                    }
                }
                if ($show) {
                    return '<div class="reply-visible">' . $matches[1] . '</div>';
                } else {
                    return "<div class='alert alert-primary alert-outline'><span class='c-sub fs14'><i class='fa-regular fa-eye'></i>&nbsp; 此处含有隐藏内容，请提交评论并审核通过刷新后即可查看！</span></div>";
                }
            },
            $content
        );

        // Step 3: 恢复代码块
        foreach ($codeBlocks as $placeholder => $code) {
            $content = str_replace($placeholder, $code, $content);
        }

        // Step 4: 进行 Markdown 解析
        if (empty($lastResult)) {
            // 检查 widget 是否有效并且有 markdown 方法
            if ($widget && method_exists($widget, 'markdown')) {
                $content = $widget->markdown($content);
            } else {
                // 当未提供 widget 时，使用 Typecho 内置的 Markdown 转换器
                if (class_exists('\\Utils\\Markdown')) {
                    $content = \Utils\Markdown::convert($content);
                }
            }
        } else {
            $content = $lastResult;
        }

        return $content;
    }

    // 解析短代码属性（保持不变）
    private static function parse_atts($text) {
        $atts = array();
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) && strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
        }
        return $atts;
    }

    // 仅执行短代码替换，不进行 Markdown 转换或图片懒加载
    public static function applyShortcodesOnly($content, $widget = null)
    {
        $codeBlocks = [];
        $content = preg_replace_callback(
            '/<pre><code[^>]*>[\s\S]*?<\/code><\/pre>/i', // 匹配已转换的代码块
            function ($matches) use (&$codeBlocks) {
                $placeholder = '<!--CODEBLOCK_' . count($codeBlocks) . '-->';
                $codeBlocks[$placeholder] = $matches[0];
                return $placeholder;
            },
            $content
        );
        // 同时保护原始的markdown代码块
        $content = preg_replace_callback(
            '/```[\s\S]*?```/m', // 匹配 Markdown 代码块（包括多行）
            function ($matches) use (&$codeBlocks) {
                $placeholder = '<!--CODEBLOCK_' . count($codeBlocks) . '-->';
                $codeBlocks[$placeholder] = $matches[0]; // 存储原始代码块内容
                return $placeholder;
            },
            $content
        );

        // GitHub 短代码与直链
        $content = preg_replace_callback('/\[github=([\w\-\.]+\/[\w\-\.]+)\]/i', function($matches) {
            $repo = htmlspecialchars($matches[1]);
            return '<div class="github-card text-center" data-repo="' . $repo . '"><div class="spinner-grow text-primary"></div></div>';
        }, $content);

        // 匹配已被 Markdown 转换为 <a> 标签的 GitHub 链接
        // 需排除其它短代码内部的链接（短代码中包含的 URL 不应被 GitHub 卡片替换）
        $protectedShortcodes = [];
        $githubScanContent = self::protectNonGithubShortcodes($content, $protectedShortcodes);
        $githubScanContent = preg_replace_callback(
            '#<a\s+href="https://github\.com/([\w\-\.]+/[\w\-\.]+)"[^>]*>https://github\.com/[\w\-\.]+/[\w\-\.]+</a>#i',
            function($matches) {
                $repo = htmlspecialchars($matches[1]);
                return '<div class="github-card text-center" data-repo="' . $repo . '"><div class="spinner-grow text-primary"></div></div>';
            },
            $githubScanContent
        );
        $content = self::restoreProtectedShortcodes($githubScanContent, $protectedShortcodes);

        // alert 类短代码
        $alertShortcodes = [
            'success' => 'success',
            'primary' => 'primary',
            'danger'  => 'danger',
            'warning' => 'warning',
            'info'    => 'info',
            'dark'    => 'dark',
        ];
        foreach ($alertShortcodes as $shortcode => $class) {
            // 先处理被 Markdown 包裹在 <p> 标签中的短代码
            $content = preg_replace(
                '/<p>\s*\[' . $shortcode . '\](.*?)\[\/' . $shortcode . '\]\s*<\/p>/is',
                '<div class="alert alert-' . $class . '">$1</div>',
                $content
            );
            // 再处理普通的短代码（向后兼容）
            $content = preg_replace(
                '/\[' . $shortcode . '\](.*?)\[\/' . $shortcode . '\]/is',
                '<div class="alert alert-' . $class . '">$1</div>',
                $content
            );
        }

        // article 短代码
        $content = preg_replace_callback('/\[article\s+([^\]]+)\]/', function($matches) {
            $atts = self::parse_atts($matches[1]);
            return get_article_info($atts);
        }, $content);

        // collapse 折叠面板短代码
        static $collapseIndex = 0;
        $content = preg_replace_callback(
            '/\[collapse\s+title=(?:\'([^\']*)\'|\"([^\"]*)\")\](.*?)\[\/collapse\]/is',
            function($matches) use (&$collapseIndex) {
                $title = $matches[1] !== '' ? $matches[1] : $matches[2];
                $body = $matches[3];
                $collapseIndex++;
                $uniqid = 'collapse-' . mt_rand(100,999) . '-' . $collapseIndex;
                return '<div class="pk-sc-collapse"><a class="btn btn-primary btn-sm" data-bs-toggle="collapse" href="#' . $uniqid . '" role="button" aria-expanded="false" aria-controls="' . $uniqid . '"><i class="fa fa-angle-up"></i>&nbsp;' . htmlspecialchars($title) . '</a></div><div class="collapse" id="' . $uniqid . '">' . $body . '</div>';
            },
            $content
        );

        // download 短代码
        $content = preg_replace_callback(
            '/\[download\s+file=(?:\'([^\']*)\'|\"([^\"]*)\")\s+size=(?:\'([^\']*)\'|\"([^\"]*)\")\](.*?)\[\/download\]/is',
            function($matches) {
                $file = $matches[1] !== '' ? $matches[1] : $matches[2];
                $size = $matches[3] !== '' ? $matches[3] : $matches[4];
                $url = self::extractUrl($matches[5]);
                return '<div class="p-block p-down-box">'
                    . "<div class='mb15'><i class='fa fa-file-zipper'></i>&nbsp;<span> 文件名称：" . htmlspecialchars($file) . "</span></div>"
                    . "<div class='mb15'><i class='fa fa-download'></i>&nbsp;<span> 文件大小：" . htmlspecialchars($size) . "</span></div>"
                    . "<div class='mb15'><i class='fa-regular fa-bell'></i>&nbsp;<span> 下载声明：本站部分资源来自于网络收集，若侵犯了你的隐私或版权，请及时联系我们删除有关信息。</span></div>"
                    . "<div><i class='fa fa-link'></i><span> 下载地址：<a href='" . htmlspecialchars($url) . "' target='_blank'>点此下载</a> </span></div></p></div>";
            },
            $content
        );

        // reply 短代码
        $content = preg_replace_callback(
            '/\[reply\](.*?)\[\/reply\]/is',
            function($matches) use ($widget) {
                $show = false;
                $isArchive = (class_exists('Widget_Archive') && $widget instanceof Widget_Archive) || 
                           (class_exists('\\Typecho\\Widget\\Archive') && $widget instanceof \Typecho\Widget\Archive);
                if ($isArchive && method_exists($widget, 'is') && $widget->is('single')) {
                    $user = \Typecho\Widget::widget('Widget_User');
                    $db = Typecho_Db::get();
                    if (isset($user->hasLogin) && $user->hasLogin) {
                        $hasComment = $db->fetchRow($db->select()->from('table.comments')
                            ->where('cid = ?', isset($widget->cid) ? $widget->cid : 0)
                            ->where('mail = ?', isset($user->mail) ? $user->mail : '')
                            ->where('status = ?', 'approved')
                        );
                        if ($hasComment) $show = true;
                    } else {
                        $ip = '';
                        if (method_exists($widget, 'request') && $widget->request) {
                            $ip = method_exists($widget->request, 'getIp') ? $widget->request->getIp() : '';
                        }
                        $hasComment = $db->fetchRow($db->select()->from('table.comments')
                            ->where('cid = ?', isset($widget->cid) ? $widget->cid : 0)
                            ->where('status = ?', 'approved')
                            ->where('ip = ?', $ip)
                        );
                        if ($hasComment) $show = true;
                    }
                }
                if ($show) {
                    return '<div class="reply-visible">' . $matches[1] . '</div>';
                } else {
                    return "<div class='alert alert-primary alert-outline'><span class='c-sub fs14'><i class='fa-regular fa-eye'></i>&nbsp; 此处含有隐藏内容，请提交评论并审核通过刷新后即可查看！</span></div>";
                }
            },
            $content
        );

        foreach ($codeBlocks as $placeholder => $code) {
            $content = str_replace($placeholder, $code, $content);
        }

        return $content;
    }
}

// 为模板提供的短代码解析函数
function parse_shortcodes($content, $widget = null) {
    $content = VideoParser::parseContent($content);
    // 直接调用短代码解析,不再进行 Markdown 转换
    if (class_exists('ContentFilter')) {
        $content = ContentFilter::applyShortcodesOnly($content, $widget);
        if (function_exists('puock_rewrite_external_links_to_go')) {
            $content = puock_rewrite_external_links_to_go($content);
        }
        return $content;
    }
    if (function_exists('puock_rewrite_external_links_to_go')) {
        $content = puock_rewrite_external_links_to_go($content);
    }
    return $content;
}
