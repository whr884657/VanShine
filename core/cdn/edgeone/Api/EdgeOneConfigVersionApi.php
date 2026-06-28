<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneConfigVersionApi.php
 * 作用：EdgeOne 版本管理
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneConfigVersionApi extends EdgeOneApiBase
{
    /**
     * CreateConfigGroupVersion — 创建配置组版本
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createConfigGroupVersion(array $params = array())
    {
        return $this->invoke('CreateConfigGroupVersion', $params);
    }

    /**
     * DeployConfigGroupVersion — 发布配置组版本
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deployConfigGroupVersion(array $params = array())
    {
        return $this->invoke('DeployConfigGroupVersion', $params);
    }

    /**
     * DescribeConfigGroupVersionDetail — 查询配置组版本详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeConfigGroupVersionDetail(array $params = array())
    {
        return $this->invoke('DescribeConfigGroupVersionDetail', $params);
    }

    /**
     * DescribeConfigGroupVersions — 查询配置组版本列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeConfigGroupVersions(array $params = array())
    {
        return $this->invoke('DescribeConfigGroupVersions', $params);
    }

    /**
     * DescribeDeployHistory — 查询版本发布历史
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDeployHistory(array $params = array())
    {
        return $this->invoke('DescribeDeployHistory', $params);
    }

    /**
     * DescribeEnvironments — 查询环境信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeEnvironments(array $params = array())
    {
        return $this->invoke('DescribeEnvironments', $params);
    }

    /**
     * ModifyZoneWorkMode — 修改站点工作模式
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyZoneWorkMode(array $params = array())
    {
        return $this->invoke('ModifyZoneWorkMode', $params);
    }
}
