<?php
/**
 * 文件：core/Database.php
 * 作用：PDO 数据库连接与操作封装
 * @version 1.0.5
 */

class Database
{
    /** @var PDO|null */
    private static $instance = null;

    /** @var array */
    private static $config = array();

    /**
     * 加载数据库配置
     *
     * @return array
     */
    public static function loadConfig()
    {
        $file = InstallChecker::configFile();
        if (!file_exists($file)) {
            return array();
        }
        $config = include $file;
        return is_array($config) ? $config : array();
    }

    /**
     * 使用配置文件连接
     *
     * @return PDO
     * @throws Exception
     */
    public static function connect()
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        self::$config = self::loadConfig();
        if (empty(self::$config)) {
            throw new Exception('数据库配置文件不存在');
        }

        self::$instance = self::connectWithConfig(self::$config);
        return self::$instance;
    }

    /**
     * 使用指定配置连接
     *
     * @param array $config
     * @return PDO
     * @throws Exception
     */
    public static function connectWithConfig(array $config)
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '3306';
        $dbname = isset($config['dbname']) ? $config['dbname'] : '';
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
        $user = isset($config['username']) ? $config['username'] : '';
        $pass = isset($config['password']) ? $config['password'] : '';

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname . ';charset=' . $charset;

        try {
            $pdo = new PDO($dsn, $user, $pass, array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ));
            return $pdo;
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败：' . $e->getMessage());
        }
    }

    /**
     * 测试连接（不指定数据库名）
     *
     * @param array $config
     * @return PDO
     * @throws Exception
     */
    public static function testConnection(array $config)
    {
        $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
        $port = isset($config['port']) ? $config['port'] : '3306';
        $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
        $user = isset($config['username']) ? $config['username'] : '';
        $pass = isset($config['password']) ? $config['password'] : '';
        $dbname = isset($config['dbname']) ? $config['dbname'] : '';

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';charset=' . $charset;
        if ($dbname !== '') {
            $dsn .= ';dbname=' . $dbname;
        }

        try {
            return new PDO($dsn, $user, $pass, array(
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ));
        } catch (PDOException $e) {
            throw new Exception('连接失败：' . $e->getMessage());
        }
    }

    /**
     * 获取表前缀
     *
     * @return string
     */
    public static function prefix()
    {
        if (empty(self::$config)) {
            self::$config = self::loadConfig();
        }
        return isset(self::$config['prefix']) ? self::$config['prefix'] : 'vs_';
    }

    /**
     * 获取带前缀的表名
     *
     * @param string $name
     * @return string
     */
    public static function table($name)
    {
        return self::prefix() . $name;
    }

    /**
     * 重置连接实例
     *
     * @return void
     */
    public static function reset()
    {
        self::$instance = null;
        self::$config = array();
    }
}
