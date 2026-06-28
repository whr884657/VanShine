<?php
/**
 * 文件：core/TencentCloudConfig.php
 * 作用：腾讯云通用 API 凭证（COS、EdgeOne CDN 等共用 SecretId/SecretKey/Region/AppId）
 * @version 1.0.0
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
     * @return string
     */
    public static function getRegion()
    {
        $value = self::resolve(self::REGION, array(
            'storage_cos_region',
            'cdn_edgeone_region',
        ));
        if ($value !== '') {
            return $value;
        }

        return 'ap-guangzhou';
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

        if (count($parts) === 0) {
            return '已配置腾讯云 API 密钥，COS 与 EdgeOne CDN 可共用。';
        }

        return '已配置腾讯云 API 密钥，与 ' . implode('、', $parts) . ' 共用 SecretId / SecretKey / Region。';
    }

    /**
     * @param array<string, string> $items
     * @return void
     */
    public static function persistShared(array $items)
    {
        $shared = array();
        if (isset($items[self::SECRET_ID])) {
            $shared[self::SECRET_ID] = $items[self::SECRET_ID];
        }
        if (isset($items[self::SECRET_KEY])) {
            $shared[self::SECRET_KEY] = $items[self::SECRET_KEY];
        }
        if (isset($items[self::APP_ID])) {
            $shared[self::APP_ID] = $items[self::APP_ID];
        }
        if (isset($items[self::REGION])) {
            $shared[self::REGION] = $items[self::REGION];
        }

        if (count($shared) === 0) {
            return;
        }

        Config::setMany($shared);
        self::mirrorToLegacy($shared);
    }

    /**
     * COS 储存保存时同步通用密钥
     *
     * @param array<string, string> $post
     * @return void
     */
    public static function syncFromCosPost(array $post)
    {
        self::persistShared(array(
            self::SECRET_ID  => trim(isset($post['cfg_cos_secret_id']) ? $post['cfg_cos_secret_id'] : ''),
            self::SECRET_KEY => trim(isset($post['cfg_cos_secret_key']) ? $post['cfg_cos_secret_key'] : ''),
            self::APP_ID     => trim(isset($post['cfg_cos_app_id']) ? $post['cfg_cos_app_id'] : ''),
            self::REGION     => trim(isset($post['cfg_cos_region']) ? $post['cfg_cos_region'] : ''),
        ));
    }

    /**
     * CDN EdgeOne 保存时同步通用密钥
     *
     * @param array<string, string> $post
     * @return void
     */
    public static function syncFromEdgeOnePost(array $post)
    {
        self::persistShared(array(
            self::SECRET_ID  => trim(isset($post['cdn_tencent_secret_id']) ? $post['cdn_tencent_secret_id'] : ''),
            self::SECRET_KEY => trim(isset($post['cdn_tencent_secret_key']) ? $post['cdn_tencent_secret_key'] : ''),
            self::REGION     => trim(isset($post['cdn_tencent_region']) ? $post['cdn_tencent_region'] : ''),
        ));
    }

    /**
     * 首次升级：将旧版分散密钥迁入通用键
     *
     * @return void
     */
    public static function migrateLegacyIfNeeded()
    {
        if (Config::get(self::SECRET_ID, '') !== '') {
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
            self::APP_ID     => Config::get('storage_cos_app_id', ''),
            self::REGION     => self::getRegion(),
        );
        Config::setMany($items);
        self::mirrorToLegacy($items);
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
     * @param array<string, string> $shared
     * @return void
     */
    private static function mirrorToLegacy(array $shared)
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
        if (isset($shared[self::APP_ID]) && $shared[self::APP_ID] !== '') {
            $mirror['storage_cos_app_id'] = $shared[self::APP_ID];
        }
        if (isset($shared[self::REGION]) && $shared[self::REGION] !== '') {
            $mirror['storage_cos_region'] = $shared[self::REGION];
            $mirror['cdn_edgeone_region'] = $shared[self::REGION];
        }

        if (count($mirror) > 0) {
            Config::setMany($mirror);
        }
    }
}
