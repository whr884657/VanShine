<?php
/**
 * 文件：admin/file-stream.php
 * 作用：后台鉴权文件流（供 Flyfish Viewer 预览，支持 Range）
 * @version 1.0.43
 */

require_once __DIR__ . '/init.php';

$id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'invalid id';
    exit;
}

$item = FileItem::find($id);
if ($item === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'not found';
    exit;
}

try {
    StorageManager::streamFileItem($item);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $e->getMessage();
}
