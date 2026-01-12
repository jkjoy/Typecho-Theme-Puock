<?php
/**
 * Pouck theme for Typecho
 * 老孙移植
 * 
 * @package Typecho Pouck Theme
 * @author 老孙博客
 * @version 1.3.1
 * @link http://www.imsun.org
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
$this->need('sticky.php');
?>
<div class="row row-cols-1">
<?php if ($this->options->showsidebar): ?>    
<div class="col-lg-8 col-md-12 animated fadeInLeft ">
<div class="animated fadeInLeft ">
<?php else: ?>
<div class="col-lg-12 col-md-12">
<div class="row box-plr15">
<?php endif; ?>
<div> <!--文章列表-->
<div id="posts">
<?php if ($this->options->listmodel): ?>
<div class=" mr-0 ml-0">  
<?php while ($this->next()):
$coverImage = getPostCover($this->content, $this->cid);
$colors = ['bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info'];
?>
<article class="block card-plain post-item p-block post-item-list">
<div class="thumbnail"> 
<a class="t-sm ww" href="<?php $this->permalink() ?>"> 
<img title="<?php $this->title() ?>" alt="<?php $this->title() ?>" src='<?php $this->options->themeUrl('assets/img/load.svg'); ?>' class='lazy' data-src='<?php echo $coverImage; ?>' /> 
</a> 
</div>
<div class="post-info">
<div class="info-top">
<h2 class="info-title"> 
<?php if (isset($this->isSticky) && $this->isSticky): echo $this->stickyHtml; endif;foreach($this->categories as $category): ?>
<a class="badge d-none d-md-inline-block <?php echo $colors[array_rand($colors)]; ?> ahfff" href="<?php echo $category['permalink']; ?>">
<i class="fa-regular fa-folder-open"></i> <?php echo $category['name']; ?>
</a> 
<?php endforeach; ?>
<a class="a-link" title="<?php $this->title() ?>" href="<?php $this->permalink() ?>"><?php $this->title() ?></a> 
</h2>
<div class="info-meta c-sub text-2line d-none d-md-block"><?php $this->excerpt(200, '...'); ?></div>
</div>
<div class="info-footer w-100">
<div> 
<span class="t-sm c-sub"> 
<span class="mr-2">
<i class="fa-regular fa-eye mr-1"></i>
<span class="view">浏览：<?php get_post_view($this) ?></span>
<span class="t-sm d-none d-sm-inline-block">次阅读</span>
</span> 
<a class="c-sub-a" href="<?php $this->permalink() ?>#comments"> 
<i class="fa-regular fa-comment mr-1"></i><?php $this->commentsNum('0', '1', '%d'); ?>
<span class="t-sm d-none d-sm-inline-block">个评论</span>
</a> 
</span> 
</div>
<div> 
<?php foreach($this->categories as $category): ?>
<a class="c-sub-a t-sm ml-md-2 line-h-20 d-inline-block d-md-none" href="<?php echo $category['permalink']; ?>">
<i class="fa-regular fa-folder-open"></i> <?php echo $category['name']; ?>
</a> 
<?php endforeach; ?>
<span class="t-sm ml-md-2 c-sub line-h-20 d-none d-md-inline-block">
<i class="fa-regular fa-clock"></i> <?php $this->date(); ?>
</span> 
</div>
</div>
</div> 
<span class="title-l-c bg-primary"></span>
</article>  
<?php endwhile;
$pageprev = $this->options->pageprev ?? '0';
if ($pageprev == '1' && $this->have()): 
?>
<div class="mt20 p-flex-s-right" data-no-instant>
<?php $this->pageNav('&laquo;', '&raquo;', 1, '...', array(
                'wrapTag' => 'ul',
                'wrapClass' => 'pagination comment-ajax-load',
                'itemTag' => 'li',
                'textTag' => 'span',
                'currentClass' => 'cur',
                'prevClass' => 'prev',
                'nextClass' => 'next'
            )); ?>
</div>
<?php endif; ?>
</div>
<?php else: $this->need('card.php'); endif; ?>
</div>
</div>
</div>
</div>
<?php if ($this->options->showsidebar):$this->need('sidebar.php'); endif;$this->need('footer.php'); ?>