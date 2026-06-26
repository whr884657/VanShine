<?php
/**
 * 文件：core/LocalFileServe.php
 * 作用：本地储存对外直链（/{slug}/{文件名}）读取与输出
 * @version 1.0.35
 */

class LocalFileServe
{
    /**
     * @param string $storedName
     * @return void
     */
    public static function outputByStoredName($storedName)
    {
        $storedName = basename(str_replace('\\', '/', trim($storedName)));
        if ($storedName === '' || preg_match('/[\/\\\\]/', $storedName)) {
            self::notFound();
        }

        if (!StorageRegistry::isEnabled(1)) {
            self::notFound();
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('file_item');
            $stmt = $pdo->prepare(
                'SELECT * FROM `' . $table . '` WHERE `storage_key` = 1 AND `stored_name` = ? ORDER BY `id` DESC LIMIT 1'
            );
            $stmt->execute(array($storedName));
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            self::notFound();
        }

        if (!$item) {
            self::notFound();
        }

        require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
        require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';

        $configs = StorageRegistry::loadDriverConfigs(1);
        $root = LocalStorageDriver::resolveRootPath($configs);
        $physical = rtrim($root, '/\\') . '/' . str_replace('\\', '/', $item['pathname']);

        if (!is_file($physical) || !is_readable($physical)) {
            self::notFound();
        }

        $mime = isset($item['mime_type']) && $item['mime_type'] !== ''
            ? $item['mime_type']
            : 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($physical));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');

        $download = isset($_GET['download']) && $_GET['download'] === '1';
        if ($download) {
            $name = isset($item['original_name']) && $item['original_name'] !== ''
                ? $item['original_name']
                : $storedName;
            header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . rawurlencode($storedName) . '"');
        }

        readfile($physical);
        exit;
    }

    /**
     * @return void
     */
    private static function notFound()
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo '404 Not Found';
        exit;
    }
}
