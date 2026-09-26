<?php
require __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    redirect('index.php');
}

$error = null;
if (is_post()) {
    Csrf::verifyOrFail();
    $username = trim((string)input('username', ''));
    $password = (string)input('password', '');
    if (Auth::attempt($username, $password)) {
        redirect('index.php');
    }
    $error = t('login_error');
}

$pageTitle = t('login_title');
require __DIR__ . '/../templates/header.php';
?>
<div class="login-wrapper">
    <form method="post" class="login-box">
        <h1><?= e(t('login_title')) ?></h1>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?= Csrf::field() ?>
        <label><?= e(t('login_username')) ?>
            <input type="text" name="username" required autofocus>
        </label>
        <label><?= e(t('login_password')) ?>
            <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn btn-primary"><?= e(t('login_submit')) ?></button>
        <p class="hint"><?= e(t('default_credentials_hint')) ?></p>
        <p class="lang-switch-login">
            <a href="?lang=de">DE</a> / <a href="?lang=en">EN</a>
        </p>
    </form>
</div>
<?php require __DIR__ . '/../templates/footer.php'; ?>
