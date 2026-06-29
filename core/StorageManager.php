<?php
/**
 * 文件：core/StorageManager.php
 * 作用：文件上传、删除与储存驱动调度
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class StorageManager
{
    /**
     * @param mixed $fileField $_FILES['file']
     * @return array<int, array>
     */
    public static function normalizeUploadedFiles($fileField)
    {
        if (!is_array($fileField) || !isset($fileField['name'])) {
            return array();
        }

        if (!is_array($fileField['name'])) {
            return array($fileField);
        }

        $list = array();
        $count = count($fileField['name']);
        for ($i = 0; $i < $count; $i++) {
            if ((int) $fileField['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $list[] = array(
                'name'     => $fileField['name'][$i],
                'type'     => $fileField['type'][$i],
                'tmp_name' => $fileField['tmp_name'][$i],
                'error'    => $fileField['error'][$i],
                'size'     => $fileField['size'][$i],
            );
        }

        return $list;
    }

    /**
     * @param int   $folderId
     * @param array $uploads
     * @return array{uploaded:int, errors:array}
     * @throws Exception
     */
    public static function uploadBatchToFolder($folderId, array $uploads)
    {
        if (count($uploads) === 0) {
            throw new Exception('未选择文件');
        }

        $uploaded = 0;
        $errors = array();
        foreach ($uploads as $upload) {
            try {
                self::uploadToFolder($folderId, $upload);
                $uploaded++;
            } catch (Exception $e) {
                $name = isset($upload['name']) ? basename($upload['name']) : 'file';
                $errors[] = $name . '：' . $e->getMessage();
            }
        }

        if ($uploaded === 0) {
            throw new Exception(implode('；', $errors));
        }

        return array(
            'uploaded' => $uploaded,
            'errors'   => $errors,
        );
    }

    /**
     * @param int   $folderId
     * @param array $upload  $_FILES 单项
     * @return array
     * @throws Exception
     */
    public static function uploadToFolder($folderId, array $upload)
    {
        $folder = FileFolder::find($folderId);
        if ($folder === null) {
            throw new Exception('目标文件夹不存在');
        }

        if (!isset($upload['error']) || (int) $upload['error'] !== UPLOAD_ERR_OK) {
            $code = isset($upload['error']) ? (int) $upload['error'] : UPLOAD_ERR_NO_FILE;
            throw new Exception(self::uploadErrorMessage($code));
        }

        $tmpPath = isset($upload['tmp_name']) ? $upload['tmp_name'] : '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new Exception('无效的上传文件');
        }

        $originalName = isset($upload['name']) ? basename($upload['name']) : 'file';
        $storedName = UploadNaming::generate($originalName);
        $relativeFolder = FileFolder::storagePath($folderId);
        $pathname = $relativeFolder !== '' ? $relativeFolder . '/' . $storedName : $storedName;
        $pathname = str_replace('\\', '/', $pathname);

        $storageKey = (int) $folder['storage_key'];
        $driver = StorageRegistry::driver($storageKey);

        $handle = fopen($tmpPath, 'rb');
        if ($handle === false) {
            throw new Exception('无法读取上传文件');
        }

        try {
            $driver->writeStream($pathname, $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $publicUrl = StorageRegistry::buildUrl($storageKey, $pathname);
        $mimeType = self::detectMime($tmpPath, $originalName);
        $size = isset($upload['size']) ? (int) $upload['size'] : (int) @filesize($tmpPath);

        $id = FileItem::create(array(
            'folder_id'     => (int) $folderId,
            'storage_key'   => $storageKey,
            'pathname'      => $pathname,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'mime_type'     => $mimeType,
            'file_size'     => $size,
            'public_url'    => $publicUrl,
        ));

        return array(
            'id'            => $id,
            'folder_id'     => (int) $folderId,
            'storage_key'   => $storageKey,
            'original_name' => $originalName,
            'stored_name'   => $storedName,
            'pathname'      => $pathname,
            'mime_type'     => $mimeType,
            'file_size'     => $size,
            'public_url'    => $publicUrl,
        );
    }

    /**
     * 替换已有文件：先删储存内旧文件，再以相同 stored_name / pathname 写入新内容
     *
     * @param int   $fileId
     * @param array $upload $_FILES 单项
     * @return array
     * @throws Exception
     */
    public static function replaceFile($fileId, array $upload)
    {
        $item = FileItem::find($fileId);
        if ($item === null) {
            throw new Exception('文件不存在');
        }

        if (!isset($upload['error']) || (int) $upload['error'] !== UPLOAD_ERR_OK) {
            $code = isset($upload['error']) ? (int) $upload['error'] : UPLOAD_ERR_NO_FILE;
            throw new Exception(self::uploadErrorMessage($code));
        }

        $tmpPath = isset($upload['tmp_name']) ? $upload['tmp_name'] : '';
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new Exception('无效的上传文件');
        }

        $storageKey = (int) $item['storage_key'];
        $pathname = str_replace('\\', '/', (string) $item['pathname']);
        if ($pathname === '') {
            throw new Exception('文件路径无效');
        }

        if ($storageKey === 1) {
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
        }

        $driver = StorageRegistry::driver($storageKey);

        if ($driver->exists($pathname)) {
            $driver->delete($pathname);
        }

        $handle = fopen($tmpPath, 'rb');
        if ($handle === false) {
            throw new Exception('无法读取上传文件');
        }

        try {
            $driver->writeStream($pathname, $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $originalName = isset($upload['name']) ? basename($upload['name']) : (string) $item['original_name'];
        $mimeType = self::detectMime($tmpPath, $originalName);
        $size = isset($upload['size']) ? (int) $upload['size'] : (int) @filesize($tmpPath);
        $publicUrl = StorageRegistry::buildUrl($storageKey, $pathname);

        FileItem::update((int) $fileId, array(
            'original_name' => $originalName,
            'mime_type'     => $mimeType,
            'file_size'     => $size,
            'public_url'    => $publicUrl,
        ));

        $updated = FileItem::find($fileId);
        if ($updated === null) {
            throw new Exception('文件记录更新失败');
        }

        return $updated;
    }

    /**
     * @param array $item
     * @return void
     * @throws Exception
     */
    public static function deleteFile(array $item)
    {
        $driver = StorageRegistry::driver((int) $item['storage_key']);
        if ($driver->exists($item['pathname'])) {
            $driver->delete($item['pathname']);
        }
    }

    /**
     * 向浏览器输出文件内容（预览用，不做格式转码）
     *
     * @param array $item file_item 行
     * @return void
     * @throws Exception
     */
    public static function streamFileItem(array $item, array $options = array())
    {
        $disposition = isset($options['disposition']) && $options['disposition'] === 'attachment'
            ? 'attachment'
            : 'inline';
        $storageKey = (int) $item['storage_key'];
        $pathname = str_replace('\\', '/', (string) $item['pathname']);
        if ($pathname === '') {
            throw new Exception('文件路径无效');
        }

        $driver = StorageRegistry::driver($storageKey);
        if (!$driver->exists($pathname)) {
            throw new Exception('文件不存在');
        }

        $mime = isset($item['mime_type']) && $item['mime_type'] !== ''
            ? (string) $item['mime_type']
            : 'application/octet-stream';
        $name = isset($item['stored_name']) && $item['stored_name'] !== ''
            ? (string) $item['stored_name']
            : 'file';

        if ($storageKey === 1) {
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
            $configs = StorageRegistry::loadDriverConfigs(1);
            $root = LocalStorageDriver::resolveRootPath($configs);
            $fullPath = rtrim(str_replace('\\', '/', $root), '/')
                . '/' . ltrim($pathname, '/');
            if (!is_file($fullPath)) {
                throw new Exception('本地文件不存在');
            }
            self::outputLocalFileWithRange($fullPath, $mime, $name, $disposition);
            return;
        }

        $content = $driver->read($pathname);
        if (!is_string($content)) {
            throw new Exception('无法读取文件');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . self::escapeFilename($name) . '"');
        header('Content-Length: ' . strlen($content));
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }

    /**
     * @param string $fullPath
     * @param string $mime
     * @param string $name
     * @param string $disposition
     * @return void
     */
    private static function outputLocalFileWithRange($fullPath, $mime, $name, $disposition = 'inline')
    {
        $size = filesize($fullPath);
        if ($size === false) {
            throw new Exception('无法读取文件大小');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . self::escapeFilename($name) . '"');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');

        $start = 0;
        $end = $size - 1;
        $length = $size;

        if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            if ($matches[1] !== '') {
                $start = (int) $matches[1];
            }
            if ($matches[2] !== '') {
                $end = (int) $matches[2];
            }
            if ($start > $end || $start >= $size) {
                header('HTTP/1.1 416 Range Not Satisfiable');
                header('Content-Range: bytes */' . $size);
                exit;
            }
            if ($end >= $size) {
                $end = $size - 1;
            }
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
            header('Content-Length: ' . $length);
        } else {
            header('Content-Length: ' . $size);
        }

        $handle = fopen($fullPath, 'rb');
        if ($handle === false) {
            throw new Exception('无法打开文件');
        }

        if ($start > 0) {
            fseek($handle, $start);
        }

        $remaining = $length;
        while ($remaining > 0 && !feof($handle)) {
            $read = ($remaining > 8192) ? 8192 : $remaining;
            $buffer = fread($handle, $read);
            if ($buffer === false) {
                break;
            }
            echo $buffer;
            $remaining -= strlen($buffer);
        }

        fclose($handle);
        exit;
    }

    /**
     * @param string $name
     * @return string
     */
    private static function escapeFilename($name)
    {
        return str_replace(array('"', "\r", "\n"), '', (string) $name);
    }

    /**
     * @param string $path
     * @param string $name
     * @return string
     */
    private static function detectMime($path, $name)
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $map = array(
            'jpg'      => 'image/jpeg',
            'jpeg'     => 'image/jpeg',
            'png'      => 'image/png',
            'gif'      => 'image/gif',
            'webp'     => 'image/webp',
            'svg'      => 'image/svg+xml',
            'bmp'      => 'image/bmp',
            'ico'      => 'image/x-icon',
            'avif'     => 'image/avif',
            'pdf'      => 'application/pdf',
            'zip'      => 'application/zip',
            'rar'      => 'application/vnd.rar',
            '7z'       => 'application/x-7z-compressed',
            'mp3'      => 'audio/mpeg',
            'wav'      => 'audio/wav',
            'ogg'      => 'audio/ogg',
            'flac'     => 'audio/flac',
            'm4a'      => 'audio/mp4',
            'aac'      => 'audio/aac',
            'mp4'      => 'video/mp4',
            'webm'     => 'video/webm',
            'ogv'      => 'video/ogg',
            'mov'      => 'video/quicktime',
            'mkv'      => 'video/x-matroska',
            'html'     => 'text/html',
            'htm'      => 'text/html',
            'css'      => 'text/css',
            'js'       => 'text/javascript',
            'mjs'      => 'text/javascript',
            'cjs'      => 'text/javascript',
            'json'     => 'application/json',
            'xml'      => 'application/xml',
            'txt'      => 'text/plain',
            'md'       => 'text/markdown',
            'markdown' => 'text/markdown',
            'php'      => 'text/plain',
            'sql'      => 'application/sql',
            'csv'      => 'text/csv',
            'doc'      => 'application/msword',
            'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'      => 'application/vnd.ms-excel',
            'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        if (isset($map[$ext])) {
            return $map[$ext];
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * @param int $code PHP UPLOAD_ERR_* 常量
     * @return string
     */
    public static function uploadErrorMessage($code)
    {
        switch ((int) $code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return '文件超过服务器允许的上传大小';
            case UPLOAD_ERR_PARTIAL:
                return '文件仅部分上传，请重试';
            case UPLOAD_ERR_NO_FILE:
                return '未选择文件';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '服务器缺少临时目录';
            case UPLOAD_ERR_CANT_WRITE:
                return '服务器无法写入临时文件';
            case UPLOAD_ERR_EXTENSION:
                return '服务器扩展阻止了该类型文件上传';
            default:
                return '文件上传失败（错误码 ' . (int) $code . '）';
        }
    }
}
