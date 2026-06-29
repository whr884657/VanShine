<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneL4ProxyApi.php
 * 作用：EdgeOne 四层应用代理
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneL4ProxyApi extends EdgeOneApiBase
{
    /**
     * CreateL4Proxy — 创建四层代理实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createL4Proxy(array $params = array())
    {
        return $this->invoke('CreateL4Proxy', $params);
    }

    /**
     * ModifyL4Proxy — 修改四层代理实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL4Proxy(array $params = array())
    {
        return $this->invoke('ModifyL4Proxy', $params);
    }

    /**
     * ModifyL4ProxyStatus — 修改四层代理实例状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL4ProxyStatus(array $params = array())
    {
        return $this->invoke('ModifyL4ProxyStatus', $params);
    }

    /**
     * DescribeL4Proxy — 查询四层代理实例列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeL4Proxy(array $params = array())
    {
        return $this->invoke('DescribeL4Proxy', $params);
    }

    /**
     * DeleteL4Proxy — 删除四层代理实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteL4Proxy(array $params = array())
    {
        return $this->invoke('DeleteL4Proxy', $params);
    }

    /**
     * CreateL4ProxyRules — 创建四层代理转发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createL4ProxyRules(array $params = array())
    {
        return $this->invoke('CreateL4ProxyRules', $params);
    }

    /**
     * ModifyL4ProxyRules — 修改四层代理转发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL4ProxyRules(array $params = array())
    {
        return $this->invoke('ModifyL4ProxyRules', $params);
    }

    /**
     * ModifyL4ProxyRulesStatus — 修改四层代理转发规则状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL4ProxyRulesStatus(array $params = array())
    {
        return $this->invoke('ModifyL4ProxyRulesStatus', $params);
    }

    /**
     * DescribeL4ProxyRules — 查询四层代理转发规则列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeL4ProxyRules(array $params = array())
    {
        return $this->invoke('DescribeL4ProxyRules', $params);
    }

    /**
     * DeleteL4ProxyRules — 删除四层代理转发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteL4ProxyRules(array $params = array())
    {
        return $this->invoke('DeleteL4ProxyRules', $params);
    }
}
