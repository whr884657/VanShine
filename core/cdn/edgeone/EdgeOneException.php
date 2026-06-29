<?php
/**
 * 文件：core/cdn/edgeone/EdgeOneException.php
 * 作用：EdgeOne API 调用异常
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneException extends Exception
{
    /** @var string */
    private $errorCode = '';

    /** @var string */
    private $requestId = '';

    /**
     * @param string          $message
     * @param string          $errorCode
     * @param string          $requestId
     * @param int             $code
     * @param Exception|null  $previous
     */
    public function __construct($message, $errorCode = '', $requestId = '', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = (string) $errorCode;
        $this->requestId = (string) $requestId;
    }

    /**
     * @param Exception $e
     * @return self
     */
    public static function fromThrowable(Exception $e)
    {
        $errorCode = '';
        $requestId = '';

        if ($e instanceof TencentCloud\Common\Exception\TencentCloudSDKException) {
            $errorCode = (string) $e->getErrorCode();
            $requestId = (string) $e->getRequestId();
        }

        return new self($e->getMessage(), $errorCode, $requestId, (int) $e->getCode(), $e);
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }
}
