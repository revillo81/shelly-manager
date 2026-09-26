<?php

class GroupRepository
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM device_groups ORDER BY sort_order, name')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM device_groups WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, ?int $parentId = null): int
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO device_groups (parent_id, name, sort_order) VALUES (?, ?, 0)')
            ->execute([$parentId, $name]);
        return (int)$pdo->lastInsertId();
    }

    public static function rename(int $id, string $name): void
    {
        Database::pdo()->prepare('UPDATE device_groups SET name = ? WHERE id = ?')->execute([$name, $id]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::pdo();
        // Kindgruppen und zugeordnete Geräte lösen sich aus der Gruppe (werden nicht gelöscht)
        $pdo->prepare('UPDATE device_groups SET parent_id = NULL WHERE parent_id = ?')->execute([$id]);
        $pdo->prepare('UPDATE devices SET group_id = NULL WHERE group_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM device_groups WHERE id = ?')->execute([$id]);
    }

    /**
     * Baut eine Baumstruktur aus Gruppen (verschachtelt) inkl. zugeordneter Geräte.
     */
    public static function buildTree(array $groups, array $devices): array
    {
        $byParent = [];
        foreach ($groups as $group) {
            $byParent[$group['parent_id'] ?? 0][] = $group;
        }

        $devicesByGroup = [];
        foreach ($devices as $device) {
            $key = $device['group_id'] ?? 0;
            $devicesByGroup[$key][] = $device;
        }

        $build = function ($parentId) use (&$build, $byParent, $devicesByGroup) {
            $nodes = [];
            foreach ($byParent[$parentId] ?? [] as $group) {
                $nodes[] = [
                    'group' => $group,
                    'children' => $build($group['id']),
                    'devices' => $devicesByGroup[$group['id']] ?? [],
                ];
            }
            return $nodes;
        };

        return [
            'tree' => $build(0),
            'unassigned' => $devicesByGroup[0] ?? [],
        ];
    }
}
