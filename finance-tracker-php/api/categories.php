<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
requireLogin(true);
header('Content-Type: application/json');

$type = $_GET['type'] ?? null;
if (!in_array($type, ['income', 'expense'], true)) {
    http_response_code(400);
    die(json_encode(['error' => 'Parameter type harus income atau expense']));
}

echo json_encode(getCategories($pdo, $type));
