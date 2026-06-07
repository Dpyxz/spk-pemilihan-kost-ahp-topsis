<?php
session_start();
require_once 'koneksi.php';

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['nama'] = $user['nama'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SPK Pemilihan Kost</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink-50:  #fff0f5;
      --pink-100: #ffe0ec;
      --pink-200: #ffc2d4;
      --pink-300: #ff9abf;
      --pink-400: #ff6fa3;
      --pink-500: #f04e8a;
      --pink-600: #d43070;
      --text-dark:  #2d1b25;
      --text-mid:   #6b4158;
      --text-light: #b08090;
      --white: #ffffff;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #fff0f5 0%, #ffe4ef 40%, #fdf0f8 100%);
      display: flex; align-items: center; justify-content: center;
    }
    .login-wrap {
      width: 100%; max-width: 420px; padding: 24px;
    }
    .login-card {
      background: var(--white);
      border-radius: 24px;
      padding: 40px 36px;
      box-shadow: 0 8px 40px rgba(240,78,138,0.12);
      border: 1px solid var(--pink-100);
    }
    .logo-area {
      text-align: center; margin-bottom: 32px;
    }
    .logo-circle {
      width: 64px; height: 64px;
      background: linear-gradient(135deg, var(--pink-400), var(--pink-600));
      border-radius: 18px;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 16px;
      box-shadow: 0 4px 16px rgba(240,78,138,0.3);
    }
    .logo-title {
      font-family: 'Playfair Display', serif;
      font-size: 22px; font-weight: 700;
      color: var(--text-dark);
    }
    .logo-sub {
      font-size: 13px; color: var(--text-light); margin-top: 4px;
    }
    .form-group { margin-bottom: 18px; }
    label {
      display: block; font-size: 13px; font-weight: 600;
      color: var(--text-mid); margin-bottom: 7px;
    }
    input[type=text], input[type=password] {
      width: 100%; padding: 12px 16px;
      border: 1.5px solid var(--pink-100);
      border-radius: 12px; font-size: 14px;
      font-family: 'DM Sans', sans-serif;
      color: var(--text-dark);
      background: var(--pink-50);
      outline: none; transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus {
      border-color: var(--pink-400);
      box-shadow: 0 0 0 3px rgba(240,78,138,0.10);
      background: var(--white);
    }
    .btn-login {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--pink-400), var(--pink-600));
      color: white; border: none; border-radius: 12px;
      font-size: 15px; font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      cursor: pointer; margin-top: 8px;
      transition: opacity 0.2s, transform 0.1s;
      box-shadow: 0 4px 16px rgba(240,78,138,0.25);
    }
    .btn-login:hover { opacity: 0.92; transform: translateY(-1px); }
    .btn-login:active { transform: translateY(0); }
    .error-msg {
      background: #fff0f0; color: #c0392b;
      border: 1px solid #ffc2c2; border-radius: 10px;
      padding: 10px 14px; font-size: 13px;
      margin-bottom: 18px; text-align: center;
    }
    .hint {
      text-align: center; margin-top: 20px;
      font-size: 12px; color: var(--text-light);
    }
    .hint strong { color: var(--pink-500); }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="logo-area">
      <div class="logo-circle">🏠</div>
      <div class="logo-title">SPK Pemilihan Kost</div>
      <div class="logo-sub">Sistem Pendukung Keputusan · AHP &amp; TOPSIS</div>
    </div>

    <?php if ($error): ?>
    <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Masukkan username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
      </div>
      <button type="submit" class="btn-login">🔑 Masuk ke Dashboard</button>
    </form>

    <div class="hint">
      Default login: <strong>admin</strong> / <strong>admin123</strong>
    </div>
  </div>
</div>
</body>
</html>
