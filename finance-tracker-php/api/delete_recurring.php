<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
requireLogin(true);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$id = (int)($input['id'] ?? 0);

if ($id <= 0) {
    http_response_code(422);
    die(json_encode(['error' => 'ID tidak valid']));
}

$stmt = $pdo->prepare("UPDATE recurring_transactions SET is_active = 0 WHERE id = :id AND user_id = :uid");
$stmt->execute(['id' => $id, 'uid' => currentUserId()]);

echo json_encode(['success' => true]);
