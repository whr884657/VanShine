<?php
/**
 * 文件：admin/register.php
 * 作用：VanShine 管理员注册页（界面与参考一致，账号由安装向导创建）
 * @version 1.0.8
 */

define('VS_ROOT', dirname(__DIR__));
require_once VS_ROOT . '/core/bootstrap.php';
require_once __DIR__ . '/includes/auth_layout.php';

InstallChecker::requireInstalled();
Auth::redirectIfLoggedIn();

$base = vs_base_url();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    vs_auth_require_post();

    $action = (string) $_POST['action'];

    if ($action === 'send_code') {
        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mailLimitMsg = AuthSecurity::checkMailCodeAllowed($email);
            if ($mailLimitMsg !== null) {
                vs_auth_json(array('code' => 0, 'msg' => $mailLimitMsg));
            }
            AuthSecurity::recordMailCodeSent($email);
        }

        vs_auth_json(array(
            'code' => 0,
            'msg'  => '本系统管理员账号在安装时创建，不支持开放注册',
        ));
    }

    if ($action === 'register') {
        $resetLimitMsg = AuthSecurity::checkResetSubmitAllowed();
        if ($resetLimitMsg !== null) {
            vs_auth_json(array('code' => 0, 'msg' => $resetLimitMsg));
        }
        AuthSecurity::recordResetSubmit();

        vs_auth_json(array(
            'code' => 0,
            'msg'  => '本系统管理员账号在安装时创建，如需新账号请联系管理员',
        ));
    }

    vs_auth_json(array('code' => 0, 'msg' => '未知操作'), 400);
}

vs_auth_head('注册账号');
?>

<div class="page">
    <?php vs_auth_left_panel(false); ?>

    <div class="right">
        <div class="form-box">
            <div class="header header-desktop">
                <h1>注册账号</h1>
                <p class="header-sub">使用邮箱注册，完成验证后即可使用</p>
            </div>

            <div id="formMessage" class="form-message" role="alert" hidden></div>

            <form id="registerForm" method="post" action="" novalidate>
                <?php vs_auth_csrf_field(); ?>
                <div class="field">
                    <label for="email">邮箱</label>
                    <input id="email" name="email" type="email" placeholder="请输入邮箱" autocomplete="email" maxlength="64" required>
                </div>

                <div class="field">
                    <label for="code">验证码</label>
                    <div class="input-group">
                        <input id="code" name="code" type="text" placeholder="请输入验证码" autocomplete="one-time-code" maxlength="6" inputmode="numeric" pattern="[0-9]*">
                        <button type="button" class="code-btn" id="sendCodeBtn">获取验证码</button>
                    </div>
                </div>

                <div class="field">
                    <label for="password">密码</label>
                    <div class="input-wrap">
                        <input id="password" name="password" type="password" placeholder="请设置密码（至少6位）" autocomplete="new-password" maxlength="64" required>
                        <?php echo vs_auth_toggle_password_html(); ?>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">确认密码</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="请再次输入密码" autocomplete="new-password" maxlength="64" required>
                </div>

                <?php echo vs_auth_submit_btn('立即注册', 'submitBtn'); ?>

                <div class="divider">
                    已有账号？<a href="<?php echo vs_e($base); ?>/admin/login.php">返回登录</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var form = document.getElementById('registerForm');
    var messageEl = document.getElementById('formMessage');
    var sendCodeBtn = document.getElementById('sendCodeBtn');
    var submitBtn = document.getElementById('submitBtn');
    var countdown = 0;
    var countdownTimer = null;

    if (!form) return;

    function showMessage(text, type) {
        if (text && window.VsToast) {
            VsToast.show(text, type === 'error' ? 'error' : 'success');
            if (messageEl) messageEl.hidden = true;
            return;
        }
        if (!messageEl) return;
        messageEl.textContent = text;
        messageEl.className = 'form-message form-message--' + type;
        messageEl.hidden = false;
    }

    function hideMessage() {
        if (messageEl) messageEl.hidden = true;
    }

    function startCountdown(seconds) {
        countdown = seconds;
        if (!sendCodeBtn) return;
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = countdown + 's 后重发';

        countdownTimer = setInterval(function () {
            countdown -= 1;
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
                return;
            }
            sendCodeBtn.textContent = countdown + 's 后重发';
        }, 1000);
    }

    if (sendCodeBtn) {
        sendCodeBtn.addEventListener('click', function () {
            hideMessage();
            var email = form.email.value.trim();

            if (!email) {
                showMessage('请先输入邮箱', 'error');
                form.email.focus();
                return;
            }

            sendCodeBtn.disabled = true;

            var body = new FormData();
            body.append('action', 'send_code');
            body.append('email', email);
            if (form.csrf_token) {
                body.append('csrf_token', form.csrf_token.value);
            }

            fetch(window.location.href, {
                method: 'POST',
                body: body,
                credentials: 'same-origin'
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.code === 1) {
                        showMessage(data.msg || '验证码已发送', 'success');
                        startCountdown(60);
                    } else {
                        showMessage(data.msg || '发送失败', 'error');
                        sendCodeBtn.disabled = false;
                    }
                })
                .catch(function () {
                    showMessage('网络异常，请稍后重试', 'error');
                    sendCodeBtn.disabled = false;
                });
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideMessage();

        var email = form.email.value.trim();
        var code = form.code.value.trim();
        var password = form.password.value;
        var confirm = form.confirm_password.value;

        if (!email) {
            showMessage('请输入邮箱', 'error');
            form.email.focus();
            return;
        }
        if (!code) {
            showMessage('请输入验证码', 'error');
            form.code.focus();
            return;
        }
        if (password.length < 6) {
            showMessage('密码至少 6 位', 'error');
            form.password.focus();
            return;
        }
        if (password !== confirm) {
            showMessage('两次输入的密码不一致', 'error');
            form.confirm_password.focus();
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        var body = new FormData(form);
        body.append('action', 'register');

        fetch(window.location.href, {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.code === 1) {
                    showMessage(data.msg || '注册成功', 'success');
                    if (data.url) {
                        setTimeout(function () { window.location.href = data.url; }, 1200);
                    }
                } else {
                    showMessage(data.msg || '注册失败', 'error');
                }
            })
            .catch(function () {
                showMessage('网络异常，请稍后重试', 'error');
            })
            .finally(function () {
                if (submitBtn) submitBtn.disabled = false;
            });
    });
})();
</script>

<?php vs_auth_foot('window.CHARACTER_OPTIONS = { usernameInputId: \'email\', passwordInputId: \'password\' };'); ?>
