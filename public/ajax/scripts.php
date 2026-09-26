<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device) {
    json_response(['error' => 'not_found'], 404);
}

if ((int)$device['generation'] < 2) {
    json_response(['error' => 'scripts_not_supported'], 400);
}

$client = DeviceClientFactory::make($device);
$action = input('action', 'list');

if ($action === 'list') {
    $list = $client->rpc('Script.List');
    if ($list === null) {
        json_response(['error' => 'device_unreachable'], 502);
    }
    json_response(['scripts' => $list['scripts'] ?? []]);
}

if (!is_post()) {
    json_response(['error' => 'method_not_allowed'], 405);
}
Csrf::verifyOrFail();

if ($action === 'create') {
    $name = trim((string)input('name', 'Neues Skript'));
    $result = $client->rpc('Script.Create', ['name' => $name]);
    if ($result === null) {
        json_response(['success' => false, 'error' => 'device_unreachable'], 502);
    }
    json_response(['success' => true, 'result' => $result]);
}

$scriptId = (int)input('script_id', 0);

if ($action === 'delete') {
    $result = $client->rpc('Script.Delete', ['id' => $scriptId]);
} elseif ($action === 'start') {
    $result = $client->rpc('Script.Start', ['id' => $scriptId]);
} elseif ($action === 'stop') {
    $result = $client->rpc('Script.Stop', ['id' => $scriptId]);
} elseif ($action === 'enable') {
    $enable = input('enable', '1') === '1';
    $result = $client->rpc('Script.SetConfig', ['id' => $scriptId, 'config' => ['enable' => $enable]]);
} elseif ($action === 'rename') {
    $name = trim((string)input('name', ''));
    $result = $client->rpc('Script.SetConfig', ['id' => $scriptId, 'config' => ['name' => $name]]);
} else {
    json_response(['error' => 'unknown_action'], 400);
}

if ($result === null) {
    json_response(['success' => false, 'error' => 'device_unreachable'], 502);
}

json_response(['success' => true, 'result' => $result]);
