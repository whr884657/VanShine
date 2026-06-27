<?php
/**
 * 文件：d/guard.php
 * 作用：分享入口安全提醒（无有效 token 时）
 * @version 1.0.60
 */

require __DIR__ . '/boot.php';

if (!InstallChecker::isInstalled()) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>服务不可用</title></head>';
    echo '<body><p>系统尚未安装或暂不可用。</p></body></html>';
    exit;
}

$siteName = SiteContext::siteName();
$base = vs_base_url();

http_response_code(403);
vs_share_send_security_headers(false);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>访问受限 - <?php echo vs_e($siteName); ?></title>
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/common.css?v=<?php echo VS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo vs_e($base); ?>/assets/css/share-public.css?v=<?php echo VS_VERSION; ?>">
</head>
<body class="vs-share-page vs-share-page--guard">
<div class="vs-share-shell">
    <header class="vs-share-shell__topbar">
        <div class="vs-share-shell__brand">
            <?php if (SiteContext::siteLogo() !== '') { ?>
                <?php vs_render_site_logo('vs-share-shell__logo'); ?>
            <?php } ?>
            <span class="vs-share-shell__site"><?php echo vs_e($siteName); ?></span>
        </div>
        <span class="vs-share-shell__badge vs-share-shell__badge--warn">安全提示</span>
    </header>

    <div class="vs-share-shell__hero vs-share-shell__hero--compact">
        <div class="vs-share-guard__icon" aria-hidden="true"></div>
        <h1 class="vs-share-shell__title">安全访问提醒</h1>
        <p class="vs-share-guard__lead">您正在访问 <strong><?php echo vs_e($siteName); ?></strong> 的受保护分享入口，本页面不对公众开放目录浏览。</p>
    </div>

    <main class="vs-share-shell__main">
        <section class="vs-share-guard__block">
            <h2 class="vs-share-guard__heading">访问须知</h2>
            <ul class="vs-share-guard__list">
                <li>仅持有<strong>完整、有效分享短链接</strong>的用户方可访问对应内容。</li>
                <li>请勿尝试猜测、穷举、扫描或批量请求链接参数；异常访问将被记录。</li>
                <li>禁止对分享内容进行未授权的复制、传播、改作或用于商业用途（除非分享者明确授权）。</li>
                <li>请妥善保管您获得的分享链接，勿向无关人员转发，避免信息泄露。</li>
            </ul>
        </section>

        <section class="vs-share-guard__block">
            <h2 class="vs-share-guard__heading">法律法规提示</h2>
            <ul class="vs-share-guard__list">
                <li>《中华人民共和国<strong>网络安全法</strong>》：任何个人和组织不得从事非法侵入他人网络、干扰他人网络正常功能、窃取网络数据等危害网络安全的活动。</li>
                <li>《中华人民共和国<strong>数据安全法</strong>》《<strong>个人信息保护法</strong>》：收集、处理、传输数据应合法正当，不得非法获取或泄露他人信息。</li>
                <li>《中华人民共和国<strong>著作权法</strong>》及相关规定：未经授权不得复制、发行、通过信息网络传播他人享有著作权的作品。</li>
                <li>《中华人民共和国<strong>刑法</strong>》第二百八十五条等：违反国家规定侵入计算机信息系统，或提供专门用于侵入、非法控制程序、工具，情节严重的，依法追究刑事责任。</li>
            </ul>
            <p class="vs-share-guard__note">违反上述法律法规的行为，可能承担民事赔偿、行政处罚或刑事责任。本站保留向有关主管部门报告及追究法律责任的权利。</p>
        </section>

        <p class="vs-share-guard__tip">若您持有合法分享链接，请使用分享者提供的完整地址访问。如有疑问，请联系内容分享者或站点管理员。</p>
    </main>

    <?php vs_render_share_footer($siteName); ?>
</div>
</body>
</html>
