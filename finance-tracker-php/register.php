<?php
require_once __DIR__ . '/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan login.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)");
            $stmt->execute(['name' => $name, 'email' => $email, 'hash' => $hash]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Akun — Catatan Keuangan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="card auth-card">
    <h1 class="auth-title">💰 Buat Akun</h1>
    <p class="auth-sub">Catat keuangan kamu sendiri, terpisah dari pengguna lain.</p>
    <?php if ($error): ?><div class="msg error" style="margin-bottom:10px"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Nama</label>
      <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <label>Password</label>
      <input type="password" name="password" minlength="6" required>
      <label>Konfirmasi Password</label>
      <input type="password" name="confirm" minlength="6" required>
      <button type="submit" class="btn">Daftar</button>
    </form>
    <p class="auth-switch">Sudah punya akun? <a href="login.php">Login di sini</a></p>
  </div>
</div>
</body>
</html>
