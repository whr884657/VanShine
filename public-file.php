<?php
/**
 * 本地储存对外直链入口（站点根路由）
 * URL 形如 /{slug}/{文件名}，由 .htaccess 或 Nginx 转发到此脚本
 */

define('VS_ROOT', __DIR__);

require_once VS_ROOT . '/core/bootstrap.php';
require_once VS_ROOT . '/core/LocalFileServe.php';

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
$name = isset($_GET['name']) ? (string) $_GET['name'] : '';

LocalFileServe::outputBySlugAndName($slug, $name);
