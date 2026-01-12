<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php'); ?>
<div id="breadcrumb" class="animated fadeInUp">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a class="a-link" href="<?php $this->options->siteUrl(); ?>">首页</a></li>
        </ol>
    </nav>
</div>
<div class="text-center p-block puock-text">
    <h3 class="mt20">你访问的资源不存在！</h3>
    <h5 class="mt20"><span id="time-count-down"></span>秒后即将跳转至首页</h5>
    <div class="text-center mt20">
        <a class="a-link" href="<?php $this->options->siteUrl(); ?>"><i class="fa fa-home"></i>&nbsp;返回首页</a> 
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var countdown = 5; // 设置倒计时时间（秒）
    var countdownElement = document.getElementById('time-count-down');
    // 检查是否在 PJAX 环境中
    var isPjax = typeof window.Pjax !== 'undefined' || 
                (typeof jQuery !== 'undefined' && jQuery.pjax);
    // 更新倒计时显示
    function updateCountdown() {
        countdownElement.textContent = countdown;
        countdown--; 
        if (countdown < 0) {
            // 倒计时结束，跳转到首页
            var homeUrl = "<?php $this->options->siteUrl(); ?>";
            
            if (isPjax) {
                // 使用 PJAX 方式跳转
                if (typeof window.Pjax !== 'undefined') {
                    // 使用原生 PJAX
                    var pjax = new Pjax();
                    pjax.loadUrl(homeUrl);
                } else if (typeof jQuery !== 'undefined' && jQuery.pjax) {
                    // 使用 jQuery PJAX
                    $.pjax({url: homeUrl, container: '[data-pjax-container]'});
                }
            } else {
                // 普通跳转
                window.location.href = homeUrl;
            }
        } else {
            // 继续倒计时
            setTimeout(updateCountdown, 1000);
        }
    }
    // 开始倒计时
    updateCountdown();
    // 如果是 PJAX 加载，需要手动执行一些初始化
    if (isPjax && typeof window.puockInit !== 'undefined') {
        window.puockInit();
    }
});
</script>
<?php $this->need('footer.php'); ?>