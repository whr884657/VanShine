<?php
/**
 * 文件：admin/reset.php
 * 作用：已弃用，重定向至验证码重置页
 * @version 1.0.7
 */

define('VS_ROOT', dirname(__DIR__));
require_once VS_ROOT . '/core/bootstrap.php';

InstallChecker::requireInstalled();
vs_redirect(vs_base_url() . '/admin/forgot.php');
