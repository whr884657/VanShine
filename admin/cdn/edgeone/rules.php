<?php
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
        echo vs_edgeone_render_rule_drawers();
    }
    ?>
</div>

<script type="application/json" id="edgeoneRuleRowsMeta"><?php
echo json_encode($rules, JSON_UNESCAPED_UNICODE);
?></script>

<?php vs_edgeone_page_end();
