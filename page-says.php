<?php
/**
 * 说说页面
 * @package custom
 * @author 老孙
 * @version 1.0
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
        <?php if ($this->options->showsidebar): ?>    
            <div id="comments" class="col-lg-8 col-md-12 animated fadeInLeft">
        <?php else: ?>
            <div id="comments" class="col-lg-12 col-md-12">
        <?php endif;
            // 登录状态检测
            if ($this->user->hasLogin()) {
                $GLOBALS['isLogin'] = true;
            } else {
                $GLOBALS['isLogin'] = false;
            }

            // 评论回调函数
            function threadedComments($comments, $options) {
                $mail = $comments->mail;
                $mailHash = md5(strtolower(trim($mail)));
                $purl = (string)$comments->url;
                if (function_exists('puock_is_external_url') && function_exists('puock_go_build_url') && puock_is_external_url($purl)) {
                    $purl = puock_go_build_url($purl);
                }
                $nickname = $comments->author;
                $cnavatar = Helper::options()->cnavatar ? Helper::options()->cnavatar : 'https://cravatar.cn/avatar/';
                $avatarurl = rtrim($cnavatar, '/') . '/' . $mailHash . '?s=80&d=identicon';
                $loadingImg = Helper::options()->themeUrl . '/assets/img/load.svg';
            ?>
                <div class="mb20 puock-text moments-item">
                    <div class="row">
                        <div class="col-12 col-md-1">
                             <a class="meta ta3" href="<?php echo htmlspecialchars($purl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                <div class="avatar mb10">
                                    <img src='<?php echo $loadingImg; ?>'
                                        class='lazy md-avatar mt-1'
                                        data-src='<?php echo $avatarurl; ?>'
                                    >
                                </div>
                                <div class="t-line-1 info fs12"><?php echo $nickname; ?></div>
                            </a>
                        </div>
                        <div class="col-12 col-md-11">
                            <div class="p-block moment-content-box"> <span class="al"></span>
                                <div class="mt10 moment-content entry-content show-link-icon">
                                    <?php
                                    if ($comments->parent) {
                                        echo getPermalinkFromCoid($comments->parent);
                                    }
                                    $momentHtml = parse_smiley_shortcode($comments->content);
                                    if (function_exists('puock_rewrite_external_links_to_go')) {
                                        $momentHtml = puock_rewrite_external_links_to_go($momentHtml);
                                    }
                                    echo $momentHtml;
                                    ?>
                                </div>
                                <div class="mt10 moment-footer p-flex-s-right"> 
                                    <span class="t-sm c-sub">
                                        <span class="mr-2"><i class="fa-regular fa-clock mr-1"></i><?php echo friendly_date($comments->created); ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                 </div>
            <?php } $this->comments()->to($comments);if ($this->user->hasLogin() && $this->user->group == 'administrator') : ?>
    <div class="p-block">
        <input type="hidden" value="<?php $this->commentUrl() ?>">
        <div>
            <span class="t-lg border-bottom border-primary puock-text pb-2">
                <i class="fa-regular fa-comments mr-1"></i>有什么新鲜事
            </span>
        </div>
        <div class="mt20 clearfix" id="comment-form-box">
            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="mt10" role="form">
                <div class="form-group">
                    <textarea rows="4" name="text" id="comment" class="form-control form-control-sm t-sm" placeholder="发表您的新鲜事儿..." required><?php $this->remember('text'); ?></textarea>
                </div>
                <input type="hidden" value="<?php $this->user->screenName(); ?>" name="author" />
                <input type="hidden" value="<?php $this->user->mail(); ?>" name="mail" />
                <input type="hidden" value="<?php $this->options->siteUrl(); ?>" name="url" />
                <input type="hidden" name="_" value="<?php Typecho_Widget::widget('Widget_Security')->to($security); echo $security->getToken($this->request->getRequestUrl()); ?>">
                <div class="p-flex-sbc mt10">
                    <div class="form-foot">
                        <?php if($this->options->social): ?>
                        <button id="comment-insert-image" class="btn btn-outline-secondary btn-ssm" type="button" title="插入图片">
                            <i class="fa-solid fa-image"></i>
                        </button>
                        <button id="comment-smiley" class="btn btn-outline-secondary btn-ssm pk-modal-toggle" type="button" title="表情" data-once-load="true" data-url="<?php echo get_correct_url('/emoji/'); ?>">
                        <i class="fa-regular fa-face-smile t-md"></i>
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary btn-ssm"><i class="fa-regular fa-paper-plane"></i> 立即发表</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif;if ($comments->have()): while ($comments->next()):threadedComments($comments, $this->options);endwhile; ?>
    <!-- 分页导航 -->
    <div class="mt20 p-flex-s-right" data-no-instant>
        <?php $comments->pageNav('&laquo;', '&raquo;', 1, '...', array(
            'wrapTag' => 'ul',
            'wrapClass' => 'pagination comment-ajax-load',
            'itemTag' => 'li',
            'textTag' => 'span',
            'currentClass' => 'active',
            'prevClass' => 'prev',
            'nextClass' => 'next'
        )); ?>
    </div>
    </div>
    <?php endif;if ($this->options->showsidebar):$this->need('sidebar.php');endif; ?>
    </div>
</div>
<?php $this->need('footer.php'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('comment-insert-image');
    if (!btn) return;
    btn.addEventListener('click', function() {
        let modal = document.createElement('div');
        modal.innerHTML = `
        <style>
        .insert-image {
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(211, 134, 202, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        </style>
        <div id="img-insert-modal" class="insert-image">
            <div class="min-width-modal" style="max-width: 290px">
            <form>
                <div class="form-label">插入图片</div>
                <div class="mb15">
                    <label for="img-insert-title" class="form-label">标题（可选）</label>
                    <input id="img-insert-title" type="text" class="form-control form-control-sm">
                </div>
                <div class="mb15">
                    <label for="img-insert-url" class="form-label">图片地址 <span style="color:red">*</span></label>
                    <input id="img-insert-url" type="text" class="form-control form-control-sm" required>
                </div>
                <div class="mb15 d-flex justify-content-center wh100">
                    <button id="img-insert-cancel" class="btn btn-ssm btn-primary mr5">取消</button>
                    <button id="img-insert-confirm" class="btn btn-ssm btn-primary mr5">插入</button>
                </div>
            </form>
            </div>
        </div>
         `;
        document.body.appendChild(modal);
        document.getElementById('img-insert-cancel').onclick = function() {
            document.body.removeChild(modal);
        };
        document.getElementById('img-insert-confirm').onclick = function() {
            let url = document.getElementById('img-insert-url').value.trim();
            let title = document.getElementById('img-insert-title').value.trim();
            if (!url) {
                if (window.Puock) {
                    window.Puock.toast('图片地址不能为空！', 'warning');
                }
                return;
            }
            let tag = `<img src=\"${url}\"` + (title ? ` alt=\"${title}\" title=\"${title}\"` : '') + ` />`;
            let textarea = document.getElementById('comment');
            if (textarea) {
                let start = textarea.selectionStart, end = textarea.selectionEnd;
                let val = textarea.value;
                textarea.value = val.substring(0, start) + tag + val.substring(end);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            }
            document.body.removeChild(modal);
        };
    });
});
</script>
