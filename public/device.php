<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    flash_error(t('error_not_found'));
    redirect('index.php');
}

if (is_post()) {
    Csrf::verifyOrFail();
    $action = input('action', 'save');

    if ($action === 'save') {
        $groupId = input('group_id', '');
        $password = (string)input('password', '');

        $update = [
            'name' => trim((string)input('name', $device['name'])),
            'group_id' => $groupId !== '' ? (int)$groupId : null,
            'notes' => input('notes', ''),
            'username' => trim((string)input('username', '')) ?: null,
        ];
        if ($password !== '') {
            $update['password_enc'] = Crypto::encrypt($password);
        }

        DeviceRepository::update($id, $update);
        flash_success(t('success_saved'));
        redirect('device.php?id=' . $id);
    }

    if ($action === 'delete') {
        DeviceRepository::delete($id);
        flash_success(t('success_deleted'));
        redirect('index.php');
    }
}

$groups = GroupRepository::all();
$devicePassword = $device['password_enc'] ? Crypto::decrypt($device['password_enc']) : null;

$pageTitle = $device['name'];
require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/tree_functions.php';
?>
<p><a href="index.php" class="back-link">&larr; <?= e(t('back_to_dashboard')) ?></a></p>
<h1><?= e($device['name']) ?> <?= status_badge($device) ?? '' ?></h1>

<div class="device-subnav">
    <a href="device.php?id=<?= (int)$device['id'] ?>" class="btn btn-small btn-active"><?= e(t('control_title')) ?></a>
    <a href="device_settings.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('device_settings_title')) ?></a>
    <?php if ((int)$device['generation'] >= 2): ?>
        <a href="scripts.php?id=<?= (int)$device['id'] ?>" class="btn btn-small"><?= e(t('scripts_title')) ?></a>
    <?php endif; ?>
    <button type="button" id="btn-reboot" class="btn btn-small btn-danger" data-device-id="<?= (int)$device['id'] ?>"><?= e(t('reboot')) ?></button>
</div>

<div class="two-col">
    <section class="card">
        <h2><?= e(t('device_detail')) ?></h2>
        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="save">
            <label><?= e(t('device_name')) ?>
                <input type="text" name="name" value="<?= e($device['name']) ?>" required>
            </label>
            <label><?= e(t('device_group')) ?>
                <select name="group_id">
                    <option value=""><?= e(t('group_unassigned')) ?></option>
                    <?php foreach ($groups as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= (int)$device['group_id'] === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="device-props">
                <div class="prop-row"><span class="prop-key"><?= e(t('device_ip')) ?></span><span class="prop-val"><?= e($device['ip']) ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_mac')) ?></span><span class="prop-val"><?= e($device['mac'] ?: '–') ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_type')) ?></span><span class="prop-val"><?= e($device['type'] ?: '–') ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_model')) ?></span><span class="prop-val"><?= e($device['model'] ?: '–') ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_generation')) ?></span><span class="prop-val"><?= (int)$device['generation'] ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_firmware')) ?></span><span class="prop-val"><?= e($device['firmware'] ?: '–') ?></span></div>
                <div class="prop-row"><span class="prop-key"><?= e(t('device_auth')) ?></span><span class="prop-val"><?= $device['auth_enabled'] ? e(t('yes')) : e(t('no')) ?></span></div>
            </div>

            <?php if ($device['auth_enabled']): ?>
            <label><?= e(t('device_username')) ?>
                <input type="text" name="username" value="<?= e($device['username']) ?>">
            </label>
            <label><?= e(t('device_password')) ?>
                <input type="password" name="password" placeholder="<?= $devicePassword ? '••••••••' : '' ?>">
            </label>
            <?php endif; ?>

            <label><?= e(t('device_notes')) ?>
                <textarea name="notes" rows="3"><?= e($device['notes']) ?></textarea>
            </label>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
            </div>
        </form>

        <form method="post" onsubmit="return confirm('<?= e(t('confirm_delete')) ?>');" class="delete-device-form">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger"><?= e(t('delete')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2><?= e(t('control_title')) ?></h2>
        <p>
            <button class="btn btn-small" id="btn-refresh-status" data-device-id="<?= (int)$device['id'] ?>"><?= e(t('refresh_status')) ?></button>
        </p>
        <div id="live-status" class="live-status"><em>–</em></div>
        <div id="metrics-panel" class="metrics-panel"></div>
        <div id="control-panel" class="control-panel"></div>

        <h3><?= e(t('raw_info')) ?></h3>
        <pre class="raw-info"><?= e(json_encode(json_decode($device['raw_info'] ?: '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
    </section>
</div>

<script>
window.deviceId = <?= (int)$device['id'] ?>;
window.deviceGeneration = <?= (int)$device['generation'] ?>;
window.csrfToken = <?= json_encode(Csrf::token()) ?>;
window.i18n = {
    turn_on: <?= json_encode(t('turn_on')) ?>,
    turn_off: <?= json_encode(t('turn_off')) ?>,
    toggle: <?= json_encode(t('toggle')) ?>,
    open: <?= json_encode(t('open')) ?>,
    close: <?= json_encode(t('close')) ?>,
    stop: <?= json_encode(t('stop')) ?>,
    channel: <?= json_encode(t('channel')) ?>,
    status_online: <?= json_encode(t('status_online')) ?>,
    status_offline: <?= json_encode(t('status_offline')) ?>
};
window.metricLabels = {
    power: <?= json_encode(t('metric_power')) ?>,
    voltage: <?= json_encode(t('metric_voltage')) ?>,
    current: <?= json_encode(t('metric_current')) ?>,
    temperature: <?= json_encode(t('metric_temperature')) ?>,
    humidity: <?= json_encode(t('metric_humidity')) ?>,
    battery: <?= json_encode(t('metric_battery')) ?>
};
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
