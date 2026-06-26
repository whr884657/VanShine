<?php
/**
 * 文件：admin/includes/storage_settings.php
 * 作用：系统设置 — 储存配置折叠板块
 * @version 1.0.31
 */

/**
 * 渲染储存配置表单
 *
 * @return void
 */
function vs_settings_render_storage_section()
{
    $namingMode = Config::get('upload_naming_mode', 'sequence');
    $modes = UploadNaming::modes();

    vs_admin_accordion_start(
        'settings-storage',
        '储存配置',
        '七种储存可同时启用；上传命名方式全局统一，跨储存递增'
    );
    ?>
    <form method="post" action="" class="vs-form" id="storageForm" data-ajax="1">
        <input type="hidden" name="action" value="save_storage">

        <div class="vs-form-row">
            <label class="vs-label">上传文件命名方式</label>
            <select name="upload_naming_mode" class="vs-input">
                <?php foreach ($modes as $value => $label): ?>
                    <option value="<?php echo vs_e($value); ?>" <?php echo $namingMode === $value ? 'selected' : ''; ?>>
                        <?php echo vs_e($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="vs-form-tip">命名规则全局统一：例如递增模式下，本地已上传到 11，再在腾讯云上传会得到第 12 个文件名。</p>
        </div>

        <hr class="vs-divider">

        <?php foreach (StorageRegistry::types() as $key => $type): ?>
            <?php
            vs_admin_accordion_start(
                'storage-type-' . $type['slug'],
                $type['name'],
                '策略 KEY ' . (int) $key . '，填写后勾选启用即可参与文件管理绑定'
            );
            ?>
            <?php foreach ($type['fields'] as $field):
                $dbKey = StorageRegistry::configDbKey($key, $field['key']);
                $postKey = 'cfg_' . $type['slug'] . '_' . $field['key'];
                $value = Config::get($dbKey, '');
                ?>
                <div class="vs-form-row">
                    <?php if ($field['type'] === 'checkbox'): ?>
                        <label class="vs-checkbox">
                            <input type="checkbox" name="<?php echo vs_e($postKey); ?>" value="1"
                                <?php echo $value === '1' ? 'checked' : ''; ?>>
                            <span><?php echo vs_e($field['label']); ?></span>
                        </label>
                    <?php else: ?>
                        <label class="vs-label"><?php echo vs_e($field['label']); ?></label>
                        <?php if ($field['type'] === 'password'): ?>
                            <input type="password" name="<?php echo vs_e($postKey); ?>" class="vs-input"
                                   placeholder="留空则不修改">
                        <?php else: ?>
                            <input type="text" name="<?php echo vs_e($postKey); ?>" class="vs-input"
                                   value="<?php echo vs_e($value); ?>"
                                   placeholder="<?php echo vs_e(isset($field['placeholder']) ? $field['placeholder'] : ''); ?>">
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="vs-form-actions vs-form-actions--inline">
                <button type="button" class="vs-btn vs-btn--default vs-storage-test-btn"
                        data-storage-key="<?php echo (int) $key; ?>">测试连接</button>
            </div>
            <?php vs_admin_accordion_end(); ?>
        <?php endforeach; ?>

        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">保存储存设置</button>
        </div>
    </form>
    <?php
    vs_admin_accordion_end();
}
