<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$groups = GroupRepository::all();

if (is_post() && input('form') === 'manual') {
    Csrf::verifyOrFail();
    $ip = trim((string)input('ip', ''));
    $username = trim((string)input('username', ''));
    $password = (string)input('password', '');

    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        flash_error(t('error_generic'));
        redirect('add_device.php');
    }

    $result = ShellyDetector::detect($ip, $username ?: null, $password ?: null);
    if ($result === null) {
        flash_error(t('error_detect_failed'));
        redirect('add_device.php');
    }

    $existing = DeviceRepository::findByIp($ip);
    if ($existing) {
        flash_error(t('error_generic'));
        redirect('add_device.php');
    }

    $id = DeviceRepository::create([
        'name' => $result['model'] ?? $ip,
        'ip' => $ip,
        'mac' => $result['mac'] ?? null,
        'type' => $result['type'] ?? null,
        'model' => $result['model'] ?? null,
        'generation' => $result['generation'] ?? 1,
        'app' => $result['app'] ?? null,
        'firmware' => $result['firmware'] ?? null,
        'auth_enabled' => $result['auth_enabled'] ?? false,
        'username' => $username ?: null,
        'password_enc' => $password !== '' ? Crypto::encrypt($password) : null,
        'status' => 'online',
        'raw_info' => $result['raw_info'] ?? null,
    ]);

    flash_success(t('success_added'));
    redirect('device.php?id=' . $id);
}

$pageTitle = t('add_device_title');
require __DIR__ . '/../templates/header.php';
?>
<h1><?= e(t('add_device_title')) ?></h1>

<div class="two-col">
    <section class="card">
        <h2><?= e(t('add_device_manual')) ?></h2>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="form" value="manual">
            <label><?= e(t('ip_address')) ?>
                <input type="text" name="ip" placeholder="192.168.1.50" required>
            </label>
            <label><?= e(t('device_username')) ?>
                <input type="text" name="username">
            </label>
            <label><?= e(t('device_password')) ?>
                <input type="password" name="password">
            </label>
            <button type="submit" class="btn btn-primary"><?= e(t('detect_and_add')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2><?= e(t('add_device_scan')) ?></h2>
        <form id="scan-form">
            <?= Csrf::field() ?>
            <label><?= e(t('ip_range')) ?>
                <input type="text" name="range" value="<?= e(config('default_ip_range')) ?>" required>
            </label>
            <button type="submit" class="btn btn-primary"><?= e(t('start_scan')) ?></button>
        </form>
        <div id="scan-status" class="hint" style="display:none;"><?= e(t('scanning')) ?></div>
        <div id="scan-results"></div>
    </section>
</div>

<script>
window.i18n = {
    scan_found: <?= json_encode(t('scan_found')) ?>,
    scan_none_found: <?= json_encode(t('scan_none_found')) ?>,
    add_selected: <?= json_encode(t('add_selected')) ?>,
    device_group: <?= json_encode(t('device_group')) ?>
};
window.groupOptions = <?= json_encode(array_map(fn($g) => ['id' => $g['id'], 'name' => $g['name']], $groups)) ?>;
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
