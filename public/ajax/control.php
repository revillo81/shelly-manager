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

$component = input('component', 'switch'); // switch | roller
$channel = (int)input('channel', 0);
$action = input('action', 'toggle'); // on|off|toggle for switch, open|close|stop for roller

$client = DeviceClientFactory::make($device);

$gen = (int)$device['generation'];
$result = null;

if ($component === 'roller') {
    if ($gen >= 2) {
        $map = ['open' => 'Open', 'close' => 'Close', 'stop' => 'Stop'];
        $result = $client->rpcCoverGoPosition($channel, $map[$action] ?? 'Stop');
    } else {
        $result = $client->gen1SetRoller($channel, $action);
    }
} else {
    if ($gen >= 2) {
        if ($action === 'toggle') {
            $result = $client->rpcSwitchToggle($channel);
        } else {
            $result = $client->rpcSwitchSet($channel, $action === 'on');
        }
    } else {
        $result = $client->gen1SetRelay($channel, $action);
    }
}

if ($result === null) {
    json_response(['success' => false, 'error' => 'device_unreachable'], 502);
}

DeviceRepository::update($id, ['status' => 'online', 'last_seen' => now()]);

json_response(['success' => true, 'result' => $result]);
