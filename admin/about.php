<?php
/**
 * 文件：admin/about.php
 * 作用：VanShine 后台关于页面（系统与环境信息）
 * @version 1.0.24
 */

require_once __DIR__ . '/init.php';

$systemInfo = SystemInfo::collect();
$updateCheck = Updater::checkForUpdate();

vs_admin_layout_start('关于', 'about');
?>

<div class="vs-panel">
    <div class="vs-panel__header">
        <h2 class="vs-panel__title"><?php echo vs_e(SiteContext::siteName()); ?></h2>
        <p class="vs-panel__desc">VanShine 管理系统</p>
    </div>

    <div class="vs-info-grid">
        <?php foreach ($systemInfo as $item): ?>
            <?php
            $wideClass = !empty($item['wide']) ? ' vs-info-item--wide' : '';
            ?>
            <div class="vs-info-item<?php echo $wideClass; ?>">
                <span class="vs-info-item__label"><?php echo vs_e($item['label']); ?></span>
                <span class="vs-info-item__value">
                    <?php if ($item['label'] === '系统版本'): ?>
                        <?php echo vs_render_version_display($updateCheck); ?>
                    <?php else: ?>
                        <?php echo vs_e($item['value']); ?>
                    <?php endif; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php vs_admin_layout_end(); ?>
