<?php
require __DIR__ . '/../../src/bootstrap.php';
Auth::requireLogin();

if (!is_post()) {
    redirect('../index.php');
}
Csrf::verifyOrFail();

$action = input('action', '');

if ($action === 'create') {
    $name = trim((string)input('name', ''));
    if ($name !== '') {
        GroupRepository::create($name);
    }
} elseif ($action === 'rename') {
    $id = (int)input('id', 0);
    $name = trim((string)input('name', ''));
    if ($id > 0 && $name !== '') {
        GroupRepository::rename($id, $name);
    }
} elseif ($action === 'delete') {
    $id = (int)input('id', 0);
    if ($id > 0) {
        GroupRepository::delete($id);
    }
}

redirect('../index.php');
