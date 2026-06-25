<?php
/**
 * 文件：admin/about.php
 * 作用：VanShine 后台关于页面（系统与环境信息）
 * @version 1.0.5
 */

require_once __DIR__ . '/init.php';

$systemInfo = SystemInfo::collect();

vs_admin_layout_start('关于', 'about');
?>

<div class="vs-panel">
    <div class="vs-panel__header">
        <h2 class="vs-panel__title"><?php echo vs_e(SiteContext::siteName()); ?></h2>
        <p class="vs-panel__desc">VanShine 管理系统 · 版本 v<?php echo vs_e(VS_VERSION); ?></p>
    </div>

    <div class="vs-info-grid">
        <?php foreach ($systemInfo as $item): ?>
            <div class="vs-info-item">
                <span class="vs-info-item__label"><?php echo vs_e($item['label']); ?></span>
                <span class="vs-info-item__value"><?php echo vs_e($item['value']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php vs_admin_layout_end(); ?>
