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

    $activeGroup = '';
    foreach ($groups as $label => $items) {
        if (isset($items[$active])) {
            $activeGroup = $label;
            break;
        }
    }

    echo '<nav class="vs-edgeone-nav" aria-label="EdgeOne 功能导航">';

    echo '<div class="vs-edgeone-nav__desktop" role="menubar">';
    foreach ($groups as $label => $items) {
        $itemCount = count($items);
        $isSingle = $itemCount === 1;
        $groupActive = ($label === $activeGroup);
        $groupClass = 'vs-edgeone-nav__tab' . ($groupActive ? ' is-active' : '');

        if ($isSingle) {
            $id = key($items);
            $item = reset($items);
            $linkClass = 'vs-edgeone-nav__tab-link' . ($id === $active ? ' is-active' : '');
            echo '<div class="' . vs_e($groupClass) . '">';
            echo '<a class="' . vs_e($linkClass) . '" href="' . vs_e($item[1]) . '" role="menuitem">' . vs_e($item[0]) . '</a>';
            echo '</div>';
            continue;
        }

        echo '<div class="' . vs_e($groupClass) . ' vs-edgeone-nav__tab--dropdown">';
        echo '<button type="button" class="vs-edgeone-nav__tab-btn" aria-haspopup="true" aria-expanded="false">';
        echo vs_e($label);
        echo '<span class="vs-edgeone-nav__caret" aria-hidden="true"></span>';
        echo '</button>';
        echo '<div class="vs-edgeone-nav__dropdown" role="menu">';
        foreach ($items as $id => $item) {
            $linkClass = 'vs-edgeone-nav__dropdown-link' . ($id === $active ? ' is-active' : '');
            echo '<a class="' . vs_e($linkClass) . '" href="' . vs_e($item[1]) . '" role="menuitem">' . vs_e($item[0]) . '</a>';
        }
        echo '</div></div>';
    }
    echo '</div>';

    $activeItemLabel = '';
    if ($activeGroup !== '' && isset($groups[$activeGroup][$active])) {
        $activeItemLabel = $groups[$activeGroup][$active][0];
    }
    $drawerTitle = ($activeGroup !== '' && $activeItemLabel !== '')
        ? $activeGroup . ' · ' . $activeItemLabel
        : 'EdgeOne 功能导航';

    echo '<div class="vs-edgeone-nav__mobile">';
    echo '<div class="vs-edgeone-nav__drawer" id="edgeoneMobileNav">';
    echo '<button type="button" class="vs-edgeone-nav__drawer-toggle" aria-expanded="false" aria-controls="edgeoneMobileNavPanel">';
    echo '<span class="vs-edgeone-nav__drawer-current">' . vs_e($drawerTitle) . '</span>';
    echo '<span class="vs-edgeone-nav__caret" aria-hidden="true"></span>';
    echo '</button>';
    echo '<div class="vs-edgeone-nav__drawer-body" id="edgeoneMobileNavPanel">';

    foreach ($groups as $label => $items) {
        echo '<div class="vs-edgeone-nav__section">';
        echo '<button type="button" class="vs-edgeone-nav__section-btn" aria-expanded="false">';
        echo vs_e($label);
        echo '<span class="vs-edgeone-nav__caret" aria-hidden="true"></span>';
        echo '</button>';
        echo '<div class="vs-edgeone-nav__section-panel">';
        foreach ($items as $id => $item) {
            $linkClass = 'vs-edgeone-nav__drawer-sublink' . ($id === $active ? ' is-active' : '');
            echo '<a class="' . vs_e($linkClass) . '" href="' . vs_e($item[1]) . '">' . vs_e($item[0]) . '</a>';
        }
        echo '</div></div>';
    }

    echo '</div></div></div>';

    echo '</nav>';
}
