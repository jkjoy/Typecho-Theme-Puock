<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

 /**
 * 获取所有评论者信息的函数
 */
function getAllCommenters() {
    $db = Typecho_Db::get();
    $commenters = array();
    
    $isSqlite = strpos(strtolower(get_class($db->getAdapter())), 'sqlite') !== false;
    if ($isSqlite) {
        // SQLite 不支持 ANY_VALUE
        $query = $db->select('author', 'mail', 'COUNT(*) as comment_count', 'url')
            ->from('table.comments')
            ->where('status = ?', 'approved')
            ->where('authorId != ?', 1)
            ->group('mail')
            ->order('comment_count', Typecho_Db::SORT_DESC);
    } else {
        // MySQL 用 ANY_VALUE 兼容 ONLY_FULL_GROUP_BY
        $query = $db->select(
            'ANY_VALUE(author) as author',
            'mail',
            'COUNT(*) as comment_count',
            'ANY_VALUE(url) as url'
        )
        ->from('table.comments')
        ->where('authorId != ?', 1)
        ->where('status = ?', 'approved')
        ->group('mail')
        ->order('comment_count', Typecho_Db::SORT_DESC);
    }

    $rows = $db->fetchAll($query);
    
    // 获取 Gravatar 镜像设置
    $cnavatar = Helper::options()->cnavatar ? Helper::options()->cnavatar : 'https://cravatar.cn/avatar/';
    
    foreach ($rows as $row) {
        // PHP 8.1+ 兼容：确保 mail 不为 null
        $mail = $row['mail'] ?? '';
        $email_hash = md5(strtolower(trim($mail)));
        $avatar_url = rtrim($cnavatar, '/') . '/' . $email_hash . '?s=50&d=mp';
        $commenters[] = array(
            'nickname' => $row['author'] ?? '匿名用户',
            'email' => $mail,
            'url' => $row['url'] ?? '',
            'avatar' => $avatar_url,
            'comment_count' => $row['comment_count'] ?? 0
        );
    }
    
    return $commenters;
}

/**
 * 获取IP归属地
 */
// 加载 XdbSearcherTheme 类（避免重复加载）
if (!class_exists('XdbSearcherTheme')) {
    require_once __DIR__ . '/ip2region/XdbSearcher.php';
}

// 单例方式加载 ip2region.xdb 到内存
function getIp2regionSearcher() {
    static $searcher = null;
    if ($searcher === null) {
        $dbPath = __DIR__ . '/ip2region/ip2region_v4.xdb';
        $cBuff = XdbSearcherTheme::loadContentFromFile($dbPath);
        if ($cBuff === null) {
            $dbName = basename($dbPath);
            error_log("无法加载 " . $dbName);
            return null;
        }
        try {
            $searcher = XdbSearcherTheme::newWithBuffer($cBuff);
        } catch (Exception $e) {
            error_log("创建 ip2region searcher 失败: " . $e->getMessage());
            return null;
        }
    }
    return $searcher;
}

// 单例方式加载 ip2region_v6.xdb 到内存
function getIp2regionV6Buffer() {
    static $buffer = null;
    if ($buffer === null) {
        $dbPath = __DIR__ . '/ip2region/ip2region_v6.xdb';
        if (!is_file($dbPath)) {
            error_log("无法加载 ip2region_v6.xdb");
            return null;
        }
        $buffer = XdbSearcherTheme::loadContentFromFile($dbPath);
        if ($buffer === null) {
            error_log("无法加载 ip2region_v6.xdb");
            return null;
        }
    }
    return $buffer;
}

function ip2regionSearchV6($ip) {
    $buffer = getIp2regionV6Buffer();
    if ($buffer === null) return null;

    $packed = inet_pton($ip);
    if ($packed === false || strlen($packed) !== 16) return null;

    $il0 = ord($packed[0]);
    $il1 = ord($packed[1]);
    $idx = ($il0 * XdbSearcherTheme::VectorIndexCols * XdbSearcherTheme::VectorIndexSize)
        + ($il1 * XdbSearcherTheme::VectorIndexSize);
    $base = XdbSearcherTheme::HeaderInfoLength;

    $sPtr = unpack('V', substr($buffer, $base + $idx, 4))[1];
    $ePtr = unpack('V', substr($buffer, $base + $idx + 4, 4))[1];
    if ($sPtr == 0 && $ePtr == 0) return null;

    $segSize = 38;
    $l = 0;
    $h = (int)(($ePtr - $sPtr) / $segSize);
    while ($l <= $h) {
        $m = ($l + $h) >> 1;
        $p = $sPtr + $m * $segSize;
        $rec = substr($buffer, $p, $segSize);
        if (strlen($rec) !== $segSize) {
            break;
        }

        $sip = substr($rec, 0, 16);
        if (strcmp($packed, $sip) < 0) {
            $h = $m - 1;
            continue;
        }

        $eip = substr($rec, 16, 16);
        if (strcmp($packed, $eip) > 0) {
            $l = $m + 1;
            continue;
        }

        $dataLen = unpack('v', substr($rec, 32, 2))[1];
        $dataPtr = unpack('V', substr($rec, 34, 4))[1];
        if ($dataLen <= 0 || $dataPtr <= 0) return null;

        $region = substr($buffer, $dataPtr, $dataLen);
        return $region !== '' ? $region : null;
    }

    return null;
}

/**
 * 格式化 IP 归属地
 *
 * @param string $region 归属地字符串
 * @return string 格式化后的归属地
 */
function format_ip_region($region) {
    // 分割字符串
    $parts = explode('|', $region);

    // 去除为 0 或 空字符串的部分
    $parts = array_filter($parts, function($item) {
        return $item !== '0' && $item !== '';
    });

    // 如果第一个元素是"中国"，则移除
    if (isset($parts[0]) && $parts[0] === '中国') {
        array_shift($parts);
    }

    // 重新拼接
    return implode('', $parts);
}

// 通过 IP 获取归属地
function get_ip_region($ip) {
    $ip = trim((string)$ip);
    if ($ip === '') return '未知';

    $packed = inet_pton($ip);
    if ($packed !== false && strlen($packed) === 16) {
        $v4Mapped = substr($packed, 0, 12) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff";
        if (!$v4Mapped) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return '内网IP';
            }
            $region = ip2regionSearchV6($ip);
            if ($region === null) return '未知';
            return format_ip_region($region);
        }
        $ip = inet_ntop(substr($packed, 12, 4));
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return '未知';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return '内网IP';
    }

    $searcher = getIp2regionSearcher();
    if (!$searcher) return '未知';
    $region = $searcher->search($ip);
    if ($region === null) return '未知';
    return format_ip_region($region);
}

/**
 * 浏览器和设备信息
 *
 * @param string $userAgent 用户代理
 * @return string[]
 */
function getBrowsersInfo($userAgent) {
    $deviceInfo = [
        "system" => "",
        "systemVersion" => "",
        "browser" => "",
        "version" => "",
        "device" => "PC"
    ];

    $match = [
        // 浏览器 - 国外浏览器
        "Safari" => strstr($userAgent, 'Safari') !== false,
        "Chrome" => strstr($userAgent, 'Chrome') !== false || strstr($userAgent, 'CriOS') !== false,
        "IE" => strstr($userAgent, 'MSIE') !== false || strstr($userAgent, 'Trident') !== false,
        "Edge" => strstr($userAgent, 'Edge') !== false || strstr($userAgent, 'Edg/') !== false || strstr($userAgent, 'EdgA') !== false || strstr($userAgent, 'EdgiOS') !== false,
        "Firefox" => strstr($userAgent, 'Firefox') !== false || strstr($userAgent, 'FxiOS') !== false,
        "Firefox Focus" => strstr($userAgent, 'Focus') !== false,
        "Chromium" => strstr($userAgent, 'Chromium') !== false,
        "Opera" => strstr($userAgent, 'Opera') !== false || strstr($userAgent, 'OPR') !== false,
        "Vivaldi" => strstr($userAgent, 'Vivaldi') !== false,
        "Yandex" => strstr($userAgent, 'YaBrowser') !== false,
        "Arora" => strstr($userAgent, 'Arora') !== false,
        "Lunascape" => strstr($userAgent, 'Lunascape') !== false,
        "QupZilla" => strstr($userAgent, 'QupZilla') !== false,
        "Coc Coc" => strstr($userAgent, 'coc_coc_browser') !== false,
        "Kindle" => strstr($userAgent, 'Kindle') !== false || strstr($userAgent, 'Silk/') !== false,
        "Iceweasel" => strstr($userAgent, 'Iceweasel') !== false,
        "Konqueror" => strstr($userAgent, 'Konqueror') !== false,
        "Iceape" => strstr($userAgent, 'Iceape') !== false,
        "SeaMonkey" => strstr($userAgent, 'SeaMonkey') !== false,
        "Epiphany" => strstr($userAgent, 'Epiphany') !== false,
        // 浏览器 - 国内浏览器
        "360" => strstr($userAgent, 'QihooBrowser') !== false || strstr($userAgent, 'QHBrowser') !== false,
        "360EE" => strstr($userAgent, '360EE') !== false,
        "360SE" => strstr($userAgent, '360SE') !== false,
        "UC" => strstr($userAgent, 'UCBrowser') !== false || strstr($userAgent, ' UBrowser') !== false || strstr($userAgent, 'UCWEB') !== false,
        "QQBrowser" => strstr($userAgent, 'QQBrowser') !== false,
        "QQ" => strstr($userAgent, 'QQ/') !== false,
        "Baidu" => strstr($userAgent, 'Baidu') !== false || strstr($userAgent, 'BIDUBrowser') !== false || strstr($userAgent, 'baidubrowser') !== false || strstr($userAgent, 'baiduboxapp') !== false || strstr($userAgent, 'BaiduHD') !== false,
        "Maxthon" => strstr($userAgent, 'Maxthon') !== false,
        "Sogou" => strstr($userAgent, 'MetaSr') !== false || strstr($userAgent, 'Sogou') !== false,
        "Liebao" => strstr($userAgent, 'LBBROWSER') !== false || strstr($userAgent, 'LieBaoFast') !== false,
        "2345Explorer" => strstr($userAgent, '2345Explorer') !== false || strstr($userAgent, 'Mb2345Browser') !== false || strstr($userAgent, '2345chrome') !== false,
        "115Browser" => strstr($userAgent, '115Browser') !== false,
        "TheWorld" => strstr($userAgent, 'TheWorld') !== false,
        "Quark" => strstr($userAgent, 'Quark') !== false,
        "Qiyu" => strstr($userAgent, 'Qiyu') !== false,
        // 浏览器 - 手机厂商
        "XiaoMi" => strstr($userAgent, 'MiuiBrowser') !== false,
        "Huawei" => strstr($userAgent, 'HuaweiBrowser') !== false || strstr($userAgent, 'HUAWEI/') !== false || strstr($userAgent, 'HONOR') !== false || strstr($userAgent, 'HBPC/') !== false,
        "Vivo" => strstr($userAgent, 'VivoBrowser') !== false,
        "OPPO" => strstr($userAgent, 'HeyTapBrowser') !== false,
        // 浏览器 - 客户端
        "Wechat" => strstr($userAgent, 'MicroMessenger') !== false,
        "WechatWork" => strstr($userAgent, 'wxwork/') !== false,
        "Taobao" => strstr($userAgent, 'AliApp(TB') !== false,
        "Alipay" => strstr($userAgent, 'AliApp(AP') !== false,
        "Weibo" => strstr($userAgent, 'Weibo') !== false,
        "Douban" => strstr($userAgent, 'com.douban.frodo') !== false,
        "Suning" => strstr($userAgent, 'SNEBUY-APP') !== false,
        "iQiYi" => strstr($userAgent, 'IqiyiApp') !== false,
        "DingTalk" => strstr($userAgent, 'DingTalk') !== false,
        "Douyin" => strstr($userAgent, 'aweme') !== false,
        // 系统或平台
        "Windows" => strstr($userAgent, 'Windows') !== false,
        "Linux" => strstr($userAgent, 'Linux') !== false || strstr($userAgent, 'X11') !== false,
        "Mac OS" => strstr($userAgent, 'Macintosh') !== false,
        "Android" => strstr($userAgent, 'Android') !== false || strstr($userAgent, 'Adr') !== false,
        "HarmonyOS" => strstr($userAgent, 'HarmonyOS') !== false,
        "Ubuntu" => strstr($userAgent, 'Ubuntu') !== false,
        "FreeBSD" => strstr($userAgent, 'FreeBSD') !== false,
        "Debian" => strstr($userAgent, 'Debian') !== false,
        "Windows Phone" => strstr($userAgent, 'IEMobile') !== false || strstr($userAgent, 'Windows Phone') !== false,
        "BlackBerry" => strstr($userAgent, 'BlackBerry') !== false || strstr($userAgent, 'RIM') !== false,
        "MeeGo" => strstr($userAgent, 'MeeGo') !== false,
        "Symbian" => strstr($userAgent, 'Symbian') !== false,
        "iOS" => strstr($userAgent, 'like Mac OS X') !== false,
        "Chrome OS" => strstr($userAgent, 'CrOS') !== false,
        "WebOS" => strstr($userAgent, 'hpwOS') !== false,
        // 设备
        "Mobile" => strstr($userAgent, 'Mobi') !== false || strstr($userAgent, 'iPh') !== false || strstr($userAgent, '480') !== false,
        "Tablet" => strstr($userAgent, 'Tablet') !== false || strstr($userAgent, 'Pad') !== false || strstr($userAgent, 'Nexus 7') !== false,
    ];

    // 部分修正
    if ($match['Baidu'] && $match['Opera']) $match['Baidu'] = false;
    if ($match['iOS']) $match['Safari'] = true;

    // 基本信息
    $baseInfo = [
        "browser" => [
            'Safari', 'Chrome', 'Edge', 'IE', 'Firefox', 'Firefox Focus', 'Chromium',
            'Opera', 'Vivaldi', 'Yandex', 'Arora', 'Lunascape', 'QupZilla', 'Coc Coc',
            'Kindle', 'Iceweasel', 'Konqueror', 'Iceape', 'SeaMonkey', 'Epiphany', 'XiaoMi',
            'Vivo', 'OPPO', '360', '360SE', '360EE', 'UC', 'QQBrowser', 'QQ', 'Huawei', 'Baidu',
            'Maxthon', 'Sogou', 'Liebao', '2345Explorer', '115Browser', 'TheWorld', 'Quark', 'Qiyu',
            'Wechat', 'WechatWork', 'Taobao', 'Alipay', 'Weibo', 'Douban', 'Suning', 'iQiYi', 'DingTalk', 'Douyin'
        ],
        "system" => [
            'Windows', 'Linux', 'Mac OS', 'Android', 'HarmonyOS', 'Ubuntu',
            'FreeBSD', 'Debian', 'iOS', 'Windows Phone', 'BlackBerry', 'MeeGo',
            'Symbian', 'Chrome OS', 'WebOS'
        ],
        "device" => ['Mobile', 'Tablet'],
    ];

    foreach ($baseInfo as $k => $v) {
        foreach ($v as $xv) {
            if ($match[$xv]) $deviceInfo[$k] = $xv;
        }
    }

    // 操作系统版本信息
    $windowsVersion = [
        '6.3' => '8.1',
        '6.2' => '8',
        '6.1' => '7',
        '6.0' => 'Vista',
        '5.2' => 'XP',
        '5.1' => 'XP',
        '5.0' => '2000',
    ];

    // Extract Windows NT version and build number
    if (preg_match("/\(Windows NT ([\d.]+)(?:;[^)]*Build\/(\d+))?(?:;[^)]*)?\)/i", $userAgent, $matches)) {
        $ntVersion = $matches[1];
        $buildNumber = isset($matches[2]) ? (int)$matches[2] : 0;

        if ($ntVersion === '10.0') {
            // Check build number for Windows 11
            if ($buildNumber >= 22000) {
                $deviceInfo['systemVersion'] = '11';
            } else {
                // Fallback heuristic: Check Edge version (Windows 11 requires Edge >= 91)
                if ($deviceInfo['browser'] === 'Edge' && preg_match("/Edg\/([\d.]+)/", $userAgent, $edgeMatch)) {
                    $edgeVersion = (float)$edgeMatch[1];
                    if ($edgeVersion >= 91) {
                        $deviceInfo['systemVersion'] = '11'; // Assume Windows 11 for modern Edge
                    } else {
                        $deviceInfo['systemVersion'] = '10';
                    }
                } else {
                    $deviceInfo['systemVersion'] = '10';
                    // 不再记录警告日志，避免产生错误日志
                    // error_log("Ambiguous Windows version (NT 10.0, no build number): $userAgent");
                }
            }
        } else {
            $deviceInfo['systemVersion'] = $windowsVersion[$ntVersion] ?? $ntVersion;
        }
    }

    $HarmonyOSVersion = [
        10 => "2",
        12 => "3"
    ];
    $systemVersion = [
        "Windows" => $deviceInfo['systemVersion'], // Already set above
        "Android" => preg_match("/Android ([\d.]+);/", $userAgent, $matches) ? $matches[1] : '',
        "HarmonyOS" => preg_match("/Android ([\d.]+)[;)]/", $userAgent, $matches) ? ($HarmonyOSVersion[(int)$matches[1]] ?? '') : '',
        "iOS" => preg_match("/OS ([\d_]+) like/", $userAgent, $matches) ? str_replace('_', '.', $matches[1]) : '',
        "Debian" => preg_match("/Debian\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Windows Phone" => preg_match("/Windows Phone( OS)? ([\d.]+);/", $userAgent, $matches) ? $matches[2] : '',
        "Mac OS" => preg_match("/Mac OS X ([\d_]+)/", $userAgent, $matches) ? str_replace('_', '.', $matches[1]) : '',
        "WebOS" => preg_match("/hpwOS\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''
    ];

    if (isset($deviceInfo['system']) && $deviceInfo['system'] !== "" && isset($systemVersion[$deviceInfo['system']])) {
        $deviceInfo['systemVersion'] = $systemVersion[$deviceInfo['system']];
        if ($deviceInfo['systemVersion'] == $userAgent) {
            $deviceInfo['systemVersion'] = '';
        }
    }

    // 浏览器版本信息
    $browsers_360SE = [
        108 => '14.0',
        86 => '13.0',
        78 => '12.0',
        69 => '11.0',
        63 => '10.0',
        55 => '9.1',
        45 => '8.1',
        42 => '8.0',
        31 => '7.0',
        21 => '6.3',
    ];
    $browsers_360EE = [
        95 => '21',
        86 => '13.0',
        78 => '12.0',
        69 => '11.0',
        63 => '9.5',
        55 => '9.0',
        50 => '8.7',
        30 => '7.5',
    ];
    $browsers_liebao = [
        57 => '6.5',
        49 => '6.0',
        46 => '5.9',
        42 => '5.3',
        39 => '5.2',
        34 => '5.0',
        29 => '4.5',
        21 => '4.0'
    ];
    $browsers_2345 = [
        69 => '10.0',
        55 => '9.9',
    ];

    $chromeVersion = preg_match('/Chrome\/([\d]+)/', $userAgent, $matches) ? (int)$matches[1] : 0;

    $browsersVersion = [
        "Safari" => preg_match("/Version\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Chrome" => preg_match("/Chrome\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/CriOS\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "IE" => preg_match("/MSIE ([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/rv:([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "Edge" => preg_match("/Edge\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/Edg\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/EdgA\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/EdgiOS\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''))),
        "Firefox" => preg_match("/Firefox\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/FxiOS\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "Firefox Focus" => preg_match("/Focus\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Chromium" => preg_match("/Chromium\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Opera" => preg_match("/Opera\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/OPR\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "Vivaldi" => preg_match("/Vivaldi\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Yandex" => preg_match("/YaBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Brave" => preg_match("/Chrome\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Arora" => preg_match("/Arora\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Lunascape" => preg_match("/Lunascape[\/\s]([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "QupZilla" => preg_match("/QupZilla[\/\s]([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Coc Coc" => preg_match("/coc_coc_browser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Kindle" => preg_match("/Version\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Iceweasel" => preg_match("/Iceweasel\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Konqueror" => preg_match("/Konqueror\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Iceape" => preg_match("/Iceape\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "SeaMonkey" => preg_match("/SeaMonkey\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Epiphany" => preg_match("/Epiphany\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "360" => preg_match("/QihooBrowser(HD)?\/([\d.]+)/", $userAgent, $matches) ? $matches[2] : '',
        "Maxthon" => preg_match("/Maxthon\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "QQBrowser" => preg_match("/QQBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "QQ" => preg_match("/QQ\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Baidu" => preg_match("/BIDUBrowser[\s\/]([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/baiduboxapp\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "UC" => preg_match("/UC?Browser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Sogou" => preg_match("/SE ([\d.X]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/SogouMobileBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : ''),
        "115Browser" => preg_match("/115Browser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "TheWorld" => preg_match("/TheWorld ([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "XiaoMi" => preg_match("/MiuiBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Vivo" => preg_match("/VivoBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "OPPO" => preg_match("/HeyTapBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Quark" => preg_match("/Quark\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Qiyu" => preg_match("/Qiyu\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Wechat" => preg_match("/MicroMessenger\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "WechatWork" => preg_match("/wxwork\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Taobao" => preg_match("/AliApp\(TB\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Alipay" => preg_match("/AliApp\(AP\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Weibo" => preg_match("/weibo__([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Douban" => preg_match("/com.douban.frodo\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Suning" => preg_match("/SNEBUY-APP([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "iQiYi" => preg_match("/IqiyiVersion\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "DingTalk" => preg_match("/DingTalk\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Douyin" => preg_match("/app_version\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '',
        "Huawei" => preg_match("/Version\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/HuaweiBrowser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/HBPC\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : '')),
        "360SE" => isset($browsers_360SE[$chromeVersion]) ? $browsers_360SE[$chromeVersion] : '',
        "360EE" => isset($browsers_360EE[$chromeVersion]) ? $browsers_360EE[$chromeVersion] : '',
        "Liebao" => preg_match("/LieBaoFast\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (isset($browsers_liebao[$chromeVersion]) ? $browsers_liebao[$chromeVersion] : ''),
        "2345Explorer" => preg_match("/2345Explorer\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (preg_match("/Mb2345Browser\/([\d.]+)/", $userAgent, $matches) ? $matches[1] : (isset($browsers_2345[$chromeVersion]) ? $browsers_2345[$chromeVersion] : '')),
    ];

    if (isset($deviceInfo['browser'], $browsersVersion[$deviceInfo['browser']])) {
        $deviceInfo['version'] = $browsersVersion[$deviceInfo['browser']];
        if ($deviceInfo['version'] == $userAgent) {
            $deviceInfo['version'] = '';
        }
    }

    // 修正浏览器版本信息
    if (preg_match('/\S+Browser/', $userAgent, $matches)) {
        $chrome = $matches[0];
        if ($deviceInfo['browser'] == 'Chrome' && $chrome !== 'Chrome') {
            $deviceInfo['browser'] = $chrome;
            if (preg_match('/Browser\/([\d.]+)/', $userAgent, $matches)) {
                $deviceInfo['version'] = $matches[1];
            }
        }
    }

    return $deviceInfo;
}
 
/**
 * 返回符合正则的值
 *
 * @param string $reg 正则
 * @param string $sourceData 源数据
 * @return mixed|void
 */
function pregMatch($reg, $sourceData) {
    if (preg_match($reg, $sourceData, $mat)) {
        return $mat[1] ?? '';
    }
return '';
}

/**
 * 获取设备和浏览器的图标
 * @param array $data 设备信息数组
 * @return array 包含系统和浏览器图标的数组
 */
function getDeviceIcon($data) {
    // 系统图标映射
    $systemIcons = [
        'Windows' => '<i class="fa-brands fa-windows"></i>',
        'Linux' => '<i class="fa-brands fa-linux"></i>',
        'Mac OS' => '<i class="fa-brands fa-apple"></i>', 
        'Android' => '<i class="fa-brands fa-android"></i>',
        'iOS' => '<i class="fa-brands fa-apple"></i>',
        'HarmonyOS' => '<i class="fa fa-mobile"></i>', // HarmonyOS 使用手机图标
        'Chrome OS' => '<i class="fa-brands fa-chrome"></i>',
        '' => '<i class="fa fa-desktop"></i>' // 未知系统使用桌面图标
    ];

    // 浏览器图标映射
    $browserIcons = [
        'Chrome' => '<i class="fa-brands fa-chrome"></i>',
        'Firefox' => '<i class="fa-brands fa-firefox"></i>',
        'Safari' => '<i class="fa-brands fa-safari"></i>',
        'Edge' => '<i class="fa-brands fa-edge"></i>',
        'IE' => '<i class="fa-brands fa-internet-explorer"></i>',
        'Opera' => '<i class="fa-brands fa-opera"></i>',
        'QQ' => '<i class="fa-brands fa-qq"></i>',
        'Wechat' => '<i class="fa-brands fa-weixin"></i>',
        'Weibo' => '<i class="fa-brands fa-weibo"></i>',
        '' => '<i class="fa fa-globe"></i>' // 未知浏览器使用地球图标
    ];

    // 设备类型图标
    $deviceIcons = [
        'Mobile' => 'fa fa-mobile',
        'Tablet' => 'fa fa-tablet',
        'PC' => 'fa fa-desktop'
    ];

    // 获取对应图标，如果没有匹配则使用默认图标
    $systemIcon = $systemIcons[$data['system']] ?? $systemIcons[''];
    $browserIcon = $browserIcons[$data['browser']] ?? $browserIcons[''];
    $deviceIcon = $deviceIcons[$data['device']] ?? $deviceIcons['PC'];

    return [
        'system' => $systemIcon,
        'browser' => $browserIcon,
        'device' => $deviceIcon
    ];
}

/**    
 * 评论者认证等级 + 身份    
 *    
 * @author Chrison    
 * @access public    
 * @param str $email 评论者邮址    
 * @return result     
 */     
function commentApprove($widget, $email = NULL)      
{   
    $result = array(
        "state" => -1,//状态
        "isAuthor" => 0,//是否是博主
        "userLevel" => '',//用户身份或等级名称
        "userDesc" => '',//用户title描述
        "bgColor" => '',//用户身份或等级背景色
        "commentNum" => 0//评论数量
    );
    if (empty($email)) return $result;       
    $result['state'] = 1;   
    if ($widget->authorId == $widget->ownerId) {      
        $result['isAuthor'] = 1;//」
        $result['userLevel'] = '博主';
        $result['userDesc'] = '本站站长';
        $result['bgColor'] = '#FFD67A';
        $result['commentNum'] = 999;
    }  else {
        try {
            //数据库获取
            $db = Typecho_Db::get();
            //获取评论条数
            $commentNumSql = $db->fetchAll($db->select(array('COUNT(cid)'=>'commentNum'))
                ->from('table.comments')
                ->where('mail = ?', $email));
            $commentNum = $commentNumSql[0]['commentNum'];    
            //获取友情链接
            $linkSql = $db->fetchAll($db->select()->from('table.links')
                ->where('user = ?',$email));       
            //等级判定
            if($commentNum==1){
                $result['userLevel'] = '初见 LV.1';
                $result['bgColor'] = '#999999';
                $userDesc = '人生一大步！';
            } else {
                if ($commentNum<10 && $commentNum>1) {
                    $result['userLevel'] = '初识 LV.2';
                    $result['bgColor'] = '#999999';
                }elseif ($commentNum<20 && $commentNum>=10) {
                    $result['userLevel'] = '相识 LV.3';
                    $result['bgColor'] = '#A0DAD0';
                }elseif ($commentNum<40 && $commentNum>=20) {
                    $result['userLevel'] = '熟识 LV.4';
                    $result['bgColor'] = '#A0DAD0';
                }elseif ($commentNum<80 && $commentNum>=40) {
                    $result['userLevel'] = '好友 LV.5';
                    $result['bgColor'] = '#A0DAD0';
                }elseif ($commentNum<160 && $commentNum>=80) {
                    $result['userLevel'] = '知己 LV.6';
                    $result['bgColor'] = '#A0DAD0';
                }elseif ($commentNum>=160) {
                    $result['userLevel'] = '挚友 LV.7';
                    $result['bgColor'] = '#A0DAD0';
                }
                 $userDesc = '您在本站有'.$commentNum.'条留言！'; 
            }
            if($linkSql){
                $result['userLevel'] = '「博友」';
                $result['bgColor'] = '#21b9bb';
                $userDesc = '🔗'.$linkSql[0]['description'].'&#10;✌️'.$userDesc;
            }
            
            $result['userDesc'] = $userDesc;
            $result['commentNum'] = $commentNum;
        } catch (Exception $e) {
            error_log('Error in commentApprove function: ' . $e->getMessage());
            // 设置默认值
            $result['userLevel'] = '「访客」';
            $result['bgColor'] = '#999999';
            $result['userDesc'] = '欢迎留言';
            $result['commentNum'] = 0;
        }
    } 
    return $result;
}

/**
 * 子评论加上@用户名
 * 
 * @param int $coid 评论ID
 * @return string 评论的永久链接
 */
function getPermalinkFromCoid($coid) {
	$db = Typecho_Db::get();
	$row = $db->fetchRow($db->select('author')->from('table.comments')->where('coid = ? AND status = ?', $coid, 'approved'));
	if (empty($row)) return '';
	return '<a href="#comment-'.$coid.'" class="c-sub">@'.$row['author'].'</a>';
}