<?php
/**
 * 文件：core/Domain.php
 * 作用：绑定域名读写（存于 config.bound_domains JSON）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class Domain
{
    const CONFIG_KEY = 'bound_domains';

    /**
     * 获取全部绑定域名
     *
     * @return array
     */
    public static function all()
    {
        return self::decodeList((string) Config::get(self::CONFIG_KEY, '[]'));
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

        foreach (self::all() as $row) {
            if (self::hostsMatch($host, $row['domain'])) {
                return $row;
            }
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

        $list = self::all();
        self::assertDomainUnique($list, $domain, 0);

        $id = self::nextId($list);
        $list[] = array(
            'id'            => $id,
            'domain'        => $domain,
            'site_name'     => trim(isset($data['site_name']) ? $data['site_name'] : ''),
            'icp_number'    => trim(isset($data['icp_number']) ? $data['icp_number'] : ''),
            'gongan_number' => trim(isset($data['gongan_number']) ? $data['gongan_number'] : ''),
        );

        self::saveList($list);
        return $id;
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
        $id = (int) $id;
        $domain = self::normalizeHost(isset($data['domain']) ? $data['domain'] : '');
        if ($domain === '') {
            throw new Exception('请填写域名');
        }

        $list = self::all();
        $found = false;

        foreach ($list as &$row) {
            if ((int) $row['id'] !== $id) {
                continue;
            }
            self::assertDomainUnique($list, $domain, $id);
            $row['domain'] = $domain;
            $row['site_name'] = trim(isset($data['site_name']) ? $data['site_name'] : '');
            $row['icp_number'] = trim(isset($data['icp_number']) ? $data['icp_number'] : '');
            $row['gongan_number'] = trim(isset($data['gongan_number']) ? $data['gongan_number'] : '');
            $found = true;
            break;
        }
        unset($row);

        if (!$found) {
            throw new Exception('绑定域名不存在');
        }

        self::saveList($list);
    }

    /**
     * 删除绑定域名
     *
     * @param int $id
     * @return void
     * @throws Exception
     */
    public static function delete($id)
    {
        $id = (int) $id;
        $list = self::all();
        $next = array();

        foreach ($list as $row) {
            if ((int) $row['id'] === $id) {
                continue;
            }
            $next[] = $row;
        }

        if (count($next) === count($list)) {
            throw new Exception('绑定域名不存在');
        }

        self::saveList($next);
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

    /**
     * 解析 bound_domains JSON
     *
     * @param string $raw
     * @return array
     */
    public static function decodeList($raw)
    {
        if ($raw === '') {
            return array();
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return array();
        }

        $list = array();
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $domain = self::normalizeHost(isset($row['domain']) ? $row['domain'] : '');
            if ($domain === '') {
                continue;
            }
            $list[] = array(
                'id'            => (int) (isset($row['id']) ? $row['id'] : 0),
                'domain'        => $domain,
                'site_name'     => trim(isset($row['site_name']) ? $row['site_name'] : ''),
                'icp_number'    => trim(isset($row['icp_number']) ? $row['icp_number'] : ''),
                'gongan_number' => trim(isset($row['gongan_number']) ? $row['gongan_number'] : ''),
            );
        }

        usort($list, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        return $list;
    }

    /**
     * @param array $list
     * @return void
     */
    private static function saveList(array $list)
    {
        Config::set(self::CONFIG_KEY, json_encode(array_values($list), JSON_UNESCAPED_UNICODE));
        SiteContext::clearCache();
    }

    /**
     * @param array $list
     * @return int
     */
    private static function nextId(array $list)
    {
        $max = 0;
        foreach ($list as $row) {
            $max = max($max, (int) $row['id']);
        }
        return $max + 1;
    }

    /**
     * @param array  $list
     * @param string $domain
     * @param int    $exceptId
     * @return void
     * @throws Exception
     */
    private static function assertDomainUnique(array $list, $domain, $exceptId)
    {
        foreach ($list as $row) {
            if ((int) $row['id'] === (int) $exceptId) {
                continue;
            }
            if (self::hostsMatch($domain, $row['domain'])) {
                throw new Exception('该域名已绑定');
            }
        }
    }
}
