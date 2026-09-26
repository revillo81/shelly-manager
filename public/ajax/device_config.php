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
    $key = (string)input('key', '');
    $json = (string)input('data', '');
    $data = json_decode($json, true);

    if ($key === '' || !is_array($data)) {
        json_response(['success' => false, 'error' => 'invalid_data'], 400);
    }

    $result = ($gen >= 2)
        ? ShellyConfigSections::applyGen2($client, $key, $data)
        : ShellyConfigSections::applyGen1($client, $key, $data);

    if ($result === null) {
        json_response(['success' => false, 'error' => 'device_unreachable_or_unsupported'], 502);
    }

    json_response(['success' => true, 'result' => $result]);
}

$config = ($gen >= 2) ? $client->rpcConfig() : $client->gen1SettingsGet();

if ($config === null) {
    json_response(['error' => 'device_unreachable'], 502);
}

$sections = ($gen >= 2) ? ShellyConfigSections::fromGen2($config) : ShellyConfigSections::fromGen1($config);

json_response(['sections' => $sections]);
