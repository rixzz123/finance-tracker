<?php
session_start();
require_once __DIR__ . '/config.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/** Panggil di awal halaman/API yang butuh login. $api=true -> balas JSON 401 bukan redirect. */
function requireLogin(bool $api = false): void {
    if (!isLoggedIn()) {
        if ($api) {
            http_response_code(401);
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Sesi berakhir, silakan login kembali.']));
        }
        header('Location: login.php');
        exit;
    }
}

function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function currentUserName(): string {
    return $_SESSION['user_name'] ?? '';
}
