<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, minimum-scale=1.0">
	    <script>
	        (function () {
	            if (!window.trustedTypes || !window.trustedTypes.createPolicy) return;
	
	            function ensureDefaultPolicy(win) {
	                if (!win || !win.trustedTypes || !win.trustedTypes.createPolicy) return;
	                try {
	                    win.trustedTypes.createPolicy('default', {
	                        createHTML: function (input) { return input; },
	                        createScript: function (input) { return input; },
	                        createScriptURL: function (input) { return input; }
	                    }, { allowDuplicates: true });
	                    return;
	                } catch (e) {}
	                try {
	                    win.trustedTypes.createPolicy('default', {
	                        createHTML: function (input) { return input; },
	                        createScript: function (input) { return input; },
	                        createScriptURL: function (input) { return input; }
	                    });
	                } catch (e) {}
	            }
	            try {
	                ensureDefaultPolicy(window);
	
	                // Ensure same-origin iframes (e.g. html2canvas) also have a default policy
	                // so document.write/innerHTML sinks won't be blocked by require-trusted-types-for.
	                var origAppendChild = Node.prototype.appendChild;
	                Node.prototype.appendChild = function (child) {
	                    var ret = origAppendChild.call(this, child);
	                    try {
	                        if (child && child.tagName === 'IFRAME' && child.contentWindow) {
	                            ensureDefaultPolicy(child.contentWindow);
	                        }
	                    } catch (e) {}
	                    return ret;
	                };
	
	                var origInsertBefore = Node.prototype.insertBefore;
	                Node.prototype.insertBefore = function (newNode, referenceNode) {
	                    var ret = origInsertBefore.call(this, newNode, referenceNode);
	                    try {
	                        if (newNode && newNode.tagName === 'IFRAME' && newNode.contentWindow) {
	                            ensureDefaultPolicy(newNode.contentWindow);
	                        }
	                    } catch (e) {}
	                    return ret;
	                };
	            } catch (e) {}
	        })();
	    </script>
    <meta http-equiv='content-language' content='zh_CN'>
    <title>
        <?php if (!empty($GLOBALS['puock_goto_active'])):
        echo htmlspecialchars((string)($GLOBALS['puock_goto_title'] ?? '即将离开本站'), ENT_QUOTES, 'UTF-8'); ?> 
        - 
        <?php $this->options->title();else:
            $this->archiveTitle([
                'category' => _t('分类 %s 下的文章'),
                'search'   => _t('包含关键字 %s 的文章'),
                'tag'      => _t('标签 %s 下的文章'),
                'author'   => _t('%s 发布的文章')
            ], '', ' - ');
            $this->options->title();
            if ($this->is('index')) echo ' - ';
            if ($this->is('index'))
                $this->options->description();endif; ?>
    </title>
    <link rel="canonical" href="<?php $this->options->siteUrl(); ?>">
    <meta name='robots' content='max-image-preview:large' />
    <?php $this->options->addhead(); ?>
    <style id='puock-inline-css' type='text/css'>
        body {--pk-c-primary: <?php if ($this->options->primaryColor): $this->options->primaryColor() ?><?php else: ?>#A7E6F4<?php endif; ?> !important;}
        :root {--puock-block-not-tran: 80% !important;}
    </style> 
    <?php if ($this->options->icoUrl): ?>
    <link rel="icon" href="<?php $this->options->icoUrl() ?>" sizes="32x32" />
    <link rel="apple-touch-icon" href="<?php $this->options->icoUrl() ?>" />
	<?php endif; ?>
	<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/style.lite.css'); ?>?ver=<?php echo get_theme_version(); ?>" type="text/css" media="all" />
	<link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/highlight.css'); ?>?ver=<?php echo get_theme_version(); ?>" type="text/css" media="all" />
	<script src='<?php $this->options->themeUrl('assets/js/jquery.min.js'); ?>?ver=<?php echo get_theme_version(); ?>' type="text/javascript"></script>
	<!-- 通过自有函数输出HTML头部信息 -->
	<?php $this->header(); ?>
</head>
<body class="puock-auto custom-background">
    <div>
        <div id="header-box" class="animated fadeInDown"></div>
        <header id="header" class="animated fadeInDown blur">
            <div class="navbar navbar-dark shadow-sm">
                <div class="container"> 
                    <?php if($this->options->logoUrl): ?>
                    <a href="<?php $this->options->siteUrl(); ?>" id="logo" class="navbar-brand logo-loop-light">
                        <img id="logo-light" alt="logo" class="w-100 " src="<?php $this->options->logoUrl() ?>"> 
                        <img id="logo-dark" alt="logo" class="w-100 d-none" src="<?php $this->options->logoUrl() ?>"> 
                    </a>
                    <?php else: ?>
                    <a href="<?php $this->options->siteUrl(); ?>" id="logo" class="navbar-brand logo-loop-light"> 
                        <span class="puock-text txt-logo"><?php $this->options->title(); ?></span> 
                    </a>
                    <?php endif; ?>
                    <div class="d-none d-lg-block puock-links">
                    <div id="menus" class="t-md ">
                    <ul>
                    <?php if ($this->is('index')): ?>
                    <li class="menu-current current-menu-item"><?php else: ?><li><?php endif; ?>
                        <a class="nav-link" href="<?php $this->options->siteUrl(); ?>">
                           <?php _e('首页'); ?>
                        </a>
                    </li>
                    <li class='menu-item'>
                        <a class='ww' data-color='auto' href='#'>分类<i class="fa fa-chevron-down t-sm ml-1 menu-sub-icon"></i></a>
                        <ul class="sub-menu ">
                            <?php $categories = Typecho_Widget::widget('Widget_Metas_Category_List');while($categories->next()): ?>
                            <li class="menu-itemmenu-item-child">
                            <a href="<?php $categories->permalink(); ?>" class='ww' data-color='auto'>
                            <?php $categories->name(); ?>
                            </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </li>
                    <?php \Widget\Contents\Page\Rows::alloc()->to($pages);while ($pages->next()): ?>
                    <li class="menu-item <?php if ($this->is('page', $pages->slug)) echo ' current-menu-item current_page_item menu-current'; ?>">
                        <a class='ww'
                            href="<?php $pages->permalink(); ?>"
                            title="<?php $pages->title(); ?>">
                            <?php $pages->title(); ?>
                        </a>
                    </li>
                    <?php endwhile;if($this->user->hasLogin()): ?> 
                        <li>
                            <a data-bs-toggle="tooltip" title="用户中心" href="/admin/" target="_blank">
                            <img alt="用户中心" src="<?php $stats = get_site_statistics();echo $stats['avatar']; ?>" class="min-avatar">
                            </a>
                        </li>
	                    <?php else: ?>
	                        <li>
	                            <a data-no-instant data-bs-toggle="tooltip" title="登入" data-title="登入" class="pk-login-open" href="<?php $this->options->loginUrl(); ?>" aria-label="登入">
	                                <i class="fa fa-right-to-bracket"></i>
	                            </a>
	                        </li>
	                    <?php endif; ?>
                        <li>
                            <button type="button" class="colorMode" data-bs-toggle="tooltip" title="模式切换" aria-label="模式切换">
                                <i class="fa fa-circle-half-stroke"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="search-modal-btn" data-bs-toggle="tooltip" title="搜索" aria-label="搜索">
                                <i class="fa fa-search"></i>
                            </button>
                        </li>
                        </ul>
                        </div>
                    </div>
                    <div class="mobile-menus d-block d-lg-none p-1 puock-text"> 
                        <i class="fa fa-bars t-md mr-2 mobile-menu-s"></i> 
                        <i class="fa fa-circle-half-stroke colorMode t-md mr-2"></i> 
                        <i class="search-modal-btn fa fa-search t-md position-relative" style="top:0.5px"></i> 
                    </div>
                </div>
            </div>
        </header>
        <div id="search" class="d-none">
            <div class="w-100 d-flex justify-content-center">
                <div id="search-main" class="container p-block">
                    <form class="global-search-form" action="<?php $this->options->siteUrl(); ?>">
                        <div class="search-layout">
                            <div class="search-input"> <input required type="text" name="s" class="form-control" placeholder="请输入搜索关键字"> </div>
                            <div class="search-start"> <button type="submit" class="btn-dark btn"><i class="fa fa-search mr-1"></i>搜索</button> </div>
                            <div class="search-close-btn"> <button type="button" class="btn-danger btn ml-1 search-modal-btn"><i class="fa fa-close"></i></button> </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="mobile-menu" class="d-none">
            <div class="menus">
                <div class="p-block">
                    <div class="text-end"><i class="fa fa-close t-xl puock-link mobile-menu-close ta3"></i></div>
                    <nav>
                        <ul class='puock-links t-md'>
                            <li class='menu-item current-menu-item'>
                                <span>
                                    <a href="<?php $this->options->siteUrl(); ?>">首页</a>
                                </span>
                            </li>
                            <?php \Widget\Contents\Page\Rows::alloc()->to($pages); while ($pages->next()): ?>
                            <li class='menu-item'>
                                <span>
                                    <a class='ww' href="<?php $pages->permalink(); ?>" title="<?php $pages->title(); ?>">
                                    <?php $pages->title(); ?>
                                    </a>
                                </span>
                            </li>
                            <?php endwhile; ?>
                            <li class='menu-item'>
                            <span><a href="#">分类</a>
                            <a href="#menu" data-bs-toggle="collapse"><i class="fa fa-chevron-down t-sm ml-1 menu-sub-icon"></i></a></span>
                            <ul id="menu" class="sub-menu collapse">
                            <?php $categories = Typecho_Widget::widget('Widget_Metas_Category_List');while($categories->next()): ?>
                            <li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-child">
                            <span>
                            <a href="<?php $categories->permalink(); ?>" class='ww' data-color='auto'>
                            <?php $categories->name(); ?>
                            </a>
                            </span> 
                            </li>
                            <?php endwhile; ?>
                            </ul>
                            <?php if($this->user->hasLogin()): ?> 
                        <li>
                        <a data-bs-toggle="tooltip" title="用户中心" href="/admin/" target="_blank">
                            <img alt="用户中心" src="<?php $stats = get_site_statistics();echo $stats['avatar']; ?>" class="min-avatar">
                        </a>
                        </li>
	                    <?php else: ?>
	                    <li>
	                        <a data-no-instant data-bs-toggle="tooltip" title="登入" data-title="登入" class="pk-login-open" href="<?php $this->options->loginUrl(); ?>" aria-label="登入">
	                            <i class="fa fa-right-to-bracket"></i>
	                        </a>
	                    </li>
	                    <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
<div id="mobile-menu-backdrop" class="modal-backdrop d-none"></div>
<div id="search-backdrop" class="modal-backdrop d-none"></div>
<template id="pk-login-template">
    <div class="min-width-modal">
        <?php Typecho_Widget::widget('Widget_Security')->to($security); ?>
        <?php $pkLoginAction = Typecho_Router::url('do', array('action' => 'login', 'widget' => 'Login'), Typecho_Common::url('index.php', $this->options->rootUrl)); ?>
        <form id="front-login-form" action="<?php echo $security->getTokenUrl($pkLoginAction, $this->request->getRequestUrl()); ?>" method="post">
            <div class="mb15">
                <label for="_front_login_username" class="form-label">用户名/邮箱</label>
                <input type="text" name="name" class="form-control form-control-sm" id="_front_login_username" required placeholder="请输入用户名或邮箱">
            </div>
            <div class="mb15">
                <label for="_front_login_password" class="form-label">密码</label>
                <input type="password" name="password" class="form-control form-control-sm" required id="_front_login_password" placeholder="请输入密码">
            </div>
            <div class="mb15 d-flex justify-content-center wh100">
                <button class="btn btn-ssm btn-primary mr5" type="submit">
                    <i class="fa fa-right-to-bracket"></i> 立即登录
                </button>
            </div>
            <input type="hidden" name="referer" value="<?php echo htmlspecialchars($this->request->getRequestUrl()); ?>">
        </form>
    </div>
</template>
<main id="content" class="mt15 container" role="main"> <!--全局上方-->
<?php if($this->options->adlisttop): ?>
<div class="puock-text p-block t-md ad-global-top"><?php $this->options->adlisttop(); ?></div>
<?php endif;if($this->options->gonggao): ?>
<div class="puock-text p-block t-md global-top-notice">
<div data-swiper="init" data-swiper-class="global-top-notice-swiper" data-swiper-args='{"direction":"vertical","autoplay":{"delay":3000,"disableOnInteraction":false},"loop":true}'>
<div class="swiper global-top-notice-swiper">
<div class="swiper-wrapper">
<?php
// 获取公告内容
$gonggao = $this->options->gonggao;
if ($gonggao) {
    // 按行分割
    $lines = explode("\n", $gonggao);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        // 只处理格式正确的行
        if (count($parts) >= 3) {
            $title = trim($parts[0]);
            $url = trim($parts[1]);
            $icon = trim($parts[2]);
            $href = $url !== '' ? htmlspecialchars($url) : '';
            // 图标为空时使用默认
            $icon_class = $icon !== '' ? $icon : 'fa-regular fa-bell';
            // 输出 HTML
            echo '<div class="swiper-slide t-line-1">';
            if ($href !== '') {
                echo '<a class="ta3" data-no-instant href="' . $href . '">';
                echo '<span class="notice-icon"><i class="' . $icon_class . '"></i></span>';
                echo '<span>' . htmlspecialchars($title) . '</span>';
                echo '</a>';
            } else {
                echo '<span class="ta3">';
                echo '<span class="notice-icon"><i class="' . $icon_class . '"></i></span>';
                echo '<span>' . htmlspecialchars($title) . '</span>';
                echo '</span>';
            }
            echo '</div>';
        }
    }
}
?>
</div>
</div>
</div>
</div>
<?php endif; ?>            