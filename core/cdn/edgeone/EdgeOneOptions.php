<?php
/**
 * 文件：core/cdn/edgeone/EdgeOneOptions.php
 * 作用：EdgeOne 配置键（凭证与 COS 共用 TencentCloudConfig）
 * @version 1.0.2
 */

class EdgeOneOptions
{
    /** vs_config 键前缀 */
    const CONFIG_PREFIX = 'cdn_edgeone_';

    const ENABLED  = 'enabled';
    const TOKEN    = 'token';
    const LANGUAGE = 'language';

    /** 兼容旧键名（读写经 TencentCloudConfig 镜像） */
    const SECRET_ID  = 'secret_id';
    const SECRET_KEY = 'secret_key';
    const REGION     = 'region';

    const DEFAULT_LANGUAGE = 'zh-CN';
    const API_VERSION = '2022-09-01';
    const SERVICE = 'teo';

    /** 三个常用地域主用接入点（随机选取其一） */
    const PRIMARY_ENDPOINTS = array(
        array('region' => 'ap-guangzhou', 'endpoint' => 'teo.ap-guangzhou.tencentcloudapi.com'),
        array('region' => 'ap-shanghai', 'endpoint' => 'teo.ap-shanghai.tencentcloudapi.com'),
        array('region' => 'ap-chongqing', 'endpoint' => 'teo.ap-chongqing.tencentcloudapi.com'),
    );

    /** 后备接入点（主用失败时依次尝试） */
    const FALLBACK_ENDPOINTS = array(
        array('region' => 'ap-guangzhou', 'endpoint' => 'teo.tencentcloudapi.com'),
        array('region' => 'ap-nanjing', 'endpoint' => 'teo.ap-nanjing.tencentcloudapi.com'),
        array('region' => 'ap-beijing', 'endpoint' => 'teo.ap-beijing.tencentcloudapi.com'),
        array('region' => 'ap-chengdu', 'endpoint' => 'teo.ap-chengdu.tencentcloudapi.com'),
        array('region' => 'ap-hongkong', 'endpoint' => 'teo.ap-hongkong.tencentcloudapi.com'),
    );

    /**
     * EdgeOne 专属配置键（不含共用腾讯云密钥）
     *
     * @return array<string, string>
     */
    public static function configKeys()
    {
        return array(
            self::ENABLED,
            self::TOKEN,
            self::LANGUAGE,
        );
    }

    /**
     * @param string $key
     * @return string
     */
    public static function configKey($key)
    {
        return self::CONFIG_PREFIX . $key;
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return Config::get(self::configKey(self::ENABLED), '0') === '1';
    }

    /**
     * @return array<string, string>
     */
    public static function buildClientConfigs()
    {
        TencentCloudConfig::migrateLegacyIfNeeded();

        $configs = array(
            self::SECRET_ID  => TencentCloudConfig::getSecretId(),
            self::SECRET_KEY => TencentCloudConfig::getSecretKey(),
            self::REGION     => TencentCloudConfig::getEdgeOneRegion(),
            self::TOKEN      => trim(Config::get(self::configKey(self::TOKEN), '')),
            self::LANGUAGE   => trim(Config::get(self::configKey(self::LANGUAGE), '')),
        );

        if ($configs[self::LANGUAGE] === '') {
            $configs[self::LANGUAGE] = self::DEFAULT_LANGUAGE;
        }

        return $configs;
    }

    /**
     * 构建接入点链：随机主用 + 打乱顺序的后备
     *
     * @return array<int, array{region: string, endpoint: string}>
     */
    public static function buildEndpointChain()
    {
        $primaries = self::PRIMARY_ENDPOINTS;
        $picked = $primaries[array_rand($primaries)];

        $chain = array($picked);
        $seen = array($picked['endpoint'] => true);

        $fallbacks = self::FALLBACK_ENDPOINTS;
        shuffle($fallbacks);
        foreach ($fallbacks as $item) {
            if (!isset($seen[$item['endpoint']])) {
                $chain[] = $item;
                $seen[$item['endpoint']] = true;
            }
        }

        return $chain;
    }
}
