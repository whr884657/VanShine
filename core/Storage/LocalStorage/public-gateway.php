<?php
/**
 * 本地储存对外直链入口（由 LocalStorageDriver 部署到 /{slug}/index.php）
 */

define('VS_ROOT', dirname(__DIR__));

require_once VS_ROOT . '/core/bootstrap.php';
require_once VS_ROOT . '/core/LocalFileServe.php';

$name = '';
if (isset($_GET['name'])) {
    $name = (string) $_GET['name'];
} elseif (isset($_SERVER['PATH_INFO'])) {
    $name = ltrim((string) $_SERVER['PATH_INFO'], '/');
}

LocalFileServe::outputByStoredName($name);
