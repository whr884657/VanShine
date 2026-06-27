<?php
/**
 * 文件：core/ShareRouter.php
 * 作用：分享入口路由（/d/?token=，无需 index.php 与额外伪静态）
 * @version 1.0.58
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
     * @return bool
     */
    public static function isStreamRequest()
    {
        if (isset($_GET['stream']) && (string) $_GET['stream'] === '1') {
            return true;
        }

        $path = self::pathInfo();
        if ($path !== '' && preg_match('/^[A-Za-z0-9]{16,64}\/stream$/', $path) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public static function parseToken()
    {
        if (isset($_GET['token'])) {
            $token = trim((string) $_GET['token']);
            if ($token !== '' && self::isValidToken($token)) {
                return $token;
            }
        }

        $path = self::pathInfo();
        if ($path !== '' && preg_match('/^([A-Za-z0-9]{16,64})(?:\/stream)?$/', $path, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * 旧版 /d/index.php/{token} 或 PATH 格式 → /d/?token=
     *
     * @return void
     */
    public static function redirectLegacyToQueryIfNeeded()
    {
        if (isset($_GET['token']) && self::pathInfo() === '') {
            return;
        }

        $token = self::parseToken();
        if ($token === '') {
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
