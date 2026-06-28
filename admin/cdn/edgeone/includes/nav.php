<?php
/**
 * 文件：admin/cdn/edgeone/includes/nav.php
 * @param string $active
 * @return void
 */
function vs_edgeone_nav($active)
{
    $base = vs_base_url() . '/admin/cdn/edgeone';
    $groups = array(
        '总览' => array(
            'cdn_edgeone' => array('概览', $base . '/index.php'),
        ),
        '站点与域名' => array(
            'cdn_edgeone_zones'   => array('站点管理', $base . '/zones.php'),
            'cdn_edgeone_domains' => array('域名加速', $base . '/domains.php'),
            'cdn_edgeone_l7'      => array('七层加速', $base . '/l7.php'),
            'cdn_edgeone_alias'   => array('别称域名', $base . '/alias.php'),
            'cdn_edgeone_dns'     => array('DNS 记录', $base . '/dns.php'),
        ),
        '内容与函数' => array(
            'cdn_edgeone_content' => array('内容管理', $base . '/content.php'),
            'cdn_edgeone_edge'    => array('边缘函数', $base . '/edge.php'),
        ),
        '安全与证书' => array(
            'cdn_edgeone_security' => array('安全策略', $base . '/security.php'),
            'cdn_edgeone_cert'     => array('证书管理', $base . '/cert.php'),
            'cdn_edgeone_origin'   => array('源站防护', $base . '/origin.php'),
        ),
        '网络与负载' => array(
            'cdn_edgeone_l4' => array('四层代理', $base . '/l4.php'),
            'cdn_edgeone_lb' => array('负载均衡', $base . '/lb.php'),
        ),
        '监控与计费' => array(
            'cdn_edgeone_analytics' => array('数据分析', $base . '/analytics.php'),
            'cdn_edgeone_logs'      => array('日志服务', $base . '/logs.php'),
            'cdn_edgeone_billing'   => array('套餐计费', $base . '/billing.php'),
        ),
        '配置与扩展' => array(
            'cdn_edgeone_config'   => array('配置版本', $base . '/config.php'),
            'cdn_edgeone_advanced' => array('扩展功能', $base . '/advanced.php'),
        ),
    );

    echo '<nav class="vs-edgeone-nav" aria-label="EdgeOne 功能导航">';
    foreach ($groups as $label => $items) {
        echo '<div class="vs-edgeone-nav__group">';
        echo '<span class="vs-edgeone-nav__label">' . vs_e($label) . '</span>';
        echo '<div class="vs-edgeone-nav__links">';
        foreach ($items as $id => $item) {
            $cls = 'vs-edgeone-nav__link' . ($id === $active ? ' is-active' : '');
            echo '<a class="' . $cls . '" href="' . vs_e($item[1]) . '">' . vs_e($item[0]) . '</a>';
        }
        echo '</div></div>';
    }
    echo '</nav>';
}
