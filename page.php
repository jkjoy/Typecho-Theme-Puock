<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php'); ?>
<div id="breadcrumb" class="animated fadeInUp">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="a-link" href="<?php $this->options->siteUrl(); ?>">首页</a></li>
            <li class="breadcrumb-item active " aria-current="page"><?php $this->title() ?></li>
        </ol>
    </nav>
</div>   
<div id="page-empty">
    <div id="page" class="row row-cols-1">
        <?php if ($this->options->showsidebar): ?>    
        <div id="post-main" class="col-lg-8 col-md-12 animated fadeInLeft ">
        <?php else: ?>
        <div id="post-main" class="col-lg-12 col-md-12">
        <?php endif; ?>
            <div class="p-block"><div>
            <h1 id="post-title" class="mb-0 puock-text t-xxl"><?php $this->title() ?></h1>
        </div>
        <div class="options p-flex-sbc mt20"><div>
        <div class="option puock-bg ta3 t-sm mr-1">
            <i class="fa-regular fa-eye mr-1"></i>
            <span id="post-views">
                <span class="view">浏览：<?php get_post_view($this) ?></span>
            </span>
            <span>次阅读</span>
        </div> 
        <a href="#comments">
            <div class="option puock-bg ta3 t-sm mr-1">
                <i class="fa-regular fa-comment mr-1"></i><?php $this->commentsNum('0 评论', '1 评论', '%d 评论'); ?>
            </div>
        </a>
    <?php if($this->user->hasLogin() && $this->user->pass('editor', true)): ?>    
    <a target="_blank" href="<?php $this->options->adminUrl('write-page.php?cid=' . $this->cid); ?>"> 
        <div class="option puock-bg ta3 t-sm mr-1">
            <i class="fa-regular fa-pen-to-square mr-1"></i>编辑 
        </div> 
    </a>                 
    <?php endif; ?>        
    </div>
    <div>
        <div class="option puock-bg ta3 t-sm mr-1 d-none d-lg-inline-block post-main-size">
            <i class="fa fa-up-right-and-down-left-from-center"></i>
        </div>
    </div>
</div>
<div class="mt20 puock-text entry-content show-link-icon">
    <p class="fs12 c-sub">
<?php
$modified = $this->modified;
$now = time();
$days = ($now - $modified) / 86400;
 
if($days > 180){
    echo '<i class="fa fa-circle-exclamation me-1"></i> 本文最后更新于 ' . date('Y-m-d H:i', $modified) . '，文中所关联的信息可能已发生改变，请知悉！';
}
?>
</p>
<?php
// 手动解析短代码以兼容 Typecho 1.3.0
// 获取页面内容
ob_start();
$this->content();
$content = ob_get_clean();
echo parse_shortcodes($content, $this);
?>
</div>
</div>    
<?php if ($this->allow('comment')):$this->need('comments.php');endif; ?>
</div>   
<?php if ($this->options->showsidebar): $this->need('sidebar.php');endif; $this->need('footer.php'); ?>