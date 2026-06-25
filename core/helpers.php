<?php
/**
 * 文件：core/helpers.php
 * 作用：VanShine 通用辅助函数
 * @version 1.0.13
 */

/**
 * HTML 转义
 *
 * @param mixed $value
 * @return string
 */
function vs_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * 密码哈希（不可逆，算法不对外公开）
 *
 * @param string $password
 * @return string
 */
function vs_password_hash($password)
{
    return md5(md5($password));
}

/**
 * 获取站点根 URL
 *
 * @return string
 */
function vs_base_url()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $https ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    if (defined('VS_ROOT') && isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])), '/');
        $projectRoot = rtrim(str_replace('\\', '/', realpath(VS_ROOT)), '/');
        if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
            $path = substr($projectRoot, strlen($docRoot));
            $cached = rtrim($scheme . '://' . $host . $path, '/');
            return $cached;
        }
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = str_replace('\\', '/', dirname($script));
    $dir = preg_replace('#/(admin|install)(/.*)?$#', '', $dir);
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }
    $cached = rtrim($scheme . '://' . $host . $dir, '/');
    return $cached;
}

/**
 * 获取项目根路径
 *
 * @return string
 */
function vs_root_path()
{
    return VS_ROOT;
}

/**
 * 重定向
 *
 * @param string $url
 * @return void
 */
function vs_redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * 构建浏览器标题（避免页面名与站点名重复）
 *
 * @param string $pageTitle
 * @param string|null $siteName
 * @return string
 */
function vs_page_title($pageTitle, $siteName = null)
{
    if ($siteName === null) {
        if (class_exists('SiteContext') && InstallChecker::isInstalled()) {
            $siteName = SiteContext::siteName();
        } else {
            $siteName = 'VanShine';
        }
    }

    $pageTitle = trim((string) $pageTitle);
    $siteName = trim((string) $siteName);

    if ($siteName === '') {
        $siteName = 'VanShine';
    }

    if ($pageTitle === '' || $pageTitle === $siteName) {
        return $siteName;
    }

    $suffix = ' - ' . $siteName;
    if (strlen($pageTitle) >= strlen($suffix) && substr($pageTitle, -strlen($suffix)) === $suffix) {
        return $pageTitle;
    }

    return $pageTitle . $suffix;
}

/**
 * 渲染页面头部
 *
 * @param string $title
 * @param array  $cssFiles
 * @return void
 */
function vs_render_head($title, array $cssFiles = array(), $useSiteConfig = true)
{
    $base = vs_base_url();
    $siteName = 'VanShine';
    $favicon = '';
    $keywords = '';
    $description = '';

    if ($useSiteConfig && class_exists('InstallChecker') && InstallChecker::isInstalled()) {
        $siteName = SiteContext::siteName();
        $favicon = SiteContext::siteFavicon();
        $keywords = SiteContext::siteKeywords();
        $description = SiteContext::siteDescription();
    }

    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="zh-CN">' . "\n";
    echo '<head>' . "\n";
    echo '<meta charset="UTF-8">' . "\n";
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    if ($description !== '') {
        echo '<meta name="description" content="' . vs_e($description) . '">' . "\n";
    }
    if ($keywords !== '') {
        echo '<meta name="keywords" content="' . vs_e($keywords) . '">' . "\n";
    }
    echo '<title>' . vs_e(vs_page_title($title, $siteName)) . '</title>' . "\n";
    if ($favicon !== '') {
        echo '<link rel="icon" href="' . vs_e(vs_favicon_href($favicon)) . '">' . "\n";
    }
    echo '<link rel="stylesheet" href="' . vs_e($base) . '/assets/css/common.css?v=' . VS_VERSION . '">' . "\n";
    echo '<link rel="stylesheet" href="' . vs_e($base) . '/assets/css/modal.css?v=' . VS_VERSION . '">' . "\n";
    echo '<link rel="stylesheet" href="' . vs_e($base) . '/assets/css/icons.css?v=' . VS_VERSION . '">' . "\n";
    foreach ($cssFiles as $css) {
        echo '<link rel="stylesheet" href="' . vs_e($base) . '/assets/css/' . vs_e($css) . '?v=' . VS_VERSION . '">' . "\n";
    }
    echo '</head>' . "\n";
    echo '<body class="vs-body">' . "\n";
}

/**
 * 渲染页面底部
 *
 * @param array $jsFiles
 * @return void
 */
function vs_render_foot(array $jsFiles = array())
{
    $base = vs_base_url();
    vs_render_modal_shell();
    echo '<script>window.VS_BASE_URL = ' . json_encode($base) . ';</script>' . "\n";
    echo '<script src="' . vs_e($base) . '/assets/js/modal.js?v=' . VS_VERSION . '"></script>' . "\n";
    echo '<script src="' . vs_e($base) . '/assets/js/common.js?v=' . VS_VERSION . '"></script>' . "\n";
    foreach ($jsFiles as $js) {
        echo '<script src="' . vs_e($base) . '/assets/js/' . vs_e($js) . '?v=' . VS_VERSION . '"></script>' . "\n";
    }
    echo '</body></html>';
}

/**
 * 解析 Favicon 地址（支持完整 URL 或站点相对路径）
 *
 * @param string $path
 * @return string
 */
function vs_favicon_href($path)
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $base = vs_base_url();
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

/**
 * 渲染站点 Logo 图片（未配置时不输出）
 *
 * @param string $class CSS 类名
 * @return void
 */
function vs_render_site_logo($class = 'vs-logo-icon')
{
    if (!class_exists('SiteContext')) {
        return;
    }

    $logo = trim(SiteContext::siteLogo());
    if ($logo === '') {
        return;
    }

    $href = vs_favicon_href($logo);
    if ($href === '') {
        return;
    }

    $classAttr = trim($class . ' vs-site-logo-img');
    echo '<img class="' . vs_e($classAttr) . '" src="' . vs_e($href) . '" alt="' . vs_e(SiteContext::siteName()) . '">';
}

/**
 * 渲染页脚备案信息
 *
 * @return void
 */
function vs_render_beian_footer()
{
    if (!InstallChecker::isInstalled()) {
        return;
    }

    $beian = SiteContext::beianInfo();
    if ($beian['icp_number'] === '' && $beian['gongan_number'] === '') {
        return;
    }

    echo '<div class="vs-beian">';
    if ($beian['icp_number'] !== '') {
        echo '<a href="' . vs_e($beian['icp_link']) . '" target="_blank" rel="noopener noreferrer">' . vs_e($beian['icp_number']) . '</a>';
    }
    if ($beian['gongan_number'] !== '') {
        if ($beian['icp_number'] !== '') {
            echo '<span class="vs-beian__sep">|</span>';
        }
        echo '<a href="' . vs_e($beian['gongan_link']) . '" target="_blank" rel="noopener noreferrer">' . vs_e($beian['gongan_number']) . '</a>';
    }
    echo '</div>';
}

/**
 * 渲染统一弹窗骨架（全站共用）
 *
 * @return void
 */
function vs_render_modal_shell()
{
    echo '<div class="vs-modal-root" id="vsModalRoot" hidden aria-hidden="true">' . "\n";
    echo '<div class="vs-modal-overlay" id="vsModalOverlay"></div>' . "\n";
    echo '<div class="vs-modal" role="dialog" aria-modal="true" aria-labelledby="vsModalTitle">' . "\n";
    echo '<div class="vs-modal__head"><h3 class="vs-modal__title" id="vsModalTitle"></h3></div>' . "\n";
    echo '<div class="vs-modal__body" id="vsModalBody"></div>' . "\n";
    echo '<div class="vs-modal__foot" id="vsModalFoot"></div>' . "\n";
    echo '</div></div>' . "\n";
}
