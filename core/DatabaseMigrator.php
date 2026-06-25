<?php
/**
 * 文件：core/DatabaseMigrator.php
 * 作用：版本更新时执行 install/migrations 下的增量 SQL
 * @version 1.0.15
 */

class DatabaseMigrator
{
    const CONFIG_KEY = 'schema_migrations';

    /**
     * 迁移脚本目录
     *
     * @return string
     */
    public static function migrationsDir()
    {
        return VS_ROOT . '/install/migrations';
    }

    /**
     * 执行尚未应用的迁移
     *
     * @return array
     */
    public static function runPending()
    {
        if (!InstallChecker::isInstalled()) {
            return array('ok' => true, 'msg' => '系统未安装，跳过数据库迁移', 'applied' => array());
        }

        $pending = self::getPendingFiles();
        if (count($pending) === 0) {
            return array('ok' => true, 'msg' => '数据库结构已是最新', 'applied' => array());
        }

        $applied = array();

        try {
            $pdo = Database::connect();
            $prefix = Database::prefix();

            foreach ($pending as $version => $file) {
                self::executeFile($pdo, $file, $prefix);
                self::markApplied($version);
                $applied[] = $version;
            }
        } catch (Exception $e) {
            return array(
                'ok'      => false,
                'msg'     => '数据库迁移失败：' . $e->getMessage(),
                'applied' => $applied,
            );
        }

        Config::clearCache();

        return array(
            'ok'      => true,
            'msg'     => count($applied) > 0 ? '已执行 ' . count($applied) . ' 项数据库迁移' : '数据库结构已是最新',
            'applied' => $applied,
        );
    }

    /**
     * 待执行的迁移文件（按版本升序）
     *
     * @return array
     */
    public static function getPendingFiles()
    {
        $dir = self::migrationsDir();
        if (!is_dir($dir)) {
            return array();
        }

        $applied = self::getAppliedVersions();
        $pending = array();
        $files = glob($dir . '/*.sql');
        if ($files === false) {
            return array();
        }

        foreach ($files as $file) {
            $base = basename($file, '.sql');
            if (!preg_match('/^\d+\.\d+\.\d+$/', $base)) {
                continue;
            }
            if (in_array($base, $applied, true)) {
                continue;
            }
            $pending[$base] = $file;
        }

        uksort($pending, 'version_compare');
        return $pending;
    }

    /**
     * 已应用的迁移版本
     *
     * @return array
     */
    public static function getAppliedVersions()
    {
        $raw = Config::get(self::CONFIG_KEY, '');
        if ($raw === '') {
            return array();
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return array();
        }

        return array_values($data);
    }

    /**
     * 标记迁移版本已应用
     *
     * @param string $version
     * @return void
     * @throws Exception
     */
    public static function markApplied($version)
    {
        $applied = self::getAppliedVersions();
        if (!in_array($version, $applied, true)) {
            $applied[] = $version;
            usort($applied, 'version_compare');
            Config::set(self::CONFIG_KEY, json_encode($applied, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 执行单个迁移文件
     *
     * @param PDO    $pdo
     * @param string $file
     * @param string $prefix
     * @return void
     * @throws Exception
     */
    public static function executeFile(PDO $pdo, $file, $prefix)
    {
        if (!is_file($file) || !is_readable($file)) {
            throw new Exception('迁移文件不可读：' . basename($file));
        }

        $sql = file_get_contents($file);
        $sql = str_replace('{prefix}', $prefix, $sql);
        $statements = DatabaseInstaller::parseSqlStatements($sql);

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
    }
}
