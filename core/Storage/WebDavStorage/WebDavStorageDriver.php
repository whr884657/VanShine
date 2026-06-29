<?php
/**
 * 文件：core/Storage/WebDavStorage/WebDavStorageDriver.php
 * 作用：WebDAV 储存对接
 * 依赖：本目录 vendor/（composer install 于 WebDavStorage/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class WebDavStorageDriver
{
    /** @var int 策略 KEY */
    const KEY = 7;

    /** @var string */
    const NAME = 'WebDAV';

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
     * @throws WebDavStorageException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new WebDavStorageException(
                'WebDAV 依赖未安装，请在 core/Storage/WebDavStorage 目录执行 composer install'
            );
        }
        require_once $autoload;
        $loaded = true;
    }

    /**
     * @param string   $pathname
     * @param resource $handle
     * @return void
     * @throws WebDavStorageException
     */
    public function writeStream($pathname, $handle)
    {
        try {
            $this->filesystem()->writeStream($pathname, $handle);
        } catch (FilesystemException $e) {
            throw new WebDavStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws WebDavStorageException
     */
    public function write($pathname, $contents)
    {
        try {
            $this->filesystem()->write($pathname, $contents);
        } catch (FilesystemException $e) {
            throw new WebDavStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return string
     * @throws WebDavStorageException
     */
    public function read($pathname)
    {
        try {
            return $this->filesystem()->read($pathname);
        } catch (FilesystemException $e) {
            throw new WebDavStorageException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @param string $pathname
     * @return void
     * @throws WebDavStorageException
     */
    public function delete($pathname)
    {
        try {
            $this->filesystem()->delete($pathname);
        } catch (FilesystemException $e) {
            throw new WebDavStorageException($e->getMessage(), (int) $e->getCode(), $e);
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
        $base = isset($configs[WebDavStorageOptions::URL]) ? $configs[WebDavStorageOptions::URL] : '';
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

        $adapter = new WebDAVAdapter(new Client(array(
            'baseUri' => (string) $this->configs[WebDavStorageOptions::BASE_URI],
            'userName' => isset($this->configs[WebDavStorageOptions::USERNAME]) ? (string) $this->configs[WebDavStorageOptions::USERNAME] : '',
            'password' => isset($this->configs[WebDavStorageOptions::PASSWORD]) ? (string) $this->configs[WebDavStorageOptions::PASSWORD] : '',
        )));

        $this->filesystem = new Filesystem($adapter);
        return $this->filesystem;
    }

    /**
     * @return void
     * @throws WebDavStorageException
     */
    private function validateConfigs()
    {
        if (empty($this->configs[WebDavStorageOptions::BASE_URI])) {
            throw new WebDavStorageException('缺少 base_uri 配置');
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[WebDavStorageOptions::URL])) {
            $configs[WebDavStorageOptions::URL] = rtrim((string) $configs[WebDavStorageOptions::URL], '/');
        }
        if (isset($configs[WebDavStorageOptions::BASE_URI])) {
            $configs[WebDavStorageOptions::BASE_URI] = rtrim((string) $configs[WebDavStorageOptions::BASE_URI], '/');
        }
        return $configs;
    }
}

class WebDavStorageException extends Exception
{
}
