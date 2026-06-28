<?php
/**
 * 文件：admin/cdn/edgeone/security.php
 * 作用：EdgeOne 安全加速与证书
 * @version 1.0.1
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';

$zones = array();
$zoneId = '';
$security = null;
$certs = array();
$error = '';
$securityError = '';
$certsError = '';

if (vs_edgeone_is_ready()) {
    try {
        $eo = EdgeOne::create();
        $zones = vs_edgeone_fetch_zones($eo);
        $zoneId = vs_edgeone_selected_zone();
        if ($zoneId !== '') {
            $sResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->security->describeSecurityPolicy(array(
                    'ZoneId' => $zoneId,
                    'Entity' => '@ZoneLevel@domain',
                ));
            });
            if ($sResult['ok']) {
                $security = $sResult['data'];
            } else {
                $securityError = $sResult['error'];
            }

            $cResult = vs_edgeone_try_call(function () use ($eo, $zoneId) {
                return $eo->certificate->describeDefaultCertificates(array('ZoneId' => $zoneId));
            }, array());
            if ($cResult['ok']) {
                $certs = isset($cResult['data']['DefaultCertificates']) ? $cResult['data']['DefaultCertificates'] : array();
            } else {
                $certsError = $cResult['error'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

vs_admin_layout_start('EdgeOne · 安全加速', VS_EDGEONE_ACTIVE_MENU);
?>
<link rel="stylesheet" href="<?php echo vs_e($vsBase); ?>/assets/css/edgeone-admin.css">

<div class="vs-edgeone-page">
<?php vs_edgeone_render_setup_notice(); ?>
<?php vs_edgeone_render_error($error); ?>
<?php vs_edgeone_nav('cdn_edgeone_security'); ?>
<?php if (vs_edgeone_is_ready() && count($zones) > 0): ?><div class="vs-panel"><?php vs_edgeone_render_zone_picker($zones); ?></div><?php endif; ?>

<div class="vs-panel">
    <h3 class="vs-panel__title">Web 安全防护配置</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($securityError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($securityError); ?></p>
    <?php else: ?>
        <pre class="vs-edgeone-json"><?php echo vs_e(json_encode($security, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</div>

<div class="vs-panel">
    <h3 class="vs-panel__title">默认证书列表</h3>
    <?php if ($zoneId === ''): ?>
        <p class="vs-form-tip">请先选择站点</p>
    <?php elseif ($certsError !== ''): ?>
        <p class="vs-form-tip">加载失败：<?php echo vs_e($certsError); ?></p>
    <?php elseif (count($certs) === 0): ?>
        <p class="vs-form-tip">暂无证书数据</p>
    <?php else: ?>
        <div class="vs-table-wrap">
            <table class="vs-table">
                <thead><tr><th>证书 ID</th><th>域名</th><th>状态</th></tr></thead>
                <tbody>
                <?php foreach ($certs as $c): ?>
                    <tr>
                        <td><?php echo vs_e(isset($c['CertId']) ? $c['CertId'] : ''); ?></td>
                        <td><?php echo vs_e(isset($c['Host']) ? $c['Host'] : ''); ?></td>
                        <td><?php echo vs_e(vs_edgeone_translate('CertStatus', isset($c['Status']) ? $c['Status'] : '')); ?></td>
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
