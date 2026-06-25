<?php
/**
 * 文件：admin/upgrade.php
 * 作用：VanShine 系统升级（手动检测、更新、更新记录）
 * @version 1.0.17
 */

require_once __DIR__ . '/init.php';

$localVersion = VS_VERSION;
$updateHistory = UpdateLog::payloadForApi();

vs_admin_layout_start('系统升级', 'upgrade');
?>

<div class="vs-panel vs-upgrade-panel">
    <div class="vs-panel__header">
        <h2 class="vs-panel__title">版本与更新</h2>
        <p class="vs-panel__desc">从 Gitee 仓库检测最新版本，支持手动更新。更新不会替换 config/database.php；仅当版本记录标明含数据库变更时才会执行结构更新 SQL。</p>
    </div>

    <div class="vs-upgrade-current">
        <span class="vs-upgrade-current__label">当前版本</span>
        <span class="vs-upgrade-current__value">v<?php echo vs_e($localVersion); ?></span>
    </div>

    <div id="upgradeStatus" class="vs-alert" role="alert" hidden></div>

    <div class="vs-upgrade-actions">
        <button type="button" class="vs-btn vs-btn--primary" id="upgradeCheckBtn">检测更新</button>
        <button type="button" class="vs-btn vs-btn--default" id="upgradeApplyBtn">安装更新</button>
    </div>

    <p class="vs-form-tip vs-form-tip--block">更新前请备份数据库与网站文件。点击「安装更新」后将进行二次备份确认。</p>
</div>

<div class="vs-panel vs-panel--spaced vs-upgrade-log-panel">
    <div class="vs-panel__header">
        <h2 class="vs-panel__title">更新记录</h2>
        <p class="vs-panel__desc">各版本更新说明（来源：update-log.json，与 Gitee 仓库同步）</p>
    </div>

    <?php if (count($updateHistory) === 0): ?>
        <p class="vs-form-tip">暂无更新记录</p>
    <?php else: ?>
        <div class="vs-upgrade-log">
            <?php foreach ($updateHistory as $row): ?>
                <article class="vs-upgrade-log__item">
                    <div class="vs-upgrade-log__head">
                        <h3 class="vs-upgrade-log__ver">v<?php echo vs_e($row['version']); ?></h3>
                        <?php if ($row['date'] !== ''): ?>
                            <span class="vs-upgrade-log__date"><?php echo vs_e($row['date']); ?></span>
                        <?php endif; ?>
                        <?php if ($row['db_changes']): ?>
                            <span class="vs-upgrade-log__badge">含数据库变更</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($row['title'] !== ''): ?>
                        <p class="vs-upgrade-log__title"><?php echo vs_e($row['title']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($row['changes'])): ?>
                        <ul class="vs-upgrade-log__list">
                            <?php foreach ($row['changes'] as $line): ?>
                                <li><?php echo vs_e($line); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php vs_admin_layout_end(array('upgrade.js')); ?>
