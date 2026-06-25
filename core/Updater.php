<?php
/**
 * 文件：core/Updater.php
 * 作用：VanShine 在线更新（Gitee 版本检测与更新包应用）
 * @version 1.0.15
 */

class Updater
{
    const MANIFEST_URL = 'https://gitee.com/xunjinlu/VanShine/raw/main/update.json';
    const VERSION_URL  = 'https://gitee.com/xunjinlu/VanShine/raw/main/core/version.php';
    const DEFAULT_REPO = 'xunjinlu/VanShine';
    const DEFAULT_BRANCH = 'main';

    /**
     * 本地版本号
     *
     * @return string
     */
    public static function localVersion()
    {
        return defined('VS_VERSION') ? VS_VERSION : '0.0.0';
    }

    /**
     * 更新临时目录
     *
     * @return string
     */
    public static function updateDir()
    {
        $root = VS_ROOT . '/storage';
        $dir = $root . '/update';

        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
            self::writeDenyHtaccess($root);
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        self::writeDenyHtaccess($dir);

        return $dir;
    }

    /**
     * 写入禁止 Web 访问的 .htaccess（Apache）
     *
     * @param string $dir
     * @return void
     */
    public static function writeDenyHtaccess($dir)
    {
        $file = rtrim($dir, '/\\') . '/.htaccess';
        if (is_file($file)) {
            return;
        }
        @file_put_contents($file, "Deny from all\n");
    }

    /**
     * 检测是否有可用更新
     *
     * @return array
     */
    public static function checkForUpdate()
    {
        $local = self::localVersion();
        $manifest = self::fetchRemoteManifest();

        if ($manifest === null) {
            return array(
                'ok'               => false,
                'local_version'    => $local,
                'remote_version'   => '',
                'update_available' => false,
                'ahead_of_remote'  => false,
                'error'            => '无法连接 Gitee 获取版本信息，请稍后重试',
            );
        }

        $remote = isset($manifest['version']) ? trim($manifest['version']) : '';
        $cmp = version_compare($local, $remote);

        return array(
            'ok'               => true,
            'local_version'    => $local,
            'remote_version'   => $remote,
            'update_available' => ($remote !== '' && $cmp < 0),
            'ahead_of_remote'  => ($remote !== '' && $cmp > 0),
            'title'            => isset($manifest['title']) ? $manifest['title'] : '',
            'release_date'     => isset($manifest['release_date']) ? $manifest['release_date'] : '',
            'changes'          => isset($manifest['changes']) && is_array($manifest['changes']) ? $manifest['changes'] : array(),
            'repo'             => isset($manifest['repo']) ? $manifest['repo'] : self::DEFAULT_REPO,
            'branch'           => isset($manifest['branch']) ? $manifest['branch'] : self::DEFAULT_BRANCH,
            'error'            => '',
        );
    }

    /**
     * 下载并应用更新
     *
     * @return array
     */
    public static function applyUpdate()
    {
        if (!class_exists('ZipArchive')) {
            return array('ok' => false, 'msg' => '服务器未启用 ZipArchive 扩展，无法解压更新包');
        }

        $check = self::checkForUpdate();
        if (!$check['ok']) {
            return array('ok' => false, 'msg' => $check['error']);
        }
        if (!$check['update_available']) {
            return array('ok' => false, 'msg' => '当前已是最新版本，无需更新');
        }

        $repo = $check['repo'];
        $branch = $check['branch'];
        $zipUrl = 'https://gitee.com/' . $repo . '/repository/archive/' . rawurlencode($branch) . '.zip';

        $updateDir = self::updateDir();
        $zipPath = $updateDir . '/vanshine-update.zip';
        $extractDir = $updateDir . '/extract';

        self::cleanupPaths(array($zipPath, $extractDir));

        if (!self::downloadFile($zipUrl, $zipPath)) {
            self::cleanupPaths(array($zipPath, $extractDir));
            return array('ok' => false, 'msg' => '更新包下载失败，请检查服务器网络或 Gitee 访问');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            self::cleanupPaths(array($zipPath, $extractDir));
            return array('ok' => false, 'msg' => '更新包解压失败：无法打开 ZIP 文件');
        }

        if (!is_dir($extractDir)) {
            @mkdir($extractDir, 0755, true);
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            self::cleanupPaths(array($zipPath, $extractDir));
            return array('ok' => false, 'msg' => '更新包解压失败');
        }
        $zip->close();

        $sourceRoot = self::detectExtractRoot($extractDir);
        if ($sourceRoot === null) {
            self::cleanupPaths(array($zipPath, $extractDir));
            return array('ok' => false, 'msg' => '更新包结构异常，未找到有效目录');
        }

        $dbConfigHash = self::databaseConfigFingerprint();

        try {
            self::copyTree($sourceRoot, VS_ROOT, self::protectedRelativePaths());
            self::assertDatabaseConfigUnchanged($dbConfigHash);
        } catch (Exception $e) {
            self::cleanupPaths(array($zipPath, $extractDir));
            return array('ok' => false, 'msg' => '文件覆盖失败：' . $e->getMessage());
        }

        self::cleanupPaths(array($zipPath, $extractDir));

        $migration = DatabaseMigrator::runPending();
        if (empty($migration['ok'])) {
            return array(
                'ok'      => false,
                'msg'     => '文件已更新，但' . $migration['msg'],
                'version' => $check['remote_version'],
            );
        }

        $msg = '更新完成，当前版本 v' . $check['remote_version'];
        if (!empty($migration['applied'])) {
            $msg .= '，已同步数据库（' . implode('、', $migration['applied']) . '）';
        }

        return array(
            'ok'      => true,
            'msg'     => $msg,
            'version' => $check['remote_version'],
        );
    }

    /**
     * 数据库配置文件路径
     *
     * @return string
     */
    public static function databaseConfigPath()
    {
        return VS_ROOT . '/config/database.php';
    }

    /**
     * 更新前记录数据库配置指纹
     *
     * @return string|null
     */
    public static function databaseConfigFingerprint()
    {
        $path = self::databaseConfigPath();
        if (!is_file($path)) {
            return null;
        }
        return md5_file($path);
    }

    /**
     * 确认数据库配置未被覆盖
     *
     * @param string|null $beforeHash
     * @return void
     * @throws Exception
     */
    public static function assertDatabaseConfigUnchanged($beforeHash)
    {
        $path = self::databaseConfigPath();
        if (!is_file($path)) {
            throw new Exception('数据库配置文件不存在，更新已中止以保护连接信息');
        }
        if ($beforeHash === null) {
            return;
        }
        $afterHash = md5_file($path);
        if ($afterHash !== $beforeHash) {
            throw new Exception('数据库配置文件已被意外修改，更新已中止');
        }
    }

    /**
     * 拉取远程 update.json
     *
     * @return array|null
     */
    public static function fetchRemoteManifest()
    {
        $body = self::httpGet(self::MANIFEST_URL, 15);
        if ($body !== false && $body !== '') {
            $data = json_decode($body, true);
            if (is_array($data) && !empty($data['version'])) {
                return $data;
            }
        }

        $versionBody = self::httpGet(self::VERSION_URL, 15);
        if ($versionBody === false || $versionBody === '') {
            return null;
        }

        if (preg_match("/define\s*\(\s*'VS_VERSION'\s*,\s*'([^']+)'\s*\)/", $versionBody, $matches)) {
            return array(
                'version'      => $matches[1],
                'title'        => '版本更新',
                'release_date' => '',
                'changes'      => array('检测到新版本，建议立即更新'),
                'repo'         => self::DEFAULT_REPO,
                'branch'       => self::DEFAULT_BRANCH,
            );
        }

        return null;
    }

    /**
     * HTTP GET
     *
     * @param string $url
     * @param int    $timeout
     * @return string|false
     */
    public static function httpGet($url, $timeout = 30)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'VanShine-Updater/' . self::localVersion(),
            ));
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body !== false && $code >= 200 && $code < 300) {
                return $body;
            }
            return false;
        }

        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => $timeout,
                'header'  => "User-Agent: VanShine-Updater/" . self::localVersion() . "\r\n",
            ),
            'ssl' => array(
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ),
        ));

        return @file_get_contents($url, false, $context);
    }

    /**
     * 下载文件到本地
     *
     * @param string $url
     * @param string $dest
     * @return bool
     */
    public static function downloadFile($url, $dest)
    {
        if (function_exists('curl_init')) {
            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                return false;
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'VanShine-Updater/' . self::localVersion(),
            ));
            $ok = curl_exec($ch) !== false;
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);
            if (!$ok || $code < 200 || $code >= 300) {
                @unlink($dest);
                return false;
            }
            return is_file($dest) && filesize($dest) > 0;
        }

        $body = self::httpGet($url, 300);
        if ($body === false || $body === '') {
            return false;
        }
        return file_put_contents($dest, $body) !== false;
    }

    /**
     * 更新时保留的相对路径
     *
     * @return array
     */
    public static function protectedRelativePaths()
    {
        return array(
            'config/database.php',
            'config/install.lock',
            'storage/update',
        );
    }

    /**
     * 是否为绝不可覆盖的路径（硬编码安全规则）
     *
     * @param string $relative
     * @return bool
     */
    public static function isImmutablePath($relative)
    {
        $relative = strtolower(str_replace('\\', '/', $relative));
        $immutable = array(
            'config/database.php',
        );
        return in_array($relative, $immutable, true);
    }

    /**
     * 识别解压后的项目根目录
     *
     * @param string $extractDir
     * @return string|null
     */
    public static function detectExtractRoot($extractDir)
    {
        if (!is_dir($extractDir)) {
            return null;
        }

        $items = scandir($extractDir);
        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $extractDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                return $path;
            }
        }

        return $extractDir;
    }

    /**
     * 递归复制目录（跳过受保护路径）
     *
     * @param string $src
     * @param string $dst
     * @param array  $protected
     * @return void
     */
    public static function copyTree($src, $dst, array $protected)
    {
        $src = rtrim(str_replace('\\', '/', realpath($src)), '/');
        $dst = rtrim(str_replace('\\', '/', realpath($dst)), '/');

        if ($src === false || $dst === false) {
            throw new Exception('路径无效');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $fullPath = str_replace('\\', '/', $item->getPathname());
            $relative = ltrim(substr($fullPath, strlen($src)), '/');

            if (self::isImmutablePath($relative) || self::isProtectedPath($relative, $protected)) {
                continue;
            }

            $target = $dst . '/' . $relative;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    if (!@mkdir($target, 0755, true)) {
                        throw new Exception('无法创建目录：' . $relative);
                    }
                }
            } else {
                $targetDir = dirname($target);
                if (!is_dir($targetDir)) {
                    if (!@mkdir($targetDir, 0755, true)) {
                        throw new Exception('无法创建目录：' . dirname($relative));
                    }
                }
                if (!@copy($fullPath, $target)) {
                    throw new Exception('无法写入文件：' . $relative);
                }
            }
        }
    }

    /**
     * 是否受保护路径
     *
     * @param string $relative
     * @param array  $protected
     * @return bool
     */
    public static function isProtectedPath($relative, array $protected)
    {
        $relative = str_replace('\\', '/', $relative);
        foreach ($protected as $rule) {
            $rule = str_replace('\\', '/', $rule);
            if ($relative === $rule) {
                return true;
            }
            if (substr($rule, -1) !== '/' && strpos($relative, $rule . '/') === 0) {
                return true;
            }
            if (substr($rule, -1) === '/' && strpos($relative, rtrim($rule, '/')) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 清理路径（文件或目录）
     *
     * @param array $paths
     * @return void
     */
    public static function cleanupPaths(array $paths)
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                self::removeDir($path);
            }
        }
    }

    /**
     * 递归删除目录
     *
     * @param string $dir
     * @return void
     */
    public static function removeDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
