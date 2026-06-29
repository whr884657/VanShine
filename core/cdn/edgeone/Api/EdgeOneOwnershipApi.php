<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneOwnershipApi.php
 * 作用：EdgeOne 归属权
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneOwnershipApi extends EdgeOneApiBase
{
    /**
     * VerifyOwnership — 验证归属权
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function verifyOwnership(array $params = array())
    {
        return $this->invoke('VerifyOwnership', $params);
    }
}
