<?php
/**
 * 文件：core/bootstrap.php
 * 作用：VanShine 系统引导入口，按序加载 core 下全部核心类
 *
 * 说明：
 * - 前台 /admin /install 等入口在定义 VS_ROOT 后 require 本文件
 * - 不在此加载 EdgeOne SDK（由 admin/cdn/edgeone/init.php 按需加载）
 */

defined('VS_ROOT') or define('VS_ROOT', dirname(__DIR__));

require_once VS_ROOT . '/core/version.php';
require_once VS_ROOT . '/core/helpers.php';
require_once VS_ROOT . '/core/InstallChecker.php';
require_once VS_ROOT . '/core/Database.php';
require_once VS_ROOT . '/core/DatabaseInstaller.php';
require_once VS_ROOT . '/core/Domain.php';
require_once VS_ROOT . '/core/SiteContext.php';
require_once VS_ROOT . '/core/Config.php';
require_once VS_ROOT . '/core/Mailer.php';
require_once VS_ROOT . '/core/Auth.php';
require_once VS_ROOT . '/core/AuthSecurity.php';
require_once VS_ROOT . '/core/AjaxResponse.php';
require_once VS_ROOT . '/core/SystemInfo.php';
require_once VS_ROOT . '/core/Updater.php';
require_once VS_ROOT . '/core/DatabaseMigrator.php';
require_once VS_ROOT . '/core/UpdateLog.php';
require_once VS_ROOT . '/core/UserAvatar.php';
require_once VS_ROOT . '/core/StorageRegistry.php';
require_once VS_ROOT . '/core/UploadNaming.php';
require_once VS_ROOT . '/core/FileFolder.php';
require_once VS_ROOT . '/core/FileItem.php';
require_once VS_ROOT . '/core/FileShare.php';
require_once VS_ROOT . '/core/ShareRouter.php';
require_once VS_ROOT . '/core/StorageManager.php';
require_once VS_ROOT . '/core/TencentCloudConfig.php';

if (session_status() === PHP_SESSION_NONE) {
    AuthSecurity::configureSessionCookies();
    session_start();
    AuthSecurity::ensureCsrfToken();
}
