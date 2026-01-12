<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="row row-cols-1 animated fadeInUp " id="magazines">
<?php $this->widget('Widget_Metas_Category_List')->to($categories);while($categories->next()): ?>
<div class="col-md-6 pr-0 magazine">
    <div class="p-block">
        <div> 
            <span class="t-lg puock-text pb-2 d-inline-block border-bottom border-primary">
                <a class="ta3 a-link" href="<?php $categories->permalink(); ?>">
                    <i class="fa fa-layer-group"></i>&nbsp;<?php $categories->name(); ?>
                </a>
            </span> 
        </div>
        <?php 
        $this->widget('Widget_Archive@category_' . $categories->mid, 'pageSize=5&type=category', 'mid=' . $categories->mid)->to($posts);
        if($posts->have()):$postCount = 0;while($posts->next()):$postCount++;$coverImage = getPostCover($posts->content, $posts->cid);if($postCount === 1): ?>
        <!-- 第一篇文章使用特殊样式 -->
        <div class="mtb10 magazine-media-item"> 
            <a class="img ww" href="<?php $posts->permalink() ?>"> 
                <img alt="<?php $posts->title() ?>" src='<?php echo $coverImage; ?>' height="80" width="120"/> 
            </a>
            <div class="t-line-1">
                <h2 class="t-lg t-line-1">
                    <a class="a-link" title="<?php $posts->title() ?>" href="<?php $posts->permalink() ?>">
                        <?php $posts->title() ?>
                    </a> 
                </h2>
                <div class="t-md c-sub text-2line"><?php $posts->excerpt(200, '...'); ?></div>
            </div>
        </div>
        <?php else: ?>
        <!-- 其他文章使用原有样式 -->
        <div class="media-link media-row-2">
            <div class="t-lg t-line-1 row">
                <div class="col-lg-9 col-12 text-nowrap text-truncate"> 
                    <i class="fa fa-angle-right t-sm c-sub mr-1"></i> 
                    <a class="a-link t-w-400 t-md" title="<?php $posts->title(); ?>" href="<?php $posts->permalink() ?>"><?php $posts->title(); ?></a> 
                </div>
                <div class="col-lg-3 text-end d-none d-lg-block"> 
                    <span class="c-sub t-sm"><?php $posts->date(); ?></span> 
                </div>
            </div>
        </div>
        <?php endif;endwhile;else: ?>
            <p>该分类下没有文章。</p>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
</div>