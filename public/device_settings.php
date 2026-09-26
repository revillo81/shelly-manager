<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    flash_error(t('error_not_found'));
    redirect('index.php');
}

$pageTitle = $device['name'] . ' – ' . t('device_settings_title');
require __DIR__ . '/../templates/header.php';
?>
<p><a href="index.php">&larr; <?= e(t('back_to_dashboard')) ?></a></p>
<h1><?= e($device['name']) ?> – <?= e(t('device_settings_title')) ?></h1>

<p class="device-subnav">
    <a href="device.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('control_title')) ?></a>
    <a href="device_settings.php?id=<?= (int)$device['id'] ?>" class="btn btn-small btn-active"><?= e(t('device_settings_title')) ?></a>
    <?php if ((int)$device['generation'] >= 2): ?>
        <a href="scripts.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('scripts_title')) ?></a>
    <?php endif; ?>
</p>

<p class="hint"><?= e(t('device_settings_hint')) ?></p>

<div id="settings-loading" class="hint"><?= e(t('loading')) ?></div>
<div id="settings-sections"></div>

<script>
window.deviceId = <?= (int)$device['id'] ?>;
window.csrfToken = <?= json_encode(Csrf::token()) ?>;
window.i18n = {
    save: <?= json_encode(t('save')) ?>,
    saved: <?= json_encode(t('success_saved')) ?>,
    save_failed: <?= json_encode(t('error_generic')) ?>,
    load_failed: <?= json_encode(t('device_settings_load_failed')) ?>
};
</script>
<script src="assets/js/settings.js"></script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
