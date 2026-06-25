<?php
/**
 * 文件：core/Updater.php
 * 作用：VanShine 在线更新（Gitee 版本检测与更新包应用）
 * @version 1.0.26
 */

class Updater
{
    const MANIFEST_URL = 'https://gitee.com/xunjinlu/VanShine/raw/main/update.json';
    const VERSION_URL  = 'https://gitee.com/xunjinlu/VanShine/raw/main/core/version.php';
    const DEFAULT_REPO = 'xunjinlu/VanShine';
    const DEFAULT_BRANCH = 'main';

    /** Gitee 更新可信域名（直连 HTTPS，不依赖本地 CA 证书包） */
    const TRUSTED_UPDATE_HOSTS = array(
        'gitee.com',
        'www.gitee.com',
        'foruda.gitee.com',
    );

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

        try {
            self::cleanupUpdateWorkspace($updateDir);

            $downloadOk = false;
            $triedUrls = array();
            foreach (self::buildUpdatePackageUrls($repo, $branch, $remoteVersion, $manifest) as $item) {
                @unlink($zipPath);
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
                return array('ok' => false, 'msg' => '更新包解压失败');
            }
            $zip->close();

            $sourceRoot = self::detectExtractRoot($extractDir);
            if ($sourceRoot === null) {
                return array('ok' => false, 'msg' => '更新包结构异常，未找到有效目录');
            }

            $dbConfigHash = self::databaseConfigFingerprint();

            try {
                self::copyTree($sourceRoot, VS_ROOT, self::protectedRelativePaths());
                self::assertDatabaseConfigUnchanged($dbConfigHash);
            } catch (Exception $e) {
                return array('ok' => false, 'msg' => '文件覆盖失败：' . $e->getMessage());
            }

            $migration = array('ok' => true, 'applied' => array(), 'msg' => '无数据库结构变更，已跳过');
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
                $msg .= '，已同步数据库结构（' . implode('、', $migration['applied']) . '）';
            } else {
                $msg .= '（本次无数据库结构变更）';
            }

            return array(
                'ok'      => true,
                'msg'     => $msg,
                'version' => $check['remote_version'],
            );
        } finally {
            self::cleanupUpdateWorkspace($updateDir);
        }
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
        if (!empty($manifest['package_url']) && self::isTrustedUpdateUrl($manifest['package_url'])) {
            $urls[] = array('label' => '自定义更新包', 'url' => $manifest['package_url']);
        }

        $ver = ltrim(trim($version), 'vV');
        if ($ver !== '') {
            $tag = 'v' . $ver;
            $fileName = 'VanShine' . $ver . '.zip';
            $urls[] = array(
                'label' => 'Gitee 发行版',
                'url'   => self::buildReleasePackageUrl($repo, $ver),
            );
        }

        return $urls;
    }

    /**
     * Gitee 发行版压缩包直链（与浏览器下载一致）
     *
     * @param string $repo  如 xunjinlu/VanShine
     * @param string $version 如 1.0.22
     * @return string
     */
    public static function buildReleasePackageUrl($repo, $version)
    {
        $ver = ltrim(trim($version), 'vV');
        $tag = 'v' . $ver;
        $fileName = 'VanShine' . $ver . '.zip';
        return 'https://gitee.com/' . $repo . '/releases/download/'
            . rawurlencode($tag) . '/' . rawurlencode($fileName);
    }

    /**
     * 是否为 VanShine 更新可信 HTTPS 地址（仅 Gitee 官方域名）
     *
     * @param string $url
     * @return bool
     */
    public static function isTrustedUpdateUrl($url)
    {
        if ($url === '' || !is_string($url)) {
            return false;
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
            return false;
        }

        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        return in_array($host, self::TRUSTED_UPDATE_HOSTS, true);
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
     * 为 cURL 配置 SSL
     *
     * Gitee 发行源使用 HTTPS 直连下载，不绑定本地 cacert.pem：
     * - 站点 HTTPS 证书与「出站访问 Gitee」无关
     * - 本地 CA 根证书包会随时间过时，且受 open_basedir 限制
     * - 仅对白名单域名放宽链校验，下载后仍校验 ZIP 文件头
     *
     * @param resource $ch
     * @param string   $url
     * @return void
     */
    public static function configureCurlSsl($ch, $url = '')
    {
        if ($url !== '' && self::isTrustedUpdateUrl($url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            return;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
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
            self::configureCurlSsl($ch, $url);
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

        $sslOptions = array(
            'verify_peer'      => !self::isTrustedUpdateUrl($url),
            'verify_peer_name' => !self::isTrustedUpdateUrl($url),
        );

        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => $timeout,
                'header'  => "User-Agent: VanShine-Updater/" . self::localVersion() . "\r\n",
            ),
            'ssl' => $sslOptions,
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
            self::configureCurlSsl($ch, $url);
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
            'storage',
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
     * 清空更新临时目录（zip、解压目录及残留文件）
     *
     * @param string|null $updateDir
     * @return void
     */
    public static function cleanupUpdateWorkspace($updateDir = null)
    {
        if ($updateDir === null) {
            $updateDir = VS_ROOT . '/storage/update';
        }
        if (!is_dir($updateDir)) {
            return;
        }

        $items = scandir($updateDir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if ($item === '.htaccess') {
                continue;
            }
            $path = $updateDir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDir($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }
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
