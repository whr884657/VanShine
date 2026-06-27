<?php
/**
 * 文件：d/stream-handler.php
 * 作用：分享文件流输出（由 index.php 或 stream.php 调用，需已 boot）
 * @version 1.0.55
 */

header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: SAMEORIGIN');

$token = ShareRouter::parseToken();
$fileId = (int) (isset($_GET['file']) ? $_GET['file'] : 0);
$download = !empty($_GET['download']);

if ($token === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'forbidden';
    exit;
}

$share = FileShare::findByToken($token);
if ($share === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'not found';
    exit;
}

$valid = FileShare::validateActive($share);
if (empty($valid['ok'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo $valid['msg'];
    exit;
}

if (FileShare::requiresPassword($share) && !FileShare::isUnlocked($token)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '需要访问密码';
    exit;
}

$item = FileShare::resolveFile($share, $fileId);
if ($item === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'file not found';
    exit;
}

if ($download) {
    FileShare::recordDownload((int) $share['id']);
}

try {
    StorageManager::streamFileItem($item, array(
        'disposition' => $download ? 'attachment' : 'inline',
    ));
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'stream error';
    exit;
}
