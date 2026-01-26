<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; 
$this->need('header.php'); 
// 计算字数使用纯文本，不影响实际渲染内容
	$plainContent = strip_tags($this->content);
	$wordCount = mb_strlen($plainContent, 'UTF-8');
			$pkPosterExcerptSource = trim((string)preg_replace('/\\s+/u', ' ', $plainContent));
			$pkPosterExcerpt = $pkPosterExcerptSource;
			if (mb_strlen($pkPosterExcerpt, 'UTF-8') > 60) {
			    $pkPosterExcerpt = mb_substr($pkPosterExcerpt, 0, 60, 'UTF-8') . '...';
			}
				$pkLogoUrl = trim((string)($this->options->logoUrl ?? ''));
?>
<div id="breadcrumb" class="animated fadeInUp">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="a-link" href="<?php $this->options->siteUrl(); ?>">首页</a></li>
            <li class="breadcrumb-item"><?php $this->category(','); ?></li>
            <li class="breadcrumb-item active " aria-current="page">正文</li>
        </ol>
    </nav>        
</div>
<!--内页上方-->
<div id="post" class="container mt20">
<?php if ($this->options->articletop): ?>
<div class="puock-text p-block t-md ad-page-top"><?php $this->options->articletop(); ?></div>
<?php endif; ?>
<div class="row row-cols-1 post-row">
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
            <span class="view"><?php get_post_view($this) ?></span>
        </span>
        <span>次浏览</span>
    </div> 
    <a href="#comments">
        <div class="option puock-bg ta3 t-sm mr-1">
            <i class="fa-regular fa-comment mr-1"></i><?php $this->commentsNum('0 评论', '1 评论', '%d 评论'); ?>
        </div>
    </a>
    <?php if($this->user->hasLogin() && $this->user->pass('editor', true)): ?>    
    <a target="_blank" href="<?php $this->options->adminUrl('write-post.php?cid=' . $this->cid); ?>"> 
        <div class="option puock-bg ta3 t-sm mr-1">
            <i class="fa-regular fa-pen-to-square mr-1"></i>编辑 
        </div> 
    </a>                 
    <?php endif; ?>
</div>
<div class="option puock-bg ta3 t-sm mr-1 d-none d-lg-inline-block post-main-size">
    <i class="fa fa-up-right-and-down-left-from-center"></i>
</div>
</div>
<div class="mt20 entry-content-box">
<div class="entry-content show-link-icon content-main puock-text ">
<p class="fs12 c-sub no-indent"> <i class="fa-regular fa-clock"></i> &nbsp;本文共计<?php echo $wordCount; ?>字，预计需要花费 <?php echo ceil($wordCount / 800); ?>分钟才能阅读完成。 </p>
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
	// 获取文章内容
	ob_start();
	$this->content();
	$content = ob_get_clean();
		$pkPosterCover = trim((string)getPostCover($content, $this->cid, $this->fields ?? null));
		ob_start();
		$this->title();
		$pkPosterTitle = trim(ob_get_clean());
		$pkRewardAlipay = trim((string)($this->options->rewardAlipayQrUrl ?? ''));
		$pkRewardWx = trim((string)($this->options->rewardWxQrUrl ?? ''));
		$pkNormalizeUrl = function ($raw) {
		    $raw = trim((string)$raw);
		    if ($raw === '') return '';
		    if (preg_match('#^(data|blob):#i', $raw)) return $raw;
		    if (preg_match('#^https?://#i', $raw)) return $raw;
			    $site = (string)$this->options->siteUrl;
			    $site = $site !== '' ? rtrim($site, '/') . '/' : (rtrim((string)($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/') . '/');
			    $scheme = parse_url($site, PHP_URL_SCHEME) ?: ($_SERVER['REQUEST_SCHEME'] ?? 'https');
		    if (str_starts_with($raw, '//')) {
		        return $scheme . ':' . $raw;
		    }
		    if (str_starts_with($raw, '/')) {
		        return rtrim($site, '/') . $raw;
		    }
		    // Looks like a bare host/path (e.g. images.example.com/a.webp)
		    if (preg_match('#^[a-z0-9.-]+\\.[a-z]{2,}(/|$)#i', $raw)) {
		        return $scheme . '://' . $raw;
		    }
		    return rtrim($site, '/') . '/' . ltrim($raw, '/');
		};
		$pkPosterCover = $pkNormalizeUrl($pkPosterCover);
		$pkLogoUrl = $pkNormalizeUrl($pkLogoUrl);

			// Don't use ?? here: Typecho options may expose URLs via magic getters and isset() can be false.
			$pkThemeBase = trim((string)$this->options->themeUrl);
			$pkTimthumbBase = $pkNormalizeUrl(rtrim($pkThemeBase, '/') . '/timthumb.php');
			$pkSiteUrl = (string)$this->options->siteUrl;

		$pkPosterCoverForPoster = $pkPosterCover;
		if ($pkPosterCoverForPoster !== '' && function_exists('puock_timthumb_is_remote_url') && puock_timthumb_is_remote_url($pkPosterCoverForPoster, $pkSiteUrl)) {
		    $pkPosterCoverForPoster = puock_timthumb_build_url($pkTimthumbBase, $pkPosterCoverForPoster, 640, 320, 1, 90);
		}

		$pkLogoUrlForPoster = $pkLogoUrl;
		if ($pkLogoUrlForPoster !== '' && function_exists('puock_timthumb_is_remote_url') && puock_timthumb_is_remote_url($pkLogoUrlForPoster, $pkSiteUrl)) {
		    $pkLogoUrlForPoster = puock_timthumb_build_url($pkTimthumbBase, $pkLogoUrlForPoster, 260, 0, 0, 90);
		}

		echo parse_shortcodes($content, $this);
?>
</div>
<div class="t-separator c-sub t-sm mt30">正文完</div>
<div class="footer-info puock-text mt20">
<div class="mt20 tags">
<?php if ($this->tags):foreach ($this->tags as $tag): ?>
<a href="<?php echo $tag['permalink']; ?>" class="pk-badge pk-badge-sm mr5 mb10"><i class="fa-solid fa-tag"></i> <?php echo $tag['name']; ?></a> 
<?php endforeach; else: endif; ?>
</div>
<div class="p-flex-sbc mt20 t-sm">
<div> 
<span>发表至：</span>
<?php foreach($this->categories as $category): ?>
<a class=" mr5" href="<?php echo $category['permalink']; ?>">
<i class="fa-regular fa-folder-open"></i> <?php echo $category['name']; ?>
</a> 
<?php endforeach; ?>
</div>
<div> 
<span class="c-sub"><i class="fa-regular fa-clock"></i> <?php $this->date(); ?></span>
</div>
</div>
</div>
</div>
<!-- 分享海报点赞打赏 -->
<div class="mt15 post-action-panel">
	    <div class="post-action-content">
		        <div class="d-flex justify-content-center w-100 c-sub">
		            <div class="circle-button puock-bg text-center " id="post-like" data-id="<?php echo $this->cid; ?>"> 
		                <i class="fa-regular fa-thumbs-up t-md"></i>&nbsp;<span class="t-sm"><?php get_post_like($this) ?></span>
		            </div>
			            <?php if ($pkRewardAlipay || $pkRewardWx): ?>
			            <div class="circle-button puock-bg text-center reward-modal-open" title="赞赏" aria-label="赞赏" role="button" tabindex="0">
			                <i class="fa fa-sack-dollar"></i>
			            </div>
			            <template id="pk-reward-template">
			                <div class="p-flex-sbc">
			                    <?php if ($pkRewardAlipay): ?>
			                    <div class="mr10" id="reward-alipay">
			                        <img src="<?php echo htmlspecialchars($pkRewardAlipay); ?>" style="width: 140px" alt="支付宝赞赏" title="支付宝赞赏" data-bs-toggle="tooltip" loading="lazy"/>
			                        <p class="mt10 text-center fs12">请使用支付宝扫一扫</p>
			                    </div>
			                    <?php endif;if ($pkRewardWx): ?>
			                    <div id="reward-wx">
			                        <img src="<?php echo htmlspecialchars($pkRewardWx); ?>" style="width: 140px" alt="微信赞赏" title="微信赞赏" data-bs-toggle="tooltip" loading="lazy"/>
			                        <p class="mt10 text-center fs12">请使用微信扫一扫</p>
			                    </div>
			                    <?php endif; ?>
			                </div>
			            </template>
			            <?php endif; ?>
			    <div class="circle-button puock-bg text-center post-poster-open"
			                role="button"
			                tabindex="0"
			                title="海报"
			                aria-label="海报"
				                data-poster-title="<?php echo htmlspecialchars($pkPosterTitle); ?>"
				                data-poster-excerpt="<?php echo htmlspecialchars($pkPosterExcerpt); ?>"
				                data-poster-cover="<?php echo htmlspecialchars($pkPosterCoverForPoster); ?>"
				                data-poster-cover-default="<?php $this->options->themeUrl('assets/img/cover.png'); ?>"
				                data-poster-logo="<?php echo htmlspecialchars($pkLogoUrlForPoster); ?>"
				                data-poster-img-proxy="0"
				            >
				                <i class="fa-regular fa-images"></i>
				            </div>
			    <div class="circle-button puock-bg text-center share-modal-open" title="分享" aria-label="分享" role="button" tabindex="0">
			    <i class="fa fa-share-from-square t-md"></i>
		    </div>
            <div class="ls">
                <div class="circle-button puock-bg text-center post-menu-toggle post-menus-box">
                    <i class="fa fa-bars t-md"></i>
                </div>
            </div>
        </div>
    </div>
</div> 
<!--内页中-->
</div>
<?php if ($this->options->articlemid): ?>
<!--文章中广告-->
<div class="puock-text p-block t-md ad-page-content-bottom"><?php $this->options->articlemid(); ?></div>
<?php endif;$this->related(4)->to($relatedPosts); if ($relatedPosts->have()):?>
<!--相关文章-->
    <div class="p-block pb-0">
        <div class="row puock-text post-relevant"> 
            <?php while ($relatedPosts->next()): ?> 
            <a href="<?php $relatedPosts->permalink(); ?>" class="col-6 col-md-3 post-relevant-item ww"> 
                <div style="background:url('<?php echo htmlspecialchars(getPostThumb($relatedPosts->content, $relatedPosts->cid, $relatedPosts->fields ?? null, 600, 400, 1, 85), ENT_QUOTES); ?>')">
                    <div class="title"> <?php $relatedPosts->title(); ?></div>
                </div>
            </a> 
            <?php endwhile; ?>	
        </div>
    </div>
<?php endif; ?>
<!--上一篇与下一篇-->
<div class="p-block p-lf-15">
    <div class="row text-center pd-links single-next-or-pre t-md">
        <div class="col-6 p-border-r-1 p-0">
            <?php
            // 查询上一篇（比当前文章更早的文章）
            $db = Typecho_Db::get();
            $prev = $db->fetchRow($db->select('cid', 'title', 'slug', 'created')
                ->from('table.contents')
                ->where('created < ?', $this->created)  // 比当前文章更早
                ->where('type = ?', 'post')            // 只查询文章，排除页面
                ->where('status = ?', 'publish')       // 只查询已发布的
                ->order('created', Typecho_Db::SORT_DESC)  // 按时间降序（最近的上一篇）
                ->limit(1));

            if ($prev):
                // 生成正确链接（兼容伪静态和自定义固定链接）
                $prevUrl = Typecho_Router::url('post', $prev, $this->options->index);
            ?>
                <a href="<?php echo $prevUrl; ?>" rel="prev" title="<?php echo $prev['title']; ?>">
                    <div class="abhl puock-text">
                        <p class="t-line-1"><?php echo $prev['title']; ?></p>
                        <span>上一篇</span>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?php $this->options->siteUrl(); ?>" rel="prev">
                    <div class="abhl puock-text">
                        <p class="t-line-1">没有上一篇</p>
                        <span>上一篇</span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="col-6 p-0">
            <?php
            // 查询下一篇（比当前文章更新的文章）
            $next = $db->fetchRow($db->select('cid', 'title', 'slug', 'created')
                ->from('table.contents')
                ->where('created > ?', $this->created)  // 比当前文章更新
                ->where('type = ?', 'post')            // 只查询文章，排除页面
                ->where('status = ?', 'publish')        // 只查询已发布的
                ->order('created', Typecho_Db::SORT_ASC)   // 按时间升序（最早的下一条）
                ->limit(1));

            if ($next):
                // 生成正确链接（兼容伪静态和自定义固定链接）
                $nextUrl = Typecho_Router::url('post', $next, $this->options->index);
            ?>
                <a href="<?php echo $nextUrl; ?>" rel="next" title="<?php echo $next['title']; ?>">
                    <div class="abhl puock-text">
                        <p class="t-line-1"><?php echo $next['title']; ?></p>
                        <span>下一篇</span>
                    </div>
                </a>
            <?php else: ?>
                <a href="<?php $this->options->siteUrl(); ?>" rel="next">
                    <div class="abhl puock-text">
                        <p class="t-line-1">已是最新文章</p>
                        <span>下一篇</span>
                    </div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<!--评论上方--> 
<?php $this->need('comments.php');if ($this->options->articlefoot): ?>
<!--文章底部广告-->
<div class="puock-text p-block t-md ad-comment-top"><?php $this->options->articlefoot(); ?></div> 
<?php endif; ?>
</div>
<?php if ($this->options->showsidebar): $this->need('sidebar.php');endif;$this->need('footer.php'); ?>