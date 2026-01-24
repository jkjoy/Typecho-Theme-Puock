<?php
/**
 * 瞬间页面
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>
<div id="breadcrumb" class="animated fadeInUp">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="a-link" href="<?php $this->options->siteUrl(); ?>">首页</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php $this->title() ?></li>
        </ol>
    </nav>
</div>
<div id="page-moments">
    <div class="row">
        <div id="post-main" class="col-lg-8 col-md-12<?php if ($this->options->showsidebar): ?> animated fadeInLeft<?php else:endif; ?>">
           <div class="p-block">
                <div><h1 class="mb-0 puock-text t-xxl"><?php $this->title() ?></h1></div>
            </div>

            <?php
            $page = 1;
            if (isset($this->request->page)) {
                $page = max(1, (int)$this->request->page);
            }
            ?>
            <div class="mt20">
                <?php Puock_Plugin::momentsOutputPuock(20, $page, 'PUBLIC', 'markdown'); ?>
            </div>
        </div>
    <?php if ($this->options->showsidebar): $this->need('sidebar.php'); endif; ?>
    </div>
</div>
<?php $this->need('footer.php'); ?>