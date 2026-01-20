<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<div class="p-block" id="comments">
    <div>
        <span class="t-lg border-bottom border-primary puock-text pb-2">
            <i class="fa-regular fa-comments mr-1"></i>评论（<?php $this->commentsNum(_t('暂无评论'), _t('1 条评论'), _t('%d 条评论')); ?>）
        </span>
    </div>

    <!-- 评论表单 -->
    <?php if($this->allow('comment')): ?>
    <div class="mt20 clearfix" id="comment-form-box">
        <div id="<?php $this->respondId(); ?>">
            <form method="post" action="<?php $this->commentUrl() ?>" id="comment-form" class="mt10" role="form">
                <div class="form-group">
                    <textarea rows="4" name="text" id="comment" class="form-control form-control-sm t-sm" placeholder="世界这么大发表一下你的看法~" required><?php $this->remember('text'); ?></textarea>
                </div>

                <?php if($this->user->hasLogin()): ?>
                <!-- 登录用户 -->
                <div class="row row-cols-1 comment-info">
                    <input type="text" value="1" hidden name="comment-logged" id="comment-logged">
                    <div class="col-12">
                        <p class="t-sm c-sub">登录身份: <a href="<?php $this->options->profileUrl(); ?>"><?php $this->user->screenName(); ?></a> . <a href="<?php $this->options->logoutUrl(); ?>" title="Logout">登出 »</a></p>
                    </div>
                </div>
                <?php else: ?>
                <!-- 访客表单 -->
                <div class="row row-cols-1 comment-info">
                    <input type="text" value="0" hidden name="comment-logged" id="comment-logged">
                    <div class="col-12 col-sm-3">
                        <input type="text" name="author" id="comment_author" class="form-control form-control-sm t-sm" placeholder="昵称（必填）" value="<?php $this->remember('author'); ?>" required>
                    </div>
                    <div class="col-12 col-sm-3">
                        <input type="email" name="mail" id="comment_email" class="form-control form-control-sm t-sm" placeholder="邮箱（必填）" value="<?php $this->remember('mail'); ?>"<?php if ($this->options->commentsRequireMail): ?> required<?php endif; ?>>
                    </div>
                    <div class="col-12 col-sm-3">
                        <input type="url" name="url" id="comment_url" class="form-control form-control-sm t-sm" placeholder="网站" value="<?php $this->remember('url'); ?>"<?php if ($this->options->commentsRequireURL): ?> required<?php endif; ?>>
                    </div>
                </div>
                <?php endif; ?>

                <div class="p-flex-sbc mt10">
	                    <div>
	                        <?php if(!$this->user->hasLogin()): ?>
	                        <div class="d-inline-block">
	                            <a data-no-instant class="btn btn-primary btn-ssm pk-login-open" href="<?php $this->options->loginUrl(); ?>" title="快捷登录" aria-label="快捷登录">
	                                <i class="fa fa-right-to-bracket"></i>&nbsp;快捷登录
	                            </a>
	                        </div>
	                        <?php endif; ?>
	                    </div>
                    <div>
	                        <?php if($this->is('post')): ?>
	                        <button id="comment-cancel" type="button" class="btn btn-outline-dark d-none btn-ssm">取消</button>
	                        <?php endif;if($this->options->social): ?>
	                        <button id="comment-smiley" class="btn btn-outline-secondary btn-ssm pk-smiley-open" type="button" title="表情" aria-label="表情">
	                            <i class="fa-regular fa-face-smile t-md"></i>
	                        </button>
	                        <?php endif; ?>
                        <input type="hidden" name="parent" id="comment_parent" value="">
                        <?php Typecho_Widget::widget('Widget_Security')->to($security); ?>
                        <input type="hidden" name="_" value="<?php echo $security->getToken($this->request->getRequestUrl()); ?>">
                        <button type="submit" id="comment-submit" class="btn btn-primary btn-ssm">
                            <i class="fa-regular fa-paper-plane"></i>&nbsp;发布评论
                        </button>
                    </div>
                </div>
            </form>
	        </div>
	    </div>
	    <?php if ($this->options->social): ?>
	    <template id="pk-smiley-template">
	        <?php
	        $pkSmileys = [
	            [':?:', 'doubt.png', '疑问', '疑问'],
	            [':razz:', 'razz.png', '调皮', '调皮'],
	            [':sad:', 'sad.png', '难过', '难过'],
	            [':evil:', 'evil.png', '抠鼻', '抠鼻'],
	            [':naughty:', 'naughty.png', '顽皮', '顽皮'],
	            [':!:', 'scare.png', '吓', '吓'],
	            [':smile:', 'smile.png', '微笑', '微笑'],
	            [':oops:', 'oops.png', '憨笑', '憨笑'],
	            [':neutral:', 'neutral.png', '亲亲', '亲亲'],
	            [':cry:', 'cry.png', '大哭', '大哭'],
	            [':mrgreen:', 'mrgreen.png', '呲牙', '呲牙'],
	            [':grin:', 'grin.png', '坏笑', '坏笑'],
	            [':eek:', 'eek.png', '惊讶', '惊讶'],
	            [':shock:', 'shock.png', '发呆', '发呆'],
	            [':???:', 'bz.png', '撇嘴', '撇嘴'],
	            [':cool:', 'cool.png', '酷', '酷'],
	            [':lol:', 'lol.png', '偷笑', '偷笑'],
	            [':mad:', 'mad.png', '咒骂', '咒骂'],
	            [':twisted:', 'twisted.png', '发怒', '发怒'],
	            [':roll:', 'roll.png', '白眼', '白眼'],
	            [':wink:', 'wink.png', '鼓掌', '鼓掌'],
	            [':idea:', 'idea.png', '想法', '想法'],
	            [':despise:', 'despise.png', '蔑视', '蔑视'],
	            [':celebrate:', 'celebrate.png', '庆祝', '庆祝'],
	            [':watermelon:', 'watermelon.png', '西瓜', '西瓜'],
	            [':xmas:', 'xmas.png', '圣诞', '圣诞'],
	            [':warn:', 'warn.png', '警告', '警告'],
	            [':rainbow:', 'rainbow.png', '彩虹', '彩虹'],
	            [':loveyou:', 'loveyou.png', '爱你', '爱你'],
	            [':love:', 'love.png', '爱', '爱'],
	            [':beer:', 'beer.png', '啤酒', '啤酒']
	        ];
	        ?>
	        <div id="smiley" class="animate bounce" style="max-width: 290px">
	            <?php foreach ($pkSmileys as $smiley): ?>
	                <div class="smiley-item">
	                    <img data-id="<?php echo $smiley[0]; ?>"
	                         src="<?php $this->options->themeUrl('assets/img/smiley/' . $smiley[1]); ?>"
	                         class="smiley-img"
	                         alt="<?php echo $smiley[0] . '-' . $smiley[2]; ?>"
	                         title="<?php echo $smiley[3]; ?>"/>
	                </div>
	            <?php endforeach; ?>
	        </div>
	    </template>
	    <?php endif; ?>
	    <div id="comment-ajax-load" class="text-center mt20 d-none">
	        <div class="pk-skeleton _comment">
	            <div class="_h">
	                <div class="_avatar"></div>
                <div class="_info">
                    <div class="_name"></div>
                    <div class="_date"></div>
                </div>
            </div>
            <div class="_text">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <div class="pk-skeleton _comment">
            <div class="_h">
                <div class="_avatar"></div>
                <div class="_info">
                    <div class="_name"></div>
                    <div class="_date"></div>
                </div>
            </div>
            <div class="_text">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <div class="pk-skeleton _comment">
            <div class="_h">
                <div class="_avatar"></div>
                <div class="_info">
                    <div class="_name"></div>
                    <div class="_date"></div>
                </div>
            </div>
            <div class="_text">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="mt20 alert alert-warning">
        <i class="fa fa-exclamation-circle"></i> 评论已关闭
    </div>    
    <?php endif; ?>

    <!-- 评论列表 -->
    <?php if ($this->have()): ?>
    <div id="post-comments">
        <?php $this->comments()->to($comments);$comments->listComments(); ?>
        
        <!-- 评论分页 -->
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
    <?php endif; ?>
</div>

<?php function threadedComments($comments, $options) { ?>
<li id="<?php $comments->theId(); ?>" class="post-comment">
    <div class="info">
        <div>
            <?php $mail = $comments->mail;$mailHash = md5(strtolower(trim($mail)));
            $cnavatar = Helper::options()->cnavatar ? Helper::options()->cnavatar : 'https://cravatar.cn/avatar/';
            $avatarurl = rtrim($cnavatar, '/') . '/' . $mailHash . '?s=80&d=identicon';
            $loadingImg = Helper::options()->themeUrl . '/assets/img/load.svg';
            ?>
            <img src="<?php echo $loadingImg; ?>"
                 data-src="<?php echo $avatarurl; ?>"
                 class="avatar avatar-64 photo md-avatar lazy"
                 width="60" height="60">
        </div>
        <div class="ml-2 two-info">
            <div class="puock-text ta3b">
                <span class="t-md puock-links">
                    <?php
                    $authorName = (string)($comments->author ?? '');
                    $authorName = $authorName !== '' ? $authorName : '匿名用户';
                    $authorNameEsc = htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8');

                    $authorUrl = trim((string)($comments->url ?? ''));
                    if ($authorUrl !== '' && function_exists('puock_is_external_url') && function_exists('puock_go_build_url') && puock_is_external_url($authorUrl)) {
                        $authorUrl = puock_go_build_url($authorUrl);
                    }

                    if ($authorUrl !== '') {
                        echo '<a href="' . htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer nofollow">' . $authorNameEsc . '</a>';
                    } else {
                        echo $authorNameEsc;
                    }
                    ?>
                </span>
                <?php $commentApprove = commentApprove($comments, $comments->mail);if ($comments->authorId === $comments->ownerId): ?>
                <span class="t-sm text-danger">
                    <i class="fa-regular fa-gem mr-1"></i>博主
                </span>
                <?php else: ?>
                <span class="t-sm c-sub">
                    <i class="fa-regular fa-gem mr-1"></i><?php echo $commentApprove['userLevel']; ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="t-sm c-sub">
                <span><?php echo friendly_date($comments->created); ?></span>
                <button type="button" class="btn btn-link p-0 hide-info animated bounceIn c-sub-a t-sm ml-1 comment-reply" data-coid="<?php echo $comments->coid; ?>"><span class="comment-reply-text"><i class="fa fa-share-from-square"></i>回复</span></button> 
            </div>
        </div>
    </div>
    <div class="content">
        <div class="content-text t-md mt10 puock-text">
             <?php
             if ($comments->parent) {
                 echo getPermalinkFromCoid($comments->parent);
             }
             $commentHtml = parse_smiley_shortcode($comments->content);
             if (function_exists('puock_rewrite_external_links_to_go')) {
                 $commentHtml = puock_rewrite_external_links_to_go($commentHtml);
             }
             echo $commentHtml;
             ?>
            <div class="comment-os c-sub">
            <?php
            $deviceInfo = getBrowsersInfo($comments->agent);
            $icons = getDeviceIcon($deviceInfo);
            ?>
            <!-- 系统信息 -->
            <span class="mt10" title="<?php echo $deviceInfo['system'] . ' ' . $deviceInfo['systemVersion']; ?>">
                <?php echo $icons['system']; ?>&nbsp;
                <?php 
                echo $deviceInfo['system'] 
                    ? 
                    : '未知系统';
                ?>
            </span>
            <!-- 浏览器信息 -->
            <span class="mt10" title="<?php echo $deviceInfo['browser'] . ' ' . $deviceInfo['version']; ?>">
                <?php echo $icons['browser']; ?>&nbsp;
                <?php 
                echo $deviceInfo['browser'] 
                    ? 
                    : '未知浏览器';
                ?>
            </span>
            <!-- IP 地理位置 -->   
                <?php if($comments->ip): ?>
                <span class="mt10" title="IP">
                    <i class="fa-solid fa-location-dot"></i>&nbsp;<?php echo get_ip_region($comments->ip); ?>
                </span>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($comments->children): ?>
    <div class="comment-children">
        <?php $comments->threadedComments($options); ?>
    </div>
    <?php endif; ?>
</li>
<?php } ?>
