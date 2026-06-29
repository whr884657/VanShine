<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneAccelerationDomainApi.php
 * 作用：EdgeOne 加速域名管理
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneAccelerationDomainApi extends EdgeOneApiBase
{
    /**
     * CreateAccelerationDomain — 创建加速域名
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createAccelerationDomain(array $params = array())
    {
        return $this->invoke('CreateAccelerationDomain', $params);
    }

    /**
     * DescribeAccelerationDomains — 查询加速域名列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeAccelerationDomains(array $params = array())
    {
        return $this->invoke('DescribeAccelerationDomains', $params);
    }

    /**
     * ModifyAccelerationDomain — 修改加速域名信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyAccelerationDomain(array $params = array())
    {
        return $this->invoke('ModifyAccelerationDomain', $params);
    }

    /**
     * ModifyAccelerationDomainStatuses — 批量修改加速域名状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyAccelerationDomainStatuses(array $params = array())
    {
        return $this->invoke('ModifyAccelerationDomainStatuses', $params);
    }

    /**
     * DeleteAccelerationDomains — 批量删除加速域名
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteAccelerationDomains(array $params = array())
    {
        return $this->invoke('DeleteAccelerationDomains', $params);
    }

    /**
     * CreateSharedCNAME — 创建共享 CNAME
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSharedCNAME(array $params = array())
    {
        return $this->invoke('CreateSharedCNAME', $params);
    }

    /**
     * DescribeSharedCNAME — 查询共享 CNAME 列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSharedCNAME(array $params = array())
    {
        return $this->invoke('DescribeSharedCNAME', $params);
    }

    /**
     * ModifySharedCNAME — 修改共享 CNAME
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySharedCNAME(array $params = array())
    {
        return $this->invoke('ModifySharedCNAME', $params);
    }

    /**
     * BindSharedCNAME — 绑定共享 CNAME
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function bindSharedCNAME(array $params = array())
    {
        return $this->invoke('BindSharedCNAME', $params);
    }

    /**
     * DeleteSharedCNAME — 删除共享 CNAME
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSharedCNAME(array $params = array())
    {
        return $this->invoke('DeleteSharedCNAME', $params);
    }
}
