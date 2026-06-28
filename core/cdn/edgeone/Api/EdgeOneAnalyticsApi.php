<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneAnalyticsApi.php
 * 作用：EdgeOne 数据分析
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneAnalyticsApi extends EdgeOneApiBase
{
    /**
     * DescribeDDoSAttackData — 查询 DDoS 攻击时序数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDDoSAttackData(array $params = array())
    {
        return $this->invoke('DescribeDDoSAttackData', $params);
    }

    /**
     * DescribeDDoSAttackEvent — 查询 DDoS 攻击事件列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDDoSAttackEvent(array $params = array())
    {
        return $this->invoke('DescribeDDoSAttackEvent', $params);
    }

    /**
     * DescribeDDoSAttackTopData — 查询 DDoS 攻击 Top 数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeDDoSAttackTopData(array $params = array())
    {
        return $this->invoke('DescribeDDoSAttackTopData', $params);
    }

    /**
     * DescribeTimingL7OriginPullData — 查询回源时序数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeTimingL7OriginPullData(array $params = array())
    {
        return $this->invoke('DescribeTimingL7OriginPullData', $params);
    }

    /**
     * DescribeTimingL4Data — 查询四层流量时序数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeTimingL4Data(array $params = array())
    {
        return $this->invoke('DescribeTimingL4Data', $params);
    }

    /**
     * DescribeTimingL7AnalysisData — 查询流量分析时序数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeTimingL7AnalysisData(array $params = array())
    {
        return $this->invoke('DescribeTimingL7AnalysisData', $params);
    }

    /**
     * DescribeTopL7AnalysisData — 查询流量分析 Top 数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeTopL7AnalysisData(array $params = array())
    {
        return $this->invoke('DescribeTopL7AnalysisData', $params);
    }
}
