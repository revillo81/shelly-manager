<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

if (!is_post()) {
    json_response(['error' => 'method_not_allowed'], 405);
}
Csrf::verifyOrFail();

$range = trim((string)input('range', config('default_ip_range')));
if ($range === '') {
    json_response(['error' => 'invalid_range'], 400);
}

$existingIps = array_column(DeviceRepository::all(), 'ip');

$results = NetworkScanner::scan($range);

$results = array_map(function ($r) use ($existingIps) {
    $r['already_added'] = in_array($r['ip'], $existingIps, true);
    return $r;
}, $results);

json_response(['devices' => $results]);
