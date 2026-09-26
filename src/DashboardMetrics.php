<?php

/**
 * Ruft den Live-Status aller übergebenen Geräte parallel ab (curl_multi),
 * damit das Dashboard nicht für jedes Gerät einzeln warten muss.
 */
class DashboardMetrics
{
    public static function fetchAll(array $devices, int $timeout = 2): array
    {
        if (empty($devices)) {
            return [];
        }

        $mh = curl_multi_init();
        $handles = [];
        $genById = [];

        foreach ($devices as $device) {
            $gen = (int)$device['generation'];
            $genById[$device['id']] = $gen;
            $path = $gen >= 2 ? '/rpc/Shelly.GetStatus' : '/status';

            $ch = curl_init('http://' . $device['ip'] . $path);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
            ];

            if (!empty($device['username']) && !empty($device['password_enc'])) {
                $pass = Crypto::decrypt($device['password_enc']);
                if ($pass !== null && $pass !== '') {
                    $opts[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
                    $opts[CURLOPT_USERPWD] = $device['username'] . ':' . $pass;
                }
            }

            curl_setopt_array($ch, $opts);
            curl_multi_add_handle($mh, $ch);
            $handles[$device['id']] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.2);
        } while ($running > 0);

        $result = [];
        foreach ($handles as $id => $ch) {
            $body = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            $data = ($body && $code < 400) ? json_decode($body, true) : null;

            if (is_array($data)) {
                $result[$id] = [
                    'online' => true,
                    'metrics' => ShellyMetrics::summarize($data, $genById[$id]),
                ];
            } else {
                $result[$id] = ['online' => false, 'metrics' => []];
            }
        }
        curl_multi_close($mh);

        return $result;
    }
}
