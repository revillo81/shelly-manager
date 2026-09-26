<?php

/**
 * Durchsucht einen IP-Bereich (CIDR-Notation, z.B. 192.168.1.0/24) parallel
 * nach erreichbaren Shelly-Geräten und liefert die Erkennungsergebnisse zurück.
 */
class NetworkScanner
{
    public static function scan(string $cidr): array
    {
        $ips = self::expandCidr($cidr);
        if (empty($ips)) {
            return [];
        }

        $timeout = 1; // kurze Timeouts für den Scan
        $found = [];
        $parallelism = max(5, (int)config('scan_parallelism', 40));

        foreach (array_chunk($ips, $parallelism) as $chunk) {
            $results = self::probeChunk($chunk, $timeout);
            foreach ($results as $ip => $raw) {
                $parsed = self::parseProbe($raw);
                if ($parsed !== null) {
                    $parsed['ip'] = $ip;
                    $found[] = $parsed;
                }
            }
        }

        return $found;
    }

    private static function probeChunk(array $ips, int $timeout): array
    {
        $mh = curl_multi_init();
        $handles = [];

        foreach ($ips as $ip) {
            // Gen2+ zuerst
            $ch = curl_init('http://' . $ip . '/rpc/Shelly.GetDeviceInfo');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$ip] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 0.2);
        } while ($running > 0);

        $rpcResults = [];
        foreach ($handles as $ip => $ch) {
            $body = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($body && $code < 400) {
                $rpcResults[$ip] = $body;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // Für IPs ohne Gen2-Antwort: Gen1 versuchen
        $remaining = array_diff($ips, array_keys($rpcResults));
        $gen1Results = [];
        if (!empty($remaining)) {
            $mh = curl_multi_init();
            $handles = [];
            foreach ($remaining as $ip) {
                $ch = curl_init('http://' . $ip . '/shelly');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => $timeout,
                    CURLOPT_TIMEOUT => $timeout,
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$ip] = $ch;
            }
            $running = null;
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh, 0.2);
            } while ($running > 0);

            foreach ($handles as $ip => $ch) {
                $body = curl_multi_getcontent($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($body && $code < 400) {
                    $gen1Results[$ip] = $body;
                }
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);
        }

        return $rpcResults + $gen1Results;
    }

    private static function parseProbe(string $raw): ?array
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }

        if (isset($data['gen'])) {
            return [
                'generation' => (int)$data['gen'],
                'type' => $data['app'] ?? ($data['model'] ?? 'unknown'),
                'model' => $data['model'] ?? null,
                'app' => $data['app'] ?? null,
                'mac' => $data['mac'] ?? null,
                'firmware' => $data['fw_id'] ?? ($data['ver'] ?? null),
                'auth_enabled' => !empty($data['auth_en']),
                'raw_info' => $data,
            ];
        }

        if (isset($data['type'])) {
            return [
                'generation' => 1,
                'type' => $data['type'],
                'model' => $data['type'],
                'app' => null,
                'mac' => $data['mac'] ?? null,
                'firmware' => $data['fw'] ?? null,
                'auth_enabled' => !empty($data['auth']),
                'raw_info' => $data,
            ];
        }

        return null;
    }

    /**
     * Erweitert eine CIDR-Range (z.B. 192.168.1.0/24) zu einer Liste einzelner IPs
     * (ohne Netz- und Broadcast-Adresse bei /24 oder größer).
     */
    public static function expandCidr(string $cidr): array
    {
        if (strpos($cidr, '/') === false) {
            return [$cidr];
        }

        [$base, $maskBits] = explode('/', $cidr, 2);
        $maskBits = (int)$maskBits;
        if ($maskBits < 16 || $maskBits > 32) {
            // Sicherheitsgrenze: keine riesigen Scans erlauben
            $maskBits = max(16, min(32, $maskBits));
        }

        $baseLong = ip2long($base);
        if ($baseLong === false) {
            return [];
        }

        $hostBits = 32 - $maskBits;
        $count = 2 ** $hostBits;
        $network = $baseLong & (~0 << $hostBits);

        $ips = [];
        $start = ($count > 2) ? 1 : 0;
        $end = ($count > 2) ? $count - 2 : $count - 1;

        for ($i = $start; $i <= $end; $i++) {
            $ips[] = long2ip($network + $i);
        }

        return $ips;
    }
}
