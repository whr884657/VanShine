<?php
/**
 * 文件：core/StorageRegistry.php
 * 作用：七种储存类型注册、配置键映射与驱动加载
 * @version 1.0.34
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
                    array(
                        'key'         => 'root',
                        'label'       => '物理根目录 (Root Path)',
                        'type'        => 'text',
                        'placeholder' => '留空则使用项目 upload/',
                        'hint'        => '服务器上实际存放文件的文件夹绝对路径；一般留空即可，默认使用网站目录下的 upload/ 文件夹。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'placeholder' => 'https://cdn.example.com',
                        'hint'        => '文件对外访问的域名或 CDN 地址，需与 Bucket 公开访问配置一致，末尾不要加斜杠。',
                    ),
                    array(
                        'key'  => 'access_key_id',
                        'label'=> '访问密钥 ID (Access Key ID)',
                        'type' => 'text',
                        'hint' => 'AWS IAM 用户的安全凭证中的 Access Key ID，用于 API 身份识别。',
                    ),
                    array(
                        'key'  => 'secret_access_key',
                        'label'=> '访问密钥 (Secret Access Key)',
                        'type' => 'password',
                        'hint' => '与 Access Key ID 配对的 Secret，仅在创建时显示一次，请妥善保管。',
                    ),
                    array(
                        'key'         => 'endpoint',
                        'label'       => '服务 Endpoint',
                        'type'        => 'text',
                        'placeholder' => 'https://s3.amazonaws.com',
                        'hint'        => 'S3 兼容存储的 API 地址；AWS 官方可填 https://s3.amazonaws.com，MinIO 等填自建地址。',
                    ),
                    array(
                        'key'         => 'region',
                        'label'       => '区域 (Region)',
                        'type'        => 'text',
                        'placeholder' => 'ap-east-1',
                        'hint'        => 'Bucket 所在区域代码，如 ap-east-1、us-east-1，在 AWS 控制台 Bucket 概览中查看。',
                    ),
                    array(
                        'key'         => 'bucket',
                        'label'       => '储存桶名称 (Bucket)',
                        'type'        => 'text',
                        'hint'        => 'S3 储存桶名称（Bucket Name），上传的文件将保存在此桶内。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'placeholder' => 'https://your-bucket.oss-cn-hangzhou.aliyuncs.com',
                        'hint'        => '文件外链访问地址，可填 Bucket 默认域名或已绑定的自定义 CDN 域名。',
                    ),
                    array(
                        'key'  => 'access_key_id',
                        'label'=> 'AccessKey ID',
                        'type' => 'text',
                        'hint' => '阿里云 RAM 用户或主账号的 AccessKey ID，在「访问控制 → 用户 → 安全凭证」中获取。',
                    ),
                    array(
                        'key'  => 'access_key_secret',
                        'label'=> 'AccessKey Secret',
                        'type' => 'password',
                        'hint' => '与 AccessKey ID 配对的 Secret，创建后请立即保存，无法再次查看完整内容。',
                    ),
                    array(
                        'key'         => 'endpoint',
                        'label'       => 'Endpoint 节点',
                        'type'        => 'text',
                        'placeholder' => 'oss-cn-hangzhou.aliyuncs.com',
                        'hint'        => 'Bucket 所在地域的 Endpoint，在 OSS 控制台 Bucket 概览「访问端口」处查看，不含 https://。',
                    ),
                    array(
                        'key'         => 'bucket',
                        'label'       => 'Bucket 储存桶名称',
                        'type'        => 'text',
                        'hint'        => 'OSS 储存桶名称，全球唯一，在 OSS 控制台创建 Bucket 时设定。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'placeholder' => 'https://bucket-xxx.cos.ap-guangzhou.myqcloud.com',
                        'hint'        => '文件公开访问域名，可填 COS 默认域名或已接入的 CDN 加速域名。',
                    ),
                    array(
                        'key'  => 'app_id',
                        'label'=> 'AppId 应用 ID',
                        'type' => 'text',
                        'hint' => '腾讯云账号 AppId（纯数字），在控制台右上角账号信息或「访问管理 → 访问密钥」页面查看。',
                    ),
                    array(
                        'key'  => 'secret_id',
                        'label'=> 'SecretId 密钥 ID',
                        'type' => 'text',
                        'hint' => 'API 密钥 SecretId，在「访问管理 → 访问密钥 → API 密钥管理」中创建。',
                    ),
                    array(
                        'key'  => 'secret_key',
                        'label'=> 'SecretKey 密钥 Key',
                        'type' => 'password',
                        'hint' => '与 SecretId 配对的 SecretKey，请勿泄露或提交到代码仓库。',
                    ),
                    array(
                        'key'         => 'region',
                        'label'       => '地域 (Region)',
                        'type'        => 'text',
                        'placeholder' => 'ap-guangzhou',
                        'hint'        => 'Bucket 所在地域简称，如 ap-guangzhou、ap-shanghai，在 COS 控制台 Bucket 列表中查看。',
                    ),
                    array(
                        'key'         => 'bucket',
                        'label'       => 'Bucket 储存桶名称',
                        'type'        => 'text',
                        'hint'        => 'COS 储存桶名称，格式通常为 name-appid，在创建 Bucket 时确定。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'placeholder' => 'https://cdn.example.com',
                        'hint'        => '空间绑定的外链默认域名或自定义 CDN 域名，用于生成文件公开访问链接。',
                    ),
                    array(
                        'key'  => 'access_key',
                        'label'=> 'AccessKey 公钥',
                        'type' => 'text',
                        'hint' => '七牛云账号的 AccessKey，在「个人中心 → 密钥管理」中获取。',
                    ),
                    array(
                        'key'  => 'secret_key',
                        'label'=> 'SecretKey 私钥',
                        'type' => 'password',
                        'hint' => '与 AccessKey 配对的 SecretKey，用于上传签名，请妥善保管。',
                    ),
                    array(
                        'key'         => 'bucket',
                        'label'       => '空间名称 (Bucket)',
                        'type'        => 'text',
                        'hint'        => '七牛云对象存储空间（Bucket）名称，在 Kodo 控制台「空间管理」中查看。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'hint'        => '文件分享外链前缀，用于拼接生成对外访问地址，按蓝奏优享实际分享域名填写。',
                    ),
                    array(
                        'key'  => 'username',
                        'label'=> '登录账号',
                        'type' => 'text',
                        'hint' => '蓝奏云优享版登录手机号或账号。',
                    ),
                    array(
                        'key'  => 'password',
                        'label'=> '登录密码',
                        'type' => 'password',
                        'hint' => '蓝奏云优享版登录密码，用于自动获取上传令牌。',
                    ),
                    array(
                        'key'         => 'folder_id',
                        'label'       => '目标文件夹 ID (Folder ID)',
                        'type'        => 'text',
                        'placeholder' => '根目录填 0',
                        'hint'        => '上传到蓝奏云中的目标文件夹 ID；上传到根目录请填 0。',
                    ),
                    array(
                        'key'         => 'uuid',
                        'label'       => '设备 UUID',
                        'type'        => 'text',
                        'placeholder' => '留空自动获取',
                        'hint'        => '客户端设备标识，一般留空由系统自动生成；仅在平台要求固定设备 ID 时填写。',
                    ),
                    array(
                        'key'         => 'token',
                        'label'       => '登录令牌 (Token)',
                        'type'        => 'password',
                        'placeholder' => '留空自动登录',
                        'hint'        => '已获取的登录 Token，留空则使用账号密码自动登录刷新。',
                    ),
                    array(
                        'key'         => 'ip',
                        'label'       => 'X-Forwarded-For',
                        'type'        => 'text',
                        'hint'        => '可选，模拟请求来源 IP；仅在平台校验客户端 IP 时需要填写。',
                    ),
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
                    array(
                        'key'         => 'url',
                        'label'       => '访问 URL (Public URL)',
                        'type'        => 'text',
                        'hint'        => '文件对外访问的基础 URL（若 WebDAV 服务有 HTTP 直链或反代地址），用于生成分享链接。',
                    ),
                    array(
                        'key'         => 'base_uri',
                        'label'       => 'WebDAV Base URI',
                        'type'        => 'text',
                        'placeholder' => 'https://dav.example.com/remote.php/dav/files/user/',
                        'hint'        => 'WebDAV 服务根路径，通常以 / 结尾；Nextcloud 示例：https://域名/remote.php/dav/files/用户名/',
                    ),
                    array(
                        'key'  => 'username',
                        'label'=> '用户名 (Username)',
                        'type' => 'text',
                        'hint' => 'WebDAV 服务器登录用户名。',
                    ),
                    array(
                        'key'  => 'password',
                        'label'=> '密码 (Password)',
                        'type' => 'password',
                        'hint' => 'WebDAV 服务器登录密码或应用专用密码。',
                    ),
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
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
            $configs['public_slug'] = LocalStorageDriver::publicSlug(false);
            $configs['url'] = LocalStorageDriver::defaultPublicUrl();
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
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
            require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
            $configs['public_slug'] = LocalStorageDriver::publicSlug(false);
            $configs['url'] = LocalStorageDriver::defaultPublicUrl();
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
            'upload_naming_mode'          => 'sequence',
            'upload_name_sequence'        => '0',
            'upload_date_sequence'        => '',
            'storage_local_public_slug'   => '',
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
