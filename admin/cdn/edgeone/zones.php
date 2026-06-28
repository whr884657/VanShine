<?php
/**
 * 文件：admin/cdn/edgeone/zones.php
 * 作用：EdgeOne 站点管理
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$zones = array();
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne · 站点管理', VS_EDGEONE_ACTIVE_MENU);
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>
<?php vs_edgeone_nav('cdn_edgeone_zones'); ?>

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
                        <td><?php echo vs_e(vs_edgeone_translate('Status', isset($zone['Status']) ? $zone['Status'] : '')); ?></td>
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
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
