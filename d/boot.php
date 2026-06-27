<?php
/**
 * 文件：d/boot.php
 * 作用：分享目录统一引导（避免 VS_ROOT 重复定义）
 * @version 1.0.55
 */

if (!defined('VS_ROOT')) {
    define('VS_ROOT', dirname(__DIR__));
    require_once VS_ROOT . '/core/bootstrap.php';
}
