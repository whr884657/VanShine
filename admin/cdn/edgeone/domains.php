<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_domains', 'EdgeOne · 域名管理');
$eo = $ctx['eo'];
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
$currentZone = vs_edgeone_find_zone_by_id($zones, $zoneId);
$canManage = $currentZone !== null && vs_edgeone_zone_supports_self_service_ops($currentZone);

$domains = array();
$domainsError = '';
$ddosData = array();
$ddosLabel = array('label' => '—', 'raw' => '');

if ($eo !== null && $zoneId !== '' && $canManage) {
    $dResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->accelerationDomain->describeAccelerationDomains(array(
            'ZoneId' => $zoneId,
            'Offset' => 0,
            'Limit'  => 100,
        ));
    }, array());
    if ($dResult['ok']) {
        $domains = isset($dResult['data']['AccelerationDomains']) && is_array($dResult['data']['AccelerationDomains'])
            ? $dResult['data']['AccelerationDomains']
            : array();
    } else {
        $domainsError = $dResult['error'];
    }

    $ddosResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
        return $eo->security->describeDDoSProtection(array('ZoneId' => $zoneId));
    });
    if ($ddosResult['ok'] && is_array($ddosResult['data'])) {
        $ddosData = $ddosResult['data'];
    }
    $ddosLabel = vs_edgeone_fetch_zone_ddos_label($eo, $zoneId);
}
?>

<div id="edgeoneDomainsPage">
    <?php echo vs_edgeone_render_domain_toolbar($zones, $zoneId, $canManage); ?>

    <div class="vs-panel vs-edgeone-domain-config-panel">
        <?php echo vs_edgeone_render_domain_zone_config($currentZone, $ddosLabel); ?>
    </div>

    <?php echo vs_edgeone_render_domain_list_panel($domains, $ddosData, $domainsError, $zoneId, $canManage); ?>

    <?php
    if ($canManage) {
        echo vs_edgeone_render_domain_drawers($currentZone, $domains);
    }
    ?>
</div>

<script type="application/json" id="edgeoneDomainRowsMeta"><?php
echo json_encode($domains, JSON_UNESCAPED_UNICODE);
?></script>

<?php vs_edgeone_page_end();
