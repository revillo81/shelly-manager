<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

$devices = DeviceRepository::all();
$metrics = DashboardMetrics::fetchAll($devices);

foreach ($metrics as $id => $m) {
    $update = ['status' => $m['online'] ? 'online' : 'offline'];
    if ($m['online']) {
        $update['last_seen'] = now();
    }
    DeviceRepository::update((int)$id, $update);
}

json_response(['metrics' => $metrics]);
