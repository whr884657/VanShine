<?php
/**
 * 文件：admin/includes/cdn_settings.php
 * 作用：系统设置 — CDN 配置折叠板块
 *
 * 说明：系统版本以 core/version.php 中 VS_VERSION 为准。
 */

/**
 * @return void
 */
function vs_settings_render_cdn_section()
{
    TencentCloudConfig::migrateLegacyIfNeeded();

    $edgeEnabled = Config::get('cdn_edgeone_enabled', '0') === '1';
    $secretId = TencentCloudConfig::getSecretId();
    $secretKey = TencentCloudConfig::getSecretKey();
    $region = TencentCloudConfig::getEdgeOneRegion();
    $token = Config::get('cdn_edgeone_token', '');
    $language = Config::get('cdn_edgeone_language', 'zh-CN');
    $sharedHint = TencentCloudConfig::sharedStatusHint();

    vs_admin_accordion_start(
        'settings-cdn',
        'CDN 配置',
        '腾讯云 EdgeOne 与阿里云 ESA；腾讯云 API 密钥与 COS 储存共用'
    );
    ?>
    <p class="vs-form-tip vs-form-tip--highlight"><?php echo vs_e($sharedHint); ?></p>

    <form method="post" action="" class="vs-form" id="cdnForm" data-ajax="1">
        <input type="hidden" name="action" value="save_cdn">

        <?php
        vs_admin_accordion_start(
            'cdn-type-edgeone',
            '腾讯云 EdgeOne',
            '站点加速、刷新预热、DNS、安全策略等；需 SecretId / SecretKey / Region',
            $edgeEnabled,
            true
        );
        ?>
        <p class="vs-form-tip">
            还没有账号？
            <a href="https://cloud.tencent.com/product/teo" target="_blank" rel="noopener noreferrer" class="vs-link-register">前往腾讯云 EdgeOne</a>
        </p>
        <div class="vs-form-row">
            <label class="vs-checkbox">
                <input type="checkbox" name="cdn_edgeone_enabled" value="1" <?php echo $edgeEnabled ? 'checked' : ''; ?>>
                <span>启用腾讯云 EdgeOne CDN</span>
            </label>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">SecretId 密钥 ID（与 COS 共用）</label>
            <input type="text" name="cdn_tencent_secret_id" class="vs-input"
                   value="<?php echo vs_e($secretId); ?>"
                   placeholder="AKIDxxxxxxxx">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">SecretKey 密钥 Key（与 COS 共用）</label>
            <input type="text" name="cdn_tencent_secret_key" class="vs-input"
                   value="<?php echo vs_e($secretKey); ?>">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">地域 Region（EdgeOne API，与 COS 桶地域独立）</label>
            <input type="text" name="cdn_tencent_region" class="vs-input"
                   value="<?php echo vs_e($region); ?>"
                   placeholder="ap-guangzhou">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">临时密钥 Token（可选）</label>
            <input type="text" name="cdn_edgeone_token" class="vs-input"
                   value="<?php echo vs_e($token); ?>">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">API 语言</label>
            <select name="cdn_edgeone_language" class="vs-input">
                <option value="zh-CN" <?php echo $language === 'zh-CN' ? 'selected' : ''; ?>>简体中文</option>
                <option value="en-US" <?php echo $language === 'en-US' ? 'selected' : ''; ?>>English</option>
            </select>
        </div>
        <div class="vs-form-actions vs-form-actions--inline">
            <button type="button" class="vs-btn vs-btn--default" id="cdnEdgeOneTestBtn">测试 EdgeOne 连接</button>
        </div>
        <?php vs_admin_accordion_end(); ?>

        <?php
        vs_admin_accordion_start(
            'cdn-type-esa',
            '阿里云 ESA',
            '边缘安全加速（开发中）',
            false,
            true
        );
        ?>
        <p class="vs-form-tip">阿里云 ESA 对接尚未开放，敬请期待。</p>
        <?php vs_admin_accordion_end(); ?>

        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">保存 CDN 设置</button>
        </div>
    </form>
    <?php
    vs_admin_accordion_end();
}
