<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneMultiPathGatewayApi.php
 * 作用：EdgeOne 多通道安全加速网关
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneMultiPathGatewayApi extends EdgeOneApiBase
{
    /**
     * ConfirmMultiPathGatewayOriginACL — 确认多通道安全加速网关回源 IP 网段更新
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function confirmMultiPathGatewayOriginACL(array $params = array())
    {
        return $this->invoke('ConfirmMultiPathGatewayOriginACL', $params);
    }

    /**
     * DescribeMultiPathGatewayOriginACL — 查询多通道安全加速网关源站防护详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGatewayOriginACL(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGatewayOriginACL', $params);
    }

    /**
     * CreateMultiPathGateway — 创建多通道安全加速网关
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createMultiPathGateway(array $params = array())
    {
        return $this->invoke('CreateMultiPathGateway', $params);
    }

    /**
     * DescribeMultiPathGateways — 查询多通道安全加速网关列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGateways(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGateways', $params);
    }

    /**
     * DescribeMultiPathGateway — 查询多通道安全加速网关详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGateway(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGateway', $params);
    }

    /**
     * ModifyMultiPathGateway — 修改多通道安全加速网关信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyMultiPathGateway(array $params = array())
    {
        return $this->invoke('ModifyMultiPathGateway', $params);
    }

    /**
     * ModifyMultiPathGatewayStatus — 修改多通道安全加速网关状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyMultiPathGatewayStatus(array $params = array())
    {
        return $this->invoke('ModifyMultiPathGatewayStatus', $params);
    }

    /**
     * DeleteMultiPathGateway — 删除多通道安全加速网关
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteMultiPathGateway(array $params = array())
    {
        return $this->invoke('DeleteMultiPathGateway', $params);
    }

    /**
     * DescribeMultiPathGatewayRegions — 查询多通道安全加速网关可用地域列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGatewayRegions(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGatewayRegions', $params);
    }

    /**
     * CreateMultiPathGatewaySecretKey — 创建多通道安全加速网关密钥
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createMultiPathGatewaySecretKey(array $params = array())
    {
        return $this->invoke('CreateMultiPathGatewaySecretKey', $params);
    }

    /**
     * DescribeMultiPathGatewaySecretKey — 查询多通道安全加速网关接入密钥
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGatewaySecretKey(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGatewaySecretKey', $params);
    }

    /**
     * ModifyMultiPathGatewaySecretKey — 修改多通道安全加速网关接入密钥
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyMultiPathGatewaySecretKey(array $params = array())
    {
        return $this->invoke('ModifyMultiPathGatewaySecretKey', $params);
    }

    /**
     * RefreshMultiPathGatewaySecretKey — 刷新多通道安全加速网关密钥
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function refreshMultiPathGatewaySecretKey(array $params = array())
    {
        return $this->invoke('RefreshMultiPathGatewaySecretKey', $params);
    }

    /**
     * CreateMultiPathGatewayLine — 创建多通道安全加速网关线路
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createMultiPathGatewayLine(array $params = array())
    {
        return $this->invoke('CreateMultiPathGatewayLine', $params);
    }

    /**
     * DescribeMultiPathGatewayLine — 查询多通道安全加速网关线路详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeMultiPathGatewayLine(array $params = array())
    {
        return $this->invoke('DescribeMultiPathGatewayLine', $params);
    }

    /**
     * ModifyMultiPathGatewayLine — 修改多通道安全加速网关线路信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyMultiPathGatewayLine(array $params = array())
    {
        return $this->invoke('ModifyMultiPathGatewayLine', $params);
    }

    /**
     * DeleteMultiPathGatewayLine — 删除多通道安全加速网关线路
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteMultiPathGatewayLine(array $params = array())
    {
        return $this->invoke('DeleteMultiPathGatewayLine', $params);
    }
}
