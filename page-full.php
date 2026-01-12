<?php 
/**
 * 宽屏页面
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
<div id="page">
<div class="row row-cols-1">
<div id="posts" class="col-12">
<div class="puock-text no-style">
<p><?php $this->content(); ?></p>
</div>
</div>
</div>
<?php if ($this->allow('comment')):$this->need('comments.php');endif; ?>
</div>
<?php $this->need('footer.php'); ?>