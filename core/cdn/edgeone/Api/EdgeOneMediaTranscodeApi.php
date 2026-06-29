<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneMediaTranscodeApi.php
 * 作用：EdgeOne 图片与视频处理
 * @version 1.0.0
 * @generated build-apis.php
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

class EdgeOneMediaTranscodeApi extends EdgeOneApiBase
{
    /**
     * CreateJustInTimeTranscodeTemplate — 创建即时转码模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createJustInTimeTranscodeTemplate(array $params = array())
    {
        return $this->invoke('CreateJustInTimeTranscodeTemplate', $params);
    }

    /**
     * DescribeJustInTimeTranscodeTemplates — 获取即时转码模板列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeJustInTimeTranscodeTemplates(array $params = array())
    {
        return $this->invoke('DescribeJustInTimeTranscodeTemplates', $params);
    }

    /**
     * DeleteJustInTimeTranscodeTemplates — 删除即时转码模板
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteJustInTimeTranscodeTemplates(array $params = array())
    {
        return $this->invoke('DeleteJustInTimeTranscodeTemplates', $params);
    }
}
