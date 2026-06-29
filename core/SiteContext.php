<?php
/**
 * 文件：core/SiteContext.php
 * 作用：按当前访问域名解析站点展示信息
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class SiteContext
{
    /** @var array|null */
    private static $cache = null;

    /**
     * 清除解析缓存
     *
     * @return void
     */
    public static function clearCache()
    {
        self::$cache = null;
    }

    /**
     * 当前访问 Host
     *
     * @return string
     */
    public static function currentHost()
    {
        return isset($_SERVER['HTTP_HOST']) ? Domain::normalizeHost($_SERVER['HTTP_HOST']) : '';
    }

    /**
     * 解析当前站点上下文
     *
     * @return array
     */
    public static function resolve()
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $host = self::currentHost();
        $primaryDomain = Domain::normalizeHost((string) Config::get('primary_domain', ''));

        $base = array(
            'host'             => $host,
            'is_primary'       => true,
            'site_name'        => trim((string) Config::get('site_name', 'VanShine')),
            'site_description' => trim((string) Config::get('site_description', '')),
            'site_keywords'    => trim((string) Config::get('site_keywords', '')),
            'site_favicon'     => trim((string) Config::get('site_favicon', '')),
            'site_logo'        => trim((string) Config::get('site_logo', '')),
            'icp_number'       => trim((string) Config::get('site_icp', '')),
            'gongan_number'    => trim((string) Config::get('site_gongan', '')),
        );

        if ($host !== '' && $primaryDomain !== '' && Domain::hostsMatch($host, $primaryDomain)) {
            self::$cache = $base;
            return self::$cache;
        }

        if ($host !== '' && InstallChecker::isInstalled()) {
            $bound = Domain::findByHost($host);
            if ($bound) {
                self::$cache = array(
                    'host'             => $host,
                    'is_primary'       => false,
                    'site_name'        => trim($bound['site_name']) !== '' ? trim($bound['site_name']) : $base['site_name'],
                    'site_description' => $base['site_description'],
                    'site_keywords'    => $base['site_keywords'],
                    'site_favicon'     => $base['site_favicon'],
                    'site_logo'        => $base['site_logo'],
                    'icp_number'       => trim((string) $bound['icp_number']),
                    'gongan_number'    => trim((string) $bound['gongan_number']),
                );
                return self::$cache;
            }
        }

        self::$cache = $base;
        return self::$cache;
    }

    /**
     * @return string
     */
    public static function siteName()
    {
        $ctx = self::resolve();
        $name = trim($ctx['site_name']);
        return $name !== '' ? $name : 'VanShine';
    }

    /**
     * @return string
     */
    public static function siteDescription()
    {
        return self::resolve()['site_description'];
    }

    /**
     * @return string
     */
    public static function siteKeywords()
    {
        return self::resolve()['site_keywords'];
    }

    /**
     * @return string
     */
    public static function siteFavicon()
    {
        return self::resolve()['site_favicon'];
    }

    /**
     * @return string
     */
    public static function siteLogo()
    {
        return self::resolve()['site_logo'];
    }

    /**
     * ICP 备案官方查询链接
     *
     * @return string
     */
    public static function icpLink()
    {
        return 'https://beian.miit.gov.cn/';
    }

    /**
     * 公安备案官方查询链接
     *
     * @param string $number
     * @return string
     */
    public static function gonganLink($number)
    {
        $code = preg_replace('/\D/', '', $number);
        if ($code === '') {
            return 'https://beian.mps.gov.cn/';
        }
        return 'https://beian.mps.gov.cn/#/query/webSearch?code=' . rawurlencode($code);
    }

    /**
     * 获取当前域名备案信息
     *
     * @return array
     */
    public static function beianInfo()
    {
        $ctx = self::resolve();
        return array(
            'icp_number'    => trim((string) $ctx['icp_number']),
            'icp_link'      => self::icpLink(),
            'gongan_number' => trim((string) $ctx['gongan_number']),
            'gongan_link'   => self::gonganLink($ctx['gongan_number']),
        );
    }
}
