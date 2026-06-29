<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneSecurityResourceApi.php
 * 作用：EdgeOne API 防护
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneSecurityResourceApi extends EdgeOneApiBase
{
    /**
     * CreateSecurityAPIResource — 创建 API 资源
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSecurityAPIResource(array $params = array())
    {
        return $this->invoke('CreateSecurityAPIResource', $params);
    }

    /**
     * DescribeSecurityAPIResource — 查询 API 资源
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityAPIResource(array $params = array())
    {
        return $this->invoke('DescribeSecurityAPIResource', $params);
    }

    /**
     * ModifySecurityAPIResource — 修改 API 资源
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityAPIResource(array $params = array())
    {
        return $this->invoke('ModifySecurityAPIResource', $params);
    }

    /**
     * DeleteSecurityAPIResource — 删除 API 资源
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSecurityAPIResource(array $params = array())
    {
        return $this->invoke('DeleteSecurityAPIResource', $params);
    }

    /**
     * CreateSecurityAPIService — 创建 API 服务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSecurityAPIService(array $params = array())
    {
        return $this->invoke('CreateSecurityAPIService', $params);
    }

    /**
     * DescribeSecurityAPIService — 查询 API 服务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityAPIService(array $params = array())
    {
        return $this->invoke('DescribeSecurityAPIService', $params);
    }

    /**
     * ModifySecurityAPIService — 修改 API 服务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityAPIService(array $params = array())
    {
        return $this->invoke('ModifySecurityAPIService', $params);
    }

    /**
     * DeleteSecurityAPIService — 删除 API 服务
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSecurityAPIService(array $params = array())
    {
        return $this->invoke('DeleteSecurityAPIService', $params);
    }
}
