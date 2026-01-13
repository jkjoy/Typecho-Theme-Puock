<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

use Typecho\Common;
use Typecho\Db;
use Widget\Notice;
use Widget\Security;
use Widget\User;

function get_current_theme_name()
{
    $theme = '';
    try {
        $theme = (string)Helper::options()->theme;
    } catch (Exception $e) {
        $theme = '';
    }

    if ($theme !== '') {
        return $theme;
    }

    $str1 = explode('/themes/', Helper::options()->themeUrl);
    $str2 = explode('/', $str1[1] ?? '');
    return (string)($str2[0] ?? '');
}

function get_theme_option_name()
{
    $theme = get_current_theme_name();
    return $theme !== '' ? ('theme:' . $theme) : null;
}

function get_theme_settings_from_db()
{
    $name = get_theme_option_name();
    if (!$name) {
        return [];
    }

    $db = Db::get();
    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name)->limit(1));
    if (!$row || !isset($row['value'])) {
        return [];
    }

    $value = @unserialize($row['value']);
    return is_array($value) ? $value : [];
}

function get_backup_option_prefix()
{
    $theme = get_current_theme_name();
    if ($theme === '') {
        return null;
    }

    // 使用短的确定性前缀（避免 name 超长导致 DB 插入失败）
    return 'theme_backup:' . substr(md5($theme), 0, 8) . ':';
}

function create_db_backup_entry(array $settings)
{
    $db = Db::get();
    $theme = get_current_theme_name();
    if ($theme === '') {
        throw new Exception('Unknown theme');
    }

    // payload 保留完整信息
    $payload = [
        'theme' => $theme,
        'exportedAt' => date('c'),
        'settings' => $settings,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new Exception('JSON encode failed');
    }

    // 始终使用短前缀 tbk:，长度尽量短以兼容各种 DB
    $tries = 0;
    do {
        $tries++;
        $uniq = substr(sha1(microtime(true) . mt_rand()), 0, 10);
        $name = 'tbk:' . $uniq;

        try {
            $db->query(
                $db->insert('table.options')->rows([
                    'name' => $name,
                    'value' => $json,
                    'user' => 0,
                ])
            );

            error_log('[theme-backup] create_db_backup_entry name=' . $name . ' length=' . strlen($name) . ' tries=' . $tries);
            return $name;
        } catch (Exception $e) {
            error_log('[theme-backup] create_db_backup_entry insert failed try=' . $tries . ' name=' . $name . ' error=' . $e->getMessage());
            // 若失败，缩短 uniq 后重试
            if ($tries >= 4) break;
        }
    } while ($tries < 5);

    // 最终回退为更短的 uniq
    $finalUniq = substr(sha1(uniqid('', true)), 0, 6);
    $name = 'tbk:' . $finalUniq;
    try {
        $db->query(
            $db->insert('table.options')->rows([
                'name' => $name,
                'value' => $json,
                'user' => 0,
            ])
        );
        error_log('[theme-backup] create_db_backup_entry final fallback name=' . $name . ' length=' . strlen($name));
        return $name;
    } catch (Exception $e) {
        error_log('[theme-backup] create_db_backup_entry final insert failed: ' . $e->getMessage());
        throw new Exception('Failed to create backup entry');
    }
}

function list_db_backups($limit = 20)
{
    $prefix = get_backup_option_prefix();
    $db = Db::get();

    $select = $db->select('name', 'value')
        ->from('table.options')
        ->order('name', Db::SORT_DESC)
        ->limit((int)$limit);

    if ($prefix) {
        // 兼容旧格式（theme_backup:...）和新短前缀（tbk:...）
        $select->where('(name LIKE ? OR name LIKE ?)', $prefix . '%', 'tbk:%');
    } else {
        $select->where('name LIKE ?', 'tbk:%');
    }

    $rows = $db->fetchAll($select);

    $items = [];
    foreach ($rows as $row) {
        $name = (string)($row['name'] ?? '');
        $value = (string)($row['value'] ?? '');
        $decoded = json_decode($value, true);

        $exportedAt = null;
        if (is_array($decoded) && isset($decoded['exportedAt'])) {
            $exportedAt = (string)$decoded['exportedAt'];
        }

        if (!$exportedAt && preg_match('/:(\\d{14})$/', $name, $m)) {
            $exportedAt = $m[1];
        }

        $items[] = [
            'name' => $name,
            'exportedAt' => $exportedAt,
        ];
    }

    return $items;
}

function get_db_backup_json($name)
{
    $name = (string)$name;
    $db = Db::get();
    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name)->limit(1));
    if (!$row || !isset($row['value'])) {
        return null;
    }

    $value = (string)$row['value'];
    $decoded = json_decode($value, true);

    if (is_array($decoded) && isset($decoded['theme'])) {
        if ($decoded['theme'] !== get_current_theme_name()) {
            return null;
        }
        return $value;
    }

    // 向后兼容：若 name 使用旧的 theme-specific 前缀，则允许读取
    $prefix = get_backup_option_prefix();
    if ($prefix && strpos($name, $prefix) === 0) {
        return $value;
    }

    return null;
}

function delete_db_backup($name)
{
    $name = (string)$name;
    $db = Db::get();

    $row = $db->fetchRow($db->select('value')->from('table.options')->where('name = ?', $name)->limit(1));
    if (!$row || !isset($row['value'])) {
        return false;
    }

    $value = (string)$row['value'];
    $decoded = json_decode($value, true);

    // 只删除属于当前主题的备份（兼容旧格式）
    if (is_array($decoded) && isset($decoded['theme'])) {
        if ($decoded['theme'] !== get_current_theme_name()) {
            return false;
        }
    } else {
        $prefix = get_backup_option_prefix();
        if (!$prefix || strpos($name, $prefix) !== 0) {
            return false;
        }
    }

    $db->query($db->delete('table.options')->where('name = ?', $name));
    return true;
}

function export_theme_settings_json()
{
    $theme = get_current_theme_name();
    $settings = get_theme_settings_from_db();

    $payload = [
        'theme' => $theme,
        'exportedAt' => date('c'),
        'settings' => $settings,
    ];

    return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

function clear_output_buffers()
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
}

function sanitize_imported_settings(array $settings)
{
    unset($settings['_'], $settings['backup_data'], $settings['backup_file']);

    foreach (array_keys($settings) as $k) {
        if (is_string($k) && str_starts_with($k, 'backup_')) {
            unset($settings[$k]);
        }
    }

    return $settings;
}

function save_theme_settings_to_db(array $settings)
{
    $name = get_theme_option_name();
    if (!$name) {
        throw new Exception('Unknown theme');
    }

    $db = Db::get();
    $exists = $db->fetchRow($db->select('name')->from('table.options')->where('name = ?', $name)->limit(1));

    if ($exists) {
        $db->query(
            $db->update('table.options')
                ->rows(['value' => serialize($settings)])
                ->where('name = ?', $name)
        );
    } else {
        $db->query(
            $db->insert('table.options')
                ->rows([
                    'name' => $name,
                    'value' => serialize($settings),
                    'user' => 0,
                ])
        );
    }
}

function handle_theme_backup_request()
{
    if (!defined('__TYPECHO_ADMIN__')) {
        return;
    }

    if (empty($_GET['backup'])) {
        return;
    }

    $action = (string)$_GET['backup'];
    $user = User::alloc();
    if (!$user->pass('administrator', true)) {
        http_response_code(403);
        exit;
    }

    $security = Security::alloc();
    $security->protect();

    if (in_array($action, ['save_db', 'restore_db', 'delete_db', 'import'], true)) {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }
    }

    if ($action === 'save_db') {
        clear_output_buffers();
        try {
            $settings = get_theme_settings_from_db();
            $key = create_db_backup_entry($settings);
            Notice::alloc()->set(_t('已保存备份：%s', $key), 'success');
        } catch (Exception $e) {
        error_log('[theme-backup] save_db failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        Notice::alloc()->set(_t('保存备份失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'restore_db') {
        clear_output_buffers();
        $key = (string)($_GET['key'] ?? '');
        $json = get_db_backup_json($key);
        if (!$json) {
            Notice::alloc()->set(_t('备份不存在'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $decoded = json_decode($json, true);
        $settings = is_array($decoded) ? ($decoded['settings'] ?? $decoded) : null;
        if (!is_array($settings)) {
            Notice::alloc()->set(_t('备份数据损坏或格式不正确'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        try {
            $settings = sanitize_imported_settings($settings);
            save_theme_settings_to_db($settings);
            Notice::alloc()->set(_t('已从服务器备份恢复主题设置'), 'success');
        } catch (Exception $e) {
            Notice::alloc()->set(_t('恢复失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'delete_db') {
        clear_output_buffers();
        $key = (string)($_GET['key'] ?? '');
        if (delete_db_backup($key)) {
            Notice::alloc()->set(_t('备份已删除'), 'success');
        } else {
            Notice::alloc()->set(_t('删除失败：备份不存在'), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }

    if ($action === 'export') {
        clear_output_buffers();
        $json = export_theme_settings_json();
        $file = 'theme-' . get_current_theme_name() . '-backup-' . date('Ymd-His') . '.json';

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        echo $json ?: '{}';
        exit;
    }

    if ($action === 'import') {
        clear_output_buffers();
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }

        $raw = (string)($_POST['backup_data'] ?? '');
        $raw = trim($raw);
        if ($raw === '') {
            Notice::alloc()->set(_t('备份数据为空'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Notice::alloc()->set(_t('备份数据不是有效的 JSON'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        $settings = $decoded['settings'] ?? $decoded;
        if (!is_array($settings)) {
            Notice::alloc()->set(_t('备份数据格式不正确'), 'error');
            header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
            exit;
        }

        try {
            $settings = sanitize_imported_settings($settings);
            save_theme_settings_to_db($settings);
            Notice::alloc()->set(_t('主题设置已从备份恢复'), 'success');
        } catch (Exception $e) {
            Notice::alloc()->set(_t('恢复失败：%s', $e->getMessage()), 'error');
        }

        header('Location: ' . Common::url('options-theme.php', Helper::options()->adminUrl));
        exit;
    }
}

function render_theme_backup_section()
{
    if (!defined('__TYPECHO_ADMIN__')) {
        return;
    }

    $security = Security::alloc();
    $importUrl = $security->getAdminUrl('options-theme.php?backup=import');
    $saveDbUrl = $security->getAdminUrl('options-theme.php?backup=save_db');
    $backups = list_db_backups(20);

    echo '<span class="themeConfig"><h3>备份与恢复</h3></span>';
    echo '<div class="info">可将主题设置备份到服务器（数据库）恢复仅影响主题设置，不影响文章/评论数据。</div>';
    echo '<p style="margin: 0 0 10px 0;">
        <button type="button" class="btn primary" id="mango-backup-save-db">保存主题配置</button>
    </p>';

    echo '<p style="margin: 18px 0 10px 0;font-weight:600;">备份列表（最近 20 条）</p>';
    if (empty($backups)) {
        echo '<div>暂无备份，点击“保存主题配置”创建一份。</div>';
    } else {
        echo '<div style="border:1px solid #d9d9d6;border-radius:4px;overflow:hidden;">';
        echo '<table style="width:100%;border-collapse:collapse;font-size:12px;">';
        echo '<thead><tr style="background:#f7f7f7;"><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">时间</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">标识</th><th style="text-align:left;padding:8px 10px;border-bottom:1px solid #eee;">操作</th></tr></thead>';
        echo '<tbody>';
        foreach ($backups as $item) {
            $name = (string)$item['name'];
            $exportedAt = (string)($item['exportedAt'] ?? '');
            $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

            $restoreUrl = $security->getAdminUrl('options-theme.php?backup=restore_db&key=' . urlencode($name));
            $deleteUrl = $security->getAdminUrl('options-theme.php?backup=delete_db&key=' . urlencode($name));

            echo '<tr>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;white-space:nowrap;">' . htmlspecialchars($exportedAt, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;word-break:break-all;">' . $safeName . '</td>';
            echo '<td style="padding:8px 10px;border-bottom:1px solid #eee;white-space:nowrap;">';
            echo '<button type="button" class="btn primary mango-backup-restore" data-url="' . htmlspecialchars($restoreUrl, ENT_QUOTES, 'UTF-8') . '">恢复</button> ';
            echo '<button type="button" class="btn mango-backup-delete" data-url="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '">删除</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }



    echo '<style>
        .btn{display:inline-block;padding:6px 10px;border-radius:4px;border:1px solid #d9d9d6;background:#fff;cursor:pointer;font-size:12px}
        .btn.primary{background:#467B96;color:#fff;border-color:#467B96}
        .btn.danger{background:#e55353;color:#fff;border-color:#e55353}
        .btn:disabled{opacity:.6;cursor:not-allowed}
        .mango-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;z-index:99999}
        .mango-modal{width:min(520px,calc(100vw - 32px));background:#fff;border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,.2);overflow:hidden}
        .mango-modal-head{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom:1px solid #eee}
        .mango-modal-title{font-weight:700;color:#333}
        .mango-modal-close{border:0;background:transparent;font-size:18px;line-height:1;cursor:pointer;color:#999;padding:4px 6px}
        .mango-modal-close:hover{color:#333}
        .mango-modal-body{padding:14px;color:#666;font-size:13px;line-height:1.6;word-break:break-word}
        .mango-modal-actions{display:flex;gap:10px;justify-content:flex-end;padding:12px 14px;border-top:1px solid #eee}
    </style>';

    echo '<div id="mango-confirm-backdrop" class="mango-modal-backdrop" style="display:none" aria-hidden="true">
        <div class="mango-modal" role="dialog" aria-modal="true" aria-labelledby="mango-confirm-title">
            <div class="mango-modal-head">
                <div id="mango-confirm-title" class="mango-modal-title">请确认</div>
                <button type="button" class="mango-modal-close" id="mango-confirm-close" aria-label="Close">×</button>
            </div>
            <div class="mango-modal-body" id="mango-confirm-message"></div>
            <div class="mango-modal-actions">
                <button type="button" class="btn" id="mango-confirm-cancel">取消</button>
                <button type="button" class="btn primary" id="mango-confirm-ok">确认</button>
            </div>
        </div>
    </div>';

    echo '<script>
    (function(){
        var saveDbBtn = document.getElementById("mango-backup-save-db");
        var copyBtn = document.getElementById("mango-backup-copy");
        var exportEl = document.getElementById("mango-backup-export");
        var restoreBtn = document.getElementById("mango-backup-restore");
        var importEl = document.getElementById("mango-backup-import");
        var importUrl = ' . json_encode($importUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';
        var saveDbUrl = ' . json_encode($saveDbUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ';

        function mangoConfirmModal(opts) {
            opts = opts || {};
            if (!window.Promise) return window.confirm(opts.message || "确认操作？");

            var backdrop = document.getElementById("mango-confirm-backdrop");
            var titleEl = document.getElementById("mango-confirm-title");
            var msgEl = document.getElementById("mango-confirm-message");
            var okBtn = document.getElementById("mango-confirm-ok");
            var cancelBtn = document.getElementById("mango-confirm-cancel");
            var closeBtn = document.getElementById("mango-confirm-close");
            if (!backdrop || !titleEl || !msgEl || !okBtn || !cancelBtn || !closeBtn) {
                return Promise.resolve(window.confirm(opts.message || "确认操作？"));
            }

            var lastActive = document.activeElement;
            titleEl.textContent = opts.title || "请确认";
            msgEl.textContent = opts.message || "确认操作？";

            okBtn.textContent = opts.okText || "确认";
            cancelBtn.textContent = opts.cancelText || "取消";

            okBtn.classList.remove("primary", "danger");
            okBtn.classList.add(opts.danger ? "danger" : "primary");

            function cleanup(result) {
                backdrop.style.display = "none";
                backdrop.setAttribute("aria-hidden", "true");
                backdrop.removeEventListener("click", onBackdropClick);
                okBtn.removeEventListener("click", onOk);
                cancelBtn.removeEventListener("click", onCancel);
                closeBtn.removeEventListener("click", onCancel);
                document.removeEventListener("keydown", onKeydown, true);
                if (lastActive && typeof lastActive.focus === "function") lastActive.focus();
                resolve(result);
            }

            function onBackdropClick(e) {
                if (e.target === backdrop) cleanup(false);
            }
            function onOk() { cleanup(true); }
            function onCancel() { cleanup(false); }
            function onKeydown(e) {
                if (e.key === "Escape") {
                    e.preventDefault();
                    cleanup(false);
                }
                if (e.key === "Enter") {
                    if (document.activeElement === cancelBtn) return;
                    e.preventDefault();
                    cleanup(true);
                }
            }

            var resolve;
            var p = new Promise(function(r){ resolve = r; });
            backdrop.style.display = "flex";
            backdrop.setAttribute("aria-hidden", "false");
            backdrop.addEventListener("click", onBackdropClick);
            okBtn.addEventListener("click", onOk);
            cancelBtn.addEventListener("click", onCancel);
            closeBtn.addEventListener("click", onCancel);
            document.addEventListener("keydown", onKeydown, true);
            setTimeout(function(){ okBtn.focus(); }, 0);
            return p;
        }

        async function mangoPost(url, formData) {
            var resp = await fetch(url, { method: "POST", body: formData || new FormData(), credentials: "same-origin" });
            if (resp.redirected) {
                window.location.href = resp.url;
                return;
            }
            window.location.reload();
        }

        if (saveDbBtn) {
            saveDbBtn.addEventListener("click", async function(){
                var ok = await mangoConfirmModal({ title: "保存备份", message: "确认将当前主题设置保存到服务器备份？", okText: "确认保存" });
                if (!ok) return;
                saveDbBtn.disabled = true;
                saveDbBtn.textContent = "保存中...";
                try {
                    await mangoPost(saveDbUrl);
                } catch (e) {
                    alert("保存失败，请检查网络后重试");
                } finally {
                    saveDbBtn.disabled = false;
                    saveDbBtn.textContent = "保存主题配置";
                }
            });
        }

        document.querySelectorAll(".mango-backup-restore").forEach(function(btn){
            btn.addEventListener("click", async function(){
                var url = btn.getAttribute("data-url");
                if (!url) return;
                var ok = await mangoConfirmModal({ title: "恢复备份", message: "确认从该服务器备份恢复？将覆盖当前主题设置。", okText: "确认恢复" });
                if (!ok) return;
                btn.disabled = true;
                btn.textContent = "恢复中...";
                try {
                    await mangoPost(url);
                } catch (e) {
                    alert("恢复失败，请检查网络后重试");
                } finally {
                    btn.disabled = false;
                    btn.textContent = "恢复";
                }
            });
        });

        document.querySelectorAll(".mango-backup-delete").forEach(function(btn){
            btn.addEventListener("click", async function(){
                var url = btn.getAttribute("data-url");
                if (!url) return;
                var ok = await mangoConfirmModal({ title: "删除备份", message: "确认删除该服务器备份？此操作不可撤销。", okText: "确认删除", danger: true });
                if (!ok) return;
                btn.disabled = true;
                btn.textContent = "删除中...";
                try {
                    await mangoPost(url);
                } catch (e) {
                    alert("删除失败，请检查网络后重试");
                } finally {
                    btn.disabled = false;
                    btn.textContent = "删除";
                }
            });
        });

        if (restoreBtn && importEl) {
            restoreBtn.addEventListener("click", async function(){
                var raw = (importEl.value || "").trim();
                if (!raw) { alert("请粘贴备份 JSON"); return; }
                var ok = await mangoConfirmModal({ title: "恢复备份", message: "确认恢复？将覆盖当前主题设置。", okText: "确认恢复" });
                if (!ok) return;

                restoreBtn.disabled = true;
                restoreBtn.textContent = "恢复中...";
                try {
                    var form = new FormData();
                    form.append("backup_data", raw);
                    var resp = await fetch(importUrl, { method: "POST", body: form, credentials: "same-origin" });
                    if (resp.redirected) {
                        window.location.href = resp.url;
                        return;
                    }
                    window.location.reload();
                } catch (e) {
                    alert("恢复失败，请检查网络后重试");
                } finally {
                    restoreBtn.disabled = false;
                    restoreBtn.textContent = "恢复并覆盖当前设置";
                }
            });
        }
    })();
    </script>';
}

handle_theme_backup_request();