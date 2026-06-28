<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneApiBase.php
 * 作用：EdgeOne 分类 API 基类
 * @version 1.0.0
 */

abstract class EdgeOneApiBase
{
    /** @var EdgeOneClient */
    protected $client;

    /**
     * @param EdgeOneClient $client
     */
    public function __construct(EdgeOneClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $action
     * @param array  $params
     * @return array
     * @throws EdgeOneException
     */
    protected function invoke($action, array $params = array())
    {
        return $this->client->call($action, $params);
    }
}
