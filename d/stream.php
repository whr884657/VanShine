<?php
/**
 * 文件：d/stream.php
 * 作用：分享文件流输出（签名 URL + 会话校验）
 * @version 1.0.59
 */

require __DIR__ . '/boot.php';

InstallChecker::requireInstalled();

vs_share_send_security_headers(true);

$shareId = (int) (isset($_GET['s']) ? $_GET['s'] : 0);
$fileId = (int) (isset($_GET['f']) ? $_GET['f'] : 0);
$exp = (int) (isset($_GET['e']) ? $_GET['e'] : 0);
$sig = isset($_GET['k']) ? trim((string) $_GET['k']) : '';
$download = !empty($_GET['d']);

if ($shareId <= 0 || $fileId <= 0 || $exp <= 0 || $sig === '') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'forbidden';
    exit;
}

if (!FileShare::verifyStreamSignature($shareId, $fileId, $exp, $sig, $download)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'forbidden';
    exit;
}

if (!FileShare::hasStreamSession($shareId)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'forbidden';
    exit;
}

$share = FileShare::find($shareId);
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

if (FileShare::requiresPassword($share)) {
    $token = FileShare::streamSessionToken($shareId);
    if ($token === '' || !FileShare::isUnlocked($token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo '需要访问密码';
        exit;
    }
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
