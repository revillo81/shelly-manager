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

$name = trim((string)input('name', ''));
if ($name === '') {
    json_response(['success' => false, 'error' => 'name_required'], 400);
}

$client = DeviceClientFactory::make($device);
$result = ((int)$device['generation'] >= 2)
    ? $client->rpcSetDeviceName($name)
    : $client->gen1SetDeviceName($name);

if ($result === null) {
    json_response(['success' => false, 'error' => 'device_unreachable'], 502);
}

DeviceRepository::update($id, ['name' => $name]);

json_response(['success' => true]);
