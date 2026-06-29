<?php
/**
 * 文件：core/cdn/edgeone/EdgeOneClient.php
 * 作用：EdgeOne Open API 客户端（TC3 签名 + JSON POST，多接入点容错）
 * 依赖：本目录 vendor/（composer install 于 edgeone/）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

use TencentCloud\Common\CommonClient;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;

class EdgeOneClient
{
    /** @var array */
    private $configs;

    /** @var array<int, array{region: string, endpoint: string}> */
    private $endpointChain;

    /** @var array<int, CommonClient> */
    private $sdkClients = array();

    /**
     * @param array $configs 仅由 create() 从数据库组装后传入
     */
    private function __construct(array $configs)
    {
        self::loadVendor();
        $this->configs = self::normalizeConfigs($configs);
        $this->validateConfigs();
        $this->endpointChain = EdgeOneOptions::buildEndpointChain();
    }

    /**
     * 从 vs_config 创建客户端（唯一对外实例化入口）
     *
     * @return self
     * @throws EdgeOneException
     */
    public static function create()
    {
        TencentCloudConfig::migrateLegacyIfNeeded();

        if (!EdgeOneOptions::isEnabled()) {
            throw new EdgeOneException('EdgeOne CDN 未启用，请先在系统设置 → CDN 配置中启用');
        }

        return new self(EdgeOneOptions::buildClientConfigs());
    }

    /**
     * 测试 API 连通性（不要求已启用 EdgeOne）
     *
     * @return self
     * @throws EdgeOneException
     */
    public static function createForTest()
    {
        TencentCloudConfig::migrateLegacyIfNeeded();

        if (!TencentCloudConfig::hasCredentials()) {
            throw new EdgeOneException('请先填写腾讯云 SecretId 与 SecretKey');
        }

        return new self(EdgeOneOptions::buildClientConfigs());
    }

    /**
     * @return void
     * @throws EdgeOneException
     */
    public static function loadVendor()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new EdgeOneException(
                'EdgeOne 依赖未安装，请在 core/cdn/edgeone 目录执行 composer install'
            );
        }

        require_once $autoload;
        $loaded = true;
    }

    /**
     * 调用任意 EdgeOne Action（主用接入点失败时自动切换后备）
     *
     * @param string $action
     * @param array  $params
     * @return array
     * @throws EdgeOneException
     */
    public function call($action, array $params = array())
    {
        // 空参数须编码为 JSON 对象 {}，否则 json_encode([]) 得到 [] 会触发非法 JSON 错误
        if (count($params) === 0) {
            $params = new \stdClass();
        }

        $lastException = null;
        $count = count($this->endpointChain);

        for ($i = 0; $i < $count; $i++) {
            try {
                return $this->sdkAt($i)->callJson($action, $params);
            } catch (Exception $e) {
                $lastException = $e;
                if (!$this->isRetryableException($e) || $i >= $count - 1) {
                    throw EdgeOneException::fromThrowable($e);
                }
            }
        }

        if ($lastException instanceof Exception) {
            throw EdgeOneException::fromThrowable($lastException);
        }

        throw new EdgeOneException('EdgeOne API 调用失败');
    }

    /**
     * @return array
     */
    public function configs()
    {
        return $this->configs;
    }

    /**
     * 当前实例使用的接入点链（调试用）
     *
     * @return array<int, array{region: string, endpoint: string}>
     */
    public function endpointChain()
    {
        return $this->endpointChain;
    }

    /**
     * @param int $index
     * @return CommonClient
     */
    private function sdkAt($index)
    {
        if (isset($this->sdkClients[$index])) {
            return $this->sdkClients[$index];
        }

        $point = $this->endpointChain[$index];
        $token = $this->configs[EdgeOneOptions::TOKEN];
        $credential = new Credential(
            $this->configs[EdgeOneOptions::SECRET_ID],
            $this->configs[EdgeOneOptions::SECRET_KEY],
            $token !== '' ? $token : null
        );

        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint($point['endpoint']);
        $httpProfile->setReqTimeout(60);

        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($httpProfile);
        $clientProfile->setLanguage($this->configs[EdgeOneOptions::LANGUAGE]);

        $region = $this->configs[EdgeOneOptions::REGION] !== ''
            ? $this->configs[EdgeOneOptions::REGION]
            : $point['region'];

        $this->sdkClients[$index] = new CommonClient(
            EdgeOneOptions::SERVICE,
            EdgeOneOptions::API_VERSION,
            $credential,
            $region,
            $clientProfile
        );

        return $this->sdkClients[$index];
    }

    /**
     * @param array $configs
     * @return array
     */
    private static function normalizeConfigs(array $configs)
    {
        $normalized = array(
            EdgeOneOptions::SECRET_ID   => trim((string) (isset($configs[EdgeOneOptions::SECRET_ID]) ? $configs[EdgeOneOptions::SECRET_ID] : '')),
            EdgeOneOptions::SECRET_KEY => trim((string) (isset($configs[EdgeOneOptions::SECRET_KEY]) ? $configs[EdgeOneOptions::SECRET_KEY] : '')),
            EdgeOneOptions::REGION     => trim((string) (isset($configs[EdgeOneOptions::REGION]) ? $configs[EdgeOneOptions::REGION] : '')),
            EdgeOneOptions::TOKEN      => trim((string) (isset($configs[EdgeOneOptions::TOKEN]) ? $configs[EdgeOneOptions::TOKEN] : '')),
            EdgeOneOptions::LANGUAGE   => trim((string) (isset($configs[EdgeOneOptions::LANGUAGE]) ? $configs[EdgeOneOptions::LANGUAGE] : EdgeOneOptions::DEFAULT_LANGUAGE)),
        );

        if ($normalized[EdgeOneOptions::LANGUAGE] === '') {
            $normalized[EdgeOneOptions::LANGUAGE] = EdgeOneOptions::DEFAULT_LANGUAGE;
        }

        return $normalized;
    }

    /**
     * @return void
     * @throws EdgeOneException
     */
    private function validateConfigs()
    {
        if ($this->configs[EdgeOneOptions::SECRET_ID] === '') {
            throw new EdgeOneException('腾讯云 SecretId 未配置，请在系统设置中填写');
        }
        if ($this->configs[EdgeOneOptions::SECRET_KEY] === '') {
            throw new EdgeOneException('腾讯云 SecretKey 未配置，请在系统设置中填写');
        }
    }

    /**
     * 仅网络/接入点类错误才切换后备域名重试
     *
     * @param Exception $e
     * @return bool
     */
    private function isRetryableException(Exception $e)
    {
        if (!$e instanceof TencentCloudSDKException) {
            return true;
        }

        $code = (string) $e->getErrorCode();
        if ($code === '' || $code === 'ClientError') {
            return true;
        }

        if (strpos($code, 'AuthFailure') === 0) {
            return false;
        }

        if (strpos($code, 'InvalidParameter') === 0 || strpos($code, 'MissingParameter') === 0) {
            return false;
        }

        if (strpos($code, 'ResourceNotFound') === 0 || strpos($code, 'UnsupportedOperation') === 0) {
            return false;
        }

        return false;
    }
}
