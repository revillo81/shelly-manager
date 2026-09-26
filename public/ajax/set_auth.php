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

$enabled = (string)input('enabled', '0') === '1';
$password = (string)input('password', '');

$client = DeviceClientFactory::make($device);

if ((int)$device['generation'] >= 2) {
    if ($enabled && $password === '') {
        json_response(['success' => false, 'error' => 'password_required'], 400);
    }

    $info = $client->rpcInfo();
    $realm = $info['id'] ?? null;
    if ($realm === null) {
        json_response(['success' => false, 'error' => 'device_unreachable'], 502);
    }

    $result = $client->rpcSetAuth($realm, $enabled ? $password : null);
    if ($result === null) {
        json_response(['success' => false, 'error' => 'device_unreachable'], 502);
    }
    DeviceRepository::update($id, [
        'auth_enabled' => $enabled,
        'username' => $enabled ? 'admin' : null,
        'password_enc' => $enabled ? Crypto::encrypt($password) : null,
    ]);
    json_response(['success' => true]);
}

if ($enabled && $password === '') {
    json_response(['success' => false, 'error' => 'password_required'], 400);
}

$result = $client->gen1SettingsSet('/settings/login', [
    'enabled' => $enabled ? 'true' : 'false',
    'username' => 'admin',
    'password' => $password,
]);

if ($result !== null) {
    DeviceRepository::update($id, [
        'auth_enabled' => $enabled,
        'username' => $enabled ? 'admin' : null,
        'password_enc' => $enabled ? Crypto::encrypt($password) : null,
    ]);
}

json_response(['success' => $result !== null]);
