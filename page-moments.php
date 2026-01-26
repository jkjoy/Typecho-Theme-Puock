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
            <?php
            $limit = 5;
            $visibility = 'PUBLIC';
            $render = 'markdown';
            $page = 1;
            if (isset($this->request->page)) {
                $page = max(1, (int)$this->request->page);
            }
            ?>
            <div class="mt20">
                <?php Puock_Plugin::momentsOutputPuock($limit, $page, $visibility, $render); ?>
            </div>
            <?php
            $options = Typecho_Widget::widget('Widget_Options');
            if ($limit > 0 && isset($options->plugins['activated']['Puock'])) {
                $db = Typecho_Db::get();
                $prefix = $db->getPrefix();
                $visibilityFilter = strtoupper(trim($visibility));
                $countSql = $db->select(array('COUNT(*)' => 'num'))
                    ->from($prefix . 'moments')
                    ->where('rowStatus = ?', 'NORMAL');

                if ($visibilityFilter !== 'ALL') {
                    $visibilityFilter = in_array($visibilityFilter, array('PUBLIC', 'PRIVATE'), true) ? $visibilityFilter : 'PUBLIC';
                    $countSql->where('visibility = ?', $visibilityFilter);
                }

                $countRow = $db->fetchObject($countSql);
                $total = $countRow ? (int)$countRow->num : 0;
                $totalPages = $total > 0 ? (int)ceil($total / $limit) : 0;

                if ($totalPages > 1) {
                    $baseUrl = preg_replace('/\\?.*/', '', $this->permalink);
                    $pageBase = $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&') . 'page=';
                    $window = 2;
                    $start = max(1, $page - $window);
                    $end = min($totalPages, $page + $window);
                    ?>
                    <div class="mt20 p-flex-s-right">
                        <ul class="pagination comment-ajax-load">
                            <?php if ($page > 1): ?>
                                <li class="prev">
                                    <a href="<?php echo htmlspecialchars($pageBase . ($page - 1), ENT_QUOTES, 'UTF-8'); ?>">&laquo;</a>
                                </li>
                            <?php endif; ?>
                            <?php if ($start > 1): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($pageBase . 1, ENT_QUOTES, 'UTF-8'); ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li><span>...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <li class="cur">
                                        <a href="<?php echo htmlspecialchars($pageBase . $i, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($pageBase . $i, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li><span>...</span></li>
                                <?php endif; ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($pageBase . $totalPages, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="next">
                                    <a href="<?php echo htmlspecialchars($pageBase . ($page + 1), ENT_QUOTES, 'UTF-8'); ?>">&raquo;</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    <?php if ($this->options->showsidebar): $this->need('sidebar.php'); endif; ?>
    </div>
</div>
<?php $this->need('footer.php'); ?>