<?php 
/**
 * 全部标签
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
<div id="page-tags">
    <div id="page" class="row row-cols-1">
        <div id="posts" class="col-lg-8 col-md-12 <?php if ($this->options->showsidebar): ?>animated fadeInLeft<?php else:endif; ?>">
            <div class="puock-text p-block no-style pb-2">
                <?php
                // 获取所有标签
                $tags = Typecho_Widget::widget('Widget_Metas_Tag_Cloud');
                // 准备字母数组
                $letters = range('A', 'Z');
                $letters[] = '0';  
                // 获取所有存在的首字母
                $existingLetters = array();
                $tagsArray = array();   
                while ($tags->next()) {
                    $firstChar = getFirstChar($tags->name);
                    if (!in_array($firstChar, $existingLetters)) {
                        $existingLetters[] = $firstChar;
                    }
                    $tagsArray[$firstChar][] = array(
                        'name' => $tags->name,
                        'permalink' => $tags->permalink,
                        'count' => $tags->count
                    );
                }
                // 对每个字母下的标签按名称排序
                foreach ($tagsArray as &$letterTags) {
                    usort($letterTags, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                }
                ?>
                <!-- 字母导航 -->
                <ul class='pl-0' id='tags-main-index'>
                    <?php foreach ($letters as $letter): ?>
                        <?php if (in_array($letter, $existingLetters)): ?>
                            <li class='a'><a href='#<?php echo $letter; ?>'><?php echo $letter; ?></a></li>
                        <?php else: ?>
                            <li class='t'><?php echo $letter; ?></li>
                        <?php endif;endforeach;for ($i = 1; $i <= 9; $i++): ?>
                        <li class='t'><?php echo $i; ?></li>
                    <?php endfor; ?>
                </ul>
                <!-- 标签列表 -->
                <ul id='tags-main-box' class='pl-0'>
                    <?php 
                    // 按字母顺序排序
                    ksort($tagsArray);
                    foreach ($tagsArray as $letter => $tags): ?>
                        <li class='n' id='<?php echo $letter; ?>'>
                            <h4 class='tag-name'><span class='t-lg c-sub'>#</span><?php echo $letter; ?></h4>
                            <?php foreach ($tags as $tag): ?>
                                <a href='<?php echo $tag['permalink']; ?>'><?php echo $tag['name']; ?>(<?php echo $tag['count']; ?>)</a>
                            <?php endforeach; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div> 
<?php if ($this->options->showsidebar):$this->need('sidebar.php'); endif;$this->need('footer.php'); ?>