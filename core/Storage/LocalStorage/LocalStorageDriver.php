<?php
/**
 * 文件：core/Storage/LocalStorage/LocalStorageDriver.php
 * 作用：本地储存对接
 * 依赖：本目录 vendor/（composer install 于 LocalStorage/）
 * @version 1.0.2
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
     * @param array $configs root
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
     * 加载本驱动目录下的 Composer 依赖
     *
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
     * 根据当前站点域名生成本地文件公开访问 URL 前缀
     *
     * @return string
     */
    public static function defaultPublicUrl()
    {
        return rtrim(vs_base_url(), '/') . '/upload';
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws LocalStorageException
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws LocalStorageException
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws LocalStorageException
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws LocalStorageException
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new LocalStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return bool
     */
    public function exists($pathname)
    {
        try {
            return $this->filesystem()->fileExists($pathname);
        } catch (FilesystemException $e) {
            return false;
        }
    }

    /**
     * @param array  $configs
     * @param string $pathname
     * @return string
     */
    public static function buildUrl(array $configs, $pathname)
    {
        $configs = self::normalizeConfigs($configs);
        $base = isset($configs[LocalStorageOptions::URL]) ? $configs[LocalStorageOptions::URL] : '';
        return rtrim($base, '/') . '/' . ltrim(str_replace('\\', '/', $pathname), '/');
    }

    /**
     * 保存储存策略时确保物理目录可用（默认 upload/ 可直接 Web 访问，无需符号链接）
     *
     * @param array       $configs
     * @param string|null $oldUrl 兼容旧版调用，已忽略
     * @return void
     * @throws LocalStorageException
     */
    public static function ensureSymlink(array $configs, $oldUrl = null)
    {
        $root = self::resolveRoot(self::normalizeConfigs($configs));
        if (!is_dir($root) && !@mkdir($root, 0755, true)) {
            throw new LocalStorageException('无法创建储存目录：' . $root);
        }
    }

    /**
     * @param array $configs
     * @return void
     */
    public static function removeSymlink(array $configs)
    {
        $configs = self::normalizeConfigs($configs);
        $url = isset($configs[LocalStorageOptions::URL]) ? $configs[LocalStorageOptions::URL] : '';
        if ($url === '') {
            return;
        }
        $symlinkName = self::publicPathFromUrl($url);
        if ($symlinkName === '') {
            return;
        }
        $linkPath = rtrim(vs_root_path(), '/\\') . '/' . $symlinkName;
        if (is_link($linkPath)) {
            @unlink($linkPath);
        }
    }

    /**
     * 创建 Flysystem 实例
     *
     * @return Filesystem
     */
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

    /**
     * @param array $configs
     * @return string
     */
    private static function resolveRoot(array $configs)
    {
        if (isset($configs[LocalStorageOptions::ROOT]) && $configs[LocalStorageOptions::ROOT] !== '') {
            return (string) $configs[LocalStorageOptions::ROOT];
        }
        return self::defaultRoot();
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (empty($configs[LocalStorageOptions::URL])) {
            $configs[LocalStorageOptions::URL] = self::defaultPublicUrl();
        } elseif (isset($configs[LocalStorageOptions::URL])) {
            $configs[LocalStorageOptions::URL] = rtrim((string) $configs[LocalStorageOptions::URL], '/');
        }
        return $configs;
    }

    /**
     * @param string $url
     * @return string
     */
    private static function publicPathFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = $path !== null ? $path : '';
        $parts = array_values(array_filter(explode('/', $path)));
        return $parts ? (string) $parts[0] : '';
    }
}

class LocalStorageException extends Exception
{
}
