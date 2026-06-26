<?php
/**
 * 文件：core/UploadNaming.php
 * 作用：全局统一的文件上传命名机制
 * @version 1.0.30
 */

class UploadNaming
{
    const MODE_ORIGINAL = 'original';
    const MODE_SEQUENCE = 'sequence';
    const MODE_TIMESTAMP = 'timestamp';
    const MODE_DATETIME_MS = 'datetime_ms';
    const MODE_UUID = 'uuid';
    const MODE_DATE_SEQUENCE = 'date_sequence';

    /**
     * @return array<string, string>
     */
    public static function modes()
    {
        return array(
            self::MODE_ORIGINAL      => '保留原文件名',
            self::MODE_SEQUENCE      => '全局递增序号（1、2、3…）',
            self::MODE_TIMESTAMP     => '时间戳（年月日时分秒）',
            self::MODE_DATETIME_MS   => '时间戳含毫秒',
            self::MODE_UUID          => 'UUID 随机名',
            self::MODE_DATE_SEQUENCE => '日期 + 当日递增序号',
        );
    }

    /**
     * @param string $originalName
     * @return string
     */
    public static function generate($originalName)
    {
        $originalName = basename(str_replace('\\', '/', (string) $originalName));
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? strtolower($ext) : '';
        $mode = Config::get('upload_naming_mode', self::MODE_SEQUENCE);

        switch ($mode) {
            case self::MODE_ORIGINAL:
                return self::sanitizeOriginalName($originalName);

            case self::MODE_TIMESTAMP:
                $base = date('YmdHis');
                break;

            case self::MODE_DATETIME_MS:
                $base = date('YmdHis') . substr((string) floor(microtime(true) * 1000), -3);
                break;

            case self::MODE_UUID:
                $base = self::uuid();
                break;

            case self::MODE_DATE_SEQUENCE:
                $base = date('Ymd') . '_' . self::nextDateSequence();
                break;

            case self::MODE_SEQUENCE:
            default:
                $base = (string) self::nextGlobalSequence();
                break;
        }

        return $ext !== '' ? $base . '.' . $ext : $base;
    }

    /**
     * @param string $name
     * @return string
     */
    private static function sanitizeOriginalName($name)
    {
        $name = preg_replace('/[^\w\.\-()\x{4e00}-\x{9fa5}]+/u', '_', $name);
        $name = trim($name, '._');
        if ($name === '') {
            $name = 'file';
        }
        return $name;
    }

    /**
     * @return int
     */
    private static function nextGlobalSequence()
    {
        $pdo = Database::connect();
        $table = Database::table('config');
        $key = 'upload_name_sequence';

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT `value` FROM `' . $table . '` WHERE `key` = ? FOR UPDATE');
            $stmt->execute(array($key));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $current = $row ? (int) $row['value'] : 0;
            $next = $current + 1;

            if ($row) {
                $upd = $pdo->prepare('UPDATE `' . $table . '` SET `value` = ? WHERE `key` = ?');
                $upd->execute(array((string) $next, $key));
            } else {
                $ins = $pdo->prepare('INSERT INTO `' . $table . '` (`key`, `value`) VALUES (?, ?)');
                $ins->execute(array($key, (string) $next));
            }

            $pdo->commit();
            Config::clearCache();
            return $next;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return int
     */
    private static function nextDateSequence()
    {
        $today = date('Y-m-d');
        $stored = Config::get('upload_date_sequence', '');
        $count = 1;

        if ($stored !== '' && strpos($stored, '|') !== false) {
            list($date, $num) = explode('|', $stored, 2);
            if ($date === $today) {
                $count = (int) $num + 1;
            }
        }

        Config::set('upload_date_sequence', $today . '|' . $count);
        return $count;
    }

    /**
     * @return string
     */
    private static function uuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
