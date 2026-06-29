<?php
/**
 * 文件：admin/cdn/edgeone/includes/kpi-icons.php
 * 作用：概览 KPI 内联 SVG 图标（无外链）
 */

/**
 * @param string $name flux|out|in|request|cache
 * @return string
 */
function vs_edgeone_kpi_icon_svg($name)
{
    $icons = array(
        'flux' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18V6"/><path d="M8 18v-5"/><path d="M12 18V9"/><path d="M16 18v-7"/><path d="M20 18V4"/></svg>',
        'out'  => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/></svg>',
        'in'   => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21V9"/><path d="m7 14 5 5 5-5"/><path d="M5 3h14"/></svg>',
        'request' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>',
        'cache' => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16l-2 10H6L4 7z"/><path d="M9 11h6"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
    );

    $svg = isset($icons[$name]) ? $icons[$name] : $icons['flux'];

    return '<span class="vs-edgeone-kpi__icon" aria-hidden="true">' . $svg . '</span>';
}
