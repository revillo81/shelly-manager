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

<section class="card settings-section">
    <h2><?= e(t('quick_actions_title')) ?></h2>
    <div class="quick-actions">
        <div class="quick-action">
            <strong><?= e(t('device_rename')) ?></strong>
            <div class="quick-action-row">
                <input type="text" id="rename-input" value="<?= e($device['name']) ?>" placeholder="<?= e(t('device_rename')) ?>">
                <button type="button" id="btn-rename" class="btn btn-primary btn-small"><?= e(t('save')) ?></button>
            </div>
            <span class="save-msg" id="rename-msg"></span>
        </div>

        <div class="quick-action">
            <strong><?= e(t('firmware_title')) ?></strong>
            <div class="quick-action-row">
                <button type="button" id="btn-fw-check" class="btn btn-small"><?= e(t('firmware_check')) ?></button>
                <button type="button" id="btn-fw-update" class="btn btn-small btn-primary"><?= e(t('firmware_update')) ?></button>
            </div>
            <span class="save-msg" id="fw-msg"></span>
        </div>

        <div class="quick-action">
            <strong><?= e(t('auth_protection')) ?></strong>
            <div class="quick-action-row">
                <label><input type="checkbox" id="auth-enabled" <?= !empty($device['auth_enabled']) ? 'checked' : '' ?>> <?= e(t('auth_enable')) ?></label>
                <input type="password" id="auth-password" placeholder="<?= e(t('auth_password_new')) ?>">
                <button type="button" id="btn-auth" class="btn btn-small btn-primary"><?= e(t('save')) ?></button>
            </div>
            <span class="save-msg" id="auth-msg"></span>
        </div>

        <div class="quick-action">
            <strong><?= e(t('reboot')) ?></strong>
            <div class="quick-action-row">
                <button type="button" id="btn-reboot" class="btn btn-small btn-danger"><?= e(t('reboot')) ?></button>
            </div>
            <span class="save-msg" id="reboot-msg"></span>
        </div>

        <div class="quick-action">
            <strong><?= e(t('factory_reset')) ?></strong>
            <?php if ((int)$device['generation'] < 2): ?>
                <p class="hint"><?= e(t('factory_reset_gen1_hint')) ?></p>
            <?php else: ?>
                <div class="quick-action-row">
                    <button type="button" id="btn-factory-reset" class="btn btn-small btn-danger"><?= e(t('factory_reset')) ?></button>
                </div>
                <span class="save-msg" id="factory-reset-msg"></span>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="settings-loading" class="hint"><?= e(t('loading')) ?></div>
<div id="settings-sections"></div>

<script>
window.deviceId = <?= (int)$device['id'] ?>;
window.csrfToken = <?= json_encode(Csrf::token()) ?>;
window.i18n = {
    save: <?= json_encode(t('save')) ?>,
    saved: <?= json_encode(t('success_saved')) ?>,
    save_failed: <?= json_encode(t('error_generic')) ?>,
    load_failed: <?= json_encode(t('device_settings_load_failed')) ?>,
    cat_network: <?= json_encode(t('settings_cat_network')) ?>,
    cat_connectivity: <?= json_encode(t('settings_cat_connectivity')) ?>,
    cat_device: <?= json_encode(t('settings_cat_device')) ?>,
    cat_components: <?= json_encode(t('settings_cat_components')) ?>,
    cat_advanced: <?= json_encode(t('settings_cat_advanced')) ?>,
    factory_reset_confirm: <?= json_encode(t('factory_reset_confirm')) ?>,
    firmware_update_confirm: <?= json_encode(t('firmware_update_confirm')) ?>,
    firmware_up_to_date: <?= json_encode(t('firmware_up_to_date')) ?>,
    firmware_available: <?= json_encode(t('firmware_available')) ?>,
    auth_password_required: <?= json_encode(t('auth_password_required')) ?>
};
</script>
<script src="<?= asset_url('assets/js/settings.js') ?>"></script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
