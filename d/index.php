<?php
/**
 * 文件：d/index.php
 * 作用：公开分享页（入口 /d/?token=）
 * @version 1.0.63
 */

require __DIR__ . '/boot.php';

InstallChecker::requireInstalled();

vs_share_send_security_headers(false);

header('Content-Type: text/html; charset=utf-8');

$token = ShareRouter::parseToken();
if ($token === '') {
    require __DIR__ . '/guard.php';
    exit;
}

$share = FileShare::findByToken($token);
if ($share === null) {
    require __DIR__ . '/guard.php';
    exit;
}

$valid = FileShare::validateActive($share);
$errorMsg = empty($valid['ok']) ? $valid['msg'] : '';

$needsPassword = FileShare::requiresPassword($share);
$unlocked = FileShare::isUnlocked($token);
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_password'])) {
    $attemptKey = 'vs_share_attempt_' . md5($token);
    $attempts = isset($_SESSION[$attemptKey]) ? (int) $_SESSION[$attemptKey] : 0;
    if ($attempts >= 8) {
        $passwordError = '密码尝试次数过多，请稍后再试';
    } elseif (FileShare::verifyPassword($share, (string) $_POST['share_password'])) {
        FileShare::unlock($token);
        $unlocked = true;
        unset($_SESSION[$attemptKey]);
        header('Location: ' . FileShare::publicUrl($token));
        exit;
    } else {
        $_SESSION[$attemptKey] = $attempts + 1;
        $passwordError = '访问密码错误';
    }
}

$base = vs_base_url();
$siteName = SiteContext::siteName();
$shareTitle = (string) $share['title'];
$allowPreview = (int) $share['allow_preview'] === 1;
$isFolderShare = $share['share_type'] === FileShare::TYPE_FOLDER;

if ($errorMsg === '' && (!$needsPassword || $unlocked)) {
    FileShare::recordView((int) $share['id']);
    FileShare::grantStreamSession((int) $share['id'], $token);
}

$shareFiles = array();
$shareStreams = array();
if ($errorMsg === '' && (!$needsPassword || $unlocked)) {
    foreach (FileShare::listShareFiles($share) as $row) {
        $fileId = (int) $row['id'];
        $shareFiles[] = array(
            'id'            => $fileId,
            'original_name' => (string) $row['original_name'],
            'stored_name'   => (string) $row['stored_name'],
            'mime_type'     => (string) $row['mime_type'],
            'file_size'     => (int) $row['file_size'],
        );
        $shareStreams[(string) $fileId] = FileShare::signedStreamUrl((int) $share['id'], $fileId, false);
    }
}

$fileCount = count($shareFiles);
$canShowContent = $errorMsg === '' && (!$needsPassword || $unlocked) && $fileCount > 0;
$showPreviewPanel = $canShowContent && $allowPreview;

function vs_share_format_size($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    if ($bytes < 1073741824) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return round($bytes / 1073741824, 2) . ' GB';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo vs_e($shareTitle); ?> - <?php echo vs_e($siteName); ?></title>
    <?php vs_render_share_head_extras(); ?>
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/common.css?v=<?php echo VS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/files.css?v=<?php echo VS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/share-public.css?v=<?php echo VS_VERSION; ?>">
</head>
<body class="vs-share-page">
<div class="vs-share-shell">
    <header class="vs-share-shell__topbar">
        <div class="vs-share-shell__brand">
            <?php if (SiteContext::siteLogo() !== '') { ?>
                <?php vs_render_site_logo('vs-share-shell__logo'); ?>
            <?php } ?>
            <span class="vs-share-shell__site"><?php echo vs_e($siteName); ?></span>
        </div>
        <span class="vs-share-shell__badge">安全分享</span>
    </header>

    <div class="vs-share-shell__hero">
        <h1 class="vs-share-shell__title"><?php echo vs_e($shareTitle); ?></h1>
        <div class="vs-share-shell__tags">
            <?php if ($isFolderShare) { ?>
                <span class="vs-share-tag">文件夹 · <?php echo (int) $fileCount; ?> 个文件</span>
            <?php } else { ?>
                <span class="vs-share-tag">单文件分享</span>
            <?php } ?>
            <?php if ($needsPassword) { ?>
                <span class="vs-share-tag vs-share-tag--lock">访问密码</span>
            <?php } ?>
            <?php if ($allowPreview) { ?>
                <span class="vs-share-tag vs-share-tag--preview">可预览</span>
            <?php } ?>
        </div>
    </div>

    <main class="vs-share-shell__main">
<?php if ($errorMsg !== '') { ?>
        <div class="vs-share-alert vs-share-alert--error"><?php echo vs_e($errorMsg); ?></div>
<?php } elseif ($needsPassword && !$unlocked) { ?>
        <div class="vs-share-panel">
            <div class="vs-share-panel__icon vs-share-panel__icon--lock" aria-hidden="true"></div>
            <h2 class="vs-share-panel__title">请输入访问密码</h2>
            <p class="vs-share-panel__desc">此分享已开启密码保护，验证通过后可查看与下载文件。</p>
            <?php if ($passwordError !== '') { ?>
                <div class="vs-share-alert vs-share-alert--error"><?php echo vs_e($passwordError); ?></div>
            <?php } ?>
            <form method="post" class="vs-share-panel__form">
                <input type="password" name="share_password" class="vs-share-input" placeholder="访问密码" required autocomplete="current-password">
                <button type="submit" class="vs-share-btn vs-share-btn--primary">确认访问</button>
            </form>
        </div>
<?php } elseif ($fileCount === 0) { ?>
        <div class="vs-share-alert">暂无可访问的文件</div>
<?php } else { ?>
        <div class="vs-share-layout<?php echo $showPreviewPanel ? ' has-preview' : ''; ?>"
             id="shareLayout"
             data-share-type="<?php echo vs_e($share['share_type']); ?>"
             data-allow-preview="<?php echo $allowPreview ? '1' : '0'; ?>">

            <?php if ($showPreviewPanel) { ?>
            <section class="vs-share-preview" id="sharePreview">
                <div class="vs-share-preview__head">
                    <span class="vs-share-preview__label">在线预览</span>
                    <span class="vs-share-preview__hint" id="sharePreviewHint">正在加载…</span>
                </div>
                <div class="vs-file-preview__viewer-shell vs-share-preview__stage" id="sharePreviewShell">
                    <div class="vs-file-preview__viewer-mount" id="sharePreviewMount"></div>
                    <div class="vs-file-preview__viewer-state" id="sharePreviewState">正在加载预览…</div>
                </div>
            </section>
            <?php } ?>

            <section class="vs-share-files">
                <div class="vs-share-files__head">
                    <h2 class="vs-share-files__title"><?php echo $isFolderShare ? '文件列表' : '文件信息'; ?></h2>
                    <?php if ($isFolderShare) { ?>
                        <span class="vs-share-files__count"><?php echo (int) $fileCount; ?> 项</span>
                    <?php } ?>
                </div>
                <ul class="vs-share-files__list" id="shareFileList">
                    <?php foreach ($shareFiles as $file) {
                        $dlUrl = FileShare::streamUrl($token, $file['id'], true);
                        $streamUrl = isset($shareStreams[(string) $file['id']]) ? $shareStreams[(string) $file['id']] : '';
                        $thumbUrl = ($allowPreview && vs_share_file_kind($file) === 'image' && $streamUrl !== '') ? $streamUrl : '';
                        $iconHtml = vs_share_file_icon_html($file, $thumbUrl);
                        ?>
                    <li class="vs-share-files__item" data-file-id="<?php echo (int) $file['id']; ?>">
                        <?php echo $iconHtml; ?>
                        <div class="vs-share-files__body">
                            <?php if ($allowPreview) { ?>
                            <button type="button" class="vs-share-files__name" data-preview-file="<?php echo (int) $file['id']; ?>">
                                <?php echo vs_e($file['original_name']); ?>
                            </button>
                            <?php } else { ?>
                            <span class="vs-share-files__name is-static"><?php echo vs_e($file['original_name']); ?></span>
                            <?php } ?>
                            <span class="vs-share-files__meta"><?php echo vs_e(vs_share_format_size($file['file_size'])); ?></span>
                        </div>
                        <a class="vs-share-btn vs-share-btn--ghost vs-share-files__dl" href="<?php echo vs_e($dlUrl); ?>" download rel="noopener">下载</a>
                    </li>
                    <?php } ?>
                </ul>
            </section>
        </div>
<?php } ?>
    </main>

    <?php vs_render_share_footer($siteName, '请遵守法律法规，仅在授权范围内访问与使用分享内容。'); ?>
</div>

<?php if ($canShowContent && $allowPreview) { ?>
<script>
window.VS_FILE_PREVIEW = {
    shareMode: true,
    shareStreams: <?php echo json_encode($shareStreams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
    assetBase: <?php echo json_encode($base . '/assets/vendor/preview/'); ?>
};
window.VS_SHARE_FILES = <?php echo json_encode($shareFiles, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo vs_e($base); ?>/assets/js/file-preview.js?v=<?php echo VS_VERSION; ?>"></script>
<script src="<?php echo vs_e($base); ?>/assets/js/share-public.js?v=<?php echo VS_VERSION; ?>"></script>
<?php } ?>
</body>
</html>
