<?php
/**
 * 文件：core/FileShare.php
 * 作用：文件/文件夹分享链接
 * @version 1.0.58
 */

class FileShare
{
    const TYPE_FILE = 'file';
    const TYPE_FOLDER = 'folder';

    /**
     * @return string
     */
    public static function generateToken()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param string $token
     * @return string
     */
    public static function publicUrl($token)
    {
        return rtrim(vs_base_url(), '/') . '/d/?token=' . rawurlencode((string) $token);
    }

    /**
     * @param string $token
     * @return string
     */
    public static function streamUrl($token, $fileId, $download = false)
    {
        $query = array(
            'token'  => (string) $token,
            'stream' => 1,
            'file'   => (int) $fileId,
        );
        if ($download) {
            $query['download'] = 1;
        }
        return rtrim(vs_base_url(), '/') . '/d/?' . http_build_query($query);
    }

    /**
     * @param int $id
     * @return array|null
     */
    public static function find($id)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_share');
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
            $stmt->execute(array((int) $id));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $token
     * @return array|null
     */
    public static function findByToken($token)
    {
        $token = trim((string) $token);
        if ($token === '' || !preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            return null;
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('file_share');
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `token` = ? LIMIT 1');
            $stmt->execute(array($token));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public static function listAll()
    {
        try {
            $pdo = Database::connect();
            $shareTable = Database::table('file_share');
            $fileTable = Database::table('file_item');
            $folderTable = Database::table('file_folder');
            $sql = 'SELECT s.*, '
                . 'f.`original_name` AS file_name, f.`stored_name` AS file_stored, f.`storage_key` AS file_storage_key, '
                . 'fo.`name` AS folder_name, fo.`storage_key` AS folder_storage_key '
                . 'FROM `' . $shareTable . '` s '
                . 'LEFT JOIN `' . $fileTable . '` f ON s.`file_id` = f.`id` '
                . 'LEFT JOIN `' . $folderTable . '` fo ON s.`folder_id` = fo.`id` '
                . 'ORDER BY s.`id` DESC';
            $stmt = $pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function create(array $data)
    {
        $type = isset($data['share_type']) ? (string) $data['share_type'] : self::TYPE_FILE;
        if ($type !== self::TYPE_FILE && $type !== self::TYPE_FOLDER) {
            throw new Exception('分享类型无效');
        }

        $fileId = (int) (isset($data['file_id']) ? $data['file_id'] : 0);
        $folderId = (int) (isset($data['folder_id']) ? $data['folder_id'] : 0);
        if ($type === self::TYPE_FILE) {
            if ($fileId <= 0 || FileItem::find($fileId) === null) {
                throw new Exception('文件不存在');
            }
            $folderId = 0;
        } else {
            if ($folderId <= 0 || FileFolder::find($folderId) === null) {
                throw new Exception('文件夹不存在');
            }
            $fileId = 0;
        }

        $token = self::generateToken();
        $title = trim((string) (isset($data['title']) ? $data['title'] : ''));
        $password = trim((string) (isset($data['password']) ? $data['password'] : ''));
        $passwordHash = $password !== '' ? vs_password_hash($password) : '';
        $expiresAt = self::normalizeDateTime(isset($data['expires_at']) ? $data['expires_at'] : '');
        $maxDownloads = max(0, (int) (isset($data['max_downloads']) ? $data['max_downloads'] : 0));
        $allowPreview = !empty($data['allow_preview']) ? 1 : 0;

        if ($title === '') {
            if ($type === self::TYPE_FILE) {
                $item = FileItem::find($fileId);
                $title = $item ? (string) $item['original_name'] : '文件分享';
            } else {
                $folder = FileFolder::find($folderId);
                $title = $folder ? (string) $folder['name'] : '文件夹分享';
            }
        }

        $pdo = Database::connect();
        $table = Database::table('file_share');
        $stmt = $pdo->prepare(
            'INSERT INTO `' . $table . '` '
            . '(`token`, `share_type`, `file_id`, `folder_id`, `title`, `password_hash`, '
            . '`expires_at`, `max_downloads`, `allow_preview`, `enabled`) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute(array(
            $token,
            $type,
            $fileId,
            $folderId,
            $title,
            $passwordHash,
            $expiresAt,
            $maxDownloads,
            $allowPreview,
        ));

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param int   $id
     * @param array $data
     * @return void
     * @throws Exception
     */
    public static function update($id, array $data)
    {
        $row = self::find($id);
        if ($row === null) {
            throw new Exception('分享不存在');
        }

        $title = trim((string) (isset($data['title']) ? $data['title'] : $row['title']));
        $enabled = isset($data['enabled']) ? ((int) $data['enabled'] ? 1 : 0) : (int) $row['enabled'];
        $allowPreview = isset($data['allow_preview']) ? ((int) $data['allow_preview'] ? 1 : 0) : (int) $row['allow_preview'];
        $expiresAt = array_key_exists('expires_at', $data)
            ? self::normalizeDateTime($data['expires_at'])
            : $row['expires_at'];
        $maxDownloads = array_key_exists('max_downloads', $data)
            ? max(0, (int) $data['max_downloads'])
            : (int) $row['max_downloads'];

        $passwordHash = (string) $row['password_hash'];
        if (array_key_exists('password', $data)) {
            $password = trim((string) $data['password']);
            if ($password === '') {
                $passwordHash = '';
            } else {
                $passwordHash = vs_password_hash($password);
            }
        }

        $pdo = Database::connect();
        $table = Database::table('file_share');
        $stmt = $pdo->prepare(
            'UPDATE `' . $table . '` SET '
            . '`title` = ?, `password_hash` = ?, `expires_at` = ?, `max_downloads` = ?, '
            . '`allow_preview` = ?, `enabled` = ? WHERE `id` = ?'
        );
        $stmt->execute(array(
            $title,
            $passwordHash,
            $expiresAt,
            $maxDownloads,
            $allowPreview,
            $enabled,
            (int) $id,
        ));
    }

    /**
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function delete($id)
    {
        $pdo = Database::connect();
        $table = Database::table('file_share');
        $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `id` = ?');
        $stmt->execute(array((int) $id));
    }

    /**
     * @param array $share
     * @return array{ok:bool,msg:string}
     */
    public static function validateActive(array $share)
    {
        if (empty($share['enabled'])) {
            return array('ok' => false, 'msg' => '分享链接已停用');
        }

        if (!empty($share['expires_at'])) {
            $expires = strtotime((string) $share['expires_at']);
            if ($expires !== false && time() > $expires) {
                return array('ok' => false, 'msg' => '分享链接已过期');
            }
        }

        $max = (int) $share['max_downloads'];
        if ($max > 0 && (int) $share['download_count'] >= $max) {
            return array('ok' => false, 'msg' => '分享链接下载次数已达上限');
        }

        if ($share['share_type'] === self::TYPE_FILE) {
            if (FileItem::find((int) $share['file_id']) === null) {
                return array('ok' => false, 'msg' => '分享的文件不存在或已被删除');
            }
        } elseif (FileFolder::find((int) $share['folder_id']) === null) {
            return array('ok' => false, 'msg' => '分享的文件夹不存在或已被删除');
        }

        return array('ok' => true, 'msg' => 'ok');
    }

    /**
     * @param array $share
     * @return bool
     */
    public static function requiresPassword(array $share)
    {
        return isset($share['password_hash']) && (string) $share['password_hash'] !== '';
    }

    /**
     * @param array  $share
     * @param string $password
     * @return bool
     */
    public static function verifyPassword(array $share, $password)
    {
        if (!self::requiresPassword($share)) {
            return true;
        }
        return hash_equals((string) $share['password_hash'], vs_password_hash((string) $password));
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function isUnlocked($token)
    {
        $token = (string) $token;
        if ($token === '') {
            return false;
        }
        if (empty($_SESSION['vs_share_unlock']) || !is_array($_SESSION['vs_share_unlock'])) {
            return false;
        }
        if (empty($_SESSION['vs_share_unlock'][$token])) {
            return false;
        }
        $ts = (int) $_SESSION['vs_share_unlock'][$token];
        return $ts > 0 && (time() - $ts) < 86400;
    }

    /**
     * @param string $token
     * @return void
     */
    public static function unlock($token)
    {
        if (!isset($_SESSION['vs_share_unlock']) || !is_array($_SESSION['vs_share_unlock'])) {
            $_SESSION['vs_share_unlock'] = array();
        }
        $_SESSION['vs_share_unlock'][(string) $token] = time();
    }

    /**
     * @param array $share
     * @param int   $fileId
     * @return array|null
     */
    public static function resolveFile(array $share, $fileId)
    {
        if ($share['share_type'] === self::TYPE_FILE) {
            $expected = (int) $share['file_id'];
            if ((int) $fileId > 0 && (int) $fileId !== $expected) {
                return null;
            }
            return FileItem::find($expected);
        }

        $fileId = (int) $fileId;
        if ($fileId <= 0) {
            return null;
        }

        $item = FileItem::find($fileId);
        if ($item === null) {
            return null;
        }

        $rootFolderId = (int) $share['folder_id'];
        $folderIds = FileFolder::collectDescendantIds($rootFolderId);
        $folderIds[] = $rootFolderId;

        if (!in_array((int) $item['folder_id'], $folderIds, true)) {
            return null;
        }

        return $item;
    }

    /**
     * @param array $share
     * @return array
     */
    public static function listShareFiles(array $share)
    {
        if ($share['share_type'] === self::TYPE_FILE) {
            $item = FileItem::find((int) $share['file_id']);
            return $item ? array($item) : array();
        }

        return FileFolder::listFilesRecursive((int) $share['folder_id']);
    }

    /**
     * @param int $id
     * @return void
     */
    public static function recordView($id)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_share');
            $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `view_count` = `view_count` + 1 WHERE `id` = ?');
            $stmt->execute(array((int) $id));
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public static function recordDownload($id)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_share');
            $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `download_count` = `download_count` + 1 WHERE `id` = ?');
            $stmt->execute(array((int) $id));
        } catch (Exception $e) {
            // ignore
        }
    }

    /**
     * @param int $fileId
     * @return array
     */
    public static function listByFileId($fileId)
    {
        $fileId = (int) $fileId;
        if ($fileId <= 0) {
            return array();
        }

        try {
            $pdo = Database::connect();
            $shareTable = Database::table('file_share');
            $fileTable = Database::table('file_item');
            $stmt = $pdo->prepare(
                'SELECT s.*, f.`original_name` AS file_name, f.`storage_key` AS file_storage_key '
                . 'FROM `' . $shareTable . '` s '
                . 'LEFT JOIN `' . $fileTable . '` f ON s.`file_id` = f.`id` '
                . 'WHERE s.`share_type` = ? AND s.`file_id` = ? ORDER BY s.`id` DESC'
            );
            $stmt->execute(array(self::TYPE_FILE, $fileId));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $result = array();
            foreach ($rows as $row) {
                $result[] = self::toAdminRow($row);
            }
            return $result;
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * @param array $row
     * @return array
     */
    public static function toAdminRow(array $row)
    {
        $hasPassword = isset($row['password_hash']) && (string) $row['password_hash'] !== '';
        $target = '';
        if ($row['share_type'] === self::TYPE_FILE) {
            $target = !empty($row['file_name']) ? (string) $row['file_name'] : ('文件 #' . (int) $row['file_id']);
        } else {
            $target = !empty($row['folder_name']) ? (string) $row['folder_name'] : ('文件夹 #' . (int) $row['folder_id']);
        }

        $storageKey = 0;
        if ($row['share_type'] === self::TYPE_FILE) {
            $storageKey = (int) (isset($row['file_storage_key']) ? $row['file_storage_key'] : 0);
        } else {
            $storageKey = (int) (isset($row['folder_storage_key']) ? $row['folder_storage_key'] : 0);
        }
        $storageType = StorageRegistry::type($storageKey);
        $storageName = $storageType ? (string) $storageType['name'] : '未知储存';

        return array(
            'id'              => (int) $row['id'],
            'token'           => (string) $row['token'],
            'share_type'      => (string) $row['share_type'],
            'title'           => (string) $row['title'],
            'target'          => $target,
            'storage_key'     => $storageKey,
            'storage_name'    => $storageName,
            'share_url'       => self::publicUrl($row['token']),
            'has_password'    => $hasPassword,
            'expires_at'      => $row['expires_at'] ? (string) $row['expires_at'] : '',
            'max_downloads'   => (int) $row['max_downloads'],
            'download_count'  => (int) $row['download_count'],
            'view_count'      => (int) $row['view_count'],
            'allow_preview'   => (int) $row['allow_preview'],
            'enabled'         => (int) $row['enabled'],
            'created_at'      => (string) $row['created_at'],
        );
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private static function normalizeDateTime($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
