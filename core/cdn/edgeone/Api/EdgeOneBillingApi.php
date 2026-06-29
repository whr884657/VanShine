<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneBillingApi.php
 * 作用：EdgeOne 计费
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneBillingApi extends EdgeOneApiBase
{
    /**
     * CreatePlan — 创建套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createPlan(array $params = array())
    {
        return $this->invoke('CreatePlan', $params);
    }

    /**
     * DescribePlans — 查询套餐信息列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describePlans(array $params = array())
    {
        return $this->invoke('DescribePlans', $params);
    }

    /**
     * UpgradePlan — 升级套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function upgradePlan(array $params = array())
    {
        return $this->invoke('UpgradePlan', $params);
    }

    /**
     * RenewPlan — 续费套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function renewPlan(array $params = array())
    {
        return $this->invoke('RenewPlan', $params);
    }

    /**
     * ModifyPlan — 修改套餐配置
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyPlan(array $params = array())
    {
        return $this->invoke('ModifyPlan', $params);
    }

    /**
     * IncreasePlanQuota — 增购套餐配额
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function increasePlanQuota(array $params = array())
    {
        return $this->invoke('IncreasePlanQuota', $params);
    }

    /**
     * DestroyPlan — 销毁套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function destroyPlan(array $params = array())
    {
        return $this->invoke('DestroyPlan', $params);
    }

    /**
     * CreatePlanForZone — 为未购买套餐的站点购买套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createPlanForZone(array $params = array())
    {
        return $this->invoke('CreatePlanForZone', $params);
    }

    /**
     * BindZoneToPlan — 为站点绑定套餐
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function bindZoneToPlan(array $params = array())
    {
        return $this->invoke('BindZoneToPlan', $params);
    }

    /**
     * DescribeBillingData — 查询计费数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeBillingData(array $params = array())
    {
        return $this->invoke('DescribeBillingData', $params);
    }

    /**
     * DescribeAvailablePlans — 查询当前账户可购买套餐信息列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeAvailablePlans(array $params = array())
    {
        return $this->invoke('DescribeAvailablePlans', $params);
    }
}
