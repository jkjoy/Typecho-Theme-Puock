<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 获取站点统计信息（包含当前登录用户信息）
 * @return array
 */
function get_site_statistics() {
    $db = Typecho_Db::get();
    $prefix = $db->getPrefix();
    
    // 获取当前登录用户对象
    $currentUser = Typecho_Widget::widget('Widget_User');
    
    // 如果用户未登录，获取默认uid=1的用户
    if (!$currentUser->hasLogin()) {
        $defaultUser = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', 1));
        $email = $defaultUser['mail'];
        $nickname = $defaultUser['screenName'];
    } else {
        // 用户已登录，使用当前用户信息
        $email = $currentUser->mail;
        $nickname = $currentUser->screenName;
    }
    
    // 获取用户设置的 Gravatar 镜像
    $cnavatar = Helper::options()->cnavatar ? Helper::options()->cnavatar : 'https://cravatar.cn/avatar/';
    $hash = md5($email);
    $avatar = rtrim($cnavatar, '/') . '/' . $hash . '?s=80&d=identicon';
    
    // 其余统计信息保持不变
    $totalWords = $db->fetchObject($db->select('SUM(LENGTH(text)) AS wordCount')->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish'))->wordCount;
    $tagsCount = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))->from('table.metas')->where('type = ?', 'tag'))->num;
    $userCount = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))->from('table.users'))->num;
    $postCount = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))->from('table.contents')->where('type = ?', 'post')->where('status = ?', 'publish'))->num;
    $commentCount = $db->fetchObject($db->select(array('COUNT(*)' => 'num'))->from('table.comments'))->num;
    $totalViews = $db->fetchObject(
        $db->select(array('SUM(views)' => 'viewsum'))->from('table.contents')->where('type = ?', 'post')
    )->viewsum;
    if ($totalViews === null) $totalViews = 0;
    
    return [
        'email' => $email, // 邮箱
        'nickname' => $nickname, // 昵称
        'avatar' => $avatar, // 头像URL
        'userCount' => $userCount, // 用户总数
        'postCount' => $postCount,// 文章总数
        'commentCount' => $commentCount, // 评论总数
        'totalViews' => $totalViews, // 文章总浏览量
        'tagsCount' => $tagsCount, // 标签总数
        'totalWords' => $totalWords, // 文章总字数
        'isLogin' => $currentUser->hasLogin() // 添加一个是否登录的标志
    ];
}