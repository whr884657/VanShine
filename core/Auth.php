<?php
/**
 * 文件：core/Auth.php
 * 作用：管理员认证、登录态管理、会话超时
 * @version 1.0.12
 */

class Auth
{
    const SESSION_KEY = 'vs_admin_id';
    const ACTIVITY_KEY = 'vs_last_activity';

    /**
     * 管理员登录
     *
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public static function login($username, $password)
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('admin');
            $hash = vs_password_hash($password);

            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `username` = ? AND `password` = ? AND `status` = 1 LIMIT 1');
            $stmt->execute(array($username, $hash));
            $admin = $stmt->fetch();

            if ($admin) {
                $_SESSION[self::SESSION_KEY] = (int) $admin['id'];
                $_SESSION['vs_admin_username'] = $admin['username'];
                self::touchActivity();
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                return $admin;
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * 退出登录
     *
     * @return void
     */
    public static function logout()
    {
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * 更新最后活动时间
     *
     * @return void
     */
    public static function touchActivity()
    {
        $_SESSION[self::ACTIVITY_KEY] = time();
    }

    /**
     * 是否会话超时
     *
     * @return bool
     */
    public static function isSessionExpired()
    {
        if (empty($_SESSION[self::ACTIVITY_KEY])) {
            return true;
        }

        $timeout = Config::sessionTimeout();
        return (time() - (int) $_SESSION[self::ACTIVITY_KEY]) > $timeout;
    }

    /**
     * 是否已登录（含超时检测）
     *
     * @return bool
     */
    public static function check()
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        if (self::isSessionExpired()) {
            self::logout();
            return false;
        }

        self::touchActivity();
        return true;
    }

    /**
     * 获取当前管理员 ID
     *
     * @return int
     */
    public static function id()
    {
        return isset($_SESSION[self::SESSION_KEY]) ? (int) $_SESSION[self::SESSION_KEY] : 0;
    }

    /**
     * 要求登录，未登录或超时跳转 login.php
     *
     * @return void
     */
    public static function requireLogin()
    {
        if (!empty($_SESSION[self::SESSION_KEY]) && self::isSessionExpired()) {
            self::logout();
            vs_redirect(vs_base_url() . '/admin/login.php?expired=1');
        }

        if (!self::check()) {
            vs_redirect(vs_base_url() . '/admin/login.php');
        }
    }

    /**
     * 已登录时跳转后台首页
     *
     * @return void
     */
    public static function redirectIfLoggedIn()
    {
        if (self::check()) {
            vs_redirect(vs_base_url() . '/admin/index.php');
        }
    }

    /**
     * 获取当前管理员信息
     *
     * @return array|null
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('admin');
            $stmt = $pdo->prepare('SELECT `id`, `username`, `email`, `avatar_url`, `created_at` FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
            $stmt->execute(array(self::id()));
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 更新当前账号（邮箱 / 密码）
     *
     * @param string      $email
     * @param string|null $newPassword
     * @param string|null $oldPassword
     * @param string|null $avatarUrl
     * @return true|string true 成功，string 为错误信息
     */
    public static function updateAccount($email, $newPassword = null, $oldPassword = null, $avatarUrl = null)
    {
        if (!self::check()) {
            return '请先登录';
        }

        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '邮箱格式不正确';
        }

        $avatarUrl = $avatarUrl === null ? null : trim((string) $avatarUrl);
        if ($avatarUrl !== null && $avatarUrl !== '' && !filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
            return '头像链接格式不正确';
        }

        if ($newPassword !== null && $newPassword !== '') {
            if (strlen($newPassword) < 6) {
                return '新密码至少 6 个字符';
            }
            if ($oldPassword === null || $oldPassword === '') {
                return '修改密码需输入当前密码';
            }

            try {
                $pdo = Database::connect();
                $table = Database::table('admin');
                $stmt = $pdo->prepare('SELECT `password` FROM `' . $table . '` WHERE `id` = ? LIMIT 1');
                $stmt->execute(array(self::id()));
                $row = $stmt->fetch();
                if (!$row || $row['password'] !== vs_password_hash($oldPassword)) {
                    return '当前密码不正确';
                }
            } catch (Exception $e) {
                return '验证失败，请稍后再试';
            }
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('admin');
            $savedAvatar = $avatarUrl !== null ? $avatarUrl : '';

            if ($newPassword !== null && $newPassword !== '') {
                $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `email` = ?, `avatar_url` = ?, `password` = ? WHERE `id` = ?');
                $stmt->execute(array($email, $savedAvatar, vs_password_hash($newPassword), self::id()));
            } else {
                $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `email` = ?, `avatar_url` = ? WHERE `id` = ?');
                $stmt->execute(array($email, $savedAvatar, self::id()));
            }

            return true;
        } catch (Exception $e) {
            return '保存失败：' . $e->getMessage();
        }
    }

    /**
     * 通过管理员 ID 重置密码（验证码流程）
     *
     * @param int    $adminId
     * @param string $newPassword
     * @return bool
     */
    public static function resetPasswordById($adminId, $newPassword)
    {
        $adminId = (int) $adminId;
        if ($adminId <= 0 || strlen($newPassword) < 6) {
            return false;
        }

        try {
            $pdo = Database::connect();
            $adminTable = Database::table('admin');
            $resetTable = Database::table('password_reset');

            $stmt = $pdo->prepare('UPDATE `' . $adminTable . '` SET `password` = ? WHERE `id` = ? AND `status` = 1');
            $stmt->execute(array(vs_password_hash($newPassword), $adminId));

            if ($stmt->rowCount() === 0) {
                return false;
            }

            $pdo->prepare('DELETE FROM `' . $resetTable . '` WHERE `admin_id` = ?')->execute(array($adminId));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 创建密码重置令牌（保留兼容，当前忘记密码使用验证码）
     *
     * @param int $adminId
     * @return string
     * @throws Exception
     */
    public static function createResetToken($adminId)
    {
        $pdo = Database::connect();
        $table = Database::table('password_reset');
        $token = bin2hex(random_bytes(32));
        $expireAt = date('Y-m-d H:i:s', time() + 3600);

        $pdo->prepare('DELETE FROM `' . $table . '` WHERE `admin_id` = ?')->execute(array($adminId));

        $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (`admin_id`, `token`, `expire_at`) VALUES (?, ?, ?)');
        $stmt->execute(array($adminId, $token, $expireAt));

        return $token;
    }

    /**
     * 验证重置令牌
     *
     * @param string $token
     * @return array|false
     */
    public static function validateResetToken($token)
    {
        if ($token === '') {
            return false;
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('password_reset');
            $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `token` = ? AND `expire_at` > NOW() LIMIT 1');
            $stmt->execute(array($token));
            return $stmt->fetch() ?: false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 重置密码
     *
     * @param string $token
     * @param string $newPassword
     * @return bool
     */
    public static function resetPassword($token, $newPassword)
    {
        $reset = self::validateResetToken($token);
        if (!$reset) {
            return false;
        }

        try {
            $pdo = Database::connect();
            $adminTable = Database::table('admin');
            $resetTable = Database::table('password_reset');

            $stmt = $pdo->prepare('UPDATE `' . $adminTable . '` SET `password` = ? WHERE `id` = ?');
            $stmt->execute(array(vs_password_hash($newPassword), $reset['admin_id']));

            $pdo->prepare('DELETE FROM `' . $resetTable . '` WHERE `admin_id` = ?')->execute(array($reset['admin_id']));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
