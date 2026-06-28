<?php
/**
 * 文件：admin/cdn/edgeone/domains.php
 * 作用：EdgeOne 域名加速与 DNS
 * @version 1.0.0
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$zones = array();
$domains = array();
$dnsRecords = array();
$zoneId = '';
$error = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $zoneId = vs_edgeone_selected_zone();
        if ($zoneId !== '') {
            $dResp = $eo->accelerationDomain->describeAccelerationDomains(array('ZoneId' => $zoneId, 'Offset' => 0, 'Limit' => 100));
            $domains = isset($dResp['AccelerationDomains']) ? $dResp['AccelerationDomains'] : array();
            $dnsResp = $eo->dns->describeDnsRecords(array('ZoneId' => $zoneId, 'Offset' => 0, 'Limit' => 100));
            $dnsRecords = isset($dnsResp['DnsRecords']) ? $dnsResp['DnsRecords'] : array();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne 域名加速', 'cdn_edgeone_domains');
?>

<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">
<?php vs_edgeone_render_setup_notice(); ?>
<?php if ($error !== ''): ?><div class="vs-panel vs-alert vs-alert--error"><?php echo vs_e($error); ?></div><?php endif; ?>
<?php vs_edgeone_nav('cdn_edgeone_domains'); ?>

<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?>
<div class="vs-panel"><?php vs_edgeone_render_zone_picker($zones); ?></div>
<?php endif; ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">新增加速域名</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="domain_create">
        <div class="vs-form-row">
            <label class="vs-label">加速域名</label>
            <input type="text" name="domain_name" class="vs-input" placeholder="www.example.com" required>
        </div>
        <div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">添加</button></div>
    </form>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">加速域名列表</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif (count($domains) === 0): ?>
        <p class="vs-form-tip">暂无加速域名</p>
    <?php else: ?>
        <div class="vs-table-wrap">
            <table class="vs-table">
                <thead><tr><th>域名</th><th>状态</th><th>CNAME</th></tr></thead>
                <tbody>
                <?php foreach ($domains as $row): ?>
                    <tr>
                        <td><?php echo vs_e(isset($row['DomainName']) ? $row['DomainName'] : ''); ?></td>
                        <td><?php echo vs_e(isset($row['DomainStatus']) ? $row['DomainStatus'] : '-'); ?></td>
                        <td><code><?php echo vs_e(isset($row['Cname']) ? $row['Cname'] : '-'); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">新增 DNS 记录</h3>
    <form class="vs-form vs-edgeone-api-form" data-action="dns_create">
        <div class="vs-form-grid">
            <div class="vs-form-row"><label class="vs-label">主机记录</label><input type="text" name="name" class="vs-input" required></div>
            <div class="vs-form-row"><label class="vs-label">类型</label>
                <select name="type" class="vs-input"><option>A</option><option>AAAA</option><option>CNAME</option><option>TXT</option></select>
            </div>
            <div class="vs-form-row"><label class="vs-label">记录值</label><input type="text" name="content" class="vs-input" required></div>
            <div class="vs-form-row"><label class="vs-label">TTL</label><input type="number" name="ttl" class="vs-input" value="600"></div>
        </div>
        <div class="vs-form-actions"><button type="submit" class="vs-btn vs-btn--primary">添加 DNS</button></div>
    </form>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">DNS 记录列表</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif (count($dnsRecords) === 0): ?>
        <p class="vs-form-tip">暂无 DNS 记录</p>
    <?php else: ?>
        <div class="vs-table-wrap">
            <table class="vs-table">
                <thead><tr><th>主机记录</th><th>类型</th><th>记录值</th><th>TTL</th></tr></thead>
                <tbody>
                <?php foreach ($dnsRecords as $row): ?>
                    <tr>
                        <td><?php echo vs_e(isset($row['Name']) ? $row['Name'] : ''); ?></td>
                        <td><?php echo vs_e(isset($row['Type']) ? $row['Type'] : ''); ?></td>
                        <td><?php echo vs_e(isset($row['Content']) ? $row['Content'] : ''); ?></td>
                        <td><?php echo vs_e(isset($row['TTL']) ? $row['TTL'] : ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>window.VS_EDGEONE_API = <?php echo json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE); ?>;</script>
<?php vs_admin_layout_end(array('edgeone-admin.js')); ?>
