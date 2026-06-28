<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneAliasDomainApi.php
 * 作用：EdgeOne 别称域名
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneAliasDomainApi extends EdgeOneApiBase
{
    /**
     * CreateAliasDomain — 创建别称域名
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createAliasDomain(array $params = array())
    {
        return $this->invoke('CreateAliasDomain', $params);
    }

    /**
     * DescribeAliasDomains — 查询别称域名信息列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeAliasDomains(array $params = array())
    {
        return $this->invoke('DescribeAliasDomains', $params);
    }

    /**
     * ModifyAliasDomain — 修改别称域名
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyAliasDomain(array $params = array())
    {
        return $this->invoke('ModifyAliasDomain', $params);
    }

    /**
     * ModifyAliasDomainStatus — 修改别称域名状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyAliasDomainStatus(array $params = array())
    {
        return $this->invoke('ModifyAliasDomainStatus', $params);
    }

    /**
     * DeleteAliasDomain — 删除别称域名
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteAliasDomain(array $params = array())
    {
        return $this->invoke('DeleteAliasDomain', $params);
    }
}
