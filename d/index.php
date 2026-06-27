<?php
/**
 * 文件：d/index.php
 * 作用：公开分享页（入口 /d/index.php 或 /d/index.php/{token}）
 * @version 1.0.57
 */

require __DIR__ . '/boot.php';

InstallChecker::requireInstalled();

header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

ShareRouter::redirectLegacyToQueryIfNeeded();

if (ShareRouter::isStreamRequest()) {
    require __DIR__ . '/stream-handler.php';
    exit;
}

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
$streamBase = rtrim($base, '/') . '/d/?token=' . rawurlencode($token) . '&stream=1';

if ($errorMsg === '' && (!$needsPassword || $unlocked)) {
    FileShare::recordView((int) $share['id']);
}

$shareFiles = array();
if ($errorMsg === '' && (!$needsPassword || $unlocked)) {
    foreach (FileShare::listShareFiles($share) as $row) {
        $shareFiles[] = array(
            'id'            => (int) $row['id'],
            'original_name' => (string) $row['original_name'],
            'stored_name'   => (string) $row['stored_name'],
            'mime_type'     => (string) $row['mime_type'],
            'file_size'     => (int) $row['file_size'],
        );
    }
}

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
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/common.css?v=<?php echo VS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/files.css?v=<?php echo VS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/share-public.css?v=<?php echo VS_VERSION; ?>">
</head>
<body class="vs-share-page">
<header class="vs-share-page__head">
    <div class="vs-share-page__brand"><?php echo vs_e($siteName); ?></div>
    <h1 class="vs-share-page__title"><?php echo vs_e($shareTitle); ?></h1>
    <p class="vs-share-page__meta">
        <?php if ($share['share_type'] === FileShare::TYPE_FOLDER) { ?>
            文件夹分享 · <?php echo count($shareFiles); ?> 个文件
        <?php } else { ?>
            单文件分享
        <?php } ?>
        <?php if ($needsPassword) { ?> · 已设访问密码<?php } ?>
    </p>
</header>

<main class="vs-share-page__main">
<?php if ($errorMsg !== '') { ?>
    <div class="vs-share-page__alert vs-share-page__alert--error"><?php echo vs_e($errorMsg); ?></div>
<?php } elseif ($needsPassword && !$unlocked) { ?>
    <div class="vs-share-page__panel">
        <h2 class="vs-share-page__panel-title">请输入访问密码</h2>
        <?php if ($passwordError !== '') { ?>
            <p class="vs-share-page__alert vs-share-page__alert--error"><?php echo vs_e($passwordError); ?></p>
        <?php } ?>
        <form method="post" class="vs-share-page__password-form">
            <input type="password" name="share_password" class="vs-input" placeholder="访问密码" required autocomplete="current-password">
            <button type="submit" class="vs-btn vs-btn--primary">确认访问</button>
        </form>
    </div>
<?php } elseif (count($shareFiles) === 0) { ?>
    <div class="vs-share-page__alert">暂无可访问的文件</div>
<?php } else { ?>
    <div class="vs-share-layout" id="shareLayout"
         data-share-type="<?php echo vs_e($share['share_type']); ?>"
         data-allow-preview="<?php echo (int) $share['allow_preview']; ?>">
        <?php if ((int) $share['allow_preview'] === 1) { ?>
        <section class="vs-share-preview" id="sharePreview" hidden>
            <div class="vs-file-preview__viewer-shell is-fill" id="sharePreviewShell">
                <div class="vs-file-preview__viewer-mount" id="sharePreviewMount"></div>
                <div class="vs-file-preview__viewer-state" id="sharePreviewState">选择文件以预览</div>
            </div>
        </section>
        <?php } ?>

        <section class="vs-share-files">
            <h2 class="vs-share-files__title"><?php echo $share['share_type'] === FileShare::TYPE_FOLDER ? '文件夹内容' : '分享文件'; ?></h2>
            <ul class="vs-share-files__list" id="shareFileList">
                <?php foreach ($shareFiles as $file) {
                    $dlUrl = FileShare::streamUrl($token, $file['id'], true);
                    ?>
                <li class="vs-share-files__item" data-file-id="<?php echo (int) $file['id']; ?>">
                    <button type="button" class="vs-share-files__name" data-preview-file="<?php echo (int) $file['id']; ?>">
                        <?php echo vs_e($file['original_name']); ?>
                    </button>
                    <span class="vs-share-files__size"><?php echo vs_e(vs_share_format_size($file['file_size'])); ?></span>
                    <a class="vs-btn vs-btn--default vs-share-files__dl" href="<?php echo vs_e($dlUrl); ?>" download rel="noopener">下载</a>
                </li>
                <?php } ?>
            </ul>
        </section>
    </div>
<?php } ?>
</main>

<footer class="vs-share-page__foot">
    <p>请遵守法律法规，仅在授权范围内访问与使用分享内容。</p>
</footer>
<?php vs_render_site_footer($siteName); ?>

<?php if ($errorMsg === '' && (!$needsPassword || $unlocked) && count($shareFiles) > 0 && (int) $share['allow_preview'] === 1) { ?>
<script>
window.VS_FILE_PREVIEW = {
    shareMode: true,
    shareToken: <?php echo json_encode($token); ?>,
    streamBase: <?php echo json_encode($streamBase); ?>,
    assetBase: <?php echo json_encode($base . '/assets/vendor/preview/'); ?>
};
window.VS_SHARE_FILES = <?php echo json_encode($shareFiles, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo vs_e($base); ?>/assets/js/file-preview.js?v=<?php echo VS_VERSION; ?>"></script>
<script src="<?php echo vs_e($base); ?>/assets/js/share-public.js?v=<?php echo VS_VERSION; ?>"></script>
<?php } ?>
</body>
</html>
