<?php

/**
 * HTTP-Client für die Shelly-Geräte-API.
 * Gen1 (Classic): https://shelly-api-docs.shelly.cloud/gen1/ -> REST unter /shelly, /status, /relay/{n}, /roller/{n}
 * Gen2+ (Plus/Pro): https://shelly-api-docs.shelly.cloud/gen2/ -> JSON-RPC unter /rpc/<Method>
 */
class ShellyClient
{
    private string $ip;
    private ?string $username;
    private ?string $password;
    private int $timeout;

    public function __construct(string $ip, ?string $username = null, ?string $password = null, ?int $timeout = null)
    {
        $this->ip = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout ?? (int)config('device_timeout', 3);
    }

    public function get(string $path): ?array
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $body = []): ?array
    {
        return $this->request('POST', $path, $body);
    }

    private function request(string $method, string $path, ?array $body = null): ?array
    {
        $url = 'http://' . $this->ip . $path;
        $ch = curl_init($url);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($this->username !== null && $this->password !== '') {
            $options[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
            $options[CURLOPT_USERPWD] = $this->username . ':' . $this->password;
        }

        if ($body !== null) {
            $json = json_encode($body);
            $options[CURLOPT_POSTFIELDS] = $json;
            $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    // ---- Gen1 (Classic) ----

    public function gen1Info(): ?array
    {
        return $this->get('/shelly');
    }

    public function gen1Status(): ?array
    {
        return $this->get('/status');
    }

    public function gen1SetRelay(int $channel, string $action): ?array
    {
        // action: on | off | toggle
        return $this->get('/relay/' . $channel . '?turn=' . urlencode($action));
    }

    public function gen1SetRoller(int $channel, string $action): ?array
    {
        // action: open | close | stop
        return $this->get('/roller/' . $channel . '?go=' . urlencode($action));
    }

    /** Komplette Gen1-Settings (verschachteltes Objekt: device, wifi_sta, mqtt, cloud, relays[], rollers[], lights[] ...) */
    public function gen1SettingsGet(): ?array
    {
        return $this->get('/settings');
    }

    /** Setzt Settings auf einem beliebigen Gen1-Endpunkt via Query-String (z.B. /settings, /settings/relay/0, /settings/sta ...) */
    public function gen1SettingsSet(string $endpoint, array $params): ?array
    {
        $query = http_build_query($params);
        return $this->get($endpoint . ($query !== '' ? '?' . $query : ''));
    }

    // ---- Gen2+ (RPC) ----

    public function rpcInfo(): ?array
    {
        return $this->get('/rpc/Shelly.GetDeviceInfo');
    }

    public function rpcStatus(): ?array
    {
        return $this->get('/rpc/Shelly.GetStatus');
    }

    /** Komplette Gen2+ Konfiguration aller Komponenten in einem Aufruf. */
    public function rpcConfig(): ?array
    {
        return $this->get('/rpc/Shelly.GetConfig');
    }

    /** Generischer JSON-RPC-Aufruf, z.B. rpc('Switch.SetConfig', ['id' => 0, 'config' => [...]]) */
    public function rpc(string $method, array $params = []): ?array
    {
        return $this->post('/rpc/' . $method, $params);
    }

    public function rpcSwitchSet(int $id, bool $on): ?array
    {
        return $this->post('/rpc/Switch.Set', ['id' => $id, 'on' => $on]);
    }

    public function rpcSwitchToggle(int $id): ?array
    {
        return $this->post('/rpc/Switch.Toggle', ['id' => $id]);
    }

    public function rpcCoverGoPosition(int $id, string $action): ?array
    {
        // action: Open | Close | Stop
        return $this->post('/rpc/Cover.' . $action, ['id' => $id]);
    }

    public function rpcReboot(): ?array
    {
        return $this->post('/rpc/Shelly.Reboot');
    }

    public function gen1Reboot(): ?array
    {
        return $this->get('/reboot');
    }
}
