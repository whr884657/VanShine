<?php
/**
 * 文件：core/ShareRouter.php
 * 作用：分享页 token 解析（/d/?token=）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class ShareRouter
{
    /**
     * @return string
     */
    public static function parseToken()
    {
        if (!isset($_GET['token'])) {
            return '';
        }

        $token = trim((string) $_GET['token']);
        if ($token === '' || !self::isValidToken($token)) {
            return '';
        }

        return $token;
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
