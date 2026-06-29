<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneContentIdentifierApi.php
 * 作用：EdgeOne 内容标识符
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneContentIdentifierApi extends EdgeOneApiBase
{
    /**
     * CreateContentIdentifier — 创建内容标识符
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createContentIdentifier(array $params = array())
    {
        return $this->invoke('CreateContentIdentifier', $params);
    }

    /**
     * DescribeContentIdentifiers — 批量查询内容标识符
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeContentIdentifiers(array $params = array())
    {
        return $this->invoke('DescribeContentIdentifiers', $params);
    }

    /**
     * ModifyContentIdentifier — 修改内容标识符
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyContentIdentifier(array $params = array())
    {
        return $this->invoke('ModifyContentIdentifier', $params);
    }

    /**
     * DeleteContentIdentifier — 删除内容标识符
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteContentIdentifier(array $params = array())
    {
        return $this->invoke('DeleteContentIdentifier', $params);
    }
}
