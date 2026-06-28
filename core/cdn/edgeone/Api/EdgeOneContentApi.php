<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneContentApi.php
 * 作用：EdgeOne 内容管理
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneContentApi extends EdgeOneApiBase
{
    /**
     * CreatePurgeTask — 创建清除缓存任务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createPurgeTask(array $params = array())
    {
        return $this->invoke('CreatePurgeTask', $params);
    }

    /**
     * DescribePurgeTasks — 查询清除缓存历史记录
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describePurgeTasks(array $params = array())
    {
        return $this->invoke('DescribePurgeTasks', $params);
    }

    /**
     * CreatePrefetchTask — 创建预热任务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createPrefetchTask(array $params = array())
    {
        return $this->invoke('CreatePrefetchTask', $params);
    }

    /**
     * DescribePrefetchTasks — 查询预热任务状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describePrefetchTasks(array $params = array())
    {
        return $this->invoke('DescribePrefetchTasks', $params);
    }

    /**
     * DescribeContentQuota — 查询内容管理接口配额
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeContentQuota(array $params = array())
    {
        return $this->invoke('DescribeContentQuota', $params);
    }

    /**
     * DescribePrefetchOriginLimit — 查询预热回源限速限制
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describePrefetchOriginLimit(array $params = array())
    {
        return $this->invoke('DescribePrefetchOriginLimit', $params);
    }

    /**
     * ModifyPrefetchOriginLimit — 配置预热回源限速限制
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyPrefetchOriginLimit(array $params = array())
    {
        return $this->invoke('ModifyPrefetchOriginLimit', $params);
    }
}
