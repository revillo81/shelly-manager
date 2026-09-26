<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    json_response(['error' => 'not_found'], 404);
}

$status = ShellyDetector::fetchStatus($device);
$online = $status !== null;

DeviceRepository::update($id, [
    'status' => $online ? 'online' : 'offline',
    'last_seen' => $online ? now() : $device['last_seen'],
]);

json_response([
    'online' => $online,
    'status' => $status,
    'generation' => (int)$device['generation'],
]);
