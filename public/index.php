<?php
require __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$groups = GroupRepository::all();
$devices = DeviceRepository::all();
$treeData = GroupRepository::buildTree($groups, $devices);

$pageTitle = t('dashboard_title');
require __DIR__ . '/../templates/header.php';
require __DIR__ . '/../templates/tree_functions.php';
?>
<div class="page-head">
    <h1><?= e(t('dashboard_title')) ?></h1>
    <form method="post" action="ajax/group.php" class="inline-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="create">
        <input type="text" name="name" placeholder="<?= e(t('group_name')) ?>" required>
        <button type="submit" class="btn btn-small"><?= e(t('add_group')) ?></button>
    </form>
</div>

<?php if (empty($devices)): ?>
    <p class="empty-hint"><?= e(t('dashboard_empty')) ?></p>
    <a class="btn btn-primary" href="add_device.php"><?= e(t('nav_add_device')) ?></a>
<?php else: ?>
    <ul class="device-tree" id="device-tree">
        <?php foreach ($treeData['tree'] as $node): ?>
            <?php render_tree_node($node); ?>
        <?php endforeach; ?>

        <?php if (!empty($treeData['unassigned'])): ?>
            <li class="tree-group">
                <span class="tree-toggle">▾</span>
                <span class="tree-label group-label"><?= e(t('group_unassigned')) ?></span>
                <ul>
                    <?php foreach ($treeData['unassigned'] as $device): ?>
                        <?php render_tree_device($device); ?>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<script>
window.metricLabels = {
    power: <?= json_encode(t('metric_power')) ?>,
    voltage: <?= json_encode(t('metric_voltage')) ?>,
    current: <?= json_encode(t('metric_current')) ?>,
    temperature: <?= json_encode(t('metric_temperature')) ?>,
    humidity: <?= json_encode(t('metric_humidity')) ?>,
    battery: <?= json_encode(t('metric_battery')) ?>
};
window.i18n = window.i18n || {};
window.i18n.status_online = <?= json_encode(t('status_online')) ?>;
window.i18n.status_offline = <?= json_encode(t('status_offline')) ?>;
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
