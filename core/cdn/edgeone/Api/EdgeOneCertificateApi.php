<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneCertificateApi.php
 * 作用：EdgeOne 证书
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneCertificateApi extends EdgeOneApiBase
{
    /**
     * DescribeDefaultCertificates — 查询默认证书列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDefaultCertificates(array $params = array())
    {
        return $this->invoke('DescribeDefaultCertificates', $params);
    }

    /**
     * ModifyHostsCertificate — 配置域名证书
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyHostsCertificate(array $params = array())
    {
        return $this->invoke('ModifyHostsCertificate', $params);
    }

    /**
     * ApplyFreeCertificate — 申请免费证书
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function applyFreeCertificate(array $params = array())
    {
        return $this->invoke('ApplyFreeCertificate', $params);
    }

    /**
     * CheckFreeCertificateVerification — 检查免费证书申请结果
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function checkFreeCertificateVerification(array $params = array())
    {
        return $this->invoke('CheckFreeCertificateVerification', $params);
    }
}
