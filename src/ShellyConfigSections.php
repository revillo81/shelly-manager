<?php

/**
 * Zerlegt die vollständige Shelly-Konfiguration (Gen1 /settings bzw. Gen2+ Shelly.GetConfig)
 * in einzelne, unabhängig bearbeit- und speicherbare Abschnitte ("Komponenten").
 * Damit lassen sich grundsätzlich ALLE vom Gerät unterstützten Einstellungen bearbeiten,
 * auch solche, die hier nicht explizit als eigenes Formularfeld modelliert sind.
 */
class ShellyConfigSections
{
    // Bekannte Abkürzungen/Schreibweisen für die Gen2+ RPC-Methodennamen (Komponente -> Präfix)
    private const GEN2_METHOD_PREFIX = [
        'sys' => 'Sys',
        'wifi' => 'WiFi',
        'mqtt' => 'MQTT',
        'cloud' => 'Cloud',
        'ble' => 'BLE',
        'switch' => 'Switch',
        'cover' => 'Cover',
        'input' => 'Input',
        'light' => 'Light',
        'rgb' => 'RGB',
        'rgbw' => 'RGBW',
        'em' => 'EM',
        'em1' => 'EM1',
        'pm1' => 'PM1',
        'humidity' => 'Humidity',
        'temperature' => 'Temperature',
        'voltmeter' => 'Voltmeter',
        'devicepower' => 'DevicePower',
        'smoke' => 'Smoke',
        'flood' => 'Flood',
        'ui' => 'Ui',
        'knx' => 'Knx',
        'modbus' => 'Modbus',
        'eth' => 'Eth',
    ];

    // Gen1: Objekt-Unterabschnitte im /settings-Baum -> Ziel-Endpunkt
    private const GEN1_OBJECT_ENDPOINTS = [
        'wifi_sta' => '/settings/sta',
        'wifi_sta1' => '/settings/sta1',
        'ap' => '/settings/ap',
        'mqtt' => '/settings/mqtt',
        'cloud' => '/settings/cloud',
        'sntp' => '/settings/sntp',
        'login' => '/settings/login',
        'coiot' => '/settings/coiot',
    ];

    // Gen1: Array-Unterabschnitte (mehrere Kanäle) -> Ziel-Endpunkt-Präfix
    private const GEN1_ARRAY_ENDPOINTS = [
        'relays' => '/settings/relay/',
        'rollers' => '/settings/roller/',
        'lights' => '/settings/light/',
        'dimmers' => '/settings/dimmer/',
    ];

    // Gen2+: Komponenten-Präfix (vor dem ":") -> Kategorie
    private const GEN2_CATEGORY = [
        'wifi' => 'network',
        'ble' => 'network',
        'cloud' => 'connectivity',
        'mqtt' => 'connectivity',
        'ws' => 'connectivity',
        'sys' => 'device',
        'eth' => 'device',
        'ui' => 'device',
        'knx' => 'device',
        'modbus' => 'device',
    ];

    // Gen1: Abschnitts-Schlüssel -> Kategorie
    private const GEN1_CATEGORY = [
        'wifi_sta' => 'network',
        'wifi_sta1' => 'network',
        'ap' => 'network',
        'cloud' => 'connectivity',
        'mqtt' => 'connectivity',
        'coiot' => 'connectivity',
        'general' => 'device',
        'login' => 'device',
        'sntp' => 'device',
    ];

    public static function fromGen2(array $config): array
    {
        $sections = [];
        foreach ($config as $key => $data) {
            if (!is_array($data)) {
                continue;
            }
            [$component] = self::splitKey((string)$key);
            $sections[] = [
                'key' => (string)$key,
                'label' => self::labelFromKey((string)$key),
                'category' => self::GEN2_CATEGORY[strtolower($component)] ?? 'components',
                'data' => $data,
            ];
        }
        usort($sections, fn($a, $b) => strcmp($a['key'], $b['key']));
        return $sections;
    }

    public static function applyGen2(ShellyClient $client, string $key, array $data): ?array
    {
        [$component, $id] = self::splitKey($key);
        $prefix = self::GEN2_METHOD_PREFIX[strtolower($component)] ?? ucfirst($component);
        $params = ['config' => $data];
        if ($id !== null) {
            $params = ['id' => $id, 'config' => $data];
        }
        return $client->rpc($prefix . '.SetConfig', $params);
    }

    public static function fromGen1(array $settings): array
    {
        $sections = [];
        $general = [];

        foreach ($settings as $key => $value) {
            if (isset(self::GEN1_ARRAY_ENDPOINTS[$key]) && is_array($value) && array_is_list($value)) {
                foreach ($value as $idx => $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $sections[] = [
                        'key' => $key . ':' . $idx,
                        'label' => self::labelFromKey($key) . ' ' . $idx,
                        'category' => self::GEN1_CATEGORY[$key] ?? 'components',
                        'data' => $item,
                    ];
                }
                continue;
            }

            if (isset(self::GEN1_OBJECT_ENDPOINTS[$key]) && is_array($value)) {
                $sections[] = [
                    'key' => $key,
                    'label' => self::labelFromKey($key),
                    'category' => self::GEN1_CATEGORY[$key] ?? 'components',
                    'data' => $value,
                ];
                continue;
            }

            if (!is_array($value)) {
                $general[$key] = $value;
            }
        }

        array_unshift($sections, [
            'key' => 'general',
            'label' => 'Allgemein / General',
            'category' => 'device',
            'data' => $general,
        ]);

        return $sections;
    }

    public static function applyGen1(ShellyClient $client, string $key, array $data): ?array
    {
        if ($key === 'general') {
            return $client->gen1SettingsSet('/settings', $data);
        }

        [$component, $id] = self::splitKey($key);

        if ($id !== null && isset(self::GEN1_ARRAY_ENDPOINTS[$component])) {
            return $client->gen1SettingsSet(self::GEN1_ARRAY_ENDPOINTS[$component] . $id, $data);
        }

        if (isset(self::GEN1_OBJECT_ENDPOINTS[$component])) {
            return $client->gen1SettingsSet(self::GEN1_OBJECT_ENDPOINTS[$component], $data);
        }

        return null;
    }

    private static function splitKey(string $key): array
    {
        if (strpos($key, ':') !== false) {
            [$component, $id] = explode(':', $key, 2);
            return [$component, (int)$id];
        }
        return [$key, null];
    }

    private static function labelFromKey(string $key): string
    {
        $names = [
            'sys' => 'System',
            'wifi' => 'WLAN',
            'wifi_sta' => 'WLAN',
            'mqtt' => 'MQTT',
            'cloud' => 'Cloud',
            'ble' => 'Bluetooth (BLE)',
            'switch' => 'Schalter',
            'cover' => 'Rollladen',
            'input' => 'Eingang',
            'light' => 'Licht',
            'relays' => 'Relais',
            'rollers' => 'Rollladen',
            'lights' => 'Licht',
            'dimmers' => 'Dimmer',
            'ap' => 'Access Point',
            'sntp' => 'Zeitserver (SNTP)',
            'login' => 'Login-Schutz',
            'coiot' => 'CoIoT',
            'eth' => 'Ethernet',
        ];
        return $names[strtolower($key)] ?? ucfirst($key);
    }
}

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        $i = 0;
        foreach ($array as $k => $v) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }
}
