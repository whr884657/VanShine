<?php
/**
 * 文件：core/cdn/edgeone/Api/EdgeOneEdgeKvApi.php
 * 作用：EdgeOne KV 存储（EdgeKV）
 * @version 1.0.0
 * @generated build-apis.php
 */

class EdgeOneEdgeKvApi extends EdgeOneApiBase
{
    /**
     * CreateEdgeKVNamespace — 创建 KV 命名空间
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function createEdgeKVNamespace(array $params = array())
    {
        return $this->invoke('CreateEdgeKVNamespace', $params);
    }

    /**
     * DeleteEdgeKVNamespace — 删除 KV 命名空间
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function deleteEdgeKVNamespace(array $params = array())
    {
        return $this->invoke('DeleteEdgeKVNamespace', $params);
    }

    /**
     * DescribeEdgeKVNamespaces — 查询 KV 命名空间
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function describeEdgeKVNamespaces(array $params = array())
    {
        return $this->invoke('DescribeEdgeKVNamespaces', $params);
    }

    /**
     * EdgeKVDelete — 删除 KV 数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function edgeKVDelete(array $params = array())
    {
        return $this->invoke('EdgeKVDelete', $params);
    }

    /**
     * EdgeKVGet — 查询 KV 数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function edgeKVGet(array $params = array())
    {
        return $this->invoke('EdgeKVGet', $params);
    }

    /**
     * EdgeKVList — 查询 KV 键名列表
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function edgeKVList(array $params = array())
    {
        return $this->invoke('EdgeKVList', $params);
    }

    /**
     * EdgeKVPut — 写入 KV 数据
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function edgeKVPut(array $params = array())
    {
        return $this->invoke('EdgeKVPut', $params);
    }

    /**
     * ModifyEdgeKVNamespace — 修改 KV 命名空间
     *
     * @param array $params
     * @return array
     * @throws EdgeOneException
     */
    public function modifyEdgeKVNamespace(array $params = array())
    {
        return $this->invoke('ModifyEdgeKVNamespace', $params);
    }
}
