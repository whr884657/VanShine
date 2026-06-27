<?php
/**
 * 文件：admin/files/manage.php
 * 作用：文件管理（文件夹绑定储存、上传、浏览）
 * @version 1.0.54
 */

require_once dirname(__DIR__) . '/init.php';

/**
 * @param array $folders
 * @param array $files
 * @param int   $folderId
 * @return array
 */
function vs_files_payload(array $folders, array $files, $folderId)
{
    $enabled = array();
    foreach (StorageRegistry::enabledTypes() as $key => $type) {
        $enabled[] = array(
            'key'  => (int) $key,
            'name' => $type['name'],
        );
    }

    $breadcrumb = array(array('id' => 0, 'name' => '根目录'));
    foreach (FileFolder::breadcrumb($folderId) as $row) {
        $breadcrumb[] = array(
            'id'   => (int) $row['id'],
            'name' => $row['name'],
        );
    }

    $currentFolder = null;
    if ($folderId > 0) {
        $folder = FileFolder::find($folderId);
        if ($folder !== null) {
            $type = StorageRegistry::type((int) $folder['storage_key']);
            $currentFolder = array(
                'id'          => (int) $folder['id'],
                'name'        => $folder['name'],
                'storage_key' => (int) $folder['storage_key'],
                'storage_name'=> $type ? $type['name'] : '',
            );
        }
    }

    $folderRows = array();
    foreach ($folders as $row) {
        $type = StorageRegistry::type((int) $row['storage_key']);
        $folderRows[] = array(
            'id'            => (int) $row['id'],
            'name'          => $row['name'],
            'storage_key'   => (int) $row['storage_key'],
            'storage_name'  => $type ? $type['name'] : '',
            'created_at'    => $row['created_at'],
        );
    }

    $fileRows = array();
    foreach ($files as $row) {
        $fileRows[] = array(
            'id'            => (int) $row['id'],
            'original_name' => $row['original_name'],
            'stored_name'   => $row['stored_name'],
            'mime_type'     => $row['mime_type'],
            'file_size'     => (int) $row['file_size'],
            'public_url'    => $row['public_url'],
            'created_at'    => $row['created_at'],
        );
    }

    return array(
        'folder_id'      => (int) $folderId,
        'breadcrumb'     => $breadcrumb,
        'current_folder' => $currentFolder,
        'folders'        => $folderRows,
        'files'          => $fileRows,
        'storages'       => $enabled,
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $folderId = (int) (isset($_POST['folder_id']) ? $_POST['folder_id'] : 0);

    if ($action === 'list') {
        $folders = FileFolder::listByParent($folderId);
        $files = $folderId > 0 ? FileItem::listByFolder($folderId) : array();
        AjaxResponse::success('ok', vs_files_payload($folders, $files, $folderId));
    }

    if ($action === 'create_folder') {
        try {
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $storageKey = (int) (isset($_POST['storage_key']) ? $_POST['storage_key'] : 0);
            $newId = FileFolder::create(array(
                'name'        => $name,
                'parent_id'   => $folderId,
                'storage_key' => $storageKey,
            ));
            $folders = FileFolder::listByParent($folderId);
            $files = $folderId > 0 ? FileItem::listByFolder($folderId) : array();
            $payload = vs_files_payload($folders, $files, $folderId);
            $payload['created_id'] = $newId;
            AjaxResponse::success('文件夹已创建', $payload);
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'upload') {
        if ($folderId <= 0) {
            AjaxResponse::error('请先进入已绑定储存的文件夹再上传');
        }
        if (!isset($_FILES['file'])) {
            AjaxResponse::error('未选择文件');
        }
        try {
            $batch = StorageManager::uploadBatchToFolder(
                $folderId,
                StorageManager::normalizeUploadedFiles($_FILES['file'])
            );
            $folders = FileFolder::listByParent($folderId);
            $files = FileItem::listByFolder($folderId);
            $msg = '成功上传 ' . $batch['uploaded'] . ' 个文件';
            if (!empty($batch['errors'])) {
                $msg .= '，部分失败：' . implode('；', $batch['errors']);
            }
            AjaxResponse::success($msg, vs_files_payload($folders, $files, $folderId));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'rename_folder') {
        $targetId = (int) (isset($_POST['target_id']) ? $_POST['target_id'] : 0);
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        try {
            FileFolder::rename($targetId, $name);
            $folders = FileFolder::listByParent($folderId);
            $files = $folderId > 0 ? FileItem::listByFolder($folderId) : array();
            AjaxResponse::success('文件夹已重命名', vs_files_payload($folders, $files, $folderId));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'delete_folder') {
        $targetId = (int) (isset($_POST['target_id']) ? $_POST['target_id'] : 0);
        try {
            FileFolder::delete($targetId);
            $folders = FileFolder::listByParent($folderId);
            $files = $folderId > 0 ? FileItem::listByFolder($folderId) : array();
            AjaxResponse::success('文件夹已删除', vs_files_payload($folders, $files, $folderId));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'delete_file') {
        $targetId = (int) (isset($_POST['target_id']) ? $_POST['target_id'] : 0);
        try {
            FileItem::delete($targetId);
            $folders = FileFolder::listByParent($folderId);
            $files = FileItem::listByFolder($folderId);
            AjaxResponse::success('文件已删除', vs_files_payload($folders, $files, $folderId));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'list_file_shares') {
        $fileId = (int) (isset($_POST['file_id']) ? $_POST['file_id'] : 0);
        AjaxResponse::success('ok', array('shares' => FileShare::listByFileId($fileId)));
    }

    if ($action === 'create_share') {
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

    if ($action === 'replace_file') {
        if ($folderId <= 0) {
            AjaxResponse::error('请先进入已绑定储存的文件夹');
        }
        $targetId = (int) (isset($_POST['target_id']) ? $_POST['target_id'] : 0);
        if ($targetId <= 0) {
            AjaxResponse::error('未指定要替换的文件');
        }
        if (!isset($_FILES['file'])) {
            AjaxResponse::error('未选择文件');
        }
        $uploads = StorageManager::normalizeUploadedFiles($_FILES['file']);
        if (count($uploads) === 0) {
            AjaxResponse::error('未选择文件');
        }
        if (count($uploads) > 1) {
            AjaxResponse::error('一次只能替换一个文件');
        }
        try {
            $existing = FileItem::find($targetId);
            if ($existing === null || (int) $existing['folder_id'] !== $folderId) {
                AjaxResponse::error('文件不存在或不属于当前文件夹');
            }
            StorageManager::replaceFile($targetId, $uploads[0]);
            $folders = FileFolder::listByParent($folderId);
            $files = FileItem::listByFolder($folderId);
            AjaxResponse::success(
                '文件已替换，存储名「' . $existing['stored_name'] . '」保持不变',
                vs_files_payload($folders, $files, $folderId)
            );
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    AjaxResponse::error('未知操作', 400);
}

$initialFolderId = (int) (isset($_GET['folder']) ? $_GET['folder'] : 0);
$initialFolders = FileFolder::listByParent($initialFolderId);
$initialFiles = $initialFolderId > 0 ? FileItem::listByFolder($initialFolderId) : array();
$initialData = vs_files_payload($initialFolders, $initialFiles, $initialFolderId);

vs_admin_layout_start('文件管理', 'files');
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/files.css?v=<?php echo VS_VERSION; ?>">

<div id="filesFlash" class="vs-settings-flash" role="alert" hidden></div>

<div class="vs-filemgr" id="fileManager"
     data-initial="<?php echo vs_e(json_encode($initialData, JSON_UNESCAPED_UNICODE)); ?>">

    <div class="vs-filemgr__toolbar">
        <div class="vs-filemgr__toolbar-left">
            <button type="button" class="vs-btn vs-btn--default" id="btnNewFolder">新建文件夹</button>
            <label class="vs-btn vs-btn--primary vs-filemgr__upload-btn" id="uploadWrap" hidden>
                上传文件
                <input type="file" id="fileUploadInput" multiple hidden>
            </label>
        </div>
        <div class="vs-filemgr__toolbar-right">
            <span class="vs-filemgr__view-label">视图</span>
            <button type="button" class="vs-filemgr__view-btn is-active" data-view="grid" title="大图标">▦</button>
            <button type="button" class="vs-filemgr__view-btn" data-view="grid-sm" title="小图标">▤</button>
            <button type="button" class="vs-filemgr__view-btn" data-view="list" title="列表">☰</button>
        </div>
    </div>

    <nav class="vs-filemgr__breadcrumb" id="fileBreadcrumb" aria-label="路径"></nav>

    <div class="vs-filemgr__meta" id="folderMeta" hidden></div>

    <div class="vs-filemgr__drop-hint" id="fileDropHint" hidden>松开鼠标以上传文件</div>

    <div class="vs-filemgr__content view-grid" id="fileContent">
        <p class="vs-form-tip" id="fileEmptyTip">加载中…</p>
    </div>
</div>

<div class="vs-modal-shell" id="renameModal" hidden>
    <div class="vs-modal vs-modal--sm">
        <div class="vs-modal__head">
            <h3 class="vs-modal__title">重命名文件夹</h3>
            <button type="button" class="vs-modal__close" data-close-rename aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <form id="renameForm" class="vs-form">
            <input type="hidden" name="target_id" id="renameTargetId" value="">
            <div class="vs-modal__body">
                <div class="vs-form-row">
                    <label class="vs-label">新名称</label>
                    <input type="text" name="name" id="renameInput" class="vs-input" required maxlength="100">
                </div>
            </div>
            <div class="vs-modal__foot">
                <button type="button" class="vs-btn vs-btn--default" data-close-rename>取消</button>
                <button type="submit" class="vs-btn vs-btn--primary">保存</button>
            </div>
        </form>
    </div>
</div>

<div class="vs-modal-shell" id="folderModal" hidden>
    <div class="vs-modal vs-modal--sm">
        <div class="vs-modal__head">
            <h3 class="vs-modal__title">新建文件夹</h3>
            <button type="button" class="vs-modal__close" data-close-modal aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <form id="folderForm" class="vs-form">
            <div class="vs-modal__body">
                <div class="vs-form-row">
                    <label class="vs-label">文件夹名称</label>
                    <input type="text" name="name" class="vs-input" required maxlength="100" autofocus>
                </div>
                <div class="vs-form-row" id="storagePickRow">
                    <label class="vs-label">绑定储存</label>
                    <select name="storage_key" class="vs-input" id="folderStorageSelect"></select>
                </div>
                <p class="vs-form-tip" id="storageInheritTip" hidden>子文件夹将继承上级文件夹的储存方式。</p>
            </div>
            <div class="vs-modal__foot">
                <button type="button" class="vs-btn vs-btn--default" data-close-modal>取消</button>
                <button type="submit" class="vs-btn vs-btn--primary">创建</button>
            </div>
        </form>
    </div>
</div>

<div class="vs-file-preview" id="filePreview" hidden aria-hidden="true">
    <div class="vs-file-preview__overlay" data-close-preview></div>
    <div class="vs-file-preview__panel" role="dialog" aria-modal="true" aria-labelledby="filePreviewTitle">
        <div class="vs-file-preview__handle" aria-hidden="true"></div>
        <div class="vs-file-preview__head">
            <h3 class="vs-file-preview__title" id="filePreviewTitle">文件预览</h3>
            <button type="button" class="vs-file-preview__close" data-close-preview aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="vs-file-preview__body">
            <div class="vs-file-preview__viewer-shell" id="filePreviewViewerShell">
                <div class="vs-file-preview__viewer-toolbar">
                    <button type="button" class="vs-file-preview__expand-btn" id="filePreviewExpand" title="全屏预览" aria-pressed="false">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 2H3v3M10 2h3v3M6 14H3v-3M10 14h3v-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </div>
                <div class="vs-file-preview__viewer-mount" id="filePreviewViewerMount"></div>
                <div class="vs-file-preview__viewer-state" id="filePreviewViewerState">正在加载预览…</div>
            </div>
            <details class="vs-file-preview__details" open>
                <summary class="vs-file-preview__details-toggle">
                    <span class="vs-file-preview__details-icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 2h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.3"/><path d="M5 5h6M5 8h6M5 11h4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                    </span>
                    <span class="vs-file-preview__details-text">文件信息与操作</span>
                    <span class="vs-file-preview__details-chevron" aria-hidden="true"></span>
                </summary>
            <div class="vs-file-preview__details-body">
            <div class="vs-file-preview__meta" id="filePreviewMeta"></div>
            <div class="vs-file-preview__share-section" id="filePreviewShareBox">
                <div class="vs-file-preview__share-head">
                    <span class="vs-file-preview__share-label">分享短链接</span>
                    <button type="button" class="vs-btn vs-btn--primary vs-btn--rect vs-btn--sm" id="filePreviewShareCreate">创建分享</button>
                </div>
                <div class="vs-file-preview__share-single" id="filePreviewShareSingle" hidden>
                    <input type="text" class="vs-file-preview__link-input" id="filePreviewShareUrl" readonly>
                    <button type="button" class="vs-btn vs-btn--default vs-btn--rect vs-btn--sm" id="filePreviewShareCopy">复制</button>
                </div>
                <div class="vs-file-preview__share-multi" id="filePreviewShareMulti" hidden>
                    <p class="vs-file-preview__share-multi-tip">
                        当前文件已创建 <strong id="filePreviewShareCount">0</strong> 条分享链接
                        <button type="button" class="vs-link-btn" id="filePreviewShareToggle">展开查看</button>
                    </p>
                    <ul class="vs-file-preview__share-list" id="filePreviewShareList" hidden></ul>
                </div>
            </div>
            <div class="vs-file-preview__link-box">
                <input type="text" class="vs-file-preview__link-input" id="filePreviewLink" readonly>
                <button type="button" class="vs-btn vs-btn--primary vs-btn--rect" id="filePreviewCopy">复制链接</button>
            </div>
            <div class="vs-file-preview__replace" id="filePreviewReplace">
                <p class="vs-file-preview__replace-tip">替换将<strong>先删除</strong>原文件再上传新内容，保留当前存储名与外链地址。</p>
                <label class="vs-btn vs-btn--primary vs-btn--rect vs-file-preview__replace-btn">
                    选择替换文件
                    <input type="file" id="fileReplaceInput" hidden>
                </label>
                <div class="vs-file-preview__replace-progress" id="fileReplaceProgress" hidden>
                    <div class="vs-file-preview__replace-bar"><span id="fileReplaceFill"></span></div>
                    <span class="vs-file-preview__replace-status" id="fileReplaceStatus">替换中…</span>
                </div>
            </div>
            <div class="vs-file-preview__actions">
                <a class="vs-btn vs-btn--default vs-btn--rect" id="filePreviewOpen" href="#" target="_blank" rel="noopener">新窗口打开</a>
                <a class="vs-btn vs-btn--default vs-btn--rect" id="filePreviewDownload" href="#" download>下载文件</a>
            </div>
            </div>
            </details>
        </div>
    </div>
</div>

<div class="vs-modal-shell vs-modal-shell--stack" id="shareCreateModal" hidden>
    <div class="vs-modal vs-modal--md">
        <div class="vs-modal__head">
            <h3 class="vs-modal__title" id="shareCreateTitle">创建分享</h3>
            <button type="button" class="vs-modal__close" data-close-share-create aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <form id="shareCreateForm" class="vs-form">
            <input type="hidden" name="share_type" id="shareCreateType" value="file">
            <input type="hidden" name="file_id" id="shareCreateFileId" value="">
            <input type="hidden" name="folder_id" id="shareCreateFolderId" value="">
            <div class="vs-modal__body">
                <div class="vs-form-row">
                    <label class="vs-label">分享标题（可选）</label>
                    <input type="text" name="title" id="shareCreateTitleInput" class="vs-input" maxlength="255">
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">访问密码（可选）</label>
                    <input type="password" name="password" id="shareCreatePassword" class="vs-input" autocomplete="new-password" placeholder="留空则无需密码">
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">过期日期（可选，留空表示永不过期）</label>
                    <div id="shareCreateExpiresWrap">
                        <input type="text" class="vs-input vs-date-input" id="shareCreateExpiresDate" placeholder="YYYY-MM-DD" inputmode="numeric" autocomplete="off" spellcheck="false">
                    </div>
                    <p class="vs-form-tip">手动输入日期，例如 2026-12-31，到期当日 23:59 失效</p>
                </div>
                <div class="vs-form-row">
                    <label class="vs-label">最大下载次数（0=不限）</label>
                    <input type="number" name="max_downloads" id="shareCreateMaxDl" class="vs-input" min="0" value="0">
                </div>
                <div class="vs-form-row">
                    <label class="vs-checkbox">
                        <input type="checkbox" name="allow_preview" id="shareCreatePreview" value="1" checked>
                        <span>允许访客在线预览（经服务器中转，不暴露云储直链）</span>
                    </label>
                </div>
            </div>
            <div class="vs-modal__foot">
                <button type="button" class="vs-btn vs-btn--default vs-btn--rect" data-close-share-create>取消</button>
                <button type="submit" class="vs-btn vs-btn--primary vs-btn--rect">生成短链接</button>
            </div>
        </form>
    </div>
</div>

<script>
window.VS_FILE_PREVIEW = <?php echo json_encode(array(
    'streamBase' => $vsBase . '/admin/file-stream.php',
    'assetBase'  => $vsBase . '/assets/vendor/preview/',
), JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php vs_admin_layout_end(array('datetime-picker.js', 'file-preview.js', 'files.js')); ?>
