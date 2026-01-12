<?php 
/**
 * 友情链接
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
<div id="page-links">
    <div id="page" class="row row-cols-1">
        <div id="posts" class="col-12 animated fadeInLeft ">
            <div class="puock-text no-style">
                <div class="p-block links-main" id="page-links-217">
                    <h6><?php $this->title() ?></h6>
                    <div class="links-main-box row t-sm"> 
                        <?php Puock_Plugin::output('<a class="link-item a-link col-lg-3 col-md-4 col-sm-6 col-6" href="{url}" target="_blank" rel="me" title="{name}" data-bs-toggle="tooltip"><div class="clearfix puock-bg"><img alt="{name}" src="{image}" class="lazy md-avatar" data-src="{image}" alt="{name}"><div class="info"><p class="ml-1 text-nowrap text-truncate">{name}</p><p class="c-sub ml-1 text-nowrap text-truncate">{title}</p></div></div></a>');?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php if ($this->allow('comment')):$this->need('comments.php');endif; ?>    
</div>
<?php $this->need('footer.php'); ?>