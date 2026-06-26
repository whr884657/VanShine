<?php
/**
 * 文件：core/Storage/ILanZou/ILanZouDriver.php
 * 作用：蓝奏云优享版储存对接
 * 依赖：PHP curl、openssl 扩展（无需 Composer）
 * @version 1.0.0
 */

require_once __DIR__ . '/ILanZouException.php';

class ILanZouDriver
{
    /** @var int VanShine 第七种储存方式 */
    const KEY = 6;

    /** @var string */
    const NAME = '蓝奏云优享版';

    /** @var array */
    private $configs;

    /** @var ILanZouClient|null */
    private $client;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
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
     */
    public static function loadVendor()
    {
        // 纯 PHP 实现，无外部 Composer 依赖
    }

    /**
     * @param string   $pathname VanShine 储存路径（如 2024/01/photo.jpg）
     * @param resource $handle
     * @return void
     * @throws ILanZouException
     */
    public function writeStream($pathname, $handle)
    {
        $filename = basename(str_replace('\\', '/', $pathname));
        if ($filename === '') {
            throw new ILanZouException('无效的文件路径');
        }

        $result = $this->client()->upload($filename, $handle);
        $this->saveMeta($pathname, $result['file_id'], $result['file_name']);
    }

    /**
     * @param string $pathname
     * @param string $contents
     * @return void
     * @throws ILanZouException
     */
    public function write($pathname, $contents)
    {
        $filename = basename(str_replace('\\', '/', $pathname));
        if ($filename === '') {
            throw new ILanZouException('无效的文件路径');
        }

        $result = $this->client()->upload($filename, $contents);
        $this->saveMeta($pathname, $result['file_id'], $result['file_name']);
    }

    /**
     * @param string $pathname
     * @return string
     * @throws ILanZouException
     */
    public function read($pathname)
    {
        $fileId = $this->resolveFileId($pathname);
        $url = $this->client()->getDownloadUrl($fileId);

        $response = $this->httpGet($url);
        if ($response === false) {
            throw new ILanZouException('读取文件失败');
        }

        return $response;
    }

    /**
     * @param string $pathname
     * @return void
     * @throws ILanZouException
     */
    public function delete($pathname)
    {
        $fileId = $this->resolveFileId($pathname);
        $this->client()->deleteFile($fileId);
        $this->removeMeta($pathname);
    }

    /**
     * @param string $pathname
     * @return bool
     */
    public function exists($pathname)
    {
        if ($this->hasMeta($pathname)) {
            return true;
        }

        $fileId = $this->extractFileIdFromPath($pathname);
        return $fileId !== '';
    }

    /**
     * 获取 ILanZou 远程 fileId
     *
     * @param string $pathname
     * @return string
     * @throws ILanZouException
     */
    public function getFileId($pathname)
    {
        return $this->resolveFileId($pathname);
    }

    /**
     * 获取带鉴权的临时直链（会发起 API 请求）
     *
     * @param string $pathname
     * @return string
     * @throws ILanZouException
     */
    public function getTemporaryUrl($pathname)
    {
        return $this->client()->getDownloadUrl($this->resolveFileId($pathname));
    }

    /**
     * @param array  $configs
     * @param string $pathname
     * @return string
     */
    public static function buildUrl(array $configs, $pathname)
    {
        $configs = self::normalizeConfigs($configs);
        $base = isset($configs[ILanZouOptions::URL]) ? $configs[ILanZouOptions::URL] : '';
        $queries = isset($configs['queries']) ? (string) $configs['queries'] : '';
        return rtrim($base, '/') . '/' . ltrim(str_replace('\\', '/', $pathname), '/') . $queries;
    }

    /**
     * @return ILanZouClient
     */
    private function client()
    {
        if ($this->client instanceof ILanZouClient) {
            return $this->client;
        }

        $this->client = new ILanZouClient($this->configs);
        $this->client->init();
        return $this->client;
    }

    /**
     * @param string $pathname
     * @return string
     * @throws ILanZouException
     */
    private function resolveFileId($pathname)
    {
        $meta = $this->loadMeta($pathname);
        if ($meta !== null && !empty($meta['file_id'])) {
            return (string) $meta['file_id'];
        }

        $fileId = $this->extractFileIdFromPath($pathname);
        if ($fileId !== '') {
            return $fileId;
        }

        throw new ILanZouException('未找到文件映射，请先上传或检查 pathname：' . $pathname);
    }

    /**
     * pathname 为纯数字 fileId 或 {fileId}.ext 时可直接解析
     *
     * @param string $pathname
     * @return string
     */
    private function extractFileIdFromPath($pathname)
    {
        $base = pathinfo(str_replace('\\', '/', $pathname), PATHINFO_FILENAME);
        if ($base !== '' && ctype_digit($base)) {
            return $base;
        }
        return '';
    }

    /**
     * @param string $pathname
     * @param string $fileId
     * @param string $fileName
     * @return void
     */
    private function saveMeta($pathname, $fileId, $fileName)
    {
        if ($fileId === '') {
            return;
        }

        $path = $this->metaFilePath($pathname);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode(array(
            'file_id' => $fileId,
            'file_name' => $fileName,
            'pathname' => $pathname,
            'updated_at' => date('c'),
        ), JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $pathname
     * @return array|null
     */
    private function loadMeta($pathname)
    {
        $path = $this->metaFilePath($pathname);
        if (!is_file($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);
        return is_array($json) ? $json : null;
    }

    /**
     * @param string $pathname
     * @return bool
     */
    private function hasMeta($pathname)
    {
        return is_file($this->metaFilePath($pathname));
    }

    /**
     * @param string $pathname
     * @return void
     */
    private function removeMeta($pathname)
    {
        $path = $this->metaFilePath($pathname);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * @param string $pathname
     * @return string
     */
    private function metaFilePath($pathname)
    {
        $root = function_exists('vs_root_path') ? vs_root_path() : dirname(__DIR__, 2);
        return rtrim($root, '/\\') . '/data/ilanzou-meta/' . md5($pathname) . '.json';
    }

    /**
     * @param string $url
     * @return string|false
     */
    private function httpGet($url)
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $body = curl_exec($ch);
        curl_close($ch);

        return $body;
    }

    /**
     * @return void
     * @throws ILanZouException
     */
    private function validateConfigs()
    {
        if (empty($this->configs[ILanZouOptions::USERNAME])) {
            throw new ILanZouException('缺少 username 配置');
        }
        if (empty($this->configs[ILanZouOptions::PASSWORD])) {
            throw new ILanZouException('缺少 password 配置');
        }
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        if (isset($configs[ILanZouOptions::URL])) {
            $configs[ILanZouOptions::URL] = rtrim((string) $configs[ILanZouOptions::URL], '/');
        }
        if (!isset($configs[ILanZouOptions::FOLDER_ID]) || $configs[ILanZouOptions::FOLDER_ID] === '') {
            $configs[ILanZouOptions::FOLDER_ID] = '0';
        }
        return $configs;
    }
}
