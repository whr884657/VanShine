<?php
/**
 * 文件：core/Storage/AwsS3/AwsS3Driver.php
 * 作用：AWS S3 储存对接
 * 依赖：本目录 vendor/（composer install 于 AwsS3/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;

class AwsS3Driver
{
    /** @var int 策略 KEY */
    const KEY = 2;

    /** @var string */
    const NAME = 'AWS S3';

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
     * @throws AwsS3Exception
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new AwsS3Exception(
                'AWS S3 依赖未安装，请在 core/Storage/AwsS3 目录执行 composer install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws AwsS3Exception
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new AwsS3Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws AwsS3Exception
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new AwsS3Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws AwsS3Exception
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new AwsS3Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws AwsS3Exception
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new AwsS3Exception($e->getMessage(), (int) $e->getCode(), $e);
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
        $base = isset($configs[AwsS3Options::URL]) ? $configs[AwsS3Options::URL] : '';
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

        $adapter = new AwsS3V3Adapter(
            new S3Client(array(
                'credentials' => array(
                    'key' => (string) $this->configs[AwsS3Options::ACCESS_KEY_ID],
                    'secret' => (string) $this->configs[AwsS3Options::SECRET_ACCESS_KEY],
                ),
                'endpoint' => isset($this->configs[AwsS3Options::ENDPOINT]) ? $this->configs[AwsS3Options::ENDPOINT] : null,
                'region' => isset($this->configs[AwsS3Options::REGION]) ? (string) $this->configs[AwsS3Options::REGION] : '',
                'version' => '2006-03-01',
            )),
            (string) $this->configs[AwsS3Options::BUCKET]
        );

        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    /**
     * @return void
     * @throws AwsS3Exception
     */
    private function validateConfigs()
    {
        if (empty($this->configs[AwsS3Options::BUCKET])) {
            throw new AwsS3Exception('缺少 bucket 配置');
        }
        if (empty($this->configs[AwsS3Options::ACCESS_KEY_ID])) {
            throw new AwsS3Exception('缺少 access_key_id 配置');
        }
        if (empty($this->configs[AwsS3Options::SECRET_ACCESS_KEY])) {
            throw new AwsS3Exception('缺少 secret_access_key 配置');
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[AwsS3Options::URL])) {
            $configs[AwsS3Options::URL] = rtrim((string) $configs[AwsS3Options::URL], '/');
        }
        return $configs;
    }
}

class AwsS3Exception extends Exception
{
}
