<?php
/**
 * 文件：core/Storage/ILanZou/ILanZouCrypto.php
 * 作用：蓝奏云优享版 API 签名加密（AES-128-ECB）
 * @version 1.0.0
 */

require_once __DIR__ . '/ILanZouException.php';

class ILanZouCrypto
{
    /** @var string */
    const SECRET = 'lanZouY-disk-app';

    /**
     * AES-128-ECB + PKCS7，输出 hex 字符串
     *
     * @param string $data
     * @param string|null $secret
     * @return string
     * @throws ILanZouException
     */
    public static function aesEncryptHex($data, $secret = null)
    {
        $key = $secret !== null ? (string) $secret : self::SECRET;
        if (strlen($key) !== 16) {
            throw new ILanZouException('AES 密钥长度必须为 16 字节');
        }

        $encrypted = openssl_encrypt(
            (string) $data,
            'AES-128-ECB',
            $key,
            OPENSSL_RAW_DATA
        );

        if ($encrypted === false) {
            throw new ILanZouException('AES 加密失败');
        }

        return bin2hex($encrypted);
    }

    /**
     * @return string
     */
    public static function encryptTimestamp()
    {
        $ts = (string) (int) round(microtime(true) * 1000);
        return self::aesEncryptHex($ts);
    }
}
