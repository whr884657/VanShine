<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneDiagnosisApi.php
 * 作用：EdgeOne 诊断工具
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneDiagnosisApi extends EdgeOneApiBase
{
    /**
     * DescribeIPRegion — 查询 IP 归属信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeIPRegion(array $params = array())
    {
        return $this->invoke('DescribeIPRegion', $params);
    }
}
