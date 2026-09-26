<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
requireLogin(true);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

$userId = currentUserId();
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$type       = $input['type'] ?? '';
$categoryId = (int)($input['category_id'] ?? 0);
$amount     = (float)($input['amount'] ?? 0);
$note       = trim($input['note'] ?? '');
$date       = $input['date'] ?? date('Y-m-d');
$isRecurring = !empty($input['is_recurring']);

if (!in_array($type, ['income', 'expense'], true) || $amount <= 0 || $categoryId <= 0) {
    http_response_code(422);
    die(json_encode(['error' => 'Data tidak valid. Pastikan tipe, kategori, dan jumlah terisi benar.']));
}

try {
    if ($isRecurring) {
        $frequency = $input['frequency'] ?? 'monthly';
        $endDate   = !empty($input['end_date']) ? $input['end_date'] : null;

        if (!in_array($frequency, ['daily','weekly','monthly','yearly'], true)) {
            http_response_code(422);
            die(json_encode(['error' => 'Frekuensi tidak valid']));
        }

        $stmt = $pdo->prepare("INSERT INTO recurring_transactions
            (user_id, type, category_id, amount, note, frequency, start_date, next_date, end_date)
            VALUES (:uid, :type, :cat, :amount, :note, :freq, :start, :next, :end)");
        $stmt->execute([
            'uid' => $userId, 'type' => $type, 'cat' => $categoryId, 'amount' => $amount, 'note' => $note,
            'freq' => $frequency, 'start' => $date, 'next' => $date, 'end' => $endDate,
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, category_id, amount, note, tx_date)
            VALUES (:uid, :type, :cat, :amount, :note, :date)");
        $stmt->execute(['uid' => $userId, 'type' => $type, 'cat' => $categoryId, 'amount' => $amount, 'note' => $note, 'date' => $date]);
    }

    processRecurring($pdo, $userId);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan: ' . $e->getMessage()]);
}
