<?php
/**
 * 文件：core/AjaxResponse.php
 * 作用：后台/安装 AJAX JSON 响应
 * @version 1.0.10
 */

class AjaxResponse
{
    /**
     * @param array $data
     * @param int   $httpCode
     * @return void
     */
    public static function json(array $data, $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param string $msg
     * @param array  $extra
     * @return void
     */
    public static function success($msg, array $extra = array())
    {
        self::json(array_merge(array('code' => 1, 'msg' => $msg), $extra));
    }

    /**
     * @param string $msg
     * @param int    $httpCode
     * @return void
     */
    public static function error($msg, $httpCode = 200)
    {
        self::json(array('code' => 0, 'msg' => $msg), $httpCode);
    }
}
