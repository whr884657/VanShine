<?php
/**
 * 文件：d/guard.php
 * 作用：分享入口安全提醒（无有效 token 时）
 * @version 1.0.53
 */

define('VS_ROOT', dirname(__DIR__));
require_once VS_ROOT . '/core/bootstrap.php';

if (!InstallChecker::isInstalled()) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>服务不可用</title></head>';
    echo '<body><p>系统尚未安装或暂不可用。</p></body></html>';
    exit;
}

$siteName = SiteContext::siteName();
http_response_code(403);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>访问受限 - <?php echo vs_e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo vs_e(vs_base_url()); ?>/assets/css/share-public.css?v=<?php echo VS_VERSION; ?>">
</head>
<body class="vs-share-guard">
<div class="vs-share-guard__card">
    <div class="vs-share-guard__icon" aria-hidden="true">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L3 7v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V7l-9-5z" stroke="#dc2626" stroke-width="1.5"/>
            <path d="M12 8v5M12 16h.01" stroke="#dc2626" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </div>
    <h1 class="vs-share-guard__title">安全访问提醒</h1>
    <p class="vs-share-guard__lead">您正在访问 <?php echo vs_e($siteName); ?> 的受保护分享入口。</p>
    <ul class="vs-share-guard__list">
        <li>本页面仅供持有<strong>有效分享短链接</strong>的用户访问文件或文件夹。</li>
        <li>未经授权的探测、扫描、批量请求将被记录并可能触发防护策略。</li>
        <li>请勿尝试猜测或穷举链接参数，此类行为可能违反相关法律法规。</li>
        <li>云储存资源均经服务器中转，<strong>不会</strong>直接暴露原始储存地址与密钥信息。</li>
    </ul>
    <p class="vs-share-guard__foot">如果您拥有分享链接，请使用完整短链接访问。如有疑问，请联系分享者。</p>
</div>
</body>
</html>
