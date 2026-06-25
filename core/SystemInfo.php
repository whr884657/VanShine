<?php
/**
 * 文件：core/SystemInfo.php
 * 作用：服务器与运行环境信息（关于页面）
 * @version 1.0.5
 */

class SystemInfo
{
    /**
     * 获取系统环境信息
     *
     * @return array
     */
    public static function collect()
    {
        $info = array(
            array('label' => '系统名称', 'value' => 'VanShine'),
            array('label' => '系统版本', 'value' => 'v' . VS_VERSION),
            array('label' => 'PHP 版本', 'value' => PHP_VERSION),
            array('label' => '服务器软件', 'value' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '未知'),
            array('label' => '操作系统', 'value' => PHP_OS),
            array('label' => '服务器时间', 'value' => date('Y-m-d H:i:s')),
            array('label' => '时区', 'value' => date_default_timezone_get()),
            array('label' => '当前域名', 'value' => SiteContext::currentHost()),
        );

        if (InstallChecker::isInstalled()) {
            try {
                $pdo = Database::connect();
                $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                $info[] = array('label' => 'MySQL 版本', 'value' => $version);
            } catch (Exception $e) {
                $info[] = array('label' => 'MySQL 版本', 'value' => '连接失败');
            }

            $info[] = array('label' => '会话超时', 'value' => (int) (Config::sessionTimeout() / 60) . ' 分钟（代码固定）');
            $info[] = array('label' => '主绑定域名', 'value' => Config::get('primary_domain', '') ?: '未设置（使用系统默认信息）');
            $info[] = array('label' => '绑定子域名数', 'value' => (string) count(Domain::all()));
        }

        return $info;
    }
}
