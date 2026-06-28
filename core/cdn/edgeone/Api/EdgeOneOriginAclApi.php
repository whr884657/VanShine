<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneOriginAclApi.php
 * 作用：EdgeOne 源站防护
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneOriginAclApi extends EdgeOneApiBase
{
    /**
     * EnableOriginACL — 开启源站防护
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function enableOriginACL(array $params = array())
    {
        return $this->invoke('EnableOriginACL', $params);
    }

    /**
     * ModifyOriginACL — 变更源站防护实例
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyOriginACL(array $params = array())
    {
        return $this->invoke('ModifyOriginACL', $params);
    }

    /**
     * DescribeOriginACL — 查询源站防护详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeOriginACL(array $params = array())
    {
        return $this->invoke('DescribeOriginACL', $params);
    }

    /**
     * ConfirmOriginACLUpdate — 确认回源 IP 网段更新
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function confirmOriginACLUpdate(array $params = array())
    {
        return $this->invoke('ConfirmOriginACLUpdate', $params);
    }

    /**
     * DisableOriginACL — 关闭源站防护
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function disableOriginACL(array $params = array())
    {
        return $this->invoke('DisableOriginACL', $params);
    }
}
