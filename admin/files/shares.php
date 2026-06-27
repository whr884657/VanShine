<?php
/**
 * 文件：admin/files/shares.php
 * 作用：分享链接管理
 * @version 1.0.53
 */

require_once dirname(__DIR__) . '/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'list') {
        $rows = array();
        foreach (FileShare::listAll() as $row) {
            $rows[] = FileShare::toAdminRow($row);
        }
        AjaxResponse::success('ok', array('shares' => $rows));
    }

    if ($action === 'create') {
        try {
            $type = isset($_POST['share_type']) ? (string) $_POST['share_type'] : FileShare::TYPE_FILE;
            $data = array(
                'share_type'    => $type,
                'file_id'       => (int) (isset($_POST['file_id']) ? $_POST['file_id'] : 0),
                'folder_id'     => (int) (isset($_POST['folder_id']) ? $_POST['folder_id'] : 0),
                'title'         => isset($_POST['title']) ? $_POST['title'] : '',
                'password'      => isset($_POST['password']) ? $_POST['password'] : '',
                'expires_at'    => isset($_POST['expires_at']) ? $_POST['expires_at'] : '',
                'max_downloads' => (int) (isset($_POST['max_downloads']) ? $_POST['max_downloads'] : 0),
                'allow_preview' => !isset($_POST['allow_preview']) || $_POST['allow_preview'] !== '0',
            );
            $id = FileShare::create($data);
            $share = FileShare::find($id);
            $row = $share ? FileShare::toAdminRow($share) : array();
            AjaxResponse::success('分享链接已创建', array('share' => $row));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'update') {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        try {
            FileShare::update($id, array(
                'title'         => isset($_POST['title']) ? $_POST['title'] : '',
                'password'      => isset($_POST['password']) ? $_POST['password'] : null,
                'expires_at'    => isset($_POST['expires_at']) ? $_POST['expires_at'] : '',
                'max_downloads' => (int) (isset($_POST['max_downloads']) ? $_POST['max_downloads'] : 0),
                'allow_preview' => isset($_POST['allow_preview']) ? (int) $_POST['allow_preview'] : 1,
                'enabled'       => isset($_POST['enabled']) ? (int) $_POST['enabled'] : 1,
            ));
            $share = FileShare::find($id);
            AjaxResponse::success('分享已更新', array('share' => $share ? FileShare::toAdminRow($share) : array()));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'delete') {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        try {
            FileShare::delete($id);
            AjaxResponse::success('分享已删除');
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    AjaxResponse::error('未知操作', 400);
}

$initialRows = array();
foreach (FileShare::listAll() as $row) {
    $initialRows[] = FileShare::toAdminRow($row);
}

vs_admin_layout_start('分享管理', 'file_shares');
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/shares.css?v=<?php echo VS_VERSION; ?>">

<div id="sharesFlash" class="vs-settings-flash" role="alert" hidden></div>

<div class="vs-shares" id="shareManager"
     data-initial="<?php echo vs_e(json_encode(array('shares' => $initialRows), JSON_UNESCAPED_UNICODE)); ?>">

    <div class="vs-shares__toolbar">
        <p class="vs-form-tip vs-shares__tip">分享短链接格式：<code>{域名}/d/{token}</code>，云储存文件经服务器中转，不暴露直链。</p>
    </div>

    <div class="vs-shares__cards" id="sharesCardList"></div>

    <div class="vs-shares__table-wrap vs-shares__table-wrap--desktop">
        <table class="vs-shares__table">
            <thead>
                <tr>
                    <th>标题</th>
                    <th>类型</th>
                    <th>目标</th>
                    <th>储存</th>
                    <th>密码</th>
                    <th>访问/下载</th>
                    <th>状态</th>
                    <th>短链接</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="sharesTableBody">
                <tr><td colspan="9" class="vs-shares__empty">加载中…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="vs-modal-shell" id="shareEditModal" hidden>
    <div class="vs-modal vs-modal--md">
        <div class="vs-modal__head">
            <h3 class="vs-modal__title" id="shareEditTitle">编辑分享</h3>
            <button type="button" class="vs-modal__close" data-close-share-edit aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <form id="shareEditForm" class="vs-form">
            <input type="hidden" name="id" id="shareEditId" value="">
            <div class="vs-modal__body">
                <div class="vs-form-row">
                    <label class="vs-label">分享标题</label>
                    <input type="text" name="title" id="shareEditTitleInput" class="vs-input" maxlength="255">
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">访问密码（留空表示清除密码）</label>
                    <input type="password" name="password" id="shareEditPassword" class="vs-input" autocomplete="new-password" placeholder="不修改请留空">
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">过期时间（可选，留空表示永不过期）</label>
                    <div class="vs-datetime" id="shareEditExpiresWrap" data-vs-datetime>
                        <input type="date" class="vs-input vs-datetime__date" id="shareEditExpiresDate">
                        <input type="time" class="vs-input vs-datetime__time" id="shareEditExpiresTime" step="60">
                    </div>
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">最大下载次数（0=不限）</label>
                    <input type="number" name="max_downloads" id="shareEditMaxDl" class="vs-input" min="0" value="0">
                </div>
                <div class="vs-form-row vs-form-row--inline">
                    <label class="vs-checkbox">
                        <input type="checkbox" name="allow_preview" id="shareEditPreview" value="1" checked>
                        <span>允许在线预览</span>
                    </label>
                    <label class="vs-checkbox">
                        <input type="checkbox" name="enabled" id="shareEditEnabled" value="1" checked>
                        <span>启用分享</span>
                    </label>
                </div>
            </div>
            <div class="vs-modal__foot">
                <button type="button" class="vs-btn vs-btn--default vs-btn--rect" data-close-share-edit>取消</button>
                <button type="submit" class="vs-btn vs-btn--primary vs-btn--rect">保存</button>
            </div>
        </form>
    </div>
</div>

<?php vs_admin_layout_end(array('datetime-picker.js', 'shares.js')); ?>
