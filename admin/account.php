<?php
/**
 * 文件：admin/account.php
 * 作用：VanShine 后台账号设置（邮箱、头像、密码）
 * @version 1.0.20
 */

require_once __DIR__ . '/init.php';

$error = '';
$success = '';
$avatarUrl = $vsAdmin && isset($vsAdmin['avatar_url']) ? trim((string) $vsAdmin['avatar_url']) : '';
$avatarPreview = UserAvatar::resolve($vsAdmin);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $avatarUrl = trim(isset($_POST['avatar_url']) ? $_POST['avatar_url'] : '');
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $newPassword2 = isset($_POST['new_password2']) ? $_POST['new_password2'] : '';
    $oldPassword = isset($_POST['old_password']) ? $_POST['old_password'] : '';

    if ($newPassword !== '' && $newPassword !== $newPassword2) {
        $error = '两次输入的新密码不一致';
    } else {
        $result = Auth::updateAccount(
            $email,
            $newPassword !== '' ? $newPassword : null,
            $newPassword !== '' ? $oldPassword : null,
            $avatarUrl
        );

        if ($result === true) {
            $success = '账号信息已保存';
            $vsAdmin = Auth::user();
            $avatarUrl = $vsAdmin && isset($vsAdmin['avatar_url']) ? trim((string) $vsAdmin['avatar_url']) : '';
            $avatarPreview = UserAvatar::resolve($vsAdmin);
        } else {
            $error = $result;
        }
    }
}

vs_admin_layout_start('账号设置', 'account');
?>

<div class="vs-panel">
    <?php if ($error): ?>
        <div class="vs-alert vs-alert--error"><?php echo vs_e($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="vs-alert vs-alert--success"><?php echo vs_e($success); ?></div>
    <?php endif; ?>

    <form method="post" action="" class="vs-form" id="accountForm">
        <div class="vs-form-section">
            <h3 class="vs-form-section__title">基本信息</h3>
            <div class="vs-form-row">
                <label class="vs-label">用户名</label>
                <input type="text" class="vs-input" value="<?php echo vs_e($vsAdmin ? $vsAdmin['username'] : ''); ?>" disabled>
                <p class="vs-form-tip">用户名不可修改</p>
            </div>
            <div class="vs-form-row">
                <label class="vs-label">邮箱</label>
                <input type="email" name="email" class="vs-input" required
                       value="<?php echo vs_e($vsAdmin ? $vsAdmin['email'] : ''); ?>" placeholder="admin@example.com">
                <p class="vs-form-tip">绑定 QQ 邮箱（如 123456789@qq.com）时将自动使用 QQ 头像</p>
            </div>
            <div class="vs-form-row">
                <label class="vs-label">头像链接</label>
                <div class="vs-avatar-field">
                    <img src="<?php echo vs_e($avatarPreview); ?>" alt="" class="vs-avatar-field__preview" id="avatarPreview"
                         data-fallback="<?php echo vs_e(UserAvatar::localRandomAvatar($vsAdmin ? (int) $vsAdmin['id'] : 0)); ?>">
                    <input type="url" name="avatar_url" id="avatarUrlInput" class="vs-input"
                           value="<?php echo vs_e($avatarUrl); ?>" placeholder="https://example.com/avatar.jpg">
                </div>
                <p class="vs-form-tip">填写图片链接后左侧圆圈实时预览；留空且非 QQ 邮箱时使用系统随机默认头像</p>
            </div>
        </div>

        <div class="vs-form-section">
            <h3 class="vs-form-section__title">修改密码</h3>
            <p class="vs-form-tip vs-form-tip--block">如不需要修改密码，以下三项留空即可</p>
            <div class="vs-form-row">
                <label class="vs-label">当前密码</label>
                <input type="password" name="old_password" class="vs-input" placeholder="修改密码时必填" autocomplete="current-password">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">新密码</label>
                <input type="password" name="new_password" class="vs-input" placeholder="至少 6 个字符" minlength="6" autocomplete="new-password">
            </div>
            <div class="vs-form-row">
                <label class="vs-label">确认新密码</label>
                <input type="password" name="new_password2" class="vs-input" placeholder="再次输入新密码" minlength="6" autocomplete="new-password">
            </div>
        </div>

        <div class="vs-form-actions">
            <button type="submit" class="vs-btn vs-btn--primary">保存修改</button>
        </div>
    </form>
</div>

<?php vs_admin_layout_end(array('account.js')); ?>
