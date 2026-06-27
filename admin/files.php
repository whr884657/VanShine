<?php
/**
 * 文件：admin/files.php
 * 作用：兼容旧地址，跳转至 admin/files/
 * @version 1.0.54
 */

$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
header('Location: /admin/files/' . $query, true, 301);
exit;
