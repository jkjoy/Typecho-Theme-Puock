<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$slideSetting = trim((string)($this->options->slide ?? ''));
if ($slideSetting === '') {
    return;
}
$slideIds = array_filter(array_map('trim', explode('|', $slideSetting)), 'strlen');
$slideCids = [];
foreach ($slideIds as $cid) {
    if (ctype_digit($cid)) {
        $cid = (int)$cid;
        if ($cid > 0) {
            $slideCids[] = $cid;
        }
    }
}
$slideCids = array_values(array_unique($slideCids));
if (empty($slideCids)) {
    return;
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
    'table.contents.text',
    'table.contents.order',
    'table.contents.template'
)
->from('table.contents')
->where('table.contents.type = ?', 'post')
->where('table.contents.status = ?', 'publish')
->where('table.contents.password IS NULL');
$first = true;
foreach ($slideCids as $cid) {
    if ($first) {
        $select->where('table.contents.cid = ?', $cid);
        $first = false;
    } else {
        $select->orWhere('table.contents.cid = ?', $cid);
    }
}

try {
    $posts = $db->fetchAll($select);
} catch (Exception $e) {
    $posts = [];
}
if (empty($posts)) {
    return;
}

$postsByCid = [];
foreach ($posts as $post) {
    $postsByCid[(int)$post['cid']] = $post;
}
$slidePosts = [];
foreach ($slideCids as $cid) {
    if (isset($postsByCid[$cid])) {
        $slidePosts[] = $postsByCid[$cid];
    }
}
if (empty($slidePosts)) {
    return;
}

$coverByCid = [];
if (!empty($slideCids)) {
    $coverSelect = $db->select('table.fields.cid', 'table.fields.str_value')
        ->from('table.fields')
        ->where('table.fields.name = ?', 'cover')
        ->where('table.fields.cid IN (' . implode(',', $slideCids) . ')');
    try {
        $coverRows = $db->fetchAll($coverSelect);
    } catch (Exception $e) {
        $coverRows = [];
    }
    foreach ($coverRows as $row) {
        $cid = (int)($row['cid'] ?? 0);
        $cover = isset($row['str_value']) ? trim((string)$row['str_value']) : '';
        if ($cid > 0 && $cover !== '') {
            $coverByCid[$cid] = $cover;
        }
    }
}
?>
<!--轮播图-->
<div id="index-banners" data-swiper="init" data-swiper-class="index-banner-swiper"
    data-swiper-args='{"navigation":{"nextEl":".index-banner-swiper .swiper-button-next","prevEl":".index-banner-swiper .swiper-button-prev"},"pagination":{"el":".index-banner-swiper .swiper-pagination","clickable":true,"dynamicBullets":true},"mousewheel":{"invert":true},"autoplay":{"delay":3000,"disableOnInteraction":false},"loop":true}'
    class="mb15">
    <div class="swiper index-banner-swiper">
        <div class="swiper-wrapper">
            <?php foreach ($slidePosts as $post):
                $widget = Typecho_Widget::widget('Widget_Contents_Post_Recent');
                $permalink = '';
                try {
                    $permalink = Typecho_Router::url('post', $post, $this->options->index);
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
                $cid = (int)$post['cid'];
                $fields = null;
                if (isset($coverByCid[$cid])) {
                    $fields = (object)['cover' => $coverByCid[$cid]];
                }
                $cover = getPostCover($post['text'] ?? '', $cid, $fields);
                ?>
                <div class="swiper-slide swiper-lazy">
                    <a data-no-instant href="<?php echo htmlspecialchars($permalink); ?>">
                        <img class="w-100" src="<?php echo htmlspecialchars($cover, ENT_QUOTES); ?>"
                             alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <div class="swiper-title">
                            <div class="swiper-title-text"><?php echo htmlspecialchars($post['title']); ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</div>