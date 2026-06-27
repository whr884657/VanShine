<?php
/**
 * 文件：core/ShareRouter.php
 * 作用：分享入口 d/index.php 路由解析（PATH_INFO，无需额外伪静态）
 * @version 1.0.57
 */

class ShareRouter
{
    /**
     * @return string
     */
    public static function pathInfo()
    {
        if (empty($_SERVER['PATH_INFO'])) {
            return '';
        }
        return trim((string) $_SERVER['PATH_INFO'], '/');
    }

    /**
     * 当前请求是否为分享文件流
     *
     * @return bool
     */
    public static function isStreamRequest()
    {
        $path = self::pathInfo();
        if ($path !== '' && preg_match('/^[A-Za-z0-9]{16,64}\/stream$/', $path) === 1) {
            return true;
        }

        if (isset($_GET['stream']) && (string) $_GET['stream'] === '1') {
            return true;
        }

        return false;
    }

    /**
     * 从当前请求解析分享 token
     *
     * @return string
     */
    public static function parseToken()
    {
        $path = self::pathInfo();
        if ($path !== '' && preg_match('/^([A-Za-z0-9]{16,64})(?:\/stream)?$/', $path, $matches)) {
            return $matches[1];
        }

        if (isset($_GET['token'])) {
            $token = trim((string) $_GET['token']);
            if ($token !== '' && self::isValidToken($token)) {
                return $token;
            }
        }

        return '';
    }

    /**
     * 旧版 ?token= 链接 → 标准 PATH 链接
     *
     * @return void
     */
    public static function redirectQueryToPathIfNeeded()
    {
        if (!isset($_GET['token'])) {
            return;
        }

        $token = trim((string) $_GET['token']);
        if ($token === '' || !self::isValidToken($token)) {
            return;
        }

        if (self::pathInfo() !== '') {
            return;
        }

        if (self::isStreamRequest()) {
            $fileId = (int) (isset($_GET['file']) ? $_GET['file'] : 0);
            $url = FileShare::streamUrl($token, $fileId, !empty($_GET['download']));
        } else {
            $url = FileShare::publicUrl($token);
        }

        header('Location: ' . $url, true, 301);
        exit;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function isValidToken($token)
    {
        return is_string($token) && preg_match('/^[A-Za-z0-9]{16,64}$/', $token) === 1;
    }
}
