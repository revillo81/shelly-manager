<?php

function device_icon(array $device): string
{
    $type = strtolower(($device['app'] ?? '') . ' ' . ($device['type'] ?? '') . ' ' . ($device['model'] ?? ''));
    if (strpos($type, 'roller') !== false || strpos($type, 'cover') !== false) return '🪟';
    if (strpos($type, 'plug') !== false) return '🔌';
    if (strpos($type, 'dimmer') !== false || strpos($type, 'bulb') !== false || strpos($type, 'rgbw') !== false) return '💡';
    if (strpos($type, 'ht') !== false || strpos($type, 'sensor') !== false || strpos($type, 'gas') !== false) return '🌡️';
    if (strpos($type, 'door') !== false || strpos($type, 'window') !== false) return '🚪';
    if (strpos($type, 'motion') !== false) return '🏃';
    if (strpos($type, 'button') !== false) return '🔘';
    if (strpos($type, 'em') !== false) return '⚡';
    return '🔀';
}

function status_badge(array $device): string
{
    $status = $device['status'] ?? 'unknown';
    $labelKey = 'status_' . $status;
    $label = t($labelKey);
    if ($label === $labelKey) {
        $label = t('status_unknown');
        $status = 'unknown';
    }
    return '<span class="badge badge-' . e($status) . '" data-status-badge="' . (int)$device['id'] . '">' . e($label) . '</span>';
}

function render_tree_device(array $device): void
{
    ?>
    <li class="tree-device" data-device-id="<?= (int)$device['id'] ?>">
        <a href="device.php?id=<?= (int)$device['id'] ?>" class="tree-device-link">
            <span class="device-icon"><?= device_icon($device) ?></span>
            <span class="device-info">
                <span class="device-name"><?= e($device['name']) ?></span>
                <span class="device-meta"><?= e($device['ip']) ?> · <?= e($device['model'] ?: $device['type']) ?></span>
            </span>
        </a>
        <span class="device-metrics" data-metrics-for="<?= (int)$device['id'] ?>"></span>
        <?= status_badge($device) ?>
    </li>
    <?php
}

function render_tree_node(array $node): void
{
    $group = $node['group'];
    ?>
    <li class="tree-group">
        <span class="tree-toggle">▾</span>
        <span class="tree-label group-label"><?= e($group['name']) ?></span>
        <span class="group-actions">
            <form method="post" action="ajax/group.php" class="inline-form-tiny">
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$group['id'] ?>">
                <button type="submit" class="btn-link-small" onclick="return confirm('<?= e(t('confirm_delete')) ?>');"><?= e(t('delete')) ?></button>
            </form>
        </span>
        <ul>
            <?php foreach ($node['children'] as $child): ?>
                <?php render_tree_node($child); ?>
            <?php endforeach; ?>
            <?php foreach ($node['devices'] as $device): ?>
                <?php render_tree_device($device); ?>
            <?php endforeach; ?>
        </ul>
    </li>
    <?php
}
