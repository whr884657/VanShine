<?php
/**
 * 文件：core/UpdateLog.php
 * 作用：读取本地/远程版本更新记录（update-log.json）
 * @version 1.0.17
 */

class UpdateLog
{
    const LOCAL_FILE = 'update-log.json';
    const REMOTE_URL = 'https://gitee.com/xunjinlu/VanShine/raw/main/update-log.json';

    /**
     * 本地更新记录文件路径
     *
     * @return string
     */
    public static function localPath()
    {
        return VS_ROOT . '/' . self::LOCAL_FILE;
    }

    /**
     * 读取本地 update-log.json
     *
     * @return array|null
     */
    public static function loadLocal()
    {
        $path = self::localPath();
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    /**
     * 从 Gitee 拉取 update-log.json
     *
     * @return array|null
     */
    public static function fetchRemote()
    {
        $body = Updater::httpGet(self::REMOTE_URL, 15);
        if ($body === false || $body === '') {
            return null;
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * 全部版本记录（新→旧）
     *
     * @param bool $preferRemote
     * @return array
     */
    public static function allVersions($preferRemote = false)
    {
        $data = $preferRemote ? self::fetchRemote() : null;
        if ($data === null) {
            $data = self::loadLocal();
        }
        if ($data === null || empty($data['versions']) || !is_array($data['versions'])) {
            return array();
        }

        $versions = $data['versions'];
        usort($versions, function ($a, $b) {
            return version_compare($b['version'], $a['version']);
        });

        return $versions;
    }

    /**
     * 获取指定版本记录
     *
     * @param string $version
     * @return array|null
     */
    public static function getVersion($version)
    {
        foreach (self::allVersions(false) as $row) {
            if (isset($row['version']) && $row['version'] === $version) {
                return $row;
            }
        }
        return null;
    }

    /**
     * 目标版本是否包含数据库结构变更
     *
     * @param string $version
     * @return bool
     */
    public static function versionHasDbChanges($version)
    {
        $row = self::getVersion($version);
        if ($row === null) {
            return DatabaseMigrator::hasPendingMigrations();
        }

        if (!empty($row['db_changes'])) {
            return true;
        }

        if (!empty($row['migration'])) {
            return DatabaseMigrator::isMigrationPending($row['migration']);
        }

        return false;
    }

    /**
     * 供 API 输出的版本列表
     *
     * @return array
     */
    public static function payloadForApi()
    {
        $list = array();
        foreach (self::allVersions(false) as $row) {
            $list[] = array(
                'version'    => isset($row['version']) ? $row['version'] : '',
                'date'       => isset($row['date']) ? $row['date'] : '',
                'title'      => isset($row['title']) ? $row['title'] : '',
                'db_changes' => !empty($row['db_changes']),
                'changes'    => isset($row['changes']) && is_array($row['changes']) ? $row['changes'] : array(),
            );
        }
        return $list;
    }

    /**
     * 从本地到目标版本之间是否存在数据库变更
     *
     * @param string $localVersion
     * @param string $remoteVersion
     * @return bool
     */
    public static function rangeHasDbChanges($localVersion, $remoteVersion)
    {
        if ($remoteVersion === '') {
            return false;
        }

        foreach (self::allVersions(false) as $row) {
            if (empty($row['version']) || empty($row['db_changes'])) {
                continue;
            }
            $v = $row['version'];
            if (version_compare($v, $localVersion, '>') && version_compare($v, $remoteVersion, '<=')) {
                return true;
            }
        }

        return DatabaseMigrator::hasPendingMigrations();
    }
}
