<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

if (!is_post()) {
    json_response(['error' => 'method_not_allowed'], 405);
}
Csrf::verifyOrFail();

$ips = input('ips', []);
if (is_string($ips)) {
    $ips = json_decode($ips, true) ?: [];
}

$added = [];
$skipped = [];

foreach ($ips as $ip) {
    $ip = trim((string)$ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
        continue;
    }
    if (DeviceRepository::findByIp($ip)) {
        $skipped[] = $ip;
        continue;
    }
    $result = ShellyDetector::detect($ip);
    if ($result === null) {
        $skipped[] = $ip;
        continue;
    }
    $id = DeviceRepository::create([
        'name' => $result['model'] ?? $ip,
        'ip' => $ip,
        'mac' => $result['mac'] ?? null,
        'type' => $result['type'] ?? null,
        'model' => $result['model'] ?? null,
        'generation' => $result['generation'] ?? 1,
        'app' => $result['app'] ?? null,
        'firmware' => $result['firmware'] ?? null,
        'auth_enabled' => $result['auth_enabled'] ?? false,
        'status' => 'online',
        'raw_info' => $result['raw_info'] ?? null,
    ]);
    $added[] = ['id' => $id, 'ip' => $ip];
}

json_response(['added' => $added, 'skipped' => $skipped]);
