<?php
require_once __DIR__ . '/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Email atau password salah.';
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Catatan Keuangan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="card auth-card">
    <h1 class="auth-title">💰 Masuk</h1>
    <p class="auth-sub">Login untuk melihat catatan keuangan kamu.</p>
    <?php if ($error): ?><div class="msg error" style="margin-bottom:10px"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <button type="submit" class="btn">Login</button>
    </form>
    <p class="auth-switch">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
  </div>
</div>
</body>
</html>
