<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_zones', 'EdgeOne · 站点管理');
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
?>

<div class="vs-panel">
    <h3 class="vs-panel__title">创建站点</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="zone_create">
        <div class="vs-form-row">
            <label class="vs-label">站点域名</label>
            <input type="text" name="zone_name" class="vs-input" placeholder="example.com" required>
        </div>
        <div class="vs-form-row vs-form-row--inline">
            <div class="vs-form-col">
                <label class="vs-label">接入类型</label>
                <select name="type" class="vs-input">
                    <option value="full">NS 接入</option>
                    <option value="partial">CNAME 接入</option>
                    <option value="noDomainAccess">无域名接入</option>
                </select>
            </div>
            <div class="vs-form-col">
                <label class="vs-label">套餐 PlanId（可选）</label>
                <input type="text" name="plan_id" class="vs-input" placeholder="留空使用默认">
            </div>
        </div>
        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">创建站点</button>
        </div>
    </form>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">站点列表</h3>
    <?php if (count($zones) === 0): ?>
        <p class="vs-form-tip">暂无站点</p>
    <?php else: ?>
        <div class="vs-table-wrap">
            <table class="vs-table">
                <thead>
                    <tr>
                        <th>站点备注</th>
                        <th>域名</th>
                        <th>ZoneId</th>
                        <th>运行状态</th>
                        <th>加速状态</th>
                        <th>接入方式</th>
                        <th>服务区域</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($zones as $zone): ?>
                    <tr>
                        <td><?php echo vs_e(trim((string) (isset($zone['AliasZoneName']) ? $zone['AliasZoneName'] : '')) !== '' ? $zone['AliasZoneName'] : '—'); ?></td>
                        <td><?php echo vs_e(isset($zone['ZoneName']) ? $zone['ZoneName'] : ''); ?></td>
                        <td><code><?php echo vs_e(isset($zone['ZoneId']) ? $zone['ZoneId'] : ''); ?></code></td>
                        <td><?php vs_edgeone_render_zone_runtime_badge($zone); ?></td>
                        <td><?php echo vs_e(vs_edgeone_translate('ActiveStatus', isset($zone['ActiveStatus']) ? $zone['ActiveStatus'] : '')); ?></td>
                        <td><?php echo vs_e(vs_edgeone_translate('Type', isset($zone['Type']) ? $zone['Type'] : '')); ?></td>
                        <td><?php echo vs_e(vs_edgeone_translate('Area', isset($zone['Area']) ? $zone['Area'] : '')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
vs_edgeone_render_sections($ctx['eo'], $zoneId, array(
    array(
        'title' => '站点验证信息',
        'fetch' => function ($eo, $zoneId) use ($zones) {
            $zoneName = vs_edgeone_find_zone_name($zones, $zoneId);
            if ($zoneName === '') {
                throw new Exception('无法获取站点域名');
            }
            return $eo->zone->describeIdentifications(array(
                'Filters' => array(array(
                    'Name'   => 'zone-name',
                    'Values' => array($zoneName),
                )),
                'Offset' => 0,
                'Limit'  => 20,
            ));
        },
        'empty_tip' => '暂无验证信息',
    ),
    array(
        'title' => 'CNAME 配置状态',
        'fetch' => function ($eo, $zoneId) {
            $names = vs_edgeone_fetch_domain_names($eo, $zoneId);
            if (count($names) === 0) {
                return array('CnameStatus' => array(), 'Note' => '暂无加速域名，无法校验 CNAME');
            }
            return $eo->zone->checkCnameStatus(array(
                'ZoneId'      => $zoneId,
                'RecordNames' => $names,
            ));
        },
        'empty_tip' => '暂无 CNAME 状态',
    ),
));

vs_edgeone_page_end();
