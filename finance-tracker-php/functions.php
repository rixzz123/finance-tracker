<?php
require_once __DIR__ . '/config.php';

/** Hitung tanggal berikutnya berdasarkan frekuensi */
function calculateNextDate(string $date, string $frequency): string {
    $d = new DateTime($date);
    switch ($frequency) {
        case 'daily':   $d->modify('+1 day');   break;
        case 'weekly':  $d->modify('+1 week');  break;
        case 'yearly':  $d->modify('+1 year');  break;
        default:        $d->modify('+1 month'); // monthly
    }
    return $d->format('Y-m-d');
}

/**
 * Proses aturan recurring milik satu user yang sudah jatuh tempo.
 * Dipanggil di awal setiap request agar data selalu up to date.
 */
function processRecurring(PDO $pdo, int $userId): void {
    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT * FROM recurring_transactions WHERE is_active = 1 AND user_id = :uid AND next_date <= :today");
    $stmt->execute(['uid' => $userId, 'today' => $today]);
    $rules = $stmt->fetchAll();

    $insert = $pdo->prepare("INSERT INTO transactions (user_id, type, category_id, amount, note, tx_date, recurring_id)
                              VALUES (:uid, :type, :category_id, :amount, :note, :tx_date, :recurring_id)");
    $update = $pdo->prepare("UPDATE recurring_transactions SET next_date = :next_date, is_active = :active WHERE id = :id");

    foreach ($rules as $r) {
        $nextDate = $r['next_date'];
        $guard = 0;
        while ($nextDate <= $today && (!$r['end_date'] || $nextDate <= $r['end_date']) && $guard < 500) {
            $insert->execute([
                'uid' => $userId,
                'type' => $r['type'],
                'category_id' => $r['category_id'],
                'amount' => $r['amount'],
                'note' => $r['note'],
                'tx_date' => $nextDate,
                'recurring_id' => $r['id'],
            ]);
            $nextDate = calculateNextDate($nextDate, $r['frequency']);
            $guard++;
        }
        $active = (!$r['end_date'] || $nextDate <= $r['end_date']) ? 1 : 0;
        $update->execute(['next_date' => $nextDate, 'active' => $active, 'id' => $r['id']]);
    }
}

function getCategories(PDO $pdo, ?string $type = null): array {
    if ($type) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE type = :type ORDER BY name");
        $stmt->execute(['type' => $type]);
    } else {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY type, name");
    }
    return $stmt->fetchAll();
}

function getTransactions(PDO $pdo, int $userId, int $limit = 200): array {
    $stmt = $pdo->prepare("SELECT t.id, t.type, t.amount, t.note, t.tx_date, c.name AS category
                            FROM transactions t
                            JOIN categories c ON c.id = t.category_id
                            WHERE t.user_id = :uid
                            ORDER BY t.tx_date DESC, t.id DESC
                            LIMIT :lim");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getRecurringList(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT r.id, r.type, r.amount, r.note, r.frequency, r.next_date, r.end_date, c.name AS category
                          FROM recurring_transactions r
                          JOIN categories c ON c.id = r.category_id
                          WHERE r.is_active = 1 AND r.user_id = :uid
                          ORDER BY r.next_date ASC");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function getSummary(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT type, COALESCE(SUM(amount),0) AS total FROM transactions WHERE user_id = :uid GROUP BY type");
    $stmt->execute(['uid' => $userId]);
    $rows = $stmt->fetchAll();
    $summary = ['income' => 0, 'expense' => 0];
    foreach ($rows as $row) $summary[$row['type']] = (float)$row['total'];
    $summary['balance'] = $summary['income'] - $summary['expense'];
    return $summary;
}

/** Tren pemasukan vs pengeluaran 6 bulan terakhir */
function getMonthlyTrend(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(tx_date,'%Y-%m') AS ym, type, SUM(amount) AS total
                          FROM transactions
                          WHERE user_id = :uid AND tx_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                          GROUP BY ym, type");
    $stmt->execute(['uid' => $userId]);
    $rows = $stmt->fetchAll();
    $data = [];
    foreach ($rows as $r) $data[$r['ym']][$r['type']] = (float)$r['total'];

    $result = [];
    for ($i = 5; $i >= 0; $i--) {
        $d = new DateTime("first day of -$i month");
        $ym = $d->format('Y-m');
        $result[] = [
            'label' => $d->format('M y'),
            'income' => $data[$ym]['income'] ?? 0,
            'expense' => $data[$ym]['expense'] ?? 0,
        ];
    }
    return $result;
}

function getExpenseByCategory(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT c.name AS category, SUM(t.amount) AS total
                          FROM transactions t
                          JOIN categories c ON c.id = t.category_id
                          WHERE t.type = 'expense' AND t.user_id = :uid
                          GROUP BY c.name
                          ORDER BY total DESC");
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}
