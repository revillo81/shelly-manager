<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$config = config();

if (is_post()) {
    Csrf::verifyOrFail();
    $form = input('form', '');

    if ($form === 'db') {
        $config['db_driver'] = input('db_driver', 'sqlite') === 'mysql' ? 'mysql' : 'sqlite';
        $config['mysql']['host'] = trim((string)input('mysql_host', $config['mysql']['host']));
        $config['mysql']['port'] = (int)input('mysql_port', $config['mysql']['port']);
        $config['mysql']['dbname'] = trim((string)input('mysql_dbname', $config['mysql']['dbname']));
        $config['mysql']['user'] = trim((string)input('mysql_user', $config['mysql']['user']));
        $newPass = (string)input('mysql_pass', '');
        if ($newPass !== '') {
            $config['mysql']['pass'] = $newPass;
        }
        $config['default_ip_range'] = trim((string)input('default_ip_range', $config['default_ip_range']));
        save_config($config);
        flash_success(t('settings_saved'));
        redirect('settings.php');
    }

    if ($form === 'password') {
        $current = (string)input('current_password', '');
        $new = (string)input('new_password', '');
        if ($new !== '' && Auth::changePassword($current, $new)) {
            flash_success(t('settings_saved'));
        } else {
            flash_error(t('error_generic'));
        }
        redirect('settings.php');
    }
}

$pageTitle = t('settings_title');
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e(t('settings_title')) ?></h1>

<div class="two-col">
    <section class="card">
        <h2><?= e(t('settings_db')) ?></h2>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="db">
            <label><?= e(t('settings_db_driver')) ?>
                <select name="db_driver" id="db_driver">
                    <option value="sqlite" <?= $config['db_driver'] === 'sqlite' ? 'selected' : '' ?>><?= e(t('settings_db_sqlite')) ?></option>
                    <option value="mysql" <?= $config['db_driver'] === 'mysql' ? 'selected' : '' ?>><?= e(t('settings_db_mysql')) ?></option>
                </select>
            </label>
            <div id="mysql-fields">
                <label><?= e(t('settings_mysql_host')) ?>
                    <input type="text" name="mysql_host" value="<?= e($config['mysql']['host']) ?>">
                </label>
                <label><?= e(t('settings_mysql_port')) ?>
                    <input type="number" name="mysql_port" value="<?= (int)$config['mysql']['port'] ?>">
                </label>
                <label><?= e(t('settings_mysql_dbname')) ?>
                    <input type="text" name="mysql_dbname" value="<?= e($config['mysql']['dbname']) ?>">
                </label>
                <label><?= e(t('settings_mysql_user')) ?>
                    <input type="text" name="mysql_user" value="<?= e($config['mysql']['user']) ?>">
                </label>
                <label><?= e(t('settings_mysql_pass')) ?>
                    <input type="password" name="mysql_pass" placeholder="••••••••">
                </label>
            </div>
            <label><?= e(t('settings_default_range')) ?>
                <input type="text" name="default_ip_range" value="<?= e($config['default_ip_range']) ?>">
            </label>
            <p class="hint"><?= e(t('settings_db_hint')) ?></p>
            <button type="submit" class="btn btn-primary"><?= e(t('settings_save')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2><?= e(t('settings_account')) ?></h2>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="password">
            <label><?= e(t('settings_current_password')) ?>
                <input type="password" name="current_password" required>
            </label>
            <label><?= e(t('settings_new_password')) ?>
                <input type="password" name="new_password" required minlength="6">
            </label>
            <button type="submit" class="btn btn-primary"><?= e(t('settings_change_password')) ?></button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../templates/footer.php'; ?>
