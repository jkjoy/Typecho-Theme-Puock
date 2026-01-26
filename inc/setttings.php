<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
function themeConfig($form)
{   
    themeAutoUpgradeNotice();
    echo '<style>.typecho-page-title h2 {font-weight: 600;color: #737373;}.typecho-page-title h2:before {content: "#";margin-right: 6px;color: #ff6d6d; font-size: 20px;font-weight: 600;}.themeConfig h3 {color: #737373;font-size: 20px;}.themeConfig h3:before {content: "[";margin-right: 5px;color: #ff6d6d;font-size: 25px;}.themeConfig h3:after {content: "]";margin-left: 5px;color: #ff6d6d;font-size: 25px;}.themeConfiginfo{border: 1px solid #ffadad;padding: 20px;margin: -15px 10px 25px 0;background: #ffffff;border-radius: 5px;color: #ff6d6d;}</style>';
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', NULL, NULL, _t('<span class="themeConfig"><h3>博客设置</h3></span><div class="themeConfiginfo">全局设置</div>站点 LOGO 地址'), _t('建议尺寸 100px * 100px,不填写则使用站点标题'));
    $form->addInput($logoUrl);
    $icoUrl = new Typecho_Widget_Helper_Form_Element_Text('icoUrl', NULL, NULL, _t('站点 Favicon 地址'), _t('建议尺寸 16px * 16px,不填写则使用默认图标'));
    $form->addInput($icoUrl);
    $primaryColor = new Typecho_Widget_Helper_Form_Element_Text('primaryColor', NULL, NULL, _t('主题主色调'), _t('默认浅蓝色 <b style=color:red>#A7E6F4</b> 常见的<a href="https://tool.imsun.org/" target="_blank">颜色选择器</a>'));
    $form->addInput($primaryColor);
    $cnavatar = new Typecho_Widget_Helper_Form_Element_Text('cnavatar', NULL, NULL, _t('Gravatar镜像'), _t('默认使用https://cravatar.cn/avatar/'));
    $form->addInput($cnavatar);
    $sticky = new Typecho_Widget_Helper_Form_Element_Text('sticky', NULL, NULL, _t('<span class="themeConfig"><h3>文章推荐</h3></span><div class="themeConfiginfo">填入文章的cid,多个cid以`|`符号隔开</div>置顶文章'), _t('支持多个置顶文章,如: 1|2|3. 填写文章的cid,不填写则不置顶'));
    $form->addInput($sticky);
    $listmodel = new Typecho_Widget_Helper_Form_Element_Radio('listmodel',
    array('0'=> _t('卡片模式'), '1'=> _t('列表模式')),'0', _t('<span class="themeConfig"><h3>个性化设置</h3></span><div class="themeConfiginfo">根据需求设置,部分功能需要搭配插件使用</div>列表模式'));
    $form->addInput($listmodel);
    $pageprev = new Typecho_Widget_Helper_Form_Element_Radio('pageprev',
    array('0'=> _t('不显示翻页页码'), '1'=> _t('显示页码')),'0', _t('首页文章列表页码'));
    $form->addInput($pageprev);
    $cmsmodel = new Typecho_Widget_Helper_Form_Element_Radio('cmsmodel',
    array('0'=> _t('博客模式'), '1'=> _t('CMS模式')),'0', _t('显示模式'));
    $form->addInput($cmsmodel);
    $friendlink = new Typecho_Widget_Helper_Form_Element_Radio('friendlink',
    array('0'=> _t('关闭'), '1'=> _t('开启')),
    '0', _t('首页友情链接'), _t('选择"开启"在首页显示友情链接。默认关闭,开启前请先安装插件'));
    $form->addInput($friendlink);
    $gonggao = new Typecho_Widget_Helper_Form_Element_Textarea('gonggao', NULL, NULL, _t('站点公告'), _t('<b style=color:red>使用格式: </b><b>标题|链接|图标</b><br>多条公告回车分隔.链接和图标可以为空.图标使用Font Awesome,如: fa-regular fa-bell'));
    $form->addInput($gonggao);
    $rewardAlipayQrUrl = new Typecho_Widget_Helper_Form_Element_Text(
        'rewardAlipayQrUrl',
        NULL,
        NULL,
        _t('<span class="themeConfig"><h3>赞赏设置</h3></span><div class="themeConfiginfo">用于文章页赞赏弹窗</div>支付宝赞赏二维码'),
        _t('填写二维码图片地址（不填则不显示）')
    );
    $form->addInput($rewardAlipayQrUrl);
    $rewardWxQrUrl = new Typecho_Widget_Helper_Form_Element_Text(
        'rewardWxQrUrl',
        NULL,
        NULL,
        _t('微信赞赏二维码'),
        _t('填写二维码图片地址（不填则不显示）')
    );
    $form->addInput($rewardWxQrUrl);
    $adlisttop = new Typecho_Widget_Helper_Form_Element_Textarea('adlisttop', NULL, NULL, _t('<span class="themeConfig"><h3>广告管理</h3></span><div class="themeConfiginfo">广告位设置</div>文章列表上方广告位'), _t('支持HTML'));
    $form->addInput($adlisttop);
    $adlistfoot = new Typecho_Widget_Helper_Form_Element_Textarea('adlistfoot', NULL, NULL, _t('文章列表下方广告位'), _t('支持HTML'));
    $form->addInput($adlistfoot);
    $articletop = new Typecho_Widget_Helper_Form_Element_Textarea('articletop', NULL, NULL, _t('文章页顶部广告位'), _t('支持HTML'));
    $form->addInput($articletop);
    $articlemid = new Typecho_Widget_Helper_Form_Element_Textarea('articlemid', NULL, NULL, _t('文章页中部广告位'), _t('支持HTML'));
    $form->addInput($articlemid);
    $articlefoot = new Typecho_Widget_Helper_Form_Element_Textarea('articlefoot', NULL, NULL, _t('文章页底部广告位'), _t('支持HTML'));
    $form->addInput($articlefoot);
    $addhead = new Typecho_Widget_Helper_Form_Element_Textarea('addhead', NULL, NULL, _t('<span class="themeConfig"><h3>网站验证</h3></span><div class="themeConfiginfo">若开启无刷新加载，请在标签上加上data-instant属性</div>网站验证代码'), _t('支持html代码'));
    $form->addInput($addhead);
    $tongji = new Typecho_Widget_Helper_Form_Element_Textarea('tongji', NULL, '', _t('<br><span class="themeConfig"><h3>页脚设置</h3></span><div class="themeConfiginfo">所有页面生效设置</div>网站统计代码'), _t('支持HTML'));
    $form->addInput($tongji);
    $footerinfo = new Typecho_Widget_Helper_Form_Element_Textarea('footerinfo', NULL, '<a href="/feed" target="_blank" title="RSS订阅"><i class="fa-solid fa-rss fa-2x"></i></a>
<a href="https://jiong.us/@sun" target="_blank" title="Mastodon"><i class="fa-brands fa-mastodon fa-2x"></i></a>
<a href="https://discord.gg/RUUcPEQKNt" target="_blank" title="Discord"><i class="fa-brands fa-discord fa-2x"></i></a>
<a href="https://t.me/imsunpw" target="_blank" title="Telegram"><i class="fa-brands fa-telegram fa-2x"></i></a>
<a href="mailto:imsunpw@gmail.com" target="_blank" title="Email"><i class="fa-solid fa-envelope fa-2x"></i></a>
<a href="/sitemap.xml" target="_blank" title="Sitemap"><i class="fa-solid fa-sitemap fa-2x"></i></a>', _t('底部关于我们'), _t('支持HTML'));
    $form->addInput($footerinfo);
    $footercopyright = new Typecho_Widget_Helper_Form_Element_Textarea('footercopyright', NULL, '<b>版权所有 转载请注明出处</b>', _t('底部版权信息'), _t('支持HTML'));
    $form->addInput($footercopyright);
    $ICP = new Typecho_Widget_Helper_Form_Element_Text('ICP', NULL, NULL, _t('ICP 备案号'), _t('展示网站备案ICP号'));
    $form->addInput($ICP);
    $showsidebar = new Typecho_Widget_Helper_Form_Element_Radio('showsidebar',
    array('0'=> _t('关闭'), '1'=> _t('显示')),
    '0', _t('<br><span class="themeConfig"><h3>侧边栏设置</h3></span><div class="themeConfiginfo">如果选择关闭侧边栏则下方的侧边栏显示设置将无效</div>全局侧边栏显示'), _t('选择关闭则侧边栏在所有页面均不显示'));
    $form->addInput($showsidebar);
    $bgUrl = new Typecho_Widget_Helper_Form_Element_Text('bgUrl', NULL, NULL, _t('自定义背景图片地址'), _t('用于侧边栏个人信息展示的背景图片'));
    $form->addInput($bgUrl);
    $sidebarHotPostsLimit = new Typecho_Widget_Helper_Form_Element_Text(
        'sidebarHotPostsLimit',
        NULL,
        '',
        _t('侧边栏热评文章数量'),
        _t('不填写则默认显示 5 条')
    );
    $form->addInput($sidebarHotPostsLimit);
    $sidebarHotViewsLimit = new Typecho_Widget_Helper_Form_Element_Text(
        'sidebarHotViewsLimit',
        NULL,
        '',
        _t('侧边栏热门文章数量'),
        _t('不填写则默认显示 5 条')
    );
    $form->addInput($sidebarHotViewsLimit);
    $sidebarTagsLimit = new Typecho_Widget_Helper_Form_Element_Text(
        'sidebarTagsLimit',
        NULL,
        '',
        _t('侧边栏热门标签数量'),
        _t('不填写则默认显示 50 条')
    );
    $form->addInput($sidebarTagsLimit);
    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        [
            'ShowSearch'         => _t('显示搜索框'),
            'ShowAdmin'          => _t('显示作者信息'),
            'ShowRecentPosts'    => _t('显示最新文章'),
            'ShowHotViews'       => _t('显示热门文章'),
            'ShowHotPosts'       => _t('显示热评文章'),
            'ShowRecentComments' => _t('显示最近回复'),
            'ShowTags'           => _t('显示标签云')
        ],
        ['ShowSearch', 'ShowAdmin', 'ShowRecentPosts', 'ShowHotViews', 'ShowHotPosts', 'ShowRecentComments', 'ShowTags'],
        _t('侧边栏显示')
    );
    $form->addInput($sidebarBlock->multiMode());
    if (function_exists('render_theme_backup_section')) {
        render_theme_backup_section();
    }
}

function themeFields($layout) {
    $summary= new Typecho_Widget_Helper_Form_Element_Textarea('summary', NULL, NULL, _t('文章摘要'), _t('自定义摘要'));
    $layout->addItem($summary);
    $cover= new Typecho_Widget_Helper_Form_Element_Text('cover', NULL, NULL, _t('文章封面'), _t('自定义文章封面'));
    $layout->addItem($cover);
}
