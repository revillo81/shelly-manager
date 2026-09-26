<?php

class DeviceRepository
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM devices ORDER BY name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM devices WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByIp(string $ip): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM devices WHERE ip = ?');
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO devices
            (group_id, name, ip, mac, type, model, generation, app, firmware, auth_enabled, username, password_enc, status, last_seen, raw_info, notes, created_at, updated_at)
            VALUES (:group_id, :name, :ip, :mac, :type, :model, :generation, :app, :firmware, :auth_enabled, :username, :password_enc, :status, :last_seen, :raw_info, :notes, :created_at, :updated_at)');

        $stmt->execute([
            ':group_id' => $data['group_id'] ?? null,
            ':name' => $data['name'],
            ':ip' => $data['ip'],
            ':mac' => $data['mac'] ?? null,
            ':type' => $data['type'] ?? null,
            ':model' => $data['model'] ?? null,
            ':generation' => $data['generation'] ?? 1,
            ':app' => $data['app'] ?? null,
            ':firmware' => $data['firmware'] ?? null,
            ':auth_enabled' => !empty($data['auth_enabled']) ? 1 : 0,
            ':username' => $data['username'] ?? null,
            ':password_enc' => $data['password_enc'] ?? null,
            ':status' => $data['status'] ?? 'unknown',
            ':last_seen' => $data['last_seen'] ?? now(),
            ':raw_info' => isset($data['raw_info']) ? json_encode($data['raw_info']) : null,
            ':notes' => $data['notes'] ?? null,
            ':created_at' => now(),
            ':updated_at' => now(),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $params = [':id' => $id];
        $allowed = ['group_id', 'name', 'ip', 'mac', 'type', 'model', 'generation', 'app', 'firmware',
            'auth_enabled', 'username', 'password_enc', 'status', 'last_seen', 'raw_info', 'notes'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];
                if ($field === 'raw_info' && is_array($value)) {
                    $value = json_encode($value);
                }
                if ($field === 'auth_enabled') {
                    $value = !empty($value) ? 1 : 0;
                }
                $fields[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = 'updated_at = :updated_at';
        $params[':updated_at'] = now();

        $sql = 'UPDATE devices SET ' . implode(', ', $fields) . ' WHERE id = :id';
        Database::pdo()->prepare($sql)->execute($params);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM devices WHERE id = ?')->execute([$id]);
    }
}
