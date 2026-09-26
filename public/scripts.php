<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    flash_error(t('error_not_found'));
    redirect('index.php');
}

if ((int)$device['generation'] < 2) {
    flash_error(t('scripts_not_supported'));
    redirect('device.php?id=' . $id);
}

$pageTitle = $device['name'] . ' – ' . t('scripts_title');
require __DIR__ . '/../templates/header.php';
?>
<p><a href="index.php">&larr; <?= e(t('back_to_dashboard')) ?></a></p>
<h1><?= e($device['name']) ?> – <?= e(t('scripts_title')) ?></h1>

<p class="device-subnav">
    <a href="device.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('control_title')) ?></a>
    <a href="device_settings.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('device_settings_title')) ?></a>
    <a href="scripts.php?id=<?= (int)$device['id'] ?>" class="btn btn-small btn-active"><?= e(t('scripts_title')) ?></a>
</p>

<div class="two-col">
    <section class="card">
        <h2><?= e(t('scripts_list')) ?></h2>
        <form id="script-create-form" class="inline-form">
            <input type="text" name="name" placeholder="<?= e(t('scripts_new_name')) ?>" required>
            <button type="submit" class="btn btn-small"><?= e(t('scripts_create')) ?></button>
        </form>
        <ul id="script-list" class="script-list"></ul>
    </section>

    <section class="card">
        <h2><?= e(t('scripts_editor')) ?></h2>
        <div id="script-editor" class="hint"><?= e(t('scripts_select_hint')) ?></div>
    </section>
</div>

<script>
window.deviceId = <?= (int)$device['id'] ?>;
window.csrfToken = <?= json_encode(Csrf::token()) ?>;
window.i18n = {
    scripts_running: <?= json_encode(t('scripts_running')) ?>,
    scripts_stopped: <?= json_encode(t('scripts_stopped')) ?>,
    scripts_enabled: <?= json_encode(t('scripts_enabled')) ?>,
    scripts_disabled: <?= json_encode(t('scripts_disabled')) ?>,
    start: <?= json_encode(t('scripts_start')) ?>,
    stop: <?= json_encode(t('scripts_stop')) ?>,
    enable: <?= json_encode(t('scripts_enable')) ?>,
    disable: <?= json_encode(t('scripts_disable')) ?>,
    delete: <?= json_encode(t('delete')) ?>,
    edit: <?= json_encode(t('edit')) ?>,
    save: <?= json_encode(t('save')) ?>,
    saved: <?= json_encode(t('success_saved')) ?>,
    save_failed: <?= json_encode(t('error_generic')) ?>,
    confirm_delete: <?= json_encode(t('confirm_delete')) ?>
};
</script>
<script src="assets/js/scripts.js"></script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
