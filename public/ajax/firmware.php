<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    json_response(['error' => 'not_found'], 404);
}

$client = DeviceClientFactory::make($device);
$gen = (int)$device['generation'];

if (is_post()) {
    Csrf::verifyOrFail();
    $action = (string)input('action', '');

    if ($action === 'check') {
        $result = ($gen >= 2) ? $client->rpcCheckForUpdate() : $client->gen1OtaCheck();
        if ($result === null) {
            json_response(['success' => false, 'error' => 'device_unreachable'], 502);
        }
        json_response(['success' => true, 'result' => $result]);
    }

    if ($action === 'update') {
        $result = ($gen >= 2) ? $client->rpcUpdateFirmware('stable') : $client->gen1OtaUpdate();
        json_response(['success' => $result !== null]);
    }

    json_response(['success' => false, 'error' => 'invalid_action'], 400);
}

$status = ($gen >= 2) ? $client->rpcInfo() : $client->gen1OtaStatus();
if ($status === null) {
    json_response(['error' => 'device_unreachable'], 502);
}

json_response(['status' => $status]);
