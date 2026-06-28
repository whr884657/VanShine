<?php
/**
 * 文件：admin/cdn/edgeone/includes/nav.php
 * @param string $active
 * @return void
 */
function vs_edgeone_nav($active)
{
    $base = vs_base_url() . '/admin/cdn/edgeone';
    $items = array(
        'cdn_edgeone'         => array('概览', $base . '/index.php'),
        'cdn_edgeone_zones'   => array('站点管理', $base . '/zones.php'),
        'cdn_edgeone_domains' => array('域名加速', $base . '/domains.php'),
        'cdn_edgeone_content' => array('内容管理', $base . '/content.php'),
        'cdn_edgeone_security'=> array('安全加速', $base . '/security.php'),
        'cdn_edgeone_edge'    => array('边缘函数', $base . '/edge.php'),
        'cdn_edgeone_l4'      => array('四层代理', $base . '/l4.php'),
        'cdn_edgeone_monitor' => array('监控日志', $base . '/monitor.php'),
    );

    echo '<nav class="vs-edgeone-nav" aria-label="EdgeOne 功能导航">';
    foreach ($items as $id => $item) {
        $cls = 'vs-edgeone-nav__link' . ($id === $active ? ' is-active' : '');
        echo '<a class="' . $cls . '" href="' . vs_e($item[1]) . '">' . vs_e($item[0]) . '</a>';
    }
    echo '</nav>';
}
