<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneSecurityApi.php
 * 作用：EdgeOne 安全配置
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneSecurityApi extends EdgeOneApiBase
{
    /**
     * CreateSecurityIPGroup — 创建安全 IP 组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSecurityIPGroup(array $params = array())
    {
        return $this->invoke('CreateSecurityIPGroup', $params);
    }

    /**
     * DescribeSecurityIPGroup — 查询安全 IP 组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityIPGroup(array $params = array())
    {
        return $this->invoke('DescribeSecurityIPGroup', $params);
    }

    /**
     * ModifySecurityIPGroup — 修改安全 IP 组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityIPGroup(array $params = array())
    {
        return $this->invoke('ModifySecurityIPGroup', $params);
    }

    /**
     * DeleteSecurityIPGroup — 删除安全 IP 组
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSecurityIPGroup(array $params = array())
    {
        return $this->invoke('DeleteSecurityIPGroup', $params);
    }

    /**
     * DescribeSecurityTemplateBindings — 查询指定策略模板的绑定关系列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityTemplateBindings(array $params = array())
    {
        return $this->invoke('DescribeSecurityTemplateBindings', $params);
    }

    /**
     * BindSecurityTemplateToEntity — 绑定或解绑安全策略模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function bindSecurityTemplateToEntity(array $params = array())
    {
        return $this->invoke('BindSecurityTemplateToEntity', $params);
    }

    /**
     * DescribeSecurityPolicy — 查询安全防护配置详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityPolicy(array $params = array())
    {
        return $this->invoke('DescribeSecurityPolicy', $params);
    }

    /**
     * ModifySecurityPolicy — 修改 Web & Bot 安全配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityPolicy(array $params = array())
    {
        return $this->invoke('ModifySecurityPolicy', $params);
    }

    /**
     * DescribeSecurityIPGroupContent — 分页查询 IP 组中的 IP 列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityIPGroupContent(array $params = array())
    {
        return $this->invoke('DescribeSecurityIPGroupContent', $params);
    }

    /**
     * CreateSecurityJSInjectionRule — 创建 JavaScript 注入规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSecurityJSInjectionRule(array $params = array())
    {
        return $this->invoke('CreateSecurityJSInjectionRule', $params);
    }

    /**
     * DescribeSecurityJSInjectionRule — 查询 JavaScript 注入规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityJSInjectionRule(array $params = array())
    {
        return $this->invoke('DescribeSecurityJSInjectionRule', $params);
    }

    /**
     * ModifySecurityJSInjectionRule — 修改 JavaScript 注入规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityJSInjectionRule(array $params = array())
    {
        return $this->invoke('ModifySecurityJSInjectionRule', $params);
    }

    /**
     * DeleteSecurityJSInjectionRule — 删除 JavaScript 注入规则
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSecurityJSInjectionRule(array $params = array())
    {
        return $this->invoke('DeleteSecurityJSInjectionRule', $params);
    }

    /**
     * CreateSecurityClientAttester — 创建客户端认证选项
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createSecurityClientAttester(array $params = array())
    {
        return $this->invoke('CreateSecurityClientAttester', $params);
    }

    /**
     * DescribeSecurityClientAttester — 查询客户端认证选项
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeSecurityClientAttester(array $params = array())
    {
        return $this->invoke('DescribeSecurityClientAttester', $params);
    }

    /**
     * ModifySecurityClientAttester — 修改客户端认证选项
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifySecurityClientAttester(array $params = array())
    {
        return $this->invoke('ModifySecurityClientAttester', $params);
    }

    /**
     * DeleteSecurityClientAttester — 删除客户端认证选项
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteSecurityClientAttester(array $params = array())
    {
        return $this->invoke('DeleteSecurityClientAttester', $params);
    }

    /**
     * CreateWebSecurityTemplate — 创建安全策略配置模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createWebSecurityTemplate(array $params = array())
    {
        return $this->invoke('CreateWebSecurityTemplate', $params);
    }

    /**
     * DeleteWebSecurityTemplate — 删除安全策略配置模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteWebSecurityTemplate(array $params = array())
    {
        return $this->invoke('DeleteWebSecurityTemplate', $params);
    }

    /**
     * DescribeWebSecurityTemplates — 查询安全策略配置模板列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeWebSecurityTemplates(array $params = array())
    {
        return $this->invoke('DescribeWebSecurityTemplates', $params);
    }

    /**
     * ModifyWebSecurityTemplate — 修改安全策略配置模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyWebSecurityTemplate(array $params = array())
    {
        return $this->invoke('ModifyWebSecurityTemplate', $params);
    }

    /**
     * DescribeDDoSProtection — 查询站点的独立 DDoS 防护信息
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDDoSProtection(array $params = array())
    {
        return $this->invoke('DescribeDDoSProtection', $params);
    }

    /**
     * DescribeWebSecurityTemplate — 查询安全策略配置模板详情
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeWebSecurityTemplate(array $params = array())
    {
        return $this->invoke('DescribeWebSecurityTemplate', $params);
    }

    /**
     * ModifyDDoSProtection — 修改站点的独立 DDoS 防护
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyDDoSProtection(array $params = array())
    {
        return $this->invoke('ModifyDDoSProtection', $params);
    }
}
