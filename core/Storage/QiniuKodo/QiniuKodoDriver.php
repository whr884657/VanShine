<?php
/**
 * 文件：core/Storage/QiniuKodo/QiniuKodoDriver.php
 * 作用：七牛云 Kodo 储存对接
 * 依赖：本目录 vendor/（composer install 于 QiniuKodo/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;

class QiniuKodoDriver
{
    /** @var int 策略 KEY */
    const KEY = 5;

    /** @var string */
    const NAME = '七牛云 Kodo';

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
        $this->validateConfigs();
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
     * @throws QiniuKodoException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new QiniuKodoException(
                '七牛云 Kodo 依赖未安装，请在 core/Storage/QiniuKodo 目录执行 composer install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws QiniuKodoException
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new QiniuKodoException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws QiniuKodoException
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new QiniuKodoException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws QiniuKodoException
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new QiniuKodoException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws QiniuKodoException
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new QiniuKodoException($e->getMessage(), (int) $e->getCode(), $e);
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
        $base = isset($configs[QiniuKodoOptions::URL]) ? $configs[QiniuKodoOptions::URL] : '';
        $queries = isset($configs['queries']) ? (string) $configs['queries'] : '';
        return rtrim($base, '/') . '/' . ltrim(str_replace('\\', '/', $pathname), '/') . $queries;
    }

    /**
     * @return Filesystem
     */
    private function filesystem()
    {
        if ($this->filesystem instanceof Filesystem) {
            return $this->filesystem;
        }

        $adapter = new QiniuAdapter(
            (string) $this->configs[QiniuKodoOptions::ACCESS_KEY],
            (string) $this->configs[QiniuKodoOptions::SECRET_KEY],
            (string) $this->configs[QiniuKodoOptions::BUCKET],
            isset($this->configs[QiniuKodoOptions::URL]) ? (string) $this->configs[QiniuKodoOptions::URL] : ''
        );

        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    /**
     * @return void
     * @throws QiniuKodoException
     */
    private function validateConfigs()
    {
        if (empty($this->configs[QiniuKodoOptions::BUCKET])) {
            throw new QiniuKodoException('缺少 bucket 配置');
        }
        if (empty($this->configs[QiniuKodoOptions::ACCESS_KEY])) {
            throw new QiniuKodoException('缺少 access_key 配置');
        }
        if (empty($this->configs[QiniuKodoOptions::SECRET_KEY])) {
            throw new QiniuKodoException('缺少 secret_key 配置');
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[QiniuKodoOptions::URL])) {
            $configs[QiniuKodoOptions::URL] = rtrim((string) $configs[QiniuKodoOptions::URL], '/');
        }
        return $configs;
    }
}

class QiniuKodoException extends Exception
{
}
