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
$certIndex = array();

if ($eo !== null && $zoneId !== '') {
    if ($canManage) {
        $pageData = vs_edgeone_fetch_domains_page_data($eo, $zoneId);
        $domains = $pageData['domains'];
        $domainsError = $pageData['error'];
        $ddosData = $pageData['ddos'];
        $certIndex = vs_edgeone_fetch_zone_cert_index($eo, $zoneId);
    }
    $ddosLabel = vs_edgeone_fetch_zone_ddos_label($eo, $zoneId);
}
?>

<div id="edgeoneDomainsPage">
    <?php echo vs_edgeone_render_domain_site_panel($zones, $zoneId, $currentZone, $ddosLabel, $canManage); ?>

    <?php echo vs_edgeone_render_domain_list_panel($domains, $ddosData, $domainsError, $zoneId, $canManage, $certIndex); ?>

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
