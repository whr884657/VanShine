<?php
/**
 * 文件：admin/cdn/edgeone/includes/page.php
 * 作用：EdgeOne 后台页面公共渲染
 */

/**
 * @return array{eo: EdgeOne|null, zones: array, zone_id: string, error: string}
 */
function vs_edgeone_page_context()
{
    static $ctx = null;
    if ($ctx !== null) {
        return $ctx;
    }

    $ctx = array(
        'eo'      => null,
        'zones'   => array(),
        'zone_id' => vs_edgeone_selected_zone(),
        'error'   => '',
    );

    if (!vs_edgeone_is_ready()) {
        return $ctx;
    }

    try {
        $ctx['eo'] = EdgeOne::create();
        $ctx['zones'] = vs_edgeone_fetch_zones($ctx['eo']);
        if ($ctx['zone_id'] === '' && count($ctx['zones']) > 0 && isset($ctx['zones'][0]['ZoneId'])) {
            vs_edgeone_set_zone($ctx['zones'][0]['ZoneId']);
            $ctx['zone_id'] = vs_edgeone_selected_zone();
        }
    } catch (Exception $e) {
        $ctx['error'] = $e->getMessage();
    }

    return $ctx;
}

/**
 * @param string $navId
 * @param string $pageTitle
 * @return array{eo: EdgeOne|null, zones: array, zone_id: string, error: string}
 */
function vs_edgeone_page_start($navId, $pageTitle)
{
    global $vsBase;

    $ctx = vs_edgeone_page_context();
    vs_admin_layout_start($pageTitle, VS_EDGEONE_ACTIVE_MENU);
    echo '<link rel="stylesheet" href="' . vs_e($vsBase) . '/assets/css/edgeone-admin.css">';
    echo '<div class="vs-edgeone-page">';
    vs_edgeone_render_setup_notice();
    vs_edgeone_render_error($ctx['error']);
    vs_edgeone_nav($navId);
    if (vs_edgeone_is_ready() && count($ctx['zones']) > 0) {
        echo '<div class="vs-panel">';
        vs_edgeone_render_zone_picker($ctx['zones']);
        echo '</div>';
    }

    return $ctx;
}

/**
 * @return void
 */
function vs_edgeone_page_end()
{
    global $vsBase;
    echo '</div>';
    echo '<script>window.VS_EDGEONE_API = ' . json_encode($vsBase . '/admin/cdn/edgeone/api.php', JSON_UNESCAPED_UNICODE) . ';</script>';
    vs_admin_layout_end(array('edgeone-admin.js'));
}

/**
 * @param string               $title
 * @param array{ok: bool, data: mixed, error: string} $result
 * @param bool                 $requireZone
 * @param string               $zoneId
 * @param string               $emptyTip
 * @return void
 */
function vs_edgeone_render_section($title, array $result, $requireZone, $zoneId, $emptyTip = '暂无数据')
{
    echo '<div class="vs-panel">';
    echo '<h3 class="vs-panel__title">' . vs_e($title) . '</h3>';
    if ($requireZone && $zoneId === '') {
        echo '<p class="vs-form-tip">请先选择站点</p>';
    } elseif (!$result['ok']) {
        echo '<p class="vs-form-tip">加载失败：' . vs_e($result['error']) . '</p>';
    } else {
        $data = $result['data'];
        if (is_array($data)) {
            $isEmpty = count($data) === 0;
            if (!$isEmpty) {
                foreach ($data as $key => $val) {
                    if (is_array($val) && count($val) === 0) {
                        continue;
                    }
                    if ($key === 'RequestId') {
                        continue;
                    }
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty && !isset($data['RequestId'])) {
                echo '<p class="vs-form-tip">' . vs_e($emptyTip) . '</p>';
            } else {
                vs_edgeone_render_api_data($data);
            }
        } else {
            vs_edgeone_render_api_data($data);
        }
    }
    echo '</div>';
}

/**
 * @param EdgeOne|null $eo
 * @param string       $zoneId
 * @param array<int, array{title: string, require_zone?: bool, empty_tip?: string, fetch: callable}> $sections
 * @return void
 */
function vs_edgeone_render_sections($eo, $zoneId, array $sections)
{
    if ($eo === null) {
        return;
    }

    foreach ($sections as $section) {
        $requireZone = !isset($section['require_zone']) || $section['require_zone'];
        if ($requireZone && $zoneId === '') {
            vs_edgeone_render_section($section['title'], array('ok' => true, 'data' => array(), 'error' => ''), true, '');
            continue;
        }

        $result = vs_edgeone_try_call(function () use ($section, $eo, $zoneId) {
            return call_user_func($section['fetch'], $eo, $zoneId);
        });
        $emptyTip = isset($section['empty_tip']) ? $section['empty_tip'] : '暂无数据';
        vs_edgeone_render_section($section['title'], $result, $requireZone, $zoneId, $emptyTip);
    }
}
