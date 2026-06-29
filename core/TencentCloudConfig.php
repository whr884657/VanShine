<?php
/**
 * 文件：core/TencentCloudConfig.php
 * 作用：腾讯云通用 API 凭证（COS 与 EdgeOne 共用 SecretId/SecretKey；Region 各自独立）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class TencentCloudConfig
{
    const SECRET_ID  = 'tencent_secret_id';
    const SECRET_KEY = 'tencent_secret_key';
    const APP_ID     = 'tencent_app_id';
    const REGION     = 'tencent_region';

    /**
     * @return string
     */
    public static function getSecretId()
    {
        return self::resolve(self::SECRET_ID, array(
            'storage_cos_secret_id',
            'cdn_edgeone_secret_id',
        ));
    }

    /**
     * @return string
     */
    public static function getSecretKey()
    {
        return self::resolve(self::SECRET_KEY, array(
            'storage_cos_secret_key',
            'cdn_edgeone_secret_key',
        ));
    }

    /**
     * @return string
     */
    public static function getAppId()
    {
        return self::resolve(self::APP_ID, array(
            'storage_cos_app_id',
        ));
    }

    /**
     * COS 储存桶地域（不与 EdgeOne API 地域混用）
     *
     * @return string
     */
    public static function getCosRegion()
    {
        $value = trim(Config::get('storage_cos_region', ''));
        if ($value !== '') {
            return $value;
        }

        $value = trim(Config::get(self::REGION, ''));
        if ($value !== '') {
            return $value;
        }

        return 'ap-guangzhou';
    }

    /**
     * EdgeOne CDN API 地域
     *
     * @return string
     */
    public static function getEdgeOneRegion()
    {
        $value = trim(Config::get('cdn_edgeone_region', ''));
        if ($value !== '') {
            return $value;
        }

        return 'ap-guangzhou';
    }

    /**
     * @deprecated 请使用 getCosRegion() 或 getEdgeOneRegion()
     * @return string
     */
    public static function getRegion()
    {
        return self::getCosRegion();
    }

    /**
     * @return bool
     */
    public static function hasCredentials()
    {
        return self::getSecretId() !== '' && self::getSecretKey() !== '';
    }

    /**
     * @return bool
     */
    public static function cosConfigured()
    {
        return Config::get('storage_cos_enabled', '0') === '1'
            && trim(Config::get('storage_cos_bucket', '')) !== '';
    }

    /**
     * @return bool
     */
    public static function edgeOneConfigured()
    {
        return Config::get('cdn_edgeone_enabled', '0') === '1';
    }

    /**
     * @return string
     */
    public static function sharedStatusHint()
    {
        if (!self::hasCredentials()) {
            return '尚未配置腾讯云 API 密钥，请在下方填写 SecretId 与 SecretKey。';
        }

        $parts = array();
        if (self::cosConfigured() || trim(Config::get('storage_cos_secret_id', '')) !== '') {
            $parts[] = 'COS 储存';
        }
        if (self::edgeOneConfigured() || trim(Config::get('cdn_edgeone_secret_id', '')) !== '') {
            $parts[] = 'EdgeOne CDN';
        }

        $base = 'SecretId / SecretKey 与 COS、EdgeOne 共用；COS 与 EdgeOne 的 Region 各自独立填写。';
        if (count($parts) === 0) {
            return $base;
        }

        return $base . ' 当前已用于 ' . implode('、', $parts) . '。';
    }

    /**
     * 仅同步非空的 SecretId / SecretKey
     *
     * @param array<string, string> $items
     * @return void
     */
    public static function persistSharedSecrets(array $items)
    {
        $shared = array();
        if (isset($items[self::SECRET_ID])) {
            $value = trim($items[self::SECRET_ID]);
            if ($value !== '') {
                $shared[self::SECRET_ID] = $value;
            }
        }
        if (isset($items[self::SECRET_KEY])) {
            $value = trim($items[self::SECRET_KEY]);
            if ($value !== '') {
                $shared[self::SECRET_KEY] = $value;
            }
        }

        if (count($shared) === 0) {
            return;
        }

        Config::setMany($shared);
        self::mirrorSecretsToLegacy($shared);
    }

    /**
     * COS 储存保存时同步密钥与 COS 专属配置
     *
     * @param array<string, string> $post
     * @return void
     */
    public static function syncFromCosPost(array $post)
    {
        self::persistSharedSecrets(array(
            self::SECRET_ID  => isset($post['cfg_cos_secret_id']) ? $post['cfg_cos_secret_id'] : '',
            self::SECRET_KEY => isset($post['cfg_cos_secret_key']) ? $post['cfg_cos_secret_key'] : '',
        ));

        $appId = trim(isset($post['cfg_cos_app_id']) ? $post['cfg_cos_app_id'] : '');
        if ($appId !== '') {
            Config::setMany(array(
                self::APP_ID         => $appId,
                'storage_cos_app_id' => $appId,
            ));
        }

        $region = trim(isset($post['cfg_cos_region']) ? $post['cfg_cos_region'] : '');
        if ($region !== '') {
            Config::setMany(array(
                'storage_cos_region' => $region,
                self::REGION         => $region,
            ));
        }
    }

    /**
     * CDN EdgeOne 保存时同步密钥与 EdgeOne 专属 Region
     *
     * @param array<string, string> $post
     * @return void
     */
    public static function syncFromEdgeOnePost(array $post)
    {
        self::persistSharedSecrets(array(
            self::SECRET_ID  => isset($post['cdn_tencent_secret_id']) ? $post['cdn_tencent_secret_id'] : '',
            self::SECRET_KEY => isset($post['cdn_tencent_secret_key']) ? $post['cdn_tencent_secret_key'] : '',
        ));

        $region = trim(isset($post['cdn_tencent_region']) ? $post['cdn_tencent_region'] : '');
        if ($region !== '') {
            Config::set('cdn_edgeone_region', $region);
        }
    }

    /**
     * 首次升级：将旧版分散密钥迁入通用键
     *
     * @return void
     */
    public static function migrateLegacyIfNeeded()
    {
        if (trim(Config::get(self::SECRET_ID, '')) !== '') {
            return;
        }

        $secretId = Config::get('storage_cos_secret_id', '');
        $secretKey = Config::get('storage_cos_secret_key', '');
        if ($secretId === '') {
            $secretId = Config::get('cdn_edgeone_secret_id', '');
        }
        if ($secretKey === '') {
            $secretKey = Config::get('cdn_edgeone_secret_key', '');
        }

        if ($secretId === '' && $secretKey === '') {
            return;
        }

        $items = array(
            self::SECRET_ID  => $secretId,
            self::SECRET_KEY => $secretKey,
        );

        $appId = trim(Config::get('storage_cos_app_id', ''));
        if ($appId !== '') {
            $items[self::APP_ID] = $appId;
        }

        Config::setMany($items);
        self::mirrorSecretsToLegacy($items);
    }

    /**
     * @param string              $canonical
     * @param array<int, string>  $legacyKeys
     * @return string
     */
    private static function resolve($canonical, array $legacyKeys)
    {
        $value = trim(Config::get($canonical, ''));
        if ($value !== '') {
            return $value;
        }

        foreach ($legacyKeys as $key) {
            $legacy = trim(Config::get($key, ''));
            if ($legacy !== '') {
                return $legacy;
            }
        }

        return '';
    }

    /**
     * 仅镜像 SecretId / SecretKey 到 COS 与 EdgeOne 旧键名
     *
     * @param array<string, string> $shared
     * @return void
     */
    private static function mirrorSecretsToLegacy(array $shared)
    {
        $mirror = array();

        if (isset($shared[self::SECRET_ID]) && $shared[self::SECRET_ID] !== '') {
            $mirror['storage_cos_secret_id'] = $shared[self::SECRET_ID];
            $mirror['cdn_edgeone_secret_id'] = $shared[self::SECRET_ID];
        }
        if (isset($shared[self::SECRET_KEY]) && $shared[self::SECRET_KEY] !== '') {
            $mirror['storage_cos_secret_key'] = $shared[self::SECRET_KEY];
            $mirror['cdn_edgeone_secret_key'] = $shared[self::SECRET_KEY];
        }

        if (count($mirror) > 0) {
            Config::setMany($mirror);
        }
    }
}
