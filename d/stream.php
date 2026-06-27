<?php
/**
 * 文件：d/stream.php
 * 作用：分享流入口（兼容旧链接，实际逻辑在 stream-handler.php）
 * @version 1.0.55
 */

require __DIR__ . '/boot.php';
InstallChecker::requireInstalled();
require __DIR__ . '/stream-handler.php';
