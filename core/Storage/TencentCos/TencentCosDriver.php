<?php
/**
 * 文件：core/Storage/TencentCos/TencentCosDriver.php
 * 作用：腾讯云 COS 储存对接
 * 依赖：本目录 vendor/（composer install 于 TencentCos/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Overtrue\Flysystem\Cos\CosAdapter;

class TencentCosDriver
{
    /** @var int 策略 KEY */
    const KEY = 4;

    /** @var string */
    const NAME = '腾讯云 COS';

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
     * @throws TencentCosException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new TencentCosException(
                '腾讯云 COS 依赖未安装，请在 core/Storage/TencentCos 目录执行 composer install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws TencentCosException
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new TencentCosException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws TencentCosException
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new TencentCosException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws TencentCosException
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new TencentCosException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws TencentCosException
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new TencentCosException($e->getMessage(), (int) $e->getCode(), $e);
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
        $base = isset($configs[TencentCosOptions::URL]) ? $configs[TencentCosOptions::URL] : '';
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

        $adapter = new CosAdapter(array(
            TencentCosOptions::APP_ID => (string) $this->configs[TencentCosOptions::APP_ID],
            TencentCosOptions::SECRET_ID => (string) $this->configs[TencentCosOptions::SECRET_ID],
            TencentCosOptions::SECRET_KEY => (string) $this->configs[TencentCosOptions::SECRET_KEY],
            TencentCosOptions::REGION => (string) $this->configs[TencentCosOptions::REGION],
            TencentCosOptions::BUCKET => (string) $this->configs[TencentCosOptions::BUCKET],
        ));

        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    /**
     * @return void
     * @throws TencentCosException
     */
    private function validateConfigs()
    {
        $required = array(
            TencentCosOptions::APP_ID => 'app_id',
            TencentCosOptions::SECRET_ID => 'secret_id',
            TencentCosOptions::SECRET_KEY => 'secret_key',
            TencentCosOptions::REGION => 'region',
            TencentCosOptions::BUCKET => 'bucket',
        );
        foreach ($required as $key => $label) {
            if (empty($this->configs[$key])) {
                throw new TencentCosException('缺少 ' . $label . ' 配置');
            }
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[TencentCosOptions::URL])) {
            $configs[TencentCosOptions::URL] = rtrim((string) $configs[TencentCosOptions::URL], '/');
        }
        return $configs;
    }
}

class TencentCosException extends Exception
{
}
