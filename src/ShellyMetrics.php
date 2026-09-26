<?php

/**
 * Extrahiert normalisierte Live-Messwerte (Leistung, Spannung, Strom, Temperatur,
 * Luftfeuchtigkeit, Batterie) aus einer Shelly-Status-Antwort (Gen1 oder Gen2+).
 * Liefert eine flache Liste von Metriken ohne sprachabhängige Labels
 * (Übersetzung erfolgt clientseitig über window.metricLabels).
 */
class ShellyMetrics
{
    public static function summarize(?array $status, int $generation): array
    {
        if ($status === null) {
            return [];
        }
        return $generation >= 2 ? self::fromGen2($status) : self::fromGen1($status);
    }

    private static function fromGen1(array $status): array
    {
        $metrics = [];

        if (isset($status['temperature']) && is_numeric($status['temperature'])) {
            $metrics[] = self::metric('temperature', '🌡️', round((float)$status['temperature'], 1), '°C');
        }
        if (isset($status['tmp']['value']) && is_numeric($status['tmp']['value'])) {
            $unit = ($status['tmp']['units'] ?? 'C') === 'F' ? '°F' : '°C';
            $metrics[] = self::metric('temperature', '🌡️', round((float)$status['tmp']['value'], 1), $unit);
        }
        if (isset($status['hum']['value']) && is_numeric($status['hum']['value'])) {
            $metrics[] = self::metric('humidity', '💧', round((float)$status['hum']['value'], 1), '%');
        }
        if (isset($status['bat']['value']) && is_numeric($status['bat']['value'])) {
            $metrics[] = self::metric('battery', '🔋', round((float)$status['bat']['value']), '%');
        }

        $totalPower = 0.0;
        $hasPower = false;
        $voltageSeen = false;

        foreach ((array)($status['meters'] ?? []) as $meter) {
            if (isset($meter['power']) && is_numeric($meter['power'])) {
                $totalPower += (float)$meter['power'];
                $hasPower = true;
            }
        }
        foreach ((array)($status['emeters'] ?? []) as $meter) {
            if (isset($meter['power']) && is_numeric($meter['power'])) {
                $totalPower += (float)$meter['power'];
                $hasPower = true;
            }
            if (!$voltageSeen && isset($meter['voltage']) && is_numeric($meter['voltage'])) {
                $metrics[] = self::metric('voltage', '🔌', round((float)$meter['voltage'], 1), 'V');
                $voltageSeen = true;
            }
        }

        if ($hasPower) {
            array_unshift($metrics, self::metric('power', '⚡', round($totalPower, 1), 'W'));
        }

        return $metrics;
    }

    private static function fromGen2(array $status): array
    {
        $metrics = [];
        $totalPower = 0.0;
        $hasPower = false;

        foreach ($status as $key => $data) {
            if (!is_array($data)) {
                continue;
            }

            if (preg_match('/^(switch|cover|pm1):\d+$/', (string)$key) && isset($data['apower']) && is_numeric($data['apower'])) {
                $totalPower += (float)$data['apower'];
                $hasPower = true;
            }
            if (preg_match('/^(em|em1):\d+$/', (string)$key)) {
                $p = $data['act_power'] ?? $data['total_act_power'] ?? null;
                if (is_numeric($p)) {
                    $totalPower += (float)$p;
                    $hasPower = true;
                }
            }

            if (!self::has($metrics, 'voltage') && isset($data['voltage']) && is_numeric($data['voltage'])) {
                $metrics[] = self::metric('voltage', '🔌', round((float)$data['voltage'], 1), 'V');
            }
            if (!self::has($metrics, 'current') && isset($data['current']) && is_numeric($data['current'])) {
                $metrics[] = self::metric('current', '🔀', round((float)$data['current'], 2), 'A');
            }
            $tC = $data['tC'] ?? ($data['temperature']['tC'] ?? null);
            if (!self::has($metrics, 'temperature') && is_numeric($tC)) {
                $metrics[] = self::metric('temperature', '🌡️', round((float)$tC, 1), '°C');
            }
            if (!self::has($metrics, 'humidity') && isset($data['rh']) && is_numeric($data['rh'])) {
                $metrics[] = self::metric('humidity', '💧', round((float)$data['rh'], 1), '%');
            }
            if (!self::has($metrics, 'battery') && isset($data['battery']['percent']) && is_numeric($data['battery']['percent'])) {
                $metrics[] = self::metric('battery', '🔋', round((float)$data['battery']['percent']), '%');
            }
        }

        if ($hasPower) {
            array_unshift($metrics, self::metric('power', '⚡', round($totalPower, 1), 'W'));
        }

        return $metrics;
    }

    private static function metric(string $key, string $icon, $value, string $unit): array
    {
        return ['key' => $key, 'icon' => $icon, 'value' => $value, 'unit' => $unit];
    }

    private static function has(array $metrics, string $key): bool
    {
        foreach ($metrics as $m) {
            if ($m['key'] === $key) {
                return true;
            }
        }
        return false;
    }
}
