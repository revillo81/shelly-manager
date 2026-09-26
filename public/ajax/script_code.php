<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

$id = (int)input('id', 0);
$device = DeviceRepository::find($id);
if (!$device || (int)$device['generation'] < 2) {
    json_response(['error' => 'not_found'], 404);
}

$client = DeviceClientFactory::make($device);
$scriptId = (int)input('script_id', 0);

if (is_post()) {
    Csrf::verifyOrFail();
    $code = (string)input('code', '');

    // Erster Aufruf ersetzt den kompletten Code (append=false), danach in Blöcken anhängen,
    // falls der Code die maximale Nachrichtengröße überschreitet.
    $chunkSize = 1024;
    $chunks = str_split($code, $chunkSize);
    if (empty($chunks)) {
        $chunks = [''];
    }

    $result = null;
    foreach ($chunks as $i => $chunk) {
        $result = $client->rpc('Script.PutCode', [
            'id' => $scriptId,
            'code' => $chunk,
            'append' => $i > 0,
        ]);
        if ($result === null) {
            json_response(['success' => false, 'error' => 'device_unreachable'], 502);
        }
    }

    json_response(['success' => true]);
}

// Code ggf. in mehreren Teilen abrufen (Shelly liefert "left" > 0, solange weitere Daten folgen)
$code = '';
$offset = 0;
$guard = 0;
do {
    $chunk = $client->rpc('Script.GetCode', ['id' => $scriptId, 'offset' => $offset]);
    if ($chunk === null) {
        if ($offset === 0) {
            json_response(['error' => 'device_unreachable'], 502);
        }
        break;
    }
    $data = $chunk['data'] ?? '';
    $code .= $data;
    $offset += strlen($data);
    $left = $chunk['left'] ?? 0;
    $guard++;
} while ($left > 0 && $guard < 200);

json_response(['code' => $code]);
