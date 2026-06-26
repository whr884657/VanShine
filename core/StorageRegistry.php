<?php
/**
 * 文件：core/StorageRegistry.php
 * 作用：七种储存类型注册、配置键映射与驱动加载
 * @version 1.0.32
 */

class StorageRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function types()
    {
        return array(
            1 => array(
                'key'      => 1,
                'slug'     => 'local',
                'name'     => '本地储存',
                'driver'   => 'LocalStorageDriver',
                'options'  => 'LocalStorageOptions',
                'dir'      => 'LocalStorage',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用本地储存', 'type' => 'checkbox'),
                    array('key' => 'root', 'label' => '物理根目录', 'type' => 'text', 'placeholder' => '留空则使用项目 upload/'),
                ),
            ),
            2 => array(
                'key'      => 2,
                'slug'     => 's3',
                'name'     => 'AWS S3',
                'driver'   => 'AwsS3Driver',
                'options'  => 'AwsS3Options',
                'dir'      => 'AwsS3',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用 AWS S3', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'access_key_id', 'label' => 'Access Key ID', 'type' => 'text'),
                    array('key' => 'secret_access_key', 'label' => 'Secret Access Key', 'type' => 'password'),
                    array('key' => 'endpoint', 'label' => 'Endpoint', 'type' => 'text'),
                    array('key' => 'region', 'label' => 'Region', 'type' => 'text'),
                    array('key' => 'bucket', 'label' => 'Bucket', 'type' => 'text'),
                ),
            ),
            3 => array(
                'key'      => 3,
                'slug'     => 'oss',
                'name'     => '阿里云 OSS',
                'driver'   => 'AliyunOssDriver',
                'options'  => 'AliyunOssOptions',
                'dir'      => 'AliyunOss',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用阿里云 OSS', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'access_key_id', 'label' => 'AccessKey ID', 'type' => 'text'),
                    array('key' => 'access_key_secret', 'label' => 'AccessKey Secret', 'type' => 'password'),
                    array('key' => 'endpoint', 'label' => 'Endpoint', 'type' => 'text'),
                    array('key' => 'bucket', 'label' => 'Bucket', 'type' => 'text'),
                ),
            ),
            4 => array(
                'key'      => 4,
                'slug'     => 'cos',
                'name'     => '腾讯云 COS',
                'driver'   => 'TencentCosDriver',
                'options'  => 'TencentCosOptions',
                'dir'      => 'TencentCos',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用腾讯云 COS', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'app_id', 'label' => 'AppId', 'type' => 'text'),
                    array('key' => 'secret_id', 'label' => 'SecretId', 'type' => 'text'),
                    array('key' => 'secret_key', 'label' => 'SecretKey', 'type' => 'password'),
                    array('key' => 'region', 'label' => 'Region', 'type' => 'text'),
                    array('key' => 'bucket', 'label' => 'Bucket', 'type' => 'text'),
                ),
            ),
            5 => array(
                'key'      => 5,
                'slug'     => 'qiniu',
                'name'     => '七牛云 Kodo',
                'driver'   => 'QiniuKodoDriver',
                'options'  => 'QiniuKodoOptions',
                'dir'      => 'QiniuKodo',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用七牛云 Kodo', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'access_key', 'label' => 'AccessKey', 'type' => 'text'),
                    array('key' => 'secret_key', 'label' => 'SecretKey', 'type' => 'password'),
                    array('key' => 'bucket', 'label' => 'Bucket', 'type' => 'text'),
                ),
            ),
            6 => array(
                'key'      => 6,
                'slug'     => 'ilanzou',
                'name'     => '蓝奏云优享版',
                'driver'   => 'ILanZouDriver',
                'options'  => 'ILanZouOptions',
                'dir'      => 'ILanZou',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用蓝奏云优享版', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'username', 'label' => '登录账号', 'type' => 'text'),
                    array('key' => 'password', 'label' => '登录密码', 'type' => 'password'),
                    array('key' => 'folder_id', 'label' => '目标文件夹 ID', 'type' => 'text', 'placeholder' => '根目录填 0'),
                    array('key' => 'uuid', 'label' => '设备 UUID', 'type' => 'text', 'placeholder' => '留空自动获取'),
                    array('key' => 'token', 'label' => '登录令牌', 'type' => 'password', 'placeholder' => '留空自动登录'),
                    array('key' => 'ip', 'label' => 'X-Forwarded-For', 'type' => 'text'),
                ),
            ),
            7 => array(
                'key'      => 7,
                'slug'     => 'webdav',
                'name'     => 'WebDAV',
                'driver'   => 'WebDavStorageDriver',
                'options'  => 'WebDavStorageOptions',
                'dir'      => 'WebDavStorage',
                'fields'   => array(
                    array('key' => 'enabled', 'label' => '启用 WebDAV', 'type' => 'checkbox'),
                    array('key' => 'url', 'label' => '访问 URL', 'type' => 'text'),
                    array('key' => 'base_uri', 'label' => 'WebDAV Base URI', 'type' => 'text'),
                    array('key' => 'username', 'label' => '用户名', 'type' => 'text'),
                    array('key' => 'password', 'label' => '密码', 'type' => 'password'),
                ),
            ),
        );
    }

    /**
     * @param int $key
     * @return array<string, mixed>|null
     */
    public static function type($key)
    {
        $types = self::types();
        return isset($types[(int) $key]) ? $types[(int) $key] : null;
    }

    /**
     * @param int $key
     * @return string
     */
    public static function configDbKey($key, $field)
    {
        $type = self::type($key);
        if ($type === null) {
            return '';
        }
        return 'storage_' . $type['slug'] . '_' . $field;
    }

    /**
     * @param int $key
     * @return array<string, string>
     */
    public static function loadDriverConfigs($key)
    {
        $type = self::type($key);
        if ($type === null) {
            return array();
        }

        $configs = array();
        foreach ($type['fields'] as $field) {
            if ($field['key'] === 'enabled') {
                continue;
            }
            $dbKey = self::configDbKey($key, $field['key']);
            $configs[$field['key']] = Config::get($dbKey, '');
        }

        if ((int) $key === 1) {
            $configs['url'] = self::localPublicUrl();
        }

        return $configs;
    }

    /**
     * 本地储存公开访问 URL 前缀（随当前站点域名动态生成）
     *
     * @return string
     */
    public static function localPublicUrl()
    {
        require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
        require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
        return LocalStorageDriver::defaultPublicUrl();
    }

    /**
     * @param int $key
     * @return bool
     */
    public static function isEnabled($key)
    {
        return Config::get(self::configDbKey($key, 'enabled'), '0') === '1';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function enabledTypes()
    {
        $list = array();
        foreach (self::types() as $key => $type) {
            if (self::isEnabled($key)) {
                $list[$key] = $type;
            }
        }
        return $list;
    }

    /**
     * @param int $key
     * @return object
     * @throws Exception
     */
    public static function driver($key)
    {
        $type = self::type($key);
        if ($type === null) {
            throw new Exception('未知的储存类型');
        }
        if (!self::isEnabled($key)) {
            throw new Exception($type['name'] . ' 未启用，请先在系统设置中配置');
        }

        $base = VS_ROOT . '/core/Storage/' . $type['dir'];
        $optionsFile = $base . '/' . $type['options'] . '.php';
        $driverFile = $base . '/' . $type['driver'] . '.php';

        if (!is_file($optionsFile) || !is_file($driverFile)) {
            throw new Exception($type['name'] . ' 驱动文件缺失');
        }

        require_once $optionsFile;
        require_once $driverFile;

        $driverClass = $type['driver'];
        return $driverClass::fromConfigs(self::loadDriverConfigs($key));
    }

    /**
     * @param int   $key
     * @param array $configs
     * @return object
     * @throws Exception
     */
    public static function driverWithConfigs($key, array $configs)
    {
        $type = self::type($key);
        if ($type === null) {
            throw new Exception('未知的储存类型');
        }

        $base = VS_ROOT . '/core/Storage/' . $type['dir'];
        require_once $base . '/' . $type['options'] . '.php';
        require_once $base . '/' . $type['driver'] . '.php';

        $driverClass = $type['driver'];
        return $driverClass::fromConfigs($configs);
    }

    /**
     * @param int        $key
     * @param array|null $post
     * @return array<string, string>
     */
    public static function resolveDriverConfigs($key, array $post = null)
    {
        $type = self::type($key);
        if ($type === null) {
            return array();
        }

        $configs = array();
        foreach ($type['fields'] as $field) {
            if ($field['key'] === 'enabled') {
                continue;
            }

            $dbKey = self::configDbKey($key, $field['key']);
            $value = '';

            if ($post !== null) {
                $postKey = 'cfg_' . $type['slug'] . '_' . $field['key'];
                $value = trim(isset($post[$postKey]) ? $post[$postKey] : '');
                if ($field['type'] === 'password' && $value === '') {
                    $value = Config::get($dbKey, '');
                }
            } else {
                $value = Config::get($dbKey, '');
            }

            $configs[$field['key']] = $value;
        }

        if ((int) $key === 1) {
            $configs['url'] = self::localPublicUrl();
        }

        return $configs;
    }

    /**
     * @param int        $key
     * @param array|null $post
     * @return void
     * @throws Exception
     */
    public static function testConnection($key, array $post = null)
    {
        $type = self::type($key);
        if ($type === null) {
            throw new Exception('未知的储存类型');
        }

        $configs = self::resolveDriverConfigs($key, $post);
        if (trim(isset($configs['url']) ? $configs['url'] : '') === '' && (int) $key !== 1) {
            throw new Exception('请先填写访问 URL');
        }

        $driver = self::driverWithConfigs($key, $configs);
        $probe = '.vanshine-probe/' . str_replace('.', '', uniqid('', true)) . '.txt';

        try {
            $driver->write($probe, 'VanShine storage probe');
            if (!$driver->exists($probe)) {
                throw new Exception('写入探测文件后无法确认存在');
            }
            $driver->delete($probe);
        } catch (Exception $e) {
            throw new Exception($type['name'] . ' 连接失败：' . $e->getMessage());
        }
    }

    /**
     * @param int    $key
     * @param string $pathname
     * @return string
     * @throws Exception
     */
    public static function buildUrl($key, $pathname)
    {
        $type = self::type($key);
        if ($type === null) {
            throw new Exception('未知的储存类型');
        }

        $base = VS_ROOT . '/core/Storage/' . $type['dir'];
        require_once $base . '/' . $type['options'] . '.php';
        require_once $base . '/' . $type['driver'] . '.php';

        $driverClass = $type['driver'];
        return $driverClass::buildUrl(self::loadDriverConfigs($key), $pathname);
    }

    /**
     * @return array<string, string>
     */
    public static function allConfigDefaults()
    {
        $defaults = array(
            'upload_naming_mode'     => 'sequence',
            'upload_name_sequence'   => '0',
            'upload_date_sequence'   => '',
        );

        foreach (self::types() as $key => $type) {
            foreach ($type['fields'] as $field) {
                $dbKey = self::configDbKey($key, $field['key']);
                $defaults[$dbKey] = $field['key'] === 'enabled' ? '0' : '';
            }
        }

        return $defaults;
    }

    /**
     * @param array<string, string> $post
     * @return array<string, string>
     */
    public static function configsFromPost(array $post)
    {
        $items = array(
            'upload_naming_mode' => trim(isset($post['upload_naming_mode']) ? $post['upload_naming_mode'] : 'sequence'),
        );

        $modes = UploadNaming::modes();
        if (!isset($modes[$items['upload_naming_mode']])) {
            $items['upload_naming_mode'] = UploadNaming::MODE_SEQUENCE;
        }

        foreach (self::types() as $key => $type) {
            foreach ($type['fields'] as $field) {
                $dbKey = self::configDbKey($key, $field['key']);
                if ($field['type'] === 'checkbox') {
                    $postKey = 'cfg_' . $type['slug'] . '_' . $field['key'];
                    $items[$dbKey] = isset($post[$postKey]) ? '1' : '0';
                    continue;
                }

                $postKey = 'cfg_' . $type['slug'] . '_' . $field['key'];
                $value = trim(isset($post[$postKey]) ? $post[$postKey] : '');

                if ($field['type'] === 'password' && $value === '') {
                    continue;
                }

                $items[$dbKey] = $value;
            }
        }

        return $items;
    }
}
