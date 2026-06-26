<?php
/**
 * 文件：core/StorageManager.php
 * 作用：文件上传、删除与储存驱动调度
 * @version 1.0.38
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
            throw new Exception('文件上传失败');
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
            throw new Exception('文件上传失败');
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
     * @param string $path
     * @param string $name
     * @return string
     */
    private static function detectMime($path, $name)
    {
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $map = array(
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'pdf'  => 'application/pdf',
            'zip'  => 'application/zip',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
        );

        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }
}
