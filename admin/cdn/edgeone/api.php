<?php
/**
 * 文件：admin/cdn/edgeone/api.php
 * 作用：EdgeOne 后台 AJAX 接口
 * @version 1.0.0
 */

require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    AjaxResponse::error('Method Not Allowed', 405);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'set_zone') {
    vs_edgeone_set_zone(isset($_POST['zone_id']) ? $_POST['zone_id'] : '');
    AjaxResponse::success('已切换站点');
}

if (!vs_edgeone_is_ready()) {
    AjaxResponse::error('请先在系统设置中配置并启用 EdgeOne');
}

try {
    $eo = EdgeOne::create();
} catch (Exception $e) {
    AjaxResponse::error($e->getMessage());
}

$zoneId = vs_edgeone_selected_zone();

try {
    switch ($action) {
        case 'zone_create':
            $name = trim(isset($_POST['zone_name']) ? $_POST['zone_name'] : '');
            if ($name === '') {
                throw new Exception('请填写站点域名');
            }
            $type = trim(isset($_POST['type']) ? $_POST['type'] : 'full');
            $plan = trim(isset($_POST['plan_id']) ? $_POST['plan_id'] : '');
            $params = array('ZoneName' => $name, 'Type' => $type);
            if ($plan !== '') {
                $params['PlanId'] = $plan;
            }
            $resp = $eo->zone->createZone($params);
            AjaxResponse::success('站点创建请求已提交', array('data' => $resp));

        case 'zone_delete':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $eo->zone->deleteZone(array('ZoneId' => $zoneId));
            vs_edgeone_set_zone('');
            AjaxResponse::success('站点删除请求已提交');

        case 'purge_create':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $targets = trim(isset($_POST['targets']) ? $_POST['targets'] : '');
            $type = trim(isset($_POST['purge_type']) ? $_POST['purge_type'] : 'purge_url');
            $list = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $targets)));
            if (count($list) === 0) {
                throw new Exception('请填写至少一条 URL 或目录');
            }
            $resp = $eo->content->createPurgeTask(array(
                'ZoneId'  => $zoneId,
                'Type'    => $type,
                'Targets' => $list,
            ));
            AjaxResponse::success('刷新任务已创建', array('data' => $resp));

        case 'prefetch_create':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $targets = trim(isset($_POST['targets']) ? $_POST['targets'] : '');
            $list = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $targets)));
            if (count($list) === 0) {
                throw new Exception('请填写至少一条 URL');
            }
            $resp = $eo->content->createPrefetchTask(array(
                'ZoneId'  => $zoneId,
                'Targets' => $list,
            ));
            AjaxResponse::success('预热任务已创建', array('data' => $resp));

        case 'dns_create':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $resp = $eo->dns->createDnsRecord(array(
                'ZoneId' => $zoneId,
                'Name'   => trim(isset($_POST['name']) ? $_POST['name'] : ''),
                'Type'   => trim(isset($_POST['type']) ? $_POST['type'] : 'A'),
                'Content'=> trim(isset($_POST['content']) ? $_POST['content'] : ''),
                'TTL'    => (int) (isset($_POST['ttl']) ? $_POST['ttl'] : 600),
            ));
            AjaxResponse::success('DNS 记录已创建', array('data' => $resp));

        case 'domain_create':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $domain = trim(isset($_POST['domain_name']) ? $_POST['domain_name'] : '');
            if ($domain === '') {
                throw new Exception('请填写加速域名');
            }
            $resp = $eo->accelerationDomain->createAccelerationDomain(array(
                'ZoneId' => $zoneId,
                'DomainName' => $domain,
            ));
            AjaxResponse::success('加速域名已创建', array('data' => $resp));

        default:
            AjaxResponse::error('未知操作', 400);
    }
} catch (Exception $e) {
    AjaxResponse::error($e->getMessage());
}
