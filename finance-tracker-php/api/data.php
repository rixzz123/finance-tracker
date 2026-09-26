<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
requireLogin(true);
header('Content-Type: application/json');

$userId = currentUserId();
processRecurring($pdo, $userId);

echo json_encode([
    'summary'    => getSummary($pdo, $userId),
    'transactions' => getTransactions($pdo, $userId),
    'recurring'  => getRecurringList($pdo, $userId),
    'trend'      => getMonthlyTrend($pdo, $userId),
    'byCategory' => getExpenseByCategory($pdo, $userId),
]);
