<?php
/**
 * 文件：core/version.php
 * 作用：定义 VanShine 系统当前版本号常量 VS_VERSION
 *
 * 说明：
 * - 在线更新、关于页、update.json 均读取此常量
 * - 发版时须同步 update.json、update-log.json 与发行说明目录
 */

if (!defined('VS_VERSION')) {
    define('VS_VERSION', '1.11.1');
}
