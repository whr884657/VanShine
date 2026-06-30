<?php
/**
 * 文件：admin/cdn/edgeone/rules.php
 * 页面：EdgeOne · 规则引擎（站点加速规则引擎）
 * 路由：/admin/cdn/edgeone/rules.php
 * 菜单：cdn_edgeone_rules（站点与域名 → 规则引擎）
 *
 * 作用：
 * - 展示当前站点下的 L7 加速规则列表（对齐腾讯云「规则引擎」命名）
 * - 支持拖拽调整优先级、启停、编辑、复制、删除与搜索
 * - POST 请求转发至 api.php（set_zone、rule_list_refresh、rule_save 等）
 *
 * 说明：
 * - 渲染逻辑在 includes/rules-page.php；列表交互 edgeone-admin.js，编辑器 edgeone-rules-editor.js
 * - Pages 云端托管站点不支持自助管理，仅展示提示
 * - 规则优先级：列表自上而下执行，越靠下优先级越高
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/nav.php';
require_once __DIR__ . '/includes/page.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit;
}

$ctx = vs_edgeone_page_start('cdn_edgeone_rules', 'EdgeOne · 规则引擎');
$eo = $ctx['eo'];
$zones = $ctx['zones'];
$zoneId = $ctx['zone_id'];
$currentZone = vs_edgeone_find_zone_by_id($zones, $zoneId);
$canManage = $currentZone !== null && vs_edgeone_zone_supports_self_service_ops($currentZone);

$rules = array();
$rulesError = '';

if ($eo !== null && $zoneId !== '' && $canManage) {
    $pageData = vs_edgeone_fetch_l7_rules_data($eo, $zoneId);
    $rules = $pageData['rules'];
    $rulesError = $pageData['error'];
}
?>

<div id="edgeoneRulesPage">
    <?php echo vs_edgeone_render_rules_site_panel($zones, $zoneId, $currentZone, $canManage); ?>

    <?php echo vs_edgeone_render_rules_list_panel($rules, $rulesError, $zoneId, $canManage); ?>

    <?php
    if ($canManage) {
        echo vs_edgeone_render_rule_editor_shell();
    }
    ?>
</div>

<script type="application/json" id="edgeoneRulesCatalog"><?php
echo json_encode(vs_edgeone_rules_catalog_export(), JSON_UNESCAPED_UNICODE);
?></script>

<script type="application/json" id="edgeoneRuleRowsMeta"><?php
echo json_encode($rules, JSON_UNESCAPED_UNICODE);
?></script>

<?php vs_edgeone_page_end();
