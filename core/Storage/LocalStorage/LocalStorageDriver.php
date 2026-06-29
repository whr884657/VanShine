<?php
/**
 * 文件：core/Storage/LocalStorage/LocalStorageDriver.php
 * 作用：本地储存对接
 * 依赖：本目录 vendor/（composer install 于 LocalStorage/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalStorageDriver
{
    /** @var int 策略 KEY */
    const KEY = 1;

    /** @var string */
    const NAME = '本地';

    /** @var array */
    private $configs;

    /** @var Filesystem|null */
    private $filesystem;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        self::loadVendor();
        $this->configs = self::normalizeConfigs($configs);
    }

    /**
     * @param array $configs
     * @return self
     */
    public static function fromConfigs(array $configs)
    {
        return new self($configs);
    }

    /**
     * @return void
     * @throws LocalStorageException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new LocalStorageException(
                '本地储存依赖未安装，请在 core/Storage/LocalStorage 目录执行 composer install'
            );
        }

        require_once $autoload;
        $loaded = true;
    }

    /**
     * @return string
     */
    public static function defaultRoot()
    {
        return rtrim(vs_root_path(), '/\\') . '/upload';
    }

    /**
     * 默认对外 URL 前缀：{站点域名}/upload
     *
     * @return string
     */
    public static function defaultPublicUrl()
    {
        return rtrim(vs_base_url(), '/') . '/upload';
    }

    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function exists($pathname)
    {
        try {
            return $this->filesystem()->fileExists($pathname);
        } catch (FilesystemException $e) {
            return false;
        }
    }

    /**
     * 对外 URL：{url}/{相对路径}
     *
     * @param array  $configs
     * @param string $pathname
     * @return string
     */
    public static function buildUrl(array $configs, $pathname)
    {
        $configs = self::normalizeConfigs($configs);
        $base = isset($configs[LocalStorageOptions::URL]) ? $configs[LocalStorageOptions::URL] : '';
        $path = str_replace('\\', '/', (string) $pathname);
        $path = ltrim($path, '/');
        if ($path === '') {
            return rtrim($base, '/');
        }

        $segments = explode('/', $path);
        $encoded = array();
        foreach ($segments as $segment) {
            if ($segment !== '') {
                $encoded[] = rawurlencode($segment);
            }
        }

        return rtrim($base, '/') . '/' . implode('/', $encoded);
    }

    /**
     * @param array $configs
     * @return string
     */
    public static function resolveRootPath(array $configs)
    {
        return self::resolveRoot(self::normalizeConfigs($configs));
    }

    /**
     * 清理旧版随机 slug 直链网关（1.0.35–1.0.39）
     *
     * @return void
     */
    public static function cleanupLegacyGateway()
    {
        $slug = trim((string) Config::get('storage_local_public_slug', ''));
        if ($slug !== '' && preg_match('/^[a-z]$/', $slug)) {
            $dir = rtrim(vs_root_path(), '/\\') . '/' . $slug;
            if (is_dir($dir)) {
                foreach (array('index.php', '.htaccess') as $file) {
                    $path = $dir . '/' . $file;
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
                @rmdir($dir);
            }
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('config');
            $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `key` = ?');
            $stmt->execute(array('storage_local_public_slug'));
            Config::clearCache();
        } catch (Exception $e) {
            // 安装阶段或未就绪
        }
    }

    /**
     * @return int
     */
    public static function refreshStoredPublicUrls()
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('file_item');
            $stmt = $pdo->query('SELECT `id`, `pathname` FROM `' . $table . '` WHERE `storage_key` = 1');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
        } catch (Exception $e) {
            return 0;
        }

        if (empty($rows)) {
            return 0;
        }

        $configs = StorageRegistry::loadDriverConfigs(1);
        $updated = 0;
        foreach ($rows as $row) {
            $url = self::buildUrl($configs, $row['pathname']);
            $upd = $pdo->prepare('UPDATE `' . $table . '` SET `public_url` = ? WHERE `id` = ?');
            $upd->execute(array($url, (int) $row['id']));
            $updated++;
        }

        return $updated;
    }

    private function filesystem()
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }

        $root = self::resolveRoot($this->configs);
        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        $adapter = new LocalFilesystemAdapter($root);
        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    private static function resolveRoot(array $configs)
    {
        if (isset($configs[LocalStorageOptions::ROOT]) && $configs[LocalStorageOptions::ROOT] !== '') {
            return (string) $configs[LocalStorageOptions::ROOT];
        }

        return self::defaultRoot();
    }

    private static function normalizeConfigs(array $configs)
    {
        if (empty($configs[LocalStorageOptions::URL])) {
            $configs[LocalStorageOptions::URL] = self::defaultPublicUrl();
        } else {
            $configs[LocalStorageOptions::URL] = rtrim((string) $configs[LocalStorageOptions::URL], '/');
        }

        return $configs;
    }
}

class LocalStorageException extends Exception
{
}
