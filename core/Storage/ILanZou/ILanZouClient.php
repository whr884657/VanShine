<?php
/**
 * 文件：core/Storage/ILanZou/ILanZouClient.php
 * 作用：蓝奏云优享版 API 客户端
 * @version 1.0.0
 */

require_once __DIR__ . '/ILanZouException.php';
require_once __DIR__ . '/ILanZouCrypto.php';

class ILanZouClient
{
    /** @var int 分片大小 8MB */
    const PART_SIZE = 8388608;

    /** @var string */
    const API_BASE = 'https://apis.ilanzou.com';

    /** @var string */
    const SITE = 'https://www.ilanzou.com';

    /** @var string */
    const QINIU_BUCKET = 'wpanstore-lanzou';

    /** @var string */
    const DEV_VERSION = '125';

    /** @var array */
    private $configs;

    /** @var string */
    private $uuid;

    /** @var string */
    private $token;

    /** @var string */
    private $userId = '';

    /** @var string */
    private $account = '';

    /** @var bool */
    private $initialized = false;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
        $this->uuid = isset($configs[ILanZouOptions::UUID]) ? (string) $configs[ILanZouOptions::UUID] : '';
        $this->token = isset($configs[ILanZouOptions::TOKEN]) ? (string) $configs[ILanZouOptions::TOKEN] : '';
    }

    /**
     * @return void
     * @throws ILanZouException
     */
    public function init()
    {
        if ($this->initialized) {
            return;
        }

        if ($this->uuid === '') {
            $res = $this->unproved('/getUuid', 'GET');
            $this->uuid = isset($res['uuid']) ? (string) $res['uuid'] : '';
            if ($this->uuid === '') {
                throw new ILanZouException('获取 UUID 失败');
            }
        }

        if ($this->token === '') {
            $this->login();
        }

        $res = $this->proved('/user/account/map', 'GET');
        $map = isset($res['map']) && is_array($res['map']) ? $res['map'] : array();
        $this->userId = isset($map['userId']) ? (string) $map['userId'] : '';
        $this->account = isset($map['account']) ? (string) $map['account'] : '';

        if ($this->userId === '') {
            throw new ILanZouException('获取用户信息失败');
        }

        $this->initialized = true;
    }

    /**
     * @return array uuid, token
     */
    public function getSession()
    {
        return array(
            'uuid' => $this->uuid,
            'token' => $this->token,
        );
    }

    /**
     * @param string $loginName
     * @param string $loginPwd
     * @return void
     * @throws ILanZouException
     */
    public function login($loginName = null, $loginPwd = null)
    {
        $loginName = $loginName !== null ? $loginName : (string) $this->configs[ILanZouOptions::USERNAME];
        $loginPwd = $loginPwd !== null ? $loginPwd : (string) $this->configs[ILanZouOptions::PASSWORD];

        $res = $this->unproved('/login', 'POST', array(
            'loginName' => $loginName,
            'loginPwd' => $loginPwd,
        ));

        $data = isset($res['data']) && is_array($res['data']) ? $res['data'] : array();
        $this->token = isset($data['appToken']) ? (string) $data['appToken'] : '';
        if ($this->token === '') {
            throw new ILanZouException('登录失败：未返回 appToken');
        }
    }

    /**
     * @param string $fileId
     * @return string
     * @throws ILanZouException
     */
    public function getDownloadUrl($fileId)
    {
        $this->init();

        $ts = (int) round(microtime(true) * 1000);
        $tsHex = ILanZouCrypto::aesEncryptHex((string) $ts);

        $downloadId = ILanZouCrypto::aesEncryptHex($fileId . '|' . $this->userId);
        $auth = ILanZouCrypto::aesEncryptHex($fileId . '|' . $ts);

        $query = http_build_query(array(
            'uuid' => $this->uuid,
            'devType' => '6',
            'devCode' => $this->uuid,
            'devModel' => 'chrome',
            'devVersion' => self::DEV_VERSION,
            'appVersion' => '',
            'timestamp' => $tsHex,
            'appToken' => $this->token,
            'enable' => '1',
            'downloadId' => $downloadId,
            'auth' => $auth,
        ), '', '&', PHP_QUERY_RFC3986);

        $url = self::API_BASE . '/unproved/file/redirect?' . $query;
        $headers = $this->defaultHeaders();

        $response = $this->curlRequest('GET', $url, null, $headers, false, false);
        $location = isset($response['headers']['location']) ? $response['headers']['location'] : '';

        if ($location !== '' && in_array($response['status'], array(301, 302, 303, 307, 308), true)) {
            return $location;
        }

        $body = isset($response['body']) ? $response['body'] : '';
        $json = json_decode($body, true);
        $msg = is_array($json) && isset($json['msg']) ? (string) $json['msg'] : $body;

        throw new ILanZouException('获取下载链接失败：' . $msg);
    }

    /**
     * @param string $fileId
     * @return void
     * @throws ILanZouException
     */
    public function deleteFile($fileId)
    {
        $this->init();
        $this->proved('/file/delete', 'POST', array(
            'folderIds' => '',
            'fileIds' => (string) $fileId,
            'status' => 0,
        ));
    }

    /**
     * @param string     $filename
     * @param string|resource $content
     * @param int|null   $size
     * @param string|null $folderId
     * @return array file_id, file_name
     * @throws ILanZouException
     */
    public function upload($filename, $content, $size = null, $folderId = null)
    {
        $this->init();

        $tmpFile = null;
        if (is_resource($content)) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'ilz_up_');
            $out = fopen($tmpFile, 'wb');
            stream_copy_to_stream($content, $out);
            fclose($out);
            $size = filesize($tmpFile);
            $md5 = md5_file($tmpFile);
            $payload = $tmpFile;
            $payloadIsPath = true;
        } else {
            $payload = (string) $content;
            $size = $size !== null ? (int) $size : strlen($payload);
            $md5 = md5($payload);
            $payloadIsPath = false;
        }

        if ($folderId === null) {
            $folderId = isset($this->configs[ILanZouOptions::FOLDER_ID])
                ? (string) $this->configs[ILanZouOptions::FOLDER_ID]
                : '0';
        }

        $res = $this->proved('/7n/getUpToken', 'POST', array(
            'fileId' => '',
            'fileName' => $filename,
            'fileSize' => (int) ($size / 1024) + 1,
            'folderId' => $folderId,
            'md5' => $md5,
            'type' => 1,
        ));

        $upToken = isset($res['upToken']) ? (string) $res['upToken'] : '';

        if ($upToken === '-1') {
            $map = isset($res['map']) && is_array($res['map']) ? $res['map'] : array();
            return array(
                'file_id' => isset($map['fileId']) ? (string) $map['fileId'] : '',
                'file_name' => isset($map['fileName']) ? (string) $map['fileName'] : $filename,
            );
        }

        if ($upToken === '') {
            throw new ILanZouException('获取上传凭证失败');
        }

        $now = time();
        $key = sprintf(
            'disk/%d/%d/%d/%s/%016d',
            (int) date('Y', $now),
            (int) date('n', $now),
            (int) date('j', $now),
            $this->account,
            (int) round(microtime(true) * 1000)
        );

        try {
            if ($size <= self::PART_SIZE) {
                $token = $this->uploadSmall($upToken, $key, $filename, $payload, $payloadIsPath);
            } else {
                $token = $this->uploadMultipart($upToken, $key, $filename, $payload, $size, $payloadIsPath);
            }

            $file = $this->waitUploadResult($token);

            return array(
                'file_id' => isset($file['fileId']) ? (string) $file['fileId'] : '',
                'file_name' => isset($file['fileName']) ? (string) $file['fileName'] : $filename,
            );
        } finally {
            if ($tmpFile !== null && is_file($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * @param string $path
     * @param string $method
     * @param array|null $body
     * @return array
     * @throws ILanZouException
     */
    private function unproved($path, $method, $body = null)
    {
        return $this->request('/unproved' . $path, $method, $body, false);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array|null $body
     * @return array
     * @throws ILanZouException
     */
    private function proved($path, $method, $body = null)
    {
        return $this->request('/proved' . $path, $method, $body, true);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array|null $body
     * @param bool $proved
     * @param bool $retry
     * @return array
     * @throws ILanZouException
     */
    private function request($path, $method, $body, $proved, $retry = false)
    {
        $params = array(
            'uuid' => $this->uuid,
            'devType' => '6',
            'devCode' => $this->uuid,
            'devModel' => 'chrome',
            'devVersion' => self::DEV_VERSION,
            'appVersion' => '',
            'timestamp' => ILanZouCrypto::encryptTimestamp(),
            'extra' => '2',
        );

        if ($proved) {
            $params['appToken'] = $this->token;
        }

        $url = self::API_BASE . $path . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $headers = $this->defaultHeaders();

        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $response = $this->curlRequest($method, $url, json_encode($body, JSON_UNESCAPED_UNICODE), $headers);
        } else {
            $response = $this->curlRequest($method, $url, null, $headers);
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json)) {
            throw new ILanZouException('API 响应无效：' . substr($response['body'], 0, 200));
        }

        $code = isset($json['code']) ? (int) $json['code'] : 0;
        $msg = isset($json['msg']) ? (string) $json['msg'] : '未知错误';

        if ($code !== 200) {
            if (!$retry && $proved && ($code === -1 || $code === -2 || $this->token === '')) {
                $this->login();
                return $this->request($path, $method, $body, $proved, true);
            }
            throw new ILanZouException($code . ': ' . $msg);
        }

        return $json;
    }

    /**
     * @param string $upToken
     * @param string $key
     * @param string $filename
     * @param string|resource $content
     * @return string
     * @throws ILanZouException
     */
    private function uploadSmall($upToken, $key, $filename, $payload, $payloadIsPath)
    {
        if ($payloadIsPath) {
            $post = array(
                'token' => $upToken,
                'key' => $key,
                'fname' => $filename,
                'file' => new CURLFile($payload, 'application/octet-stream', $filename),
            );
            $response = $this->curlRequest('POST', 'https://upload.qiniup.com/', $post, array(), true);
        } else {
            $boundary = '----ILanZou' . bin2hex(random_bytes(8));
            $body = $this->buildMultipartBody($boundary, array(
                'token' => $upToken,
                'key' => $key,
                'fname' => $filename,
            ), 'file', $filename, $payload);

            $response = $this->curlRequest('POST', 'https://upload.qiniup.com/', $body, array(
                'Content-Type: multipart/form-data; boundary=' . $boundary,
            ));
        }

        $json = json_decode($response['body'], true);
        $token = is_array($json) && isset($json['token']) ? (string) $json['token'] : '';
        if ($token === '') {
            throw new ILanZouException('七牛上传失败：' . $response['body']);
        }

        return $token;
    }

    /**
     * @param string $upToken
     * @param string $key
     * @param string $filename
     * @param string|resource $content
     * @param int $size
     * @return string
     * @throws ILanZouException
     */
    private function uploadMultipart($upToken, $key, $filename, $payload, $size, $payloadIsPath)
    {
        $keyBase64 = rtrim(strtr(base64_encode($key), '+/', '-_'), '=');
        $initUrl = sprintf(
            'https://upload.qiniup.com/buckets/%s/objects/%s/uploads',
            self::QINIU_BUCKET,
            rawurlencode($keyBase64)
        );

        $response = $this->curlRequest('POST', $initUrl, '', array(
            'Authorization: UpToken ' . $upToken,
        ));
        $initJson = json_decode($response['body'], true);
        $uploadId = is_array($initJson) && isset($initJson['uploadId']) ? (string) $initJson['uploadId'] : '';
        if ($uploadId === '') {
            throw new ILanZouException('初始化分片上传失败：' . $response['body']);
        }

        $partCount = (int) ceil($size / self::PART_SIZE);
        $parts = array();
        $handle = $payloadIsPath ? fopen($payload, 'rb') : null;

        for ($i = 1; $i <= $partCount; $i++) {
            if ($payloadIsPath && $handle) {
                $chunk = fread($handle, self::PART_SIZE);
            } else {
                $chunk = substr($payload, ($i - 1) * self::PART_SIZE, self::PART_SIZE);
            }

            $partUrl = sprintf(
                'https://upload.qiniup.com/buckets/%s/objects/%s/uploads/%s/%d',
                self::QINIU_BUCKET,
                rawurlencode($keyBase64),
                $uploadId,
                $i
            );

            $partResp = $this->curlRequest('PUT', $partUrl, $chunk, array(
                'Authorization: UpToken ' . $upToken,
                'Content-Type: application/octet-stream',
            ));

            $partJson = json_decode($partResp['body'], true);
            $etag = is_array($partJson) && isset($partJson['etag']) ? (string) $partJson['etag'] : '';
            if ($etag === '') {
                throw new ILanZouException('分片上传失败：part ' . $i);
            }

            $parts[] = array(
                'partNumber' => $i,
                'etag' => $etag,
            );
        }

        if ($handle) {
            fclose($handle);
        }

        $completeUrl = sprintf(
            'https://upload.qiniup.com/buckets/%s/objects/%s/uploads/%s',
            self::QINIU_BUCKET,
            rawurlencode($keyBase64),
            $uploadId
        );

        $completeBody = json_encode(array(
            'fnmae' => $filename,
            'parts' => $parts,
        ), JSON_UNESCAPED_UNICODE);

        $completeResp = $this->curlRequest('POST', $completeUrl, $completeBody, array(
            'Authorization: UpToken ' . $upToken,
            'Content-Type: application/json',
        ));

        $completeJson = json_decode($completeResp['body'], true);
        $token = is_array($completeJson) && isset($completeJson['token']) ? (string) $completeJson['token'] : '';
        if ($token === '') {
            throw new ILanZouException('完成分片上传失败：' . $completeResp['body']);
        }

        return $token;
    }

    /**
     * @param string $token
     * @return array
     * @throws ILanZouException
     */
    private function waitUploadResult($token)
    {
        $tokenTime = gmdate('D M d Y H:i:s') . ' GMT-0700 (MST)';

        for ($i = 0; $i < 10; $i++) {
            $params = http_build_query(array(
                'tokenList' => $token,
                'tokenTime' => $tokenTime,
            ), '', '&', PHP_QUERY_RFC3986);

            $res = $this->unproved('/7n/results?' . $params, 'POST');
            $list = isset($res['list']) && is_array($res['list']) ? $res['list'] : array();

            if (count($list) === 0) {
                throw new ILanZouException('上传确认失败：空响应');
            }

            $item = $list[0];
            $status = isset($item['status']) ? (int) $item['status'] : 0;
            if ($status === 1) {
                return $item;
            }

            usleep(1000000);
        }

        throw new ILanZouException('上传确认超时');
    }

    /**
     * @return array
     */
    private function defaultHeaders()
    {
        $headers = array(
            'Origin: ' . self::SITE,
            'Referer: ' . self::SITE . '/',
            'Accept-Encoding: gzip',
            'Accept-Language: zh-CN,zh;q=0.9,en-US;q=0.8',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        );

        if (!empty($this->configs[ILanZouOptions::IP])) {
            $headers[] = 'X-Forwarded-For: ' . $this->configs[ILanZouOptions::IP];
        }

        return $headers;
    }

    /**
     * @param string $boundary
     * @param array $fields
     * @param string $fileField
     * @param string $filename
     * @param string $fileContent
     * @return string
     */
    private function buildMultipartBody($boundary, $fields, $fileField, $filename, $fileContent)
    {
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n";
            $body .= $value . "\r\n";
        }

        $body .= '--' . $boundary . "\r\n";
        $body .= 'Content-Disposition: form-data; name="' . $fileField . '"; filename="' . $filename . "\"\r\n";
        $body .= "Content-Type: application/octet-stream\r\n\r\n";
        $body .= $fileContent . "\r\n";
        $body .= '--' . $boundary . "--\r\n";

        return $body;
    }

    /**
     * @param string $method
     * @param string $url
     * @param string|array|null $body
     * @param array $headers
     * @param bool $isMultipart
     * @param bool $followRedirect
     * @return array status, body, headers
     * @throws ILanZouException
     */
    private function curlRequest($method, $url, $body, $headers = array(), $isMultipart = false, $followRedirect = true)
    {
        if (!function_exists('curl_init')) {
            throw new ILanZouException('PHP curl 扩展未安装');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirect);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($body !== null) {
            if ($isMultipart && is_array($body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new ILanZouException('HTTP 请求失败：' . $err);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerRaw = substr($raw, 0, $headerSize);
        $responseBody = substr($raw, $headerSize);
        $parsedHeaders = $this->parseHeaders($headerRaw);

        return array(
            'status' => $status,
            'body' => $responseBody,
            'headers' => $parsedHeaders,
        );
    }

    /**
     * @param string $headerRaw
     * @return array
     */
    private function parseHeaders($headerRaw)
    {
        $headers = array();
        foreach (explode("\r\n", $headerRaw) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }
        return $headers;
    }
}
