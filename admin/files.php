<?php
/**
 * 文件：admin/files.php
 * 作用：文件管理（文件夹绑定储存、上传、浏览）
 * @version 1.0.34
 */

require_once __DIR__ . '/init.php';

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
        <div class="vs-file-preview__head">
            <h3 class="vs-file-preview__title" id="filePreviewTitle">文件预览</h3>
            <button type="button" class="vs-file-preview__close" data-close-preview aria-label="关闭">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 4l8 8M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
        </div>
        <div class="vs-file-preview__body">
            <div class="vs-file-preview__media" id="filePreviewMedia"></div>
            <div class="vs-file-preview__meta" id="filePreviewMeta"></div>
            <div class="vs-file-preview__link-box">
                <input type="text" class="vs-file-preview__link-input" id="filePreviewLink" readonly>
                <button type="button" class="vs-btn vs-btn--primary" id="filePreviewCopy">复制链接</button>
            </div>
            <div class="vs-file-preview__actions">
                <a class="vs-btn vs-btn--default" id="filePreviewOpen" href="#" target="_blank" rel="noopener">新窗口打开</a>
                <a class="vs-btn vs-btn--default" id="filePreviewDownload" href="#" download>下载文件</a>
            </div>
        </div>
    </div>
</div>

<?php vs_admin_layout_end(array('files.js')); ?>
