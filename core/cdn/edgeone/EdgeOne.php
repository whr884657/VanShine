<?php
/**
 * 文件：core/cdn/edgeone/EdgeOne.php
 * 作用：EdgeOne API 统一入口（供后台 CDN 模块调用）
 * @version 1.0.1
 */

class EdgeOne
{
    /** @var EdgeOneClient */
    private $client;

    /** @var EdgeOneZoneApi */
    public $zone;

    /** @var EdgeOneAccelerationDomainApi */
    public $accelerationDomain;

    /** @var EdgeOneL7AccApi */
    public $l7Acc;

    /** @var EdgeOneFunctionApi */
    public $function;

    /** @var EdgeOneAliasDomainApi */
    public $aliasDomain;

    /** @var EdgeOneSecurityApi */
    public $security;

    /** @var EdgeOneL4ProxyApi */
    public $l4Proxy;

    /** @var EdgeOneContentApi */
    public $content;

    /** @var EdgeOneAnalyticsApi */
    public $analytics;

    /** @var EdgeOneLogApi */
    public $log;

    /** @var EdgeOneBillingApi */
    public $billing;

    /** @var EdgeOneCertificateApi */
    public $certificate;

    /** @var EdgeOneOriginAclApi */
    public $originAcl;

    /** @var EdgeOneLoadBalancerApi */
    public $loadBalancer;

    /** @var EdgeOneDiagnosisApi */
    public $diagnosis;

    /** @var EdgeOneCustomErrorPageApi */
    public $customErrorPage;

    /** @var EdgeOneConfigVersionApi */
    public $configVersion;

    /** @var EdgeOneSecurityResourceApi */
    public $securityResource;

    /** @var EdgeOneDnsApi */
    public $dns;

    /** @var EdgeOneContentIdentifierApi */
    public $contentIdentifier;

    /** @var EdgeOneOwnershipApi */
    public $ownership;

    /** @var EdgeOneMediaTranscodeApi */
    public $mediaTranscode;

    /** @var EdgeOneMultiPathGatewayApi */
    public $multiPathGateway;

    /** @var EdgeOneEdgeKvApi */
    public $edgeKv;

    /**
     * @param EdgeOneClient $client
     */
    private function __construct(EdgeOneClient $client)
    {
        $this->client = $client;
        $this->zone = new EdgeOneZoneApi($client);
        $this->accelerationDomain = new EdgeOneAccelerationDomainApi($client);
        $this->l7Acc = new EdgeOneL7AccApi($client);
        $this->function = new EdgeOneFunctionApi($client);
        $this->aliasDomain = new EdgeOneAliasDomainApi($client);
        $this->security = new EdgeOneSecurityApi($client);
        $this->l4Proxy = new EdgeOneL4ProxyApi($client);
        $this->content = new EdgeOneContentApi($client);
        $this->analytics = new EdgeOneAnalyticsApi($client);
        $this->log = new EdgeOneLogApi($client);
        $this->billing = new EdgeOneBillingApi($client);
        $this->certificate = new EdgeOneCertificateApi($client);
        $this->originAcl = new EdgeOneOriginAclApi($client);
        $this->loadBalancer = new EdgeOneLoadBalancerApi($client);
        $this->diagnosis = new EdgeOneDiagnosisApi($client);
        $this->customErrorPage = new EdgeOneCustomErrorPageApi($client);
        $this->configVersion = new EdgeOneConfigVersionApi($client);
        $this->securityResource = new EdgeOneSecurityResourceApi($client);
        $this->dns = new EdgeOneDnsApi($client);
        $this->contentIdentifier = new EdgeOneContentIdentifierApi($client);
        $this->ownership = new EdgeOneOwnershipApi($client);
        $this->mediaTranscode = new EdgeOneMediaTranscodeApi($client);
        $this->multiPathGateway = new EdgeOneMultiPathGatewayApi($client);
        $this->edgeKv = new EdgeOneEdgeKvApi($client);
    }

    /**
     * 加载 EdgeOne 模块全部 PHP 文件
     *
     * @return void
     */
    public static function load()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $base = __DIR__;
        require_once $base . '/EdgeOneOptions.php';
        require_once $base . '/EdgeOneException.php';
        require_once $base . '/EdgeOneActions.php';
        require_once $base . '/EdgeOneClient.php';
        require_once $base . '/Api/EdgeOneApiBase.php';

        foreach (glob($base . '/Api/EdgeOne*Api.php') as $file) {
            require_once $file;
        }

        $loaded = true;
    }

    /**
     * 从 vs_config 创建实例（凭证仅存数据库，禁止代码内传入密钥）
     *
     * @return self
     * @throws EdgeOneException
     */
    public static function create()
    {
        self::load();
        return new self(EdgeOneClient::create());
    }

    /**
     * @return EdgeOneClient
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * 直接调用任意 Action
     *
     * @param string $action
     * @param array  $params
     * @return array
     * @throws EdgeOneException
     */
    public function call($action, array $params = array())
    {
        return $this->client->call($action, $params);
    }
}
