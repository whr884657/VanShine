<?php
/**
 * 文件：core/Updater.php
 * 作用：VanShine 在线更新（Gitee 版本检测与更新包应用）
 * @version 1.0.21
 */

class Updater
{
    const MANIFEST_URL = 'https://gitee.com/xunjinlu/VanShine/raw/main/update.json';
    const VERSION_URL  = 'https://gitee.com/xunjinlu/VanShine/raw/main/core/version.php';
    const DEFAULT_REPO = 'xunjinlu/VanShine';
    const DEFAULT_BRANCH = 'main';

    /** @var string 最近一次下载/网络错误说明 */
    private static $lastError = '';

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

        $logRow = UpdateLog::getVersion($remote);
        if ($logRow !== null) {
            if (!empty($logRow['title'])) {
                $manifest['title'] = $logRow['title'];
            }
            if (!empty($logRow['date'])) {
                $manifest['release_date'] = $logRow['date'];
            }
            if (!empty($logRow['changes']) && is_array($logRow['changes'])) {
                $manifest['changes'] = $logRow['changes'];
            }
        }

        return array(
            'ok'               => true,
            'local_version'    => $local,
            'remote_version'   => $remote,
            'update_available' => ($remote !== '' && $cmp < 0),
            'ahead_of_remote'  => ($remote !== '' && $cmp > 0),
            'title'            => isset($manifest['title']) ? $manifest['title'] : '',
            'release_date'     => isset($manifest['release_date']) ? $manifest['release_date'] : '',
            'changes'          => isset($manifest['changes']) && is_array($manifest['changes']) ? $manifest['changes'] : array(),
            'has_db_changes'   => UpdateLog::rangeHasDbChanges($local, $remote),
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
        $remoteVersion = $check['remote_version'];
        $manifest = self::fetchRemoteManifest();
        if (!is_array($manifest)) {
            $manifest = array();
        }

        $updateDir = self::updateDir();
        $zipPath = $updateDir . '/vanshine-update.zip';
        $extractDir = $updateDir . '/extract';

        self::cleanupPaths(array($zipPath, $extractDir));

        $downloadOk = false;
        $triedUrls = array();
        foreach (self::buildUpdatePackageUrls($repo, $branch, $remoteVersion, $manifest) as $item) {
            self::cleanupPaths(array($zipPath));
            $triedUrls[] = $item['label'];
            if (!self::downloadFile($item['url'], $zipPath)) {
                continue;
            }
            if (!self::isValidZipFile($zipPath)) {
                self::$lastError = '下载内容不是有效的 ZIP 更新包（' . $item['label'] . '）';
                @unlink($zipPath);
                continue;
            }
            $downloadOk = true;
            break;
        }

        if (!$downloadOk) {
            self::cleanupPaths(array($zipPath, $extractDir));
            $detail = self::$lastError !== '' ? self::$lastError : '未知错误';
            $sources = implode('、', $triedUrls);
            return array(
                'ok'  => false,
                'msg' => '更新包下载失败（已尝试：' . $sources . '）。' . $detail . '。请检查服务器能否访问 Gitee，或稍后重试。',
            );
        }

        $zip = new ZipArchive();
        $zipOpen = $zip->open($zipPath);
        if ($zipOpen !== true) {
            self::cleanupPaths(array($zipPath, $extractDir));
            $size = is_file($zipPath) ? (int) filesize($zipPath) : 0;
            return array(
                'ok'  => false,
                'msg' => '更新包解压失败：无法打开 ZIP 文件（文件大小 ' . $size . ' 字节）。请确认 Gitee 发行附件已上传，或联系管理员。',
            );
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

        $migration = array('ok' => true, 'applied' => array(), 'msg' => '无数据库结构变更，已跳过迁移');
        if (DatabaseMigrator::hasPendingMigrations()) {
            $migration = DatabaseMigrator::runPending();
            if (empty($migration['ok'])) {
                return array(
                    'ok'      => false,
                    'msg'     => '文件已更新，但' . $migration['msg'],
                    'version' => $check['remote_version'],
                );
            }
        }

        $msg = '更新完成，当前版本 v' . $check['remote_version'];
        if (!empty($migration['applied'])) {
            $msg .= '，已同步数据库（' . implode('、', $migration['applied']) . '）';
        } else {
            $msg .= '（本次无数据库结构变更）';
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
     * 构建更新包下载地址（优先 Gitee 发行版附件）
     *
     * @param string $repo
     * @param string $branch
     * @param string $version
     * @param array  $manifest
     * @return array
     */
    public static function buildUpdatePackageUrls($repo, $branch, $version, array $manifest = array())
    {
        $urls = array();
        if (!empty($manifest['package_url'])) {
            $urls[] = array('label' => '自定义更新包', 'url' => $manifest['package_url']);
        }

        $ver = ltrim(trim($version), 'vV');
        if ($ver !== '') {
            $tag = 'v' . $ver;
            $fileName = 'VanShine' . $ver . '.zip';
            $urls[] = array(
                'label' => 'Gitee 发行版',
                'url'   => 'https://gitee.com/' . $repo . '/releases/download/'
                    . rawurlencode($tag) . '/' . rawurlencode($fileName),
            );
        }

        $urls[] = array(
            'label' => '仓库快照',
            'url'   => 'https://gitee.com/' . $repo . '/repository/archive/' . rawurlencode($branch) . '.zip',
        );

        return $urls;
    }

    /**
     * 是否为有效 ZIP 文件（PK 头）
     *
     * @param string $path
     * @return bool
     */
    public static function isValidZipFile($path)
    {
        if (!is_file($path) || filesize($path) < 22) {
            return false;
        }
        $h = @fopen($path, 'rb');
        if (!$h) {
            return false;
        }
        $magic = fread($h, 4);
        fclose($h);
        return $magic === "PK\x03\x04" || $magic === "PK\x05\x06" || $magic === "PK\x07\x08";
    }

    /**
     * 获取最近一次错误说明
     *
     * @return string
     */
    public static function getLastError()
    {
        return self::$lastError;
    }

    /**
     * 路径是否在 open_basedir 允许范围内（避免 is_file 触发 Warning）
     *
     * @param string $path
     * @return bool
     */
    public static function isPathAllowed($path)
    {
        if ($path === '' || !is_string($path)) {
            return false;
        }

        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', VS_ROOT);
        if (strpos($path, $root . '/') === 0 || $path === $root) {
            return true;
        }

        $openBasedir = ini_get('open_basedir');
        if ($openBasedir === '' || $openBasedir === false) {
            return true;
        }

        foreach (explode(PATH_SEPARATOR, $openBasedir) as $base) {
            $base = rtrim(str_replace('\\', '/', $base), '/');
            if ($base === '') {
                continue;
            }
            if (strpos($path, $base . '/') === 0 || $path === $base) {
                return true;
            }
        }

        return false;
    }

    /**
     * 安全判断本地文件是否可读（不触碰 open_basedir 外的路径）
     *
     * @param string $path
     * @return bool
     */
    public static function isReadableLocalFile($path)
    {
        if (!self::isPathAllowed($path)) {
            return false;
        }
        return is_file($path) && is_readable($path);
    }

    /**
     * 为 cURL 配置 SSL（优先项目内置 CA，兼容宝塔 open_basedir）
     *
     * @param resource $ch
     * @return void
     */
    public static function configureCurlSsl($ch)
    {
        $candidates = array(
            VS_ROOT . '/core/cacert.pem',
        );

        $iniCa = ini_get('curl.cainfo');
        if ($iniCa !== '' && $iniCa !== false) {
            $candidates[] = $iniCa;
        }

        $opensslCa = ini_get('openssl.cafile');
        if ($opensslCa !== '' && $opensslCa !== false) {
            $candidates[] = $opensslCa;
        }

        foreach ($candidates as $caFile) {
            if (self::isReadableLocalFile($caFile)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caFile);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                return;
            }
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
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
                CURLOPT_MAXREDIRS        => 10,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_USERAGENT      => 'VanShine-Updater/' . self::localVersion(),
            ));
            self::configureCurlSsl($ch);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($body !== false && $code >= 200 && $code < 300) {
                return $body;
            }
            if ($error !== '') {
                self::$lastError = $error;
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

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            self::$lastError = 'HTTP 请求失败';
        }
        return $body;
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
        self::$lastError = '';

        if (function_exists('curl_init')) {
            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                self::$lastError = '无法写入临时文件：' . basename($dest);
                return false;
            }
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_FILE           => $fp,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_USERAGENT      => 'VanShine-Updater/' . self::localVersion(),
            ));
            self::configureCurlSsl($ch);
            $ok = curl_exec($ch) !== false;
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            fclose($fp);
            if (!$ok || $code < 200 || $code >= 300) {
                if ($error !== '') {
                    self::$lastError = $error;
                } elseif ($code > 0) {
                    self::$lastError = 'HTTP 状态码 ' . $code;
                } else {
                    self::$lastError = '网络连接失败';
                }
                @unlink($dest);
                return false;
            }
            if (!is_file($dest) || filesize($dest) <= 0) {
                self::$lastError = '下载文件为空';
                @unlink($dest);
                return false;
            }
            return true;
        }

        $body = self::httpGet($url, 300);
        if ($body === false || $body === '') {
            if (self::$lastError === '') {
                self::$lastError = 'HTTP 请求失败';
            }
            return false;
        }
        if (file_put_contents($dest, $body) === false) {
            self::$lastError = '无法写入临时文件';
            return false;
        }
        return true;
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

        if (self::looksLikeProjectRoot($extractDir)) {
            return $extractDir;
        }

        $items = scandir($extractDir);
        if ($items === false) {
            return null;
        }

        $dirs = array();
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $extractDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $dirs[] = $path;
            }
        }

        foreach ($dirs as $path) {
            if (self::looksLikeProjectRoot($path)) {
                return $path;
            }
        }

        if (count($dirs) === 1) {
            return $dirs[0];
        }

        return null;
    }

    /**
     * 目录是否像 VanShine 项目根
     *
     * @param string $dir
     * @return bool
     */
    public static function looksLikeProjectRoot($dir)
    {
        return is_file($dir . '/core/version.php')
            || is_file($dir . '/index.php')
            || is_file($dir . '/update.json');
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
