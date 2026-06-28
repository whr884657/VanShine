<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneLoadBalancerApi.php
 * 作用：EdgeOne 负载均衡
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneLoadBalancerApi extends EdgeOneApiBase
{
    /**
     * CreateOriginGroup — 创建源站组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createOriginGroup(array $params = array())
    {
        return $this->invoke('CreateOriginGroup', $params);
    }

    /**
     * ModifyOriginGroup — 修改源站组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyOriginGroup(array $params = array())
    {
        return $this->invoke('ModifyOriginGroup', $params);
    }

    /**
     * DeleteOriginGroup — 删除源站组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteOriginGroup(array $params = array())
    {
        return $this->invoke('DeleteOriginGroup', $params);
    }

    /**
     * DescribeOriginGroup — 获取源站组列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeOriginGroup(array $params = array())
    {
        return $this->invoke('DescribeOriginGroup', $params);
    }

    /**
     * CreateLoadBalancer — 创建负载均衡实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createLoadBalancer(array $params = array())
    {
        return $this->invoke('CreateLoadBalancer', $params);
    }

    /**
     * ModifyLoadBalancer — 修改负载均衡实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyLoadBalancer(array $params = array())
    {
        return $this->invoke('ModifyLoadBalancer', $params);
    }

    /**
     * DeleteLoadBalancer — 删除负载均衡实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteLoadBalancer(array $params = array())
    {
        return $this->invoke('DeleteLoadBalancer', $params);
    }

    /**
     * DescribeLoadBalancerList — 查询负载均衡实例列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeLoadBalancerList(array $params = array())
    {
        return $this->invoke('DescribeLoadBalancerList', $params);
    }

    /**
     * DescribeOriginGroupHealthStatus — 查询负载均衡实例下源站组健康状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeOriginGroupHealthStatus(array $params = array())
    {
        return $this->invoke('DescribeOriginGroupHealthStatus', $params);
    }
}
