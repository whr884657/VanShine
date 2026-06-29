<?php
/**
 * 文件：admin/index.php
 * 作用：VanShine 管理员后台首页（控制台）
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

require_once __DIR__ . '/init.php';

$mailEnabled = Config::isMailEnabled();
$siteDesc = SiteContext::siteDescription();

vs_admin_layout_start('控制台', 'dashboard');
?>

<div class="vs-panel">
    <div class="vs-panel__header">
        <h2 class="vs-panel__title">欢迎回来，<?php echo vs_e($vsAdmin ? $vsAdmin['username'] : '管理员'); ?></h2>
        <p class="vs-panel__desc"><?php echo vs_e($siteDesc !== '' ? $siteDesc : 'VanShine 管理控制台'); ?></p>
    </div>

    <?php if (!$mailEnabled): ?>
        <div class="vs-alert vs-alert--warning">
            邮箱发信尚未配置，忘记密码功能不可用。
            <a href="<?php echo vs_e($vsBase); ?>/admin/settings.php">前往系统设置</a>
        </div>
    <?php endif; ?>

    <div class="vs-stat-grid">
        <div class="vs-stat-card">
            <span class="vs-stat-card__label">系统名称</span>
            <span class="vs-stat-card__value"><?php echo vs_e($vsSiteName); ?></span>
        </div>
        <div class="vs-stat-card">
            <span class="vs-stat-card__label">系统版本</span>
            <span class="vs-stat-card__value">v<?php echo vs_e(VS_VERSION); ?></span>
        </div>
        <div class="vs-stat-card">
            <span class="vs-stat-card__label">登录邮箱</span>
            <span class="vs-stat-card__value"><?php echo vs_e($vsAdmin ? $vsAdmin['email'] : '-'); ?></span>
        </div>
        <div class="vs-stat-card">
            <span class="vs-stat-card__label">无操作超时</span>
            <span class="vs-stat-card__value"><?php echo (int) (Config::sessionTimeout() / 60); ?> 分钟（系统固定）</span>
        </div>
    </div>
</div>

<?php vs_admin_layout_end(); ?>
