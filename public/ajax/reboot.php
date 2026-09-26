<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

if (!is_post()) {
    json_response(['error' => 'method_not_allowed'], 405);
}
Csrf::verifyOrFail();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    json_response(['error' => 'not_found'], 404);
}

$client = DeviceClientFactory::make($device);
$result = ((int)$device['generation'] >= 2) ? $client->rpcReboot() : $client->gen1Reboot();

json_response(['success' => $result !== null]);
