<?php
/**
 * 文件：core/Storage/LocalStorage/LocalStorageDriver.php
 * 作用：本地储存对接
 * 依赖：本目录 vendor/（composer install 于 LocalStorage/）
 * @version 1.0.3
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

    const CONFIG_SLUG = 'storage_local_public_slug';

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
     * @param bool $create
     * @return string
     */
    public static function publicSlug($create = true)
    {
        $slug = trim((string) Config::get(self::CONFIG_SLUG, ''));
        if ($slug !== '' && preg_match('/^[a-z]$/', $slug)) {
            return $slug;
        }

        if (!$create) {
            return '';
        }

        $letters = str_split('abcdefghijklmnopqrstuvwxyz');
        shuffle($letters);
        $slug = $letters[0];
        Config::set(self::CONFIG_SLUG, $slug);
        return $slug;
    }

    /**
     * @return string
     */
    public static function defaultPublicUrl()
    {
        $slug = self::publicSlug(true);
        if ($slug === '') {
            return rtrim(vs_base_url(), '/');
        }
        return rtrim(vs_base_url(), '/') . '/' . $slug;
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
     * 对外 URL：/{slug}/{文件名}，不暴露 upload 子目录
     */
    public static function buildUrl(array $configs, $pathname)
    {
        $configs = self::normalizeConfigs($configs);
        $base = isset($configs[LocalStorageOptions::URL]) ? $configs[LocalStorageOptions::URL] : '';
        $file = basename(str_replace('\\', '/', $pathname));
        return rtrim($base, '/') . '/' . rawurlencode($file);
    }

    /**
     * @param array       $configs
     * @param string|null $oldSlug
     * @return void
     * @throws LocalStorageException
     */
    public static function ensureSymlink(array $configs, $oldSlug = null)
    {
        $configs = self::normalizeConfigs($configs);
        $root = self::resolveRoot($configs);
        if (!is_dir($root) && !@mkdir($root, 0755, true)) {
            throw new LocalStorageException('无法创建储存目录：' . $root);
        }

        $slug = self::publicSlug(true);
        if ($slug === '') {
            throw new LocalStorageException('无法生成本地储存对外路径标识');
        }

        if ($oldSlug !== null && $oldSlug !== '' && $oldSlug !== $slug) {
            self::removePublicRoute($oldSlug);
        }

        self::deployPublicRoute($slug);
        Config::set(self::CONFIG_SLUG, $slug);
    }

    /**
     * @param array $configs
     * @return void
     * @throws LocalStorageException
     */
    public static function ensureSymlinkIfMissing(array $configs)
    {
        $slug = self::publicSlug(false);
        if ($slug === '') {
            self::ensureSymlink($configs);
            return;
        }
        $index = rtrim(vs_root_path(), '/\\') . '/' . $slug . '/index.php';
        if (!is_file($index)) {
            self::ensureSymlink($configs);
        }
    }

    /**
     * @param string $slug
     * @return void
     */
    public static function removePublicRoute($slug)
    {
        if (!preg_match('/^[a-z]$/', $slug)) {
            return;
        }
        $dir = rtrim(vs_root_path(), '/\\') . '/' . $slug;
        if (!is_dir($dir)) {
            return;
        }
        foreach (array('index.php', '.htaccess') as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * @param array $configs
     * @return void
     */
    public static function removeSymlink(array $configs)
    {
        $slug = Config::get(self::CONFIG_SLUG, '');
        if ($slug !== '') {
            self::removePublicRoute($slug);
        }
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

    /**
     * @param string $slug
     * @return void
     * @throws LocalStorageException
     */
    private static function deployPublicRoute($slug)
    {
        $projectRoot = rtrim(vs_root_path(), '/\\');
        $dir = $projectRoot . '/' . $slug;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new LocalStorageException('无法创建对外直链目录：' . $dir);
        }

        $gateway = __DIR__ . '/public-gateway.php';
        if (!is_file($gateway)) {
            throw new LocalStorageException('本地储存网关模板缺失');
        }

        if (!@copy($gateway, $dir . '/index.php')) {
            throw new LocalStorageException('无法部署本地储存网关');
        }

        $rules = "RewriteEngine On\n"
            . "RewriteBase /" . $slug . "/\n"
            . "RewriteCond %{REQUEST_FILENAME} !-f\n"
            . "RewriteRule ^([^/]+)$ index.php?name=$1 [L,QSA]\n";
        if (@file_put_contents($dir . '/.htaccess', $rules) === false) {
            throw new LocalStorageException('无法写入 .htaccess');
        }
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
