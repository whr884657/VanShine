<?php
/**
 * 文件：core/Storage/AliyunOss/AliyunOssDriver.php
 * 作用：阿里云 OSS 储存对接
 * 依赖：本目录 vendor/（composer install 于 AliyunOss/）
 * @version 1.0.0
 */

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use OSS\OssClient;
use Zing\Flysystem\Oss\OssAdapter;

class AliyunOssDriver
{
    /** @var int 策略 KEY */
    const KEY = 3;

    /** @var string */
    const NAME = '阿里云 OSS';

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
     * @throws AliyunOssException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new AliyunOssException(
                '阿里云 OSS 依赖未安装，请在 core/Storage/AliyunOss 目录执行 composer install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws AliyunOssException
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new AliyunOssException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws AliyunOssException
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new AliyunOssException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws AliyunOssException
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new AliyunOssException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws AliyunOssException
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new AliyunOssException($e->getMessage(), (int) $e->getCode(), $e);
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
        $base = isset($configs[AliyunOssOptions::URL]) ? $configs[AliyunOssOptions::URL] : '';
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

        $client = new OssClient(
            (string) $this->configs[AliyunOssOptions::ACCESS_KEY_ID],
            (string) $this->configs[AliyunOssOptions::ACCESS_KEY_SECRET],
            (string) $this->configs[AliyunOssOptions::ENDPOINT]
        );

        $adapter = new OssAdapter(
            $client,
            (string) $this->configs[AliyunOssOptions::BUCKET]
        );

        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    /**
     * @return void
     * @throws AliyunOssException
     */
    private function validateConfigs()
    {
        if (empty($this->configs[AliyunOssOptions::BUCKET])) {
            throw new AliyunOssException('缺少 bucket 配置');
        }
        if (empty($this->configs[AliyunOssOptions::ACCESS_KEY_ID])) {
            throw new AliyunOssException('缺少 access_key_id 配置');
        }
        if (empty($this->configs[AliyunOssOptions::ACCESS_KEY_SECRET])) {
            throw new AliyunOssException('缺少 access_key_secret 配置');
        }
        if (empty($this->configs[AliyunOssOptions::ENDPOINT])) {
            throw new AliyunOssException('缺少 endpoint 配置');
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[AliyunOssOptions::URL])) {
            $configs[AliyunOssOptions::URL] = rtrim((string) $configs[AliyunOssOptions::URL], '/');
        }
        return $configs;
    }
}

class AliyunOssException extends Exception
{
}
