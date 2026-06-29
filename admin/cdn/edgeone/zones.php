<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_zones', 'EdgeOne · 站点管理');
$eo = $ctx['eo'];
$zones = $ctx['zones'];

$plans = array();
if ($eo !== null) {
    $planResult = vs_edgeone_try_call(function () use ($eo) {
        return $eo->billing->describePlans(array('Offset' => 0, 'Limit' => 100));
    });
    if ($planResult['ok'] && isset($planResult['data']['Plans']) && is_array($planResult['data']['Plans'])) {
        $plans = $planResult['data']['Plans'];
    }
}

$planMap = vs_edgeone_zone_plan_map($plans);
$availablePlans = vs_edgeone_plans_available_for_create($plans);
?>

<div id="edgeoneZonesPage" data-default-range="today">
    <div id="edgeoneZonesOverviewHost" class="vs-edgeone-zones-overview-host is-loading">
        <p class="vs-form-tip">站点数据加载中…</p>
    </div>
    <?php echo vs_edgeone_render_zones_table_panel($zones, $planMap, $availablePlans); ?>
</div>

<?php
vs_edgeone_page_end();
