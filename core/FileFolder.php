<?php
/**
 * 文件：core/FileFolder.php
 * 作用：文件管理虚拟文件夹
 * @version 1.0.31
 */

class FileFolder
{
    /**
     * @param int $parentId
     * @return array
     */
    public static function listByParent($parentId)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_folder');
            $stmt = $pdo->prepare(
                'SELECT * FROM `' . $table . '` WHERE `parent_id` = ? ORDER BY `name` ASC, `id` ASC'
            );
            $stmt->execute(array((int) $parentId));
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
        if ((int) $id <= 0) {
            return null;
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('file_folder');
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
            $stmt->execute(array((int) $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param int $id
     * @return array<int, array>
     */
    public static function breadcrumb($id)
    {
        $trail = array();
        $current = (int) $id;

        while ($current > 0) {
            $folder = self::find($current);
            if ($folder === null) {
                break;
            }
            array_unshift($trail, $folder);
            $current = (int) $folder['parent_id'];
        }

        return $trail;
    }

    /**
     * @param int $folderId
     * @return string
     */
    public static function storagePath($folderId)
    {
        $parts = array();
        foreach (self::breadcrumb($folderId) as $folder) {
            $parts[] = self::sanitizeSegment($folder['name']);
        }
        return implode('/', $parts);
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function create(array $data)
    {
        $name = trim(isset($data['name']) ? $data['name'] : '');
        $parentId = (int) (isset($data['parent_id']) ? $data['parent_id'] : 0);
        $storageKey = (int) (isset($data['storage_key']) ? $data['storage_key'] : 0);

        if ($name === '') {
            throw new Exception('请填写文件夹名称');
        }

        if (preg_match('/[\\\\\\/:*?"<>|]/', $name)) {
            throw new Exception('文件夹名称不能包含 \\ / : * ? " < > |');
        }

        if ($parentId > 0) {
            $parent = self::find($parentId);
            if ($parent === null) {
                throw new Exception('上级文件夹不存在');
            }
            $storageKey = (int) $parent['storage_key'];
        }

        if ($storageKey <= 0 || StorageRegistry::type($storageKey) === null) {
            throw new Exception('请选择有效的储存方式');
        }
        if (!StorageRegistry::isEnabled($storageKey)) {
            throw new Exception('所选储存未启用，请先在系统设置中配置');
        }

        if (self::existsName($parentId, $name)) {
            throw new Exception('同级目录下已存在同名文件夹');
        }

        $pdo = Database::connect();
        $table = Database::table('file_folder');
        $stmt = $pdo->prepare(
            'INSERT INTO `' . $table . '` (`parent_id`, `name`, `storage_key`) VALUES (?, ?, ?)'
        );
        $stmt->execute(array($parentId, $name, $storageKey));

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param int    $parentId
     * @param string $name
     * @return bool
     */
    public static function existsName($parentId, $name)
    {
        $pdo = Database::connect();
        $table = Database::table('file_folder');
        $stmt = $pdo->prepare(
            'SELECT `id` FROM `' . $table . '` WHERE `parent_id` = ? AND `name` = ? LIMIT 1'
        );
        $stmt->execute(array((int) $parentId, $name));
        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param int   $id
     * @param string $name
     * @return void
     * @throws Exception
     */
    public static function rename($id, $name)
    {
        $folder = self::find($id);
        if ($folder === null) {
            throw new Exception('文件夹不存在');
        }

        $name = trim($name);
        if ($name === '') {
            throw new Exception('请填写文件夹名称');
        }
        if (preg_match('/[\\\\\\/:*?"<>|]/', $name)) {
            throw new Exception('文件夹名称不能包含 \\ / : * ? " < > |');
        }

        $parentId = (int) $folder['parent_id'];
        if (self::existsName($parentId, $name) && $folder['name'] !== $name) {
            throw new Exception('同级目录下已存在同名文件夹');
        }

        $pdo = Database::connect();
        $table = Database::table('file_folder');
        $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `name` = ? WHERE `id` = ?');
        $stmt->execute(array($name, (int) $id));
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function delete($id)
    {
        $folder = self::find($id);
        if ($folder === null) {
            throw new Exception('文件夹不存在');
        }

        $pdo = Database::connect();
        $folderTable = Database::table('file_folder');
        $fileTable = Database::table('file_item');

        $childStmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $folderTable . '` WHERE `parent_id` = ?');
        $childStmt->execute(array((int) $id));
        if ((int) $childStmt->fetchColumn() > 0) {
            throw new Exception('请先删除子文件夹');
        }

        $fileStmt = $pdo->prepare('SELECT COUNT(*) FROM `' . $fileTable . '` WHERE `folder_id` = ?');
        $fileStmt->execute(array((int) $id));
        if ((int) $fileStmt->fetchColumn() > 0) {
            throw new Exception('请先删除文件夹内的文件');
        }

        $del = $pdo->prepare('DELETE FROM `' . $folderTable . '` WHERE `id` = ?');
        $del->execute(array((int) $id));
    }

    /**
     * @param int $folderId
     * @return array<int>
     */
    public static function collectDescendantIds($folderId)
    {
        $folderId = (int) $folderId;
        if ($folderId <= 0) {
            return array();
        }

        $ids = array();
        foreach (self::listByParent($folderId) as $child) {
            $childId = (int) $child['id'];
            $ids[] = $childId;
            $ids = array_merge($ids, self::collectDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * @param int $folderId
     * @return array
     */
    public static function listFilesRecursive($folderId)
    {
        $folderId = (int) $folderId;
        if ($folderId <= 0) {
            return array();
        }

        $files = FileItem::listByFolder($folderId);
        foreach (self::listByParent($folderId) as $child) {
            $files = array_merge($files, self::listFilesRecursive((int) $child['id']));
        }

        return $files;
    }

    /**
     * @param string $segment
     * @return string
     */
    private static function sanitizeSegment($segment)
    {
        $segment = trim((string) $segment);
        $segment = preg_replace('/[\\\\\\/:*?"<>|]+/', '_', $segment);
        return $segment !== '' ? $segment : 'folder';
    }
}
