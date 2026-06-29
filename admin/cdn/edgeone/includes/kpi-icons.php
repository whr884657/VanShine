<?php
/**
 * 文件：admin/cdn/edgeone/includes/kpi-icons.php
 * 作用：概览 KPI 内联 SVG 图标（无外链）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

/**
 * @param string $name flux|out|in|request|cache|bandwidth|shield|rate
 * @return string
 */
function vs_edgeone_kpi_icon_svg($name)
{
    $icons = array(
        'flux' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M4 18V6"/><path d="M8 18v-6"/><path d="M12 18V10"/><path d="M16 18v-9"/><path d="M20 18V4"/></svg>',
        'out'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v10"/><path d="m8 9 4-4 4 4"/><path d="M5 21h14"/></svg>',
        'in'   => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V11"/><path d="m8 15 4 4 4-4"/><path d="M5 3h14"/></svg>',
        'request' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 1.5"/></svg>',
        'cache' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"><path d="M5 8h14l-1.5 9H6.5L5 8z"/><path d="M9 8V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M9 12h6"/></svg>',
        'bandwidth' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.93 19.07 1.41-1.41"/><path d="m17.66 6.34 1.41-1.41"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/></svg>',
        'rate' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"><path d="M4 18h16"/><path d="M7 16l3-8 3 5 3-9 3 6"/></svg>',
    );

    $svg = isset($icons[$name]) ? $icons[$name] : $icons['flux'];

    return '<span class="vs-edgeone-kpi__icon" aria-hidden="true">' . $svg . '</span>';
}
