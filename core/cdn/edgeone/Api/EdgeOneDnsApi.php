<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneDnsApi.php
 * 作用：EdgeOne DNS 记录
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneDnsApi extends EdgeOneApiBase
{
    /**
     * CreateDnsRecord — 创建 DNS 记录
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createDnsRecord(array $params = array())
    {
        return $this->invoke('CreateDnsRecord', $params);
    }

    /**
     * DescribeDnsRecords — 查询 DNS 记录列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDnsRecords(array $params = array())
    {
        return $this->invoke('DescribeDnsRecords', $params);
    }

    /**
     * ModifyDnsRecords — 批量修改 DNS 记录
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyDnsRecords(array $params = array())
    {
        return $this->invoke('ModifyDnsRecords', $params);
    }

    /**
     * ModifyDnsRecordsStatus — 批量修改 DNS 记录状态
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyDnsRecordsStatus(array $params = array())
    {
        return $this->invoke('ModifyDnsRecordsStatus', $params);
    }

    /**
     * DeleteDnsRecords — 批量删除 DNS 记录
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteDnsRecords(array $params = array())
    {
        return $this->invoke('DeleteDnsRecords', $params);
    }
}
