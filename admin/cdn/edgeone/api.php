<?php
/**
 * 文件：admin/cdn/edgeone/api.php
 * 作用：EdgeOne 后台 AJAX 接口
 * @version 1.0.1
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
            $params = array(
                'ZoneId'     => $zoneId,
                'DomainName' => $domain,
            );
            $origin = trim(isset($_POST['origin']) ? $_POST['origin'] : '');
            if ($origin !== '') {
                $originType = trim(isset($_POST['origin_type']) ? $_POST['origin_type'] : 'IP_DOMAIN');
                $originInfo = array(
                    'OriginType' => $originType !== '' ? $originType : 'IP_DOMAIN',
                    'Origin'     => $origin,
                );
                $hostMode = trim(isset($_POST['host_header_mode']) ? $_POST['host_header_mode'] : '');
                $hostHeader = trim(isset($_POST['host_header']) ? $_POST['host_header'] : '');
                if ($hostMode === 'custom' && $hostHeader !== '') {
                    $originInfo['HostHeader'] = $hostHeader;
                }
                $params['OriginInfo'] = $originInfo;
            }
            $protocol = trim(isset($_POST['origin_protocol']) ? $_POST['origin_protocol'] : '');
            if ($protocol !== '') {
                $params['OriginProtocol'] = $protocol;
            }
            $httpPort = (int) (isset($_POST['http_port']) ? $_POST['http_port'] : 0);
            $httpsPort = (int) (isset($_POST['https_port']) ? $_POST['https_port'] : 0);
            if ($httpPort > 0) {
                $params['HttpOriginPort'] = $httpPort;
            }
            if ($httpsPort > 0) {
                $params['HttpsOriginPort'] = $httpsPort;
            }
            $ipv6 = trim(isset($_POST['ipv6_status']) ? $_POST['ipv6_status'] : '');
            if ($ipv6 !== '') {
                $params['IPv6Status'] = $ipv6;
            }
            $resp = $eo->accelerationDomain->createAccelerationDomain($params);
            AjaxResponse::success('加速域名已创建', array('data' => $resp));

        case 'domain_modify':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $domain = trim(isset($_POST['domain_name']) ? $_POST['domain_name'] : '');
            if ($domain === '') {
                throw new Exception('请指定域名');
            }
            $params = array(
                'ZoneId'     => $zoneId,
                'DomainName' => $domain,
            );
            $origin = trim(isset($_POST['origin']) ? $_POST['origin'] : '');
            if ($origin !== '') {
                $originType = trim(isset($_POST['origin_type']) ? $_POST['origin_type'] : 'IP_DOMAIN');
                $params['OriginInfo'] = array(
                    'OriginType' => $originType !== '' ? $originType : 'IP_DOMAIN',
                    'Origin'     => $origin,
                );
            }
            $protocol = trim(isset($_POST['origin_protocol']) ? $_POST['origin_protocol'] : '');
            if ($protocol !== '') {
                $params['OriginProtocol'] = $protocol;
            }
            $httpPort = (int) (isset($_POST['http_port']) ? $_POST['http_port'] : 0);
            $httpsPort = (int) (isset($_POST['https_port']) ? $_POST['https_port'] : 0);
            if ($httpPort > 0) {
                $params['HttpOriginPort'] = $httpPort;
            }
            if ($httpsPort > 0) {
                $params['HttpsOriginPort'] = $httpsPort;
            }
            $ipv6 = trim(isset($_POST['ipv6_status']) ? $_POST['ipv6_status'] : '');
            if ($ipv6 !== '') {
                $params['IPv6Status'] = $ipv6;
            }
            $resp = $eo->accelerationDomain->modifyAccelerationDomain($params);
            AjaxResponse::success('域名配置已更新', array('data' => $resp));

        case 'domain_status':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $domain = trim(isset($_POST['domain_name']) ? $_POST['domain_name'] : '');
            $status = trim(isset($_POST['status']) ? $_POST['status'] : '');
            if ($domain === '' || $status === '') {
                throw new Exception('参数不完整');
            }
            $resp = $eo->accelerationDomain->modifyAccelerationDomainStatuses(array(
                'ZoneId'      => $zoneId,
                'DomainNames' => array($domain),
                'Status'      => $status,
            ));
            AjaxResponse::success($status === 'offline' ? '域名已停用' : '域名已启用', array('data' => $resp));

        case 'domain_delete':
            if ($zoneId === '') {
                throw new Exception('请先选择站点');
            }
            $domain = trim(isset($_POST['domain_name']) ? $_POST['domain_name'] : '');
            if ($domain === '') {
                throw new Exception('请指定域名');
            }
            $resp = $eo->accelerationDomain->deleteAccelerationDomains(array(
                'ZoneId'      => $zoneId,
                'DomainNames' => array($domain),
            ));
            AjaxResponse::success('域名已删除', array('data' => $resp));

        case 'zones_overview_data':
            @set_time_limit(300);
            $rangeKey = trim(isset($_POST['range']) ? $_POST['range'] : 'today');
            $chartTab = trim(isset($_POST['chart_tab']) ? $_POST['chart_tab'] : 'flux');
            $zones = vs_edgeone_fetch_zones($eo);
            $metrics = vs_edgeone_fetch_zones_page_metrics($eo, $zones, $rangeKey);
            AjaxResponse::success('ok', array(
                'data' => array(
                    'overview_html' => vs_edgeone_render_zones_overview_panel($metrics, $chartTab),
                    'range'         => $rangeKey,
                    'chart_tab'     => $chartTab,
                ),
            ));

        case 'overview_data':
            @set_time_limit(300);
            $filters = vs_edgeone_overview_filters_from_request($_POST);
            $zones = vs_edgeone_fetch_zones($eo);
            $dashboard = vs_edgeone_fetch_overview_dashboard($eo, $zones, $filters);
            AjaxResponse::success('ok', array(
                'data' => array(
                    'dashboard_html' => vs_edgeone_render_overview_dashboard($dashboard),
                ),
            ));

        case 'overview_flux':
            @set_time_limit(300);
            $filters = vs_edgeone_overview_filters_from_request($_POST);
            $zones = vs_edgeone_fetch_zones($eo);
            $zoneIds = vs_edgeone_overview_zone_ids($zones, $filters['filter_zone']);
            $domain = isset($filters['filter_domain']) ? (string) $filters['filter_domain'] : '';
            $custom = isset($filters['custom_filters']) && is_array($filters['custom_filters']) ? $filters['custom_filters'] : array();
            $rangeKey = isset($filters['range']) ? (string) $filters['range'] : 'today';
            $fluxDim = isset($filters['flux_dimension']) ? (string) $filters['flux_dimension'] : 'all';
            $flux = vs_edgeone_fetch_flux_chart_by_dimension($eo, $zoneIds, $rangeKey, $domain, $custom, $fluxDim);
            AjaxResponse::success('ok', array(
                'data' => array(
                    'flux_html' => vs_edgeone_render_flux_chart_panel_inner($flux, $fluxDim),
                    'flux_dimension' => $fluxDim,
                ),
            ));

        case 'overview_domains':
            $filterZone = trim(isset($_POST['filter_zone']) ? $_POST['filter_zone'] : '');
            if ($filterZone === '' || $filterZone === '*') {
                AjaxResponse::success('ok', array('data' => array('domains' => array())));
            }
            $names = vs_edgeone_fetch_domain_names($eo, $filterZone);
            AjaxResponse::success('ok', array('data' => array('domains' => $names)));

        default:
            AjaxResponse::error('未知操作', 400);
    }
} catch (Exception $e) {
    AjaxResponse::error($e->getMessage());
}
