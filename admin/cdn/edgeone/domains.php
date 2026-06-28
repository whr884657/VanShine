<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_domains', 'EdgeOne · 域名加速');
$zoneId = $ctx['zone_id'];
$domains = array();
$domainsError = '';

if ($ctx['eo'] !== null && $zoneId !== '') {
    $dResult = vs_edgeone_try_call(function () use ($ctx, $zoneId) {
        return $ctx['eo']->accelerationDomain->describeAccelerationDomains(array(
            'ZoneId' => $zoneId,
            'Offset' => 0,
            'Limit'  => 100,
        ));
    }, array());
    if ($dResult['ok']) {
        $domains = isset($dResult['data']['AccelerationDomains']) ? $dResult['data']['AccelerationDomains'] : array();
    } else {
        $domainsError = $dResult['error'];
    }
}
?>

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
    <?php elseif ($domainsError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($domainsError); ?></p>
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
                        <td><?php echo vs_e(vs_edgeone_translate('DomainStatus', isset($row['DomainStatus']) ? $row['DomainStatus'] : '')); ?></td>
                        <td><code><?php echo vs_e(isset($row['Cname']) ? $row['Cname'] : '-'); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php vs_edgeone_page_end(); ?>
