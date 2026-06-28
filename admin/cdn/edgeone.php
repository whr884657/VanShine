<?php
/**
 * 文件：admin/cdn/edgeone.php
 * 作用：重定向至 EdgeOne 概览
 */
require_once __DIR__ . '/../init.php';
header('Location: ' . vs_base_url() . '/admin/cdn/edgeone/index.php');
exit;
