<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneL7AccApi.php
 * 作用：EdgeOne 站点加速配置（七层）
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneL7AccApi extends EdgeOneApiBase
{
    /**
     * CreateL7AccRules — 创建七层加速规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createL7AccRules(array $params = array())
    {
        return $this->invoke('CreateL7AccRules', $params);
    }

    /**
     * DescribeL7AccRules — 查询七层加速规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeL7AccRules(array $params = array())
    {
        return $this->invoke('DescribeL7AccRules', $params);
    }

    /**
     * ModifyL7AccRule — 修改七层加速规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL7AccRule(array $params = array())
    {
        return $this->invoke('ModifyL7AccRule', $params);
    }

    /**
     * DeleteL7AccRules — 删除七层加速规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteL7AccRules(array $params = array())
    {
        return $this->invoke('DeleteL7AccRules', $params);
    }

    /**
     * DescribeL7AccSetting — 查询七层加速全局配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeL7AccSetting(array $params = array())
    {
        return $this->invoke('DescribeL7AccSetting', $params);
    }

    /**
     * ModifyL7AccSetting — 修改七层加速全局配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL7AccSetting(array $params = array())
    {
        return $this->invoke('ModifyL7AccSetting', $params);
    }

    /**
     * ModifyL7AccRulePriority — 修改七层加速规则优先级
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyL7AccRulePriority(array $params = array())
    {
        return $this->invoke('ModifyL7AccRulePriority', $params);
    }
}
