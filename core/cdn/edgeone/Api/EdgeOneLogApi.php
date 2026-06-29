<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneLogApi.php
 * 作用：EdgeOne 日志服务
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneLogApi extends EdgeOneApiBase
{
    /**
     * DownloadL7Logs — 下载七层离线日志
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function downloadL7Logs(array $params = array())
    {
        return $this->invoke('DownloadL7Logs', $params);
    }

    /**
     * DownloadL4Logs — 下载四层离线日志
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function downloadL4Logs(array $params = array())
    {
        return $this->invoke('DownloadL4Logs', $params);
    }

    /**
     * CreateCLSIndex — 创建 CLS 索引
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createCLSIndex(array $params = array())
    {
        return $this->invoke('CreateCLSIndex', $params);
    }

    /**
     * CreateRealtimeLogDeliveryTask — 创建实时日志投递任务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createRealtimeLogDeliveryTask(array $params = array())
    {
        return $this->invoke('CreateRealtimeLogDeliveryTask', $params);
    }

    /**
     * ModifyRealtimeLogDeliveryTask — 修改实时日志投递任务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyRealtimeLogDeliveryTask(array $params = array())
    {
        return $this->invoke('ModifyRealtimeLogDeliveryTask', $params);
    }

    /**
     * DeleteRealtimeLogDeliveryTask — 删除实时日志投递任务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteRealtimeLogDeliveryTask(array $params = array())
    {
        return $this->invoke('DeleteRealtimeLogDeliveryTask', $params);
    }

    /**
     * DescribeRealtimeLogDeliveryTasks — 查询实时日志投递任务列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeRealtimeLogDeliveryTasks(array $params = array())
    {
        return $this->invoke('DescribeRealtimeLogDeliveryTasks', $params);
    }
}
