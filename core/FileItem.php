<?php
/**
 * 文件：core/FileItem.php
 * 作用：文件管理记录
 * @version 1.0.30
 */

class FileItem
{
    /**
     * @param int $folderId
     * @return array
     */
    public static function listByFolder($folderId)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_item');
            $stmt = $pdo->prepare(
                'SELECT * FROM `' . $table . '` WHERE `folder_id` = ? ORDER BY `id` DESC'
            );
            $stmt->execute(array((int) $folderId));
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function find($id)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_item');
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
            $stmt->execute(array((int) $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function create(array $data)
    {
        $pdo = Database::connect();
        $table = Database::table('file_item');
        $stmt = $pdo->prepare(
            'INSERT INTO `' . $table
            . '` (`folder_id`, `storage_key`, `pathname`, `original_name`, `stored_name`, `mime_type`, `file_size`, `public_url`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array(
            (int) $data['folder_id'],
            (int) $data['storage_key'],
            (string) $data['pathname'],
            (string) $data['original_name'],
            (string) $data['stored_name'],
            (string) $data['mime_type'],
            (int) $data['file_size'],
            (string) $data['public_url'],
        ));

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function delete($id)
    {
        $item = self::find($id);
        if ($item === null) {
            throw new Exception('文件不存在');
        }

        StorageManager::deleteFile($item);

        $pdo = Database::connect();
        $table = Database::table('file_item');
        $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `id` = ?');
        $stmt->execute(array((int) $id));
    }
}
