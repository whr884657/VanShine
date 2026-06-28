<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneOwnershipApi.php
 * 作用：EdgeOne 归属权
 * @version 1.0.0
 * @generated build-apis.php
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
