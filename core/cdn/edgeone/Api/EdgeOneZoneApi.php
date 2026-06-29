<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneZoneApi.php
 * 作用：EdgeOne 站点相关
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneZoneApi extends EdgeOneApiBase
{
    /**
     * CreateZone — 创建站点
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createZone(array $params = array())
    {
        return $this->invoke('CreateZone', $params);
    }

    /**
     * DescribeIdentifications — 查询站点的验证信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeIdentifications(array $params = array())
    {
        return $this->invoke('DescribeIdentifications', $params);
    }

    /**
     * ModifyZone — 修改站点
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyZone(array $params = array())
    {
        return $this->invoke('ModifyZone', $params);
    }

    /**
     * DeleteZone — 删除站点
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteZone(array $params = array())
    {
        return $this->invoke('DeleteZone', $params);
    }

    /**
     * ModifyZoneStatus — 切换站点状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyZoneStatus(array $params = array())
    {
        return $this->invoke('ModifyZoneStatus', $params);
    }

    /**
     * CheckCnameStatus — 校验域名 CNAME 配置状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function checkCnameStatus(array $params = array())
    {
        return $this->invoke('CheckCnameStatus', $params);
    }

    /**
     * IdentifyZone — 认证站点
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function identifyZone(array $params = array())
    {
        return $this->invoke('IdentifyZone', $params);
    }

    /**
     * DescribeZones — 查询站点列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeZones(array $params = array())
    {
        return $this->invoke('DescribeZones', $params);
    }

    /**
     * ExportZoneConfig — 导出站点配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function exportZoneConfig(array $params = array())
    {
        return $this->invoke('ExportZoneConfig', $params);
    }

    /**
     * ImportZoneConfig — 导入站点配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function importZoneConfig(array $params = array())
    {
        return $this->invoke('ImportZoneConfig', $params);
    }

    /**
     * DescribeZoneConfigImportResult — 查询站点配置导入结果
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeZoneConfigImportResult(array $params = array())
    {
        return $this->invoke('DescribeZoneConfigImportResult', $params);
    }
}
