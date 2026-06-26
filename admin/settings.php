<?php
/**
 * 文件：admin/settings.php
 * 作用：VanShine 后台系统设置（站点信息、域名绑定、邮箱发信）
 * @version 1.0.31
 */

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/storage_settings.php';

/**
 * 渲染绑定域名卡片列表
 *
 * @param array $domains
 * @return void
 */
function vs_settings_render_domain_list(array $domains)
{
    echo '<div class="vs-domain-list" id="domainsList">' . "\n";
    foreach ($domains as $row) {
        $icp = trim((string) $row['icp_number']);
        $gongan = trim((string) $row['gongan_number']);
        echo '<article class="vs-domain-card" data-domain-id="' . (int) $row['id'] . '">' . "\n";
        echo '<div class="vs-domain-card__grid">' . "\n";
        vs_settings_domain_cell('域名', $row['domain']);
        vs_settings_domain_cell('站点名称', $row['site_name']);
        vs_settings_domain_cell('ICP 备案号', $icp !== '' ? $icp : '未设置');
        vs_settings_domain_cell('公安备案号', $gongan !== '' ? $gongan : '未设置');
        echo '</div>' . "\n";
        echo '<div class="vs-domain-card__actions">' . "\n";
        echo '<a href="?edit_domain=' . (int) $row['id'] . '" class="vs-btn vs-btn--pill vs-btn--pill-primary">编辑</a>' . "\n";
        echo '<form method="post" class="vs-domain-delete-form" data-ajax="1">' . "\n";
        echo '<input type="hidden" name="action" value="delete_domain">' . "\n";
        echo '<input type="hidden" name="domain_id" value="' . (int) $row['id'] . '">' . "\n";
        echo '<button type="submit" class="vs-btn vs-btn--pill vs-btn--pill-danger">删除</button>' . "\n";
        echo '</form></div></article>' . "\n";
    }
    echo '</div>' . "\n";
}

/**
 * 域名卡片信息格
 *
 * @param string $label
 * @param string $value
 * @return void
 */
function vs_settings_domain_cell($label, $value)
{
    echo '<div class="vs-domain-card__cell">' . "\n";
    echo '<span class="vs-domain-card__label">' . vs_e($label) . '</span>' . "\n";
    echo '<span class="vs-domain-card__value">' . vs_e($value) . '</span>' . "\n";
    echo '</div>' . "\n";
}

/**
 * 域名列表供前端刷新表格
 *
 * @param array $domains
 * @return array
 */
function vs_settings_domains_payload(array $domains)
{
    $list = array();
    foreach ($domains as $row) {
        $list[] = array(
            'id'            => (int) $row['id'],
            'domain'        => $row['domain'],
            'site_name'     => $row['site_name'],
            'icp_number'    => $row['icp_number'],
            'gongan_number' => $row['gongan_number'],
        );
    }
    return $list;
}

$domains = Domain::all();
$editId = isset($_GET['edit_domain']) ? (int) $_GET['edit_domain'] : 0;
$editDomain = null;

if ($editId > 0) {
    foreach ($domains as $row) {
        if ((int) $row['id'] === $editId) {
            $editDomain = $row;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'save_site') {
        try {
            Config::setMany(array(
                'site_name'        => trim(isset($_POST['site_name']) ? $_POST['site_name'] : ''),
                'site_description' => trim(isset($_POST['site_description']) ? $_POST['site_description'] : ''),
                'site_keywords'    => trim(isset($_POST['site_keywords']) ? $_POST['site_keywords'] : ''),
                'site_favicon'     => trim(isset($_POST['site_favicon']) ? $_POST['site_favicon'] : ''),
                'site_logo'        => trim(isset($_POST['site_logo']) ? $_POST['site_logo'] : ''),
                'primary_domain'   => Domain::normalizeHost(isset($_POST['primary_domain']) ? $_POST['primary_domain'] : ''),
                'site_icp'         => trim(isset($_POST['site_icp']) ? $_POST['site_icp'] : ''),
                'site_gongan'      => trim(isset($_POST['site_gongan']) ? $_POST['site_gongan'] : ''),
            ));
            AjaxResponse::success('站点设置已保存');
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'add_domain') {
        try {
            Domain::create(array(
                'domain'         => isset($_POST['domain']) ? $_POST['domain'] : '',
                'site_name'      => isset($_POST['domain_site_name']) ? $_POST['domain_site_name'] : '',
                'icp_number'     => isset($_POST['domain_icp']) ? $_POST['domain_icp'] : '',
                'gongan_number'  => isset($_POST['domain_gongan']) ? $_POST['domain_gongan'] : '',
            ));
            AjaxResponse::success('绑定域名已添加', array(
                'domains' => vs_settings_domains_payload(Domain::all()),
            ));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'update_domain') {
        $id = (int) (isset($_POST['domain_id']) ? $_POST['domain_id'] : 0);
        try {
            Domain::update($id, array(
                'domain'         => isset($_POST['domain']) ? $_POST['domain'] : '',
                'site_name'      => isset($_POST['domain_site_name']) ? $_POST['domain_site_name'] : '',
                'icp_number'     => isset($_POST['domain_icp']) ? $_POST['domain_icp'] : '',
                'gongan_number'  => isset($_POST['domain_gongan']) ? $_POST['domain_gongan'] : '',
            ));
            AjaxResponse::success('绑定域名已更新', array(
                'domains' => vs_settings_domains_payload(Domain::all()),
                'clear_edit' => true,
            ));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'delete_domain') {
        $id = (int) (isset($_POST['domain_id']) ? $_POST['domain_id'] : 0);
        try {
            Domain::delete($id);
            AjaxResponse::success('绑定域名已删除', array(
                'domains' => vs_settings_domains_payload(Domain::all()),
            ));
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'save_storage') {
        try {
            $items = StorageRegistry::configsFromPost($_POST);
            Config::setMany($items);

            if (StorageRegistry::isEnabled(1)) {
                require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageOptions.php';
                require_once VS_ROOT . '/core/Storage/LocalStorage/LocalStorageDriver.php';
                LocalStorageDriver::cleanupLegacyGateway();
                LocalStorageDriver::refreshStoredPublicUrls();
            }

            AjaxResponse::success('储存设置已保存');
        } catch (Exception $e) {
            AjaxResponse::error('保存失败：' . $e->getMessage());
        }
    }

    if ($action === 'test_storage') {
        $storageKey = (int) (isset($_POST['storage_key']) ? $_POST['storage_key'] : 0);
        try {
            StorageRegistry::testConnection($storageKey, $_POST);
            $type = StorageRegistry::type($storageKey);
            $name = $type ? $type['name'] : '储存';
            AjaxResponse::success($name . ' 连接测试成功');
        } catch (Exception $e) {
            AjaxResponse::error($e->getMessage());
        }
    }

    if ($action === 'save_mail') {
        try {
            $smtpPass = isset($_POST['mail_smtp_pass']) ? $_POST['mail_smtp_pass'] : '';
            if ($smtpPass === '') {
                $smtpPass = Config::get('mail_smtp_pass');
            }

            Config::setMany(array(
                'mail_enabled'     => isset($_POST['mail_enabled']) ? '1' : '0',
                'mail_smtp_host'   => trim(isset($_POST['mail_smtp_host']) ? $_POST['mail_smtp_host'] : ''),
                'mail_smtp_port'   => trim(isset($_POST['mail_smtp_port']) ? $_POST['mail_smtp_port'] : '465'),
                'mail_smtp_user'   => trim(isset($_POST['mail_smtp_user']) ? $_POST['mail_smtp_user'] : ''),
                'mail_smtp_pass'   => $smtpPass,
                'mail_smtp_secure' => trim(isset($_POST['mail_smtp_secure']) ? $_POST['mail_smtp_secure'] : 'ssl'),
                'mail_from_email'  => trim(isset($_POST['mail_from_email']) ? $_POST['mail_from_email'] : ''),
                'mail_from_name'   => trim(isset($_POST['mail_from_name']) ? $_POST['mail_from_name'] : SiteContext::siteName()),
            ));

            AjaxResponse::success('邮箱设置已保存');
        } catch (Exception $e) {
            AjaxResponse::error('保存失败：' . $e->getMessage());
        }
    }

    if ($action === 'test_mail') {
        $testEmail = trim(isset($_POST['test_email']) ? $_POST['test_email'] : '');
        if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            AjaxResponse::error('请输入有效的测试邮箱地址');
        }
        try {
            Mailer::send(
                $testEmail,
                SiteContext::siteName() . ' 邮箱测试',
                '<p>这是一封来自 ' . htmlspecialchars(SiteContext::siteName()) . ' 的测试邮件。</p>'
            );
            AjaxResponse::success('测试邮件已发送，请查收');
        } catch (Exception $e) {
            AjaxResponse::error('发送失败：' . $e->getMessage());
        }
    }

    AjaxResponse::error('未知操作', 400);
}

Config::clearCache();
$vsCfg = Config::all();

vs_admin_layout_start('系统设置', 'settings');
?>

<div id="settingsFlash" class="vs-settings-flash" role="alert" hidden></div>

<?php
vs_admin_accordion_start(
    'settings-site',
    '主域名与站点信息',
    '主域名使用以下系统信息；未匹配到绑定子域名时也使用此信息'
);
?>
    <form method="post" action="" class="vs-form" id="siteForm" data-ajax="1">
        <input type="hidden" name="action" value="save_site">
        <div class="vs-form-row">
            <label class="vs-label">主绑定域名</label>
            <input type="text" name="primary_domain" class="vs-input"
                   value="<?php echo vs_e(Config::get('primary_domain', '')); ?>"
                   placeholder="例如 www.example.com">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">系统名称</label>
            <input type="text" name="site_name" class="vs-input" required maxlength="50"
                   value="<?php echo vs_e(Config::get('site_name', '')); ?>">
        </div>
        <div class="vs-form-grid">
            <div class="vs-form-row">
                <label class="vs-label">站点图标（Favicon）</label>
                <input type="text" name="site_favicon" class="vs-input"
                       value="<?php echo vs_e(Config::get('site_favicon', '')); ?>"
                       placeholder="/assets/img/favicon.ico 或 https://...">
                <p class="vs-form-tip">浏览器标签页小图标</p>
            </div>
            <div class="vs-form-row">
                <label class="vs-label">站点 Logo</label>
                <input type="text" name="site_logo" class="vs-input"
                       value="<?php echo vs_e(Config::get('site_logo', '')); ?>"
                       placeholder="/assets/img/logo.png 或 https://...">
                <p class="vs-form-tip">前台页眉、后台侧栏展示的站点图标</p>
            </div>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">系统描述</label>
            <textarea name="site_description" class="vs-textarea" rows="3"><?php echo vs_e(Config::get('site_description', '')); ?></textarea>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">关键词</label>
            <input type="text" name="site_keywords" class="vs-input"
                   value="<?php echo vs_e(Config::get('site_keywords', '')); ?>">
        </div>
        <div class="vs-form-grid">
            <div class="vs-form-row">
                <label class="vs-label">ICP 备案号（主域名）</label>
                <input type="text" name="site_icp" class="vs-input"
                       value="<?php echo vs_e(Config::get('site_icp', '')); ?>"
                       placeholder="例如 京ICP备12345678号">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">公安备案号（主域名）</label>
                <input type="text" name="site_gongan" class="vs-input"
                       value="<?php echo vs_e(Config::get('site_gongan', '')); ?>"
                       placeholder="例如 京公网安备11010802012345号">
            </div>
        </div>
        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">保存站点设置</button>
        </div>
    </form>
<?php vs_admin_accordion_end(); ?>

<?php
vs_admin_accordion_start(
    'settings-domains',
    '绑定子域名',
    '可为不同域名设置独立的站点名称与备案号，用户通过对应域名访问时自动展示',
    $editDomain !== null
);
?>
    <div id="domainsListWrap">
    <?php if (count($domains) > 0): ?>
        <?php vs_settings_render_domain_list($domains); ?>
    <?php else: ?>
        <p class="vs-form-tip" id="domainsEmptyTip">暂无绑定子域名，可在下方添加</p>
    <?php endif; ?>
    </div>

    <form method="post" action="" class="vs-form vs-form--spaced-top" id="domainForm" data-ajax="1">
        <input type="hidden" name="action" value="<?php echo $editDomain ? 'update_domain' : 'add_domain'; ?>">
        <?php if ($editDomain): ?>
            <input type="hidden" name="domain_id" value="<?php echo (int) $editDomain['id']; ?>">
        <?php endif; ?>
        <h4 class="vs-form-subtitle"><?php echo $editDomain ? '编辑绑定域名' : '添加绑定域名'; ?></h4>
        <div class="vs-form-grid">
            <div class="vs-form-row">
                <label class="vs-label">域名</label>
                <input type="text" name="domain" class="vs-input" required
                       value="<?php echo vs_e($editDomain ? $editDomain['domain'] : ''); ?>"
                       placeholder="sub.example.com">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">站点名称</label>
                <input type="text" name="domain_site_name" class="vs-input" required
                       value="<?php echo vs_e($editDomain ? $editDomain['site_name'] : ''); ?>">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">ICP 备案号</label>
                <input type="text" name="domain_icp" class="vs-input"
                       value="<?php echo vs_e($editDomain ? $editDomain['icp_number'] : ''); ?>">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">公安备案号</label>
                <input type="text" name="domain_gongan" class="vs-input"
                       value="<?php echo vs_e($editDomain ? $editDomain['gongan_number'] : ''); ?>">
            </div>
        </div>
        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary"><?php echo $editDomain ? '保存修改' : '添加域名'; ?></button>
            <?php if ($editDomain): ?>
                <a href="<?php echo vs_e($vsBase); ?>/admin/settings.php" class="vs-btn vs-btn--default">取消编辑</a>
            <?php endif; ?>
        </div>
    </form>
<?php vs_admin_accordion_end(); ?>

<?php
vs_admin_accordion_start(
    'settings-mail',
    '邮箱发信',
    '配置 SMTP 发信参数，并发送测试邮件验证'
);
?>
    <form method="post" action="" class="vs-form" id="mailForm" data-ajax="1">
        <input type="hidden" name="action" value="save_mail">
        <div class="vs-form-row">
            <label class="vs-checkbox">
                <input type="checkbox" name="mail_enabled" value="1" <?php echo (isset($vsCfg['mail_enabled']) && $vsCfg['mail_enabled'] === '1') ? 'checked' : ''; ?>>
                <span>启用邮箱发信</span>
            </label>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">SMTP 服务器</label>
            <input type="text" name="mail_smtp_host" class="vs-input" value="<?php echo vs_e(isset($vsCfg['mail_smtp_host']) ? $vsCfg['mail_smtp_host'] : ''); ?>">
        </div>
        <div class="vs-form-row vs-form-row--inline">
            <div class="vs-form-col">
                <label class="vs-label">SMTP 端口</label>
                <input type="text" name="mail_smtp_port" class="vs-input" value="<?php echo vs_e(isset($vsCfg['mail_smtp_port']) ? $vsCfg['mail_smtp_port'] : '465'); ?>">
            </div>
            <div class="vs-form-col">
                <label class="vs-label">加密方式</label>
                <select name="mail_smtp_secure" class="vs-input">
                    <option value="ssl" <?php echo (isset($vsCfg['mail_smtp_secure']) && $vsCfg['mail_smtp_secure'] === 'ssl') ? 'selected' : ''; ?>>SSL</option>
                    <option value="tls" <?php echo (isset($vsCfg['mail_smtp_secure']) && $vsCfg['mail_smtp_secure'] === 'tls') ? 'selected' : ''; ?>>TLS</option>
                    <option value="none" <?php echo (isset($vsCfg['mail_smtp_secure']) && $vsCfg['mail_smtp_secure'] === 'none') ? 'selected' : ''; ?>>无</option>
                </select>
            </div>
        </div>
        <div class="vs-form-row">
            <label class="vs-label">SMTP 用户名</label>
            <input type="text" name="mail_smtp_user" class="vs-input" value="<?php echo vs_e(isset($vsCfg['mail_smtp_user']) ? $vsCfg['mail_smtp_user'] : ''); ?>">
        </div>
        <div class="vs-form-row">
            <label class="vs-label">SMTP 密码</label>
            <input type="password" name="mail_smtp_pass" class="vs-input" placeholder="留空则不修改">
        </div>
        <div class="vs-form-row vs-form-row--inline">
            <div class="vs-form-col">
                <label class="vs-label">发件人邮箱</label>
                <input type="email" name="mail_from_email" class="vs-input" value="<?php echo vs_e(isset($vsCfg['mail_from_email']) ? $vsCfg['mail_from_email'] : ''); ?>">
            </div>
            <div class="vs-form-col">
                <label class="vs-label">发件人名称</label>
                <input type="text" name="mail_from_name" class="vs-input" value="<?php echo vs_e(isset($vsCfg['mail_from_name']) ? $vsCfg['mail_from_name'] : ''); ?>">
            </div>
        </div>
        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">保存邮箱设置</button>
        </div>
    </form>

    <hr class="vs-divider">

    <form method="post" action="" class="vs-form vs-form--test-mail" id="testMailForm" data-ajax="1">
        <input type="hidden" name="action" value="test_mail">
        <h4 class="vs-form-subtitle">发送测试邮件</h4>
        <div class="vs-form-row vs-form-row--test-mail">
            <input type="email" name="test_email" class="vs-input" placeholder="测试邮箱地址" required>
            <button type="submit" class="vs-btn vs-btn--default">发送测试</button>
        </div>
    </form>
<?php vs_admin_accordion_end(); ?>

<?php vs_settings_render_storage_section(); ?>

<script>window.VS_SETTINGS_BASE = <?php echo json_encode($vsBase, JSON_UNESCAPED_UNICODE); ?>;</script>

<?php vs_admin_layout_end(array('settings.js')); ?>
