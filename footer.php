<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if ($this->is('index')):
if ($this->options->cmsmodel): 
$this->need('cms.php'); 
endif;
if ($this->options->friendlink): ?>
<div class="p-block index-links">
<div> 
<span class="t-lg puock-text pb-2 d-inline-block border-bottom border-primary"> 
<i class="fa fa-link"></i>友情链接 
</span> 
</div>
<div class="mt20 t-md index-links-box"> 
<?php Puock_Plugin::output('
<a class="badge links-item" href="{url}" target="_blank" title="{title}" rel="nofollow">{name}</a>
',0,'home'); ?>
</div>
</div>
<?php endif;endif; ?>
 <!--全局下方-->
<?php if($this->options->adlistfoot): ?>
<div class="puock-text p-block t-md ad-global-bottom"><?php $this->options->adlistfoot(); ?></div>
<?php endif; ?>
</main>
<div id="post-menus" class="post-menus-box"> 
    <div id="post-menu-state" class="post-menu-toggle" title="打开或关闭文章目录"> 
        <i class="puock-text ta3 fa fa-bars"></i> 
    </div> 
    <div id="post-menu-content" class="animated slideInRight mini-scroll"> 
        <div id="post-menu-head"> </div> 
        <div id="post-menu-content-items">
        </div> 
    </div>
</div>
<div id="rb-float-actions"> 
    <?php if ($this->is('post')): ?>
    <div data-to-area="#comments" class="p-block"><i class="fa-regular fa-comments puock-text"></i></div> 
    <?php endif; ?>
    <div data-to="top" class="p-block"><i class="fa fa-arrow-up puock-text"></i></div> 
    <div data-to="bottom" class="p-block"><i class="fa fa-arrow-down puock-text"></i></div>
</div>
<footer id="footer">
    <div class="container">
        <div class="row row-cols-md-1">
            <div class="col-md-6">
                <?php if($this->options->footerinfo): ?>
                <div>
                    <span class="t-md pb-2 d-inline-block border-bottom border-primary"><i class="fa-regular fa-bell"></i> 联系我们</span> </div>
                    <p class="mt20 t-md"> 
                        <?php $this->options->footerinfo(); ?>
                    </p>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <?php if($this->options->footercopyright): ?>
                <div>
                <span class="t-md pb-2 d-inline-block border-bottom border-primary"><i class="fa-regular fa-copyright"></i> 版权说明</span> </div>
                <p class="mt20 t-md">
                    <?php $this->options->footercopyright(); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mt20 text-center t-md">
        <div class="info"> 
            <p><?php $this->options->tongji(); ?></p>
            <a href="https://beian.miit.gov.cn/" target="_blank" rel="nofollow" title="备案信息"><?php $this->options->ICP(); ?></a>
                &copy; <?php echo date('Y'); ?> <?php $this->options->title(); ?>
            <div class="fs12 mt10 c-sub"> 
                <span>  &nbsp;Theme by 
                    <a target="_blank" class="c-sub" title="Puock v<?php echo get_theme_version(); ?>" href="https://github.com/jkjoy/typecho-theme-puock">Puock</a> 
                </span> 
                <span>  &nbsp;Powered by 
                    <a target="_blank" class="c-sub" title="Typecho" href="https://typecho.org">Typecho</a> 
                    <p class="hidden"><a target="_blank" class="c-sub" title="老孙博客" href="https://imsun.org">老孙博客</a>制作</p>
                </span>
            </div>
        </div>
    </div>
    </div>
</footer>
</div>
<div id="gt-validate-box"></div>
    <script data-instant>
        var puock_metas = {
            "home": "<?php $this->options->siteUrl(); ?>",
            "use_post_menu": true,
            "is_single": false,
            "is_pjax": true,
            "main_lazy_img": true,
            "link_blank_open": true,
            "mode_switch": true,
            "off_img_viewer": false,
            "off_code_highlighting": false
        };
    </script>
    <script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/js/libs.min.js'); ?>?ver=<?php echo get_theme_version(); ?>" id="puock-libs-js"></script>
    <script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/layer/layer.js'); ?>?ver=<?php echo get_theme_version(); ?>" id="puock-layer-js"></script>
	<script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/js/spark-md5.min.js'); ?>?ver=<?php echo get_theme_version(); ?>" id="puock-spark-md5-js"></script>
	<script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/js/html2canvas.min.js'); ?>?ver=<?php echo get_theme_version(); ?>" id="puock-html2canvas-js"></script>
	<script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/js/qrcode.min.js'); ?>?t=<?php echo get_theme_version(); ?>" id="puock-qrcode-js"></script>
	<script type="text/javascript" data-no-instant src="<?php $this->options->themeUrl('assets/js/puock.js'); ?>?t=<?php echo get_theme_version(); ?>" id="puock-js"></script>
	    <?php
	    $pkNoticeRaw = \Typecho\Cookie::get('__typecho_notice');
	    $pkNoticeType = \Typecho\Cookie::get('__typecho_notice_type') ?: 'notice';
	    $pkNotices = [];
	    if (!empty($pkNoticeRaw)) {
	        $decoded = json_decode($pkNoticeRaw, true);
	        if (is_array($decoded)) {
	            $pkNotices = array_values(array_filter(array_map('strval', $decoded)));
	        } elseif (is_string($pkNoticeRaw)) {
	            $pkNotices = [trim($pkNoticeRaw)];
	        }
	        \Typecho\Cookie::delete('__typecho_notice');
	        \Typecho\Cookie::delete('__typecho_notice_type');
	        \Typecho\Cookie::delete('__typecho_notice_highlight');
	    }
	    if (!empty($pkNotices)): ?>
	    <script data-no-instant>
	        jQuery(function () {
	            var msgs = <?php echo json_encode($pkNotices, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	            var type = <?php echo json_encode($pkNoticeType, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	            var toastType = (type === 'success') ? TYPE_SUCCESS : (type === 'error') ? TYPE_DANGER : (type === 'warning') ? TYPE_WARNING : TYPE_INFO;
	            if (window.Puock && typeof window.Puock.toast === 'function') {
	                msgs.forEach(function (m) {
	                    if (m) window.Puock.toast(String(m), toastType);
	                });
	            }
	        });
	    </script>
	<?php endif; $this->footer(); ?>
</body>
</html>