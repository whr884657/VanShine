<?php
/**
 * 文件：core/bootstrap.php
 * 作用：VanShine 系统引导入口，加载核心依赖
 * @version 1.0.30
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
require_once VS_ROOT . '/core/StorageManager.php';

if (session_status() === PHP_SESSION_NONE) {
    AuthSecurity::configureSessionCookies();
    session_start();
    AuthSecurity::ensureCsrfToken();
}
