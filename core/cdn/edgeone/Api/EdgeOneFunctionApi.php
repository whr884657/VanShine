<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneFunctionApi.php
 * 作用：EdgeOne 边缘函数
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneFunctionApi extends EdgeOneApiBase
{
    /**
     * CreateFunction — 创建边缘函数
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createFunction(array $params = array())
    {
        return $this->invoke('CreateFunction', $params);
    }

    /**
     * DescribeFunctions — 查询边缘函数列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeFunctions(array $params = array())
    {
        return $this->invoke('DescribeFunctions', $params);
    }

    /**
     * ModifyFunction — 修改边缘函数
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyFunction(array $params = array())
    {
        return $this->invoke('ModifyFunction', $params);
    }

    /**
     * DeleteFunction — 删除边缘函数
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteFunction(array $params = array())
    {
        return $this->invoke('DeleteFunction', $params);
    }

    /**
     * CreateFunctionRule — 创建边缘函数触发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createFunctionRule(array $params = array())
    {
        return $this->invoke('CreateFunctionRule', $params);
    }

    /**
     * DescribeFunctionRules — 查询边缘函数触发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeFunctionRules(array $params = array())
    {
        return $this->invoke('DescribeFunctionRules', $params);
    }

    /**
     * ModifyFunctionRule — 修改边缘函数触发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyFunctionRule(array $params = array())
    {
        return $this->invoke('ModifyFunctionRule', $params);
    }

    /**
     * ModifyFunctionRulePriority — 修改边缘函数触发规则优先级
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyFunctionRulePriority(array $params = array())
    {
        return $this->invoke('ModifyFunctionRulePriority', $params);
    }

    /**
     * DeleteFunctionRules — 删除边缘函数触发规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteFunctionRules(array $params = array())
    {
        return $this->invoke('DeleteFunctionRules', $params);
    }

    /**
     * DescribeFunctionRuntimeEnvironment — 查询边缘函数运行环境
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeFunctionRuntimeEnvironment(array $params = array())
    {
        return $this->invoke('DescribeFunctionRuntimeEnvironment', $params);
    }

    /**
     * HandleFunctionRuntimeEnvironment — 操作边缘函数运行环境
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function handleFunctionRuntimeEnvironment(array $params = array())
    {
        return $this->invoke('HandleFunctionRuntimeEnvironment', $params);
    }

    /**
     * CreateFunctionReplica — 创建边缘函数副本
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createFunctionReplica(array $params = array())
    {
        return $this->invoke('CreateFunctionReplica', $params);
    }

    /**
     * DeleteFunctionReplica — 删除边缘函数副本
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteFunctionReplica(array $params = array())
    {
        return $this->invoke('DeleteFunctionReplica', $params);
    }

    /**
     * DescribeFunctionReplicas — 查询边缘函数副本列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeFunctionReplicas(array $params = array())
    {
        return $this->invoke('DescribeFunctionReplicas', $params);
    }

    /**
     * ModifyFunctionReplica — 编辑边缘函数副本
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyFunctionReplica(array $params = array())
    {
        return $this->invoke('ModifyFunctionReplica', $params);
    }

    /**
     * DescribeFunctionComponentBindings — 查询函数组件绑定列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeFunctionComponentBindings(array $params = array())
    {
        return $this->invoke('DescribeFunctionComponentBindings', $params);
    }

    /**
     * ModifyFunctionComponentBindings — 修改函数组件绑定
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyFunctionComponentBindings(array $params = array())
    {
        return $this->invoke('ModifyFunctionComponentBindings', $params);
    }
}
