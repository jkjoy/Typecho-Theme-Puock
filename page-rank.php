<?php 
/**
 * 评论墙
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<div id="breadcrumb" class="animated fadeInUp">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="a-link" href="<?php $this->options->siteUrl(); ?>">首页</a></li>
            <li class="breadcrumb-item active " aria-current="page"><?php $this->title() ?></li>
        </ol>
    </nav>
</div>
<?php $commenters = getAllCommenters(); ?>
<div id="page-reads">
    <div id="page" class="row row-cols-1">
        <?php if ($this->options->showsidebar): ?>    
        <div id="posts" class="col-lg-8 col-md-12 animated fadeInLeft ">
        <?php else: ?>
        <div id="posts" class="col-lg-12 col-md-12">
        <?php endif; ?>
            <div class="p-block puock-text">
                <h2 class="t-lg"><?php $this->title() ?></h2>
                <div class="mt20 row pd-links">
                <?php foreach ($commenters as $commenter): ?>
                    <div class="col col-6 col-md-4 col-lg-3 pl-0">
                        <div class="p-2 text-truncate text-nowrap"> 
                        <?php if ($commenter['url']): ?>
                            <a href="<?php echo htmlspecialchars($commenter['url']); ?>" target="_blank" rel="nofollow"> 
                                <img data-bs-toggle="tooltip" src='<?php $this->options->themeUrl('assets/img/load.svg'); ?>' class='lazy md-avatar' data-src='<?php echo htmlspecialchars($commenter['avatar']); ?>' title="<?php echo htmlspecialchars($commenter['nickname']); ?>" alt="<?php echo htmlspecialchars($commenter['nickname']); ?>"> 
                                <span class="t-sm">
                                <span class="c-sub">+(<?php echo $commenter['comment_count']; ?>)&nbsp;</span>
                                    <?php echo htmlspecialchars($commenter['nickname']); ?></span> 
                            </a> 
                        <?php else: ?>  
                            <img data-bs-toggle="tooltip" src='<?php $this->options->themeUrl('assets/img/load.svg'); ?>' class='lazy md-avatar' data-src='<?php echo htmlspecialchars($commenter['avatar']); ?>' title="<?php echo htmlspecialchars($commenter['nickname']); ?>" alt="<?php echo htmlspecialchars($commenter['nickname']); ?>"> 
                    <span class="t-sm">
                        <span class="c-sub">+(<?php echo $commenter['comment_count']; ?>)&nbsp;</span>
                        <?php echo htmlspecialchars($commenter['nickname']); ?>
                    </span> 
                    <?php endif; ?> 
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php if ($this->options->showsidebar):$this->need('sidebar.php');endif;$this->need('footer.php'); ?>