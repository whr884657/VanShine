<?php
/**
 * 文件：core/InstallChecker.php
 * 作用：检测系统是否已完成安装
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class InstallChecker
{
    /**
     * 安装锁文件路径
     *
     * @return string
     */
    public static function lockFile()
    {
        return VS_ROOT . '/config/install.lock';
    }

    /**
     * 数据库配置文件路径
     *
     * @return string
     */
    public static function configFile()
    {
        return VS_ROOT . '/config/database.php';
    }

    /**
     * 是否已安装
     *
     * @return bool
     */
    public static function isInstalled()
    {
        return file_exists(self::lockFile()) && file_exists(self::configFile());
    }

    /**
     * 未安装时重定向到安装向导
     *
     * @return void
     */
    public static function requireInstalled()
    {
        if (!self::isInstalled()) {
            vs_redirect(vs_base_url() . '/install/');
        }
    }

    /**
     * 已安装时禁止访问安装向导
     *
     * @return void
     */
    public static function requireNotInstalled()
    {
        if (self::isInstalled()) {
            vs_redirect(vs_base_url() . '/');
        }
    }
}
