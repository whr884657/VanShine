<?php
/**
 * 文件：core/ShareRouter.php
 * 作用：分享链接路由解析（query 参数 + 旧版路径兼容）
 * @version 1.0.56
 */

class ShareRouter
{
    /**
     * 当前请求是否为分享文件流
     *
     * @return bool
     */
    public static function isStreamRequest()
    {
        if (isset($_GET['stream']) && (string) $_GET['stream'] === '1') {
            return true;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($uri !== '' && preg_match('#/d/[A-Za-z0-9]{16,64}/stream(?:/|\?|$)#', $uri) === 1) {
            return true;
        }

        if (!empty($_SERVER['PATH_INFO'])) {
            $path = trim((string) $_SERVER['PATH_INFO'], '/');
            if (preg_match('/^[A-Za-z0-9]{16,64}\/stream$/', $path) === 1) {
                return true;
            }
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
        if (isset($_GET['token'])) {
            $token = trim((string) $_GET['token']);
            if ($token !== '' && self::isValidToken($token)) {
                return $token;
            }
        }

        if (!empty($_SERVER['PATH_INFO'])) {
            $path = trim((string) $_SERVER['PATH_INFO'], '/');
            if (preg_match('/^([A-Za-z0-9]{16,64})(?:\/stream)?$/', $path, $matches)) {
                return $matches[1];
            }
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ($uri !== '') {
            $path = parse_url($uri, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                if (preg_match('#/d/([A-Za-z0-9]{16,64})(?:/stream)?/?$#', $path, $matches)) {
                    return $matches[1];
                }
            }
        }

        return '';
    }

    /**
     * 旧版 /d/{token} 路径 → 标准 query 链接
     *
     * @return void
     */
    public static function redirectLegacyIfNeeded()
    {
        if (isset($_GET['token'])) {
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
