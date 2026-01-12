<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div id="sidebar" class="animated fadeInRight col-lg-4 d-none d-lg-block">
    <div class="sidebar-main">
        <?php if (!empty($this->options->sidebarBlock) && in_array('ShowSearch', $this->options->sidebarBlock)): ?>    
        <div class="p-block">
            <div> 
                <span class="t-lg border-bottom border-primary puock-text pb-2">
                    <i class="fa fa-search mr-1"></i>文章搜索
                </span> 
            </div>
            <div class="mt20">
                <form class="global-search-form" action="<?php $this->options->siteUrl(); ?>" method="get">
                    <div class="input-group"> 
                        <input type="text" name="s" class="form-control t-md" placeholder="输入关键字回车搜索"> 
                    </div>
                </form>
            </div>
        </div>
        <?php endif;$stats = get_site_statistics();if (!empty($this->options->sidebarBlock) && in_array('ShowAdmin', $this->options->sidebarBlock)): ?>
        <!-- 个人信息 -->   
         <div class="widget-puock-author widget">
            <div class="header" style="background-image: url('<?php echo !empty($this->options->bgUrl) ? $this->options->bgUrl : $this->options->themeUrl('assets/img/cover.png'); ?>')">
                <img src='<?php $this->options->themeUrl('assets/img/load.svg'); ?>' class='lazy avatar' data-src='<?php echo $stats['avatar']; ?>' alt='<?php echo $stats['nickname']; ?>' title='<?php echo $stats['nickname']; ?>' />
            </div>
            <div class="content t-md puock-text">
                <div class="text-center p-2">
                    <div class="t-lg"><?php echo $stats['nickname']; ?></div>
                    <div class="mt10 t-sm"><?php $this->options->description(); ?></div>
                </div>
                <div class="row mt10">
                    <div class="col-3 text-center">
                        <div class="c-sub t-sm">用户数</div>
                        <div><?php echo $stats['userCount']; ?></div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="c-sub t-sm">文章数</div>
                        <div><?php echo $stats['postCount']; ?></div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="c-sub t-sm">评论数</div>
                        <div><?php echo $stats['commentCount']; ?></div>
                    </div>
                    <div class="col-3 text-center">
                        <div class="c-sub t-sm">阅读量</div>
                        <div><?php echo $stats['totalViews']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif;if (!empty($this->options->sidebarBlock) && in_array('ShowRecentPosts', $this->options->sidebarBlock)): ?>
        <!-- 最新文章 -->
        <div class="pk-widget p-block ">
            <div>
                <span class="t-lg border-bottom border-primary puock-text pb-2">
                    <i class="fa fa-chart-simple mr-1"></i>最新文章
                </span>
            </div>
            <div class="mt20">
                <?php \Widget\Contents\Post\Recent::alloc() ->parse('<div class="media-link mt20"><h2 class="t-lg t-line-1" title="{title}"><i class="fa fa-angle-right t-sm c-sub mr-1"></i> <a class="a-link t-w-400 t-md" title="{title}" href="{permalink}">{title}</a> </h2></div>'); ?>   
            </div>
        </div>
    <?php endif;if (!empty($this->options->sidebarBlock) && in_array('ShowHotPosts', $this->options->sidebarBlock)):
    $hotPostsLimit = (int)($this->options->sidebarHotPostsLimit ?? 0);
    if ($hotPostsLimit <= 0) {
        $hotPostsLimit = 5;
    }
    $db = Typecho_Db::get();
    $select = $db->select(
        'table.contents.cid',
        'table.contents.title',
        'table.contents.slug',
        'table.contents.created',
        'table.contents.authorId',
        'table.contents.type',
        'table.contents.status',
        'table.contents.commentsNum',
        'table.contents.order',
        'table.contents.template'
    )
    ->from('table.contents')
    ->where('table.contents.type = ?', 'post')
    ->where('table.contents.status = ?', 'publish')
    ->where('table.contents.password IS NULL')
    ->order('table.contents.commentsNum', Typecho_Db::SORT_DESC)
    ->limit($hotPostsLimit);
    
    try {
        $hotPosts = $db->fetchAll($select);
    } catch (Exception $e) {
        $hotPosts = [];
    }
    if (!empty($hotPosts)): ?>
    <!-- 热门文章 -->
    <div class="pk-widget p-block">
        <div> 
            <span class="t-lg border-bottom border-primary puock-text pb-2">
                <i class="fa-solid fa-fire"></i> 热门文章
            </span> 
        </div>
        <div class="mt20">
            <?php foreach ($hotPosts as $post):
                // 更可靠的获取文章链接方式
                $widget = Typecho_Widget::widget('Widget_Contents_Post_Recent');
                $permalink = '';
                try {
                    // 方法1：使用Typecho的Router类
                    $permalink = Typecho_Router::url('post', $post, $this->options->index);
                    // 方法2：或者使用辅助函数（如果方法1不行）
                    if (empty($permalink)) {
                        $widget->push($post);
                        $permalink = $widget->permalink;
                        $widget->pop();
                    }
                    if (empty($post['title']) || empty($permalink)) {
                        continue;
                    }
                } catch (Exception $e) {
                    continue;
                }
                ?>
                <div class="media-link mt20">
                    <h2 class="t-lg t-line-1" title="<?php echo htmlspecialchars($post['title']); ?>"> 
                        <i class="fa fa-angle-right t-sm c-sub mr-1"></i> 
                        <a class="a-link t-w-400 t-md" 
                           title="<?php echo htmlspecialchars($post['title']); ?>" 
                           href="<?php echo htmlspecialchars($permalink); ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a> 
                    </h2>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif;endif;if (!empty($this->options->sidebarBlock) && in_array('ShowRecentComments', $this->options->sidebarBlock)):
        // 设置参数来排除管理员评论
        $comments = \Widget\Comments\Recent::alloc(array(
            'ignoreAuthor' => true  // 这里添加参数来排除作者/管理员评论
        )); 
        ?>
        <!-- 最近评论 -->
        <div class="pk-widget p-block ">
            <div> 
                <span class="t-lg border-bottom border-primary puock-text pb-2">
                    <i class="fa fa-comment mr-1"></i>最新评论
                </span> 
            </div>
            <div class="mt20">
                <div class="min-comments t-md">
                    <?php \Widget\Comments\Recent::alloc()->to($comments);while ($comments->next()): ?>
                    <div class="comment t-md t-line-1"> 
                        <?php echo $comments->gravatar('40', ''); ?>
                        <a class="puock-link" href="<?php $comments->permalink(); ?>"> 
                            <span class="ta3 link-hover"><?php $comments->author(false); ?></span>
                        </a> 
                        <span class="c-sub t-w-400"><?php $comments->excerpt(35, '...'); ?></span> 
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif;if (!empty($this->options->sidebarBlock) && in_array('ShowTags', $this->options->sidebarBlock)):
    $tagsLimit = (int)($this->options->sidebarTagsLimit ?? 0);
    if ($tagsLimit <= 0) {
        $tagsLimit = 5;
    }
    $tags = \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=' . $tagsLimit);
    if ($tags->have()):
    $colors = ['bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info'];?>
<!-- 热门标签 -->
<div class="pk-widget p-block ">
    <div> 
        <span class="t-lg border-bottom border-primary puock-text pb-2">
            <i class="fa fa-tag mr-1"></i>标签云
        </span> 
    </div>
    <div class="mt20">  
        <div class="widget-puock-tag-cloud">          
            <?php while ($tags->next()): ?>
            <!-- 使用随机数选择颜色类 -->
            <a href="<?php $tags->permalink(); ?>" title="<?php $tags->name(); ?>" 
                class="badge d-none d-md-inline-block <?php echo $colors[array_rand($colors)]; ?> ahfff">
                <?php $tags->name(); ?>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif;endif; ?>
</div>
</div>