<?php
/**
 * 文件：core/Domain.php
 * 作用：绑定域名数据读写
 * @version 1.0.5
 */

class Domain
{
    /**
     * 获取全部绑定域名
     *
     * @return array
     */
    public static function all()
    {
        try {
            $pdo = Database::connect();
            $table = Database::table('domain');
            $stmt = $pdo->query('SELECT * FROM `' . $table . '` ORDER BY `id` ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array();
        }
    }

    /**
     * 按域名查找（支持 www 前缀容错）
     *
     * @param string $host
     * @return array|null
     */
    public static function findByHost($host)
    {
        $host = self::normalizeHost($host);
        if ($host === '') {
            return null;
        }

        try {
            $pdo = Database::connect();
            $table = Database::table('domain');
            $rows = self::all();

            foreach ($rows as $row) {
                if (self::hostsMatch($host, $row['domain'])) {
                    return $row;
                }
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * 判断两个域名是否匹配（忽略 www 前缀差异）
     *
     * @param string $hostA
     * @param string $hostB
     * @return bool
     */
    public static function hostsMatch($hostA, $hostB)
    {
        $hostA = self::normalizeHost($hostA);
        $hostB = self::normalizeHost($hostB);

        if ($hostA === '' || $hostB === '') {
            return false;
        }

        if ($hostA === $hostB) {
            return true;
        }

        $stripWww = function ($host) {
            return preg_replace('/^www\./', '', $host);
        };

        return $stripWww($hostA) === $stripWww($hostB);
    }

    /**
     * 新增绑定域名
     *
     * @param array $data
     * @return int
     * @throws Exception
     */
    public static function create(array $data)
    {
        $domain = self::normalizeHost(isset($data['domain']) ? $data['domain'] : '');
        if ($domain === '') {
            throw new Exception('请填写域名');
        }

        $pdo = Database::connect();
        $table = Database::table('domain');
        $stmt = $pdo->prepare(
            'INSERT INTO `' . $table . '` (`domain`, `site_name`, `icp_number`, `gongan_number`) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(array(
            $domain,
            trim(isset($data['site_name']) ? $data['site_name'] : ''),
            trim(isset($data['icp_number']) ? $data['icp_number'] : ''),
            trim(isset($data['gongan_number']) ? $data['gongan_number'] : ''),
        ));

        SiteContext::clearCache();
        return (int) $pdo->lastInsertId();
    }

    /**
     * 更新绑定域名
     *
     * @param int   $id
     * @param array $data
     * @return void
     * @throws Exception
     */
    public static function update($id, array $data)
    {
        $domain = self::normalizeHost(isset($data['domain']) ? $data['domain'] : '');
        if ($domain === '') {
            throw new Exception('请填写域名');
        }

        $pdo = Database::connect();
        $table = Database::table('domain');
        $stmt = $pdo->prepare(
            'UPDATE `' . $table . '` SET `domain` = ?, `site_name` = ?, `icp_number` = ?, `gongan_number` = ? WHERE `id` = ?'
        );
        $stmt->execute(array(
            $domain,
            trim(isset($data['site_name']) ? $data['site_name'] : ''),
            trim(isset($data['icp_number']) ? $data['icp_number'] : ''),
            trim(isset($data['gongan_number']) ? $data['gongan_number'] : ''),
            (int) $id,
        ));

        SiteContext::clearCache();
    }

    /**
     * 删除绑定域名
     *
     * @param int $id
     * @return void
     */
    public static function delete($id)
    {
        $pdo = Database::connect();
        $table = Database::table('domain');
        $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `id` = ?');
        $stmt->execute(array((int) $id));
        SiteContext::clearCache();
    }

    /**
     * 规范化域名
     *
     * @param string $host
     * @return string
     */
    public static function normalizeHost($host)
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host);
        $host = preg_replace('#/.*$#', '', $host);
        $host = preg_replace('#:\d+$#', '', $host);
        return $host;
    }
}
