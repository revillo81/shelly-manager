<?php

/**
 * Erkennt den Shelly-Gerätetyp (Generation 1 "Classic" oder Generation 2+ "Plus/Pro")
 * anhand der öffentlich dokumentierten Endpunkte /shelly bzw. /rpc/Shelly.GetDeviceInfo.
 */
class ShellyDetector
{
    // Bekannte Gen1-Typkennungen -> sprechender Name (nicht abschließend)
    private const GEN1_TYPES = [
        'SHSW-1' => 'Shelly 1',
        'SHSW-PM' => 'Shelly 1PM',
        'SHSW-21' => 'Shelly 2',
        'SHSW-25' => 'Shelly 2.5',
        'SHSW-44' => 'Shelly 4Pro',
        'SHPLG-S' => 'Shelly Plug S',
        'SHPLG2-1' => 'Shelly Plug',
        'SHDM-1' => 'Shelly Dimmer',
        'SHDM-2' => 'Shelly Dimmer 2',
        'SHRGBW2' => 'Shelly RGBW2',
        'SHEM' => 'Shelly EM',
        'SHEM-3' => 'Shelly 3EM',
        'SHHT-1' => 'Shelly H&T',
        'SHDW-1' => 'Shelly Door/Window',
        'SHDW-2' => 'Shelly Door/Window 2',
        'SHMOS-01' => 'Shelly Motion',
        'SHBTN-1' => 'Shelly Button1',
        'SHIX3-1' => 'Shelly i3',
        'SHUNI-1' => 'Shelly UNI',
        'SHGS-1' => 'Shelly Gas',
        'SHVIN-1' => 'Shelly Vintage',
        'SHBLB-1' => 'Shelly Bulb',
        'SHCL-255' => 'Shelly Bulb RGBW',
    ];

    /**
     * Versucht, das Gerät unter der angegebenen IP zu erkennen.
     * Gibt ein normalisiertes Array zurück oder null, wenn kein Shelly gefunden wurde.
     */
    public static function detect(string $ip, ?string $username = null, ?string $password = null): ?array
    {
        $client = new ShellyClient($ip, $username, $password);

        // Gen2+ zuerst versuchen (RPC)
        $info = $client->rpcInfo();
        if ($info !== null && isset($info['gen'])) {
            return [
                'generation' => (int)$info['gen'],
                'type' => $info['app'] ?? ($info['model'] ?? 'unknown'),
                'model' => $info['model'] ?? null,
                'app' => $info['app'] ?? null,
                'mac' => $info['mac'] ?? null,
                'firmware' => $info['fw_id'] ?? ($info['ver'] ?? null),
                'auth_enabled' => !empty($info['auth_en']),
                'raw_info' => $info,
            ];
        }

        // Gen1 (Classic) Fallback
        $info = $client->gen1Info();
        if ($info !== null && isset($info['type'])) {
            $type = $info['type'];
            return [
                'generation' => 1,
                'type' => $type,
                'model' => self::GEN1_TYPES[$type] ?? $type,
                'app' => null,
                'mac' => $info['mac'] ?? null,
                'firmware' => $info['fw'] ?? null,
                'auth_enabled' => !empty($info['auth']),
                'raw_info' => $info,
            ];
        }

        return null;
    }

    public static function fetchStatus(array $device): ?array
    {
        $client = new ShellyClient(
            $device['ip'],
            $device['username'] ?: null,
            $device['password_enc'] ? Crypto::decrypt($device['password_enc']) : null
        );

        if ((int)$device['generation'] >= 2) {
            return $client->rpcStatus();
        }
        return $client->gen1Status();
    }
}
