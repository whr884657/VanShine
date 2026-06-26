<?php
/**
 * 本地储存对外直链入口（由 LocalStorageDriver 部署到 /{slug}/index.php，Apache 子目录备用）
 */

define('VS_ROOT', dirname(__DIR__));

require_once VS_ROOT . '/core/bootstrap.php';
require_once VS_ROOT . '/core/LocalFileServe.php';

$slug = basename(__DIR__);
$name = '';

if (isset($_GET['name']) && $_GET['name'] !== '') {
    $name = (string) $_GET['name'];
} elseif (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== '') {
    $name = ltrim((string) $_SERVER['PATH_INFO'], '/');
} elseif (isset($_SERVER['REQUEST_URI'])) {
    $path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_string($path)) {
        $prefix = '/' . $slug . '/';
        $pos = strpos($path, $prefix);
        if ($pos !== false) {
            $name = substr($path, $pos + strlen($prefix));
        }
    }
}

LocalFileServe::outputBySlugAndName($slug, $name);
