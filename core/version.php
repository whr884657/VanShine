<?php
/**
 * 文件：core/version.php
 * 作用：VanShine 全局版本号唯一来源
 * @version 1.0.36
 */

defined('VS_ROOT') or define('VS_ROOT', dirname(__DIR__));

if (!defined('VS_VERSION')) {
    define('VS_VERSION', '1.0.36');
}

/** 会话超时秒数（无操作自动退出，每次操作刷新计时） */
if (!defined('VS_SESSION_TIMEOUT')) {
    define('VS_SESSION_TIMEOUT', 1800);
}
