<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneCustomErrorPageApi.php
 * 作用：EdgeOne 自定义响应页面
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneCustomErrorPageApi extends EdgeOneApiBase
{
    /**
     * CreateCustomizeErrorPage — 创建自定义响应页面
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createCustomizeErrorPage(array $params = array())
    {
        return $this->invoke('CreateCustomizeErrorPage', $params);
    }

    /**
     * DescribeCustomErrorPages — 查询自定义响应页面列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeCustomErrorPages(array $params = array())
    {
        return $this->invoke('DescribeCustomErrorPages', $params);
    }

    /**
     * ModifyCustomErrorPage — 修改自定义响应页面
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyCustomErrorPage(array $params = array())
    {
        return $this->invoke('ModifyCustomErrorPage', $params);
    }

    /**
     * DeleteCustomErrorPage — 删除自定义响应页面
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteCustomErrorPage(array $params = array())
    {
        return $this->invoke('DeleteCustomErrorPage', $params);
    }
}
