<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$msg = '';
$edit_data = null;

// ---- TAMBAH ----
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $kode  = $conn->real_escape_string($_POST['kode']);
    $nama  = $conn->real_escape_string($_POST['nama']);
    $c1    = (float)$_POST['c1'];
    $c2    = (float)$_POST['c2'];
    $c3    = (float)$_POST['c3'];
    $c4    = (float)$_POST['c4'];
    $c5    = (float)$_POST['c5'];
    $conn->query("INSERT INTO alternatif (kode,nama,c1,c2,c3,c4,c5) VALUES ('$kode','$nama',$c1,$c2,$c3,$c4,$c5)");
    $msg = 'success|Data alternatif berhasil ditambahkan!';
}

// ---- EDIT SIMPAN ----
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id    = (int)$_POST['id'];
    $kode  = $conn->real_escape_string($_POST['kode']);
    $nama  = $conn->real_escape_string($_POST['nama']);
    $c1    = (float)$_POST['c1'];
    $c2    = (float)$_POST['c2'];
    $c3    = (float)$_POST['c3'];
    $c4    = (float)$_POST['c4'];
    $c5    = (float)$_POST['c5'];
    $conn->query("UPDATE alternatif SET kode='$kode',nama='$nama',c1=$c1,c2=$c2,c3=$c3,c4=$c4,c5=$c5 WHERE id=$id");
    $msg = 'success|Data alternatif berhasil diperbarui!';
}

// ---- HAPUS ----
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM alternatif WHERE id=$id");
    $msg = 'success|Data alternatif berhasil dihapus!';
}

// ---- EDIT FORM ----
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM alternatif WHERE id=$id")->fetch_assoc();
}

// Ambil semua data
$data = $conn->query("SELECT * FROM alternatif ORDER BY id");

list($msg_type, $msg_text) = $msg ? explode('|', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRUD Alternatif — SPK Kost</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink-50:#fff0f5;--pink-100:#ffe0ec;--pink-200:#ffc2d4;
      --pink-400:#ff6fa3;--pink-500:#f04e8a;--pink-600:#d43070;
      --text-dark:#2d1b25;--text-mid:#6b4158;--text-light:#b08090;
      --white:#ffffff;--nude-50:#fdf8f8;--nude-100:#f8f0f2;
      --radius:16px;--radius-sm:10px;
    }
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:var(--nude-50);color:var(--text-dark);}
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--white);border-right:1px solid var(--pink-100);display:flex;flex-direction:column;padding:28px 20px;z-index:100;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;}
    .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--pink-400),var(--pink-600));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;}
    .logo-text{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text-dark);line-height:1.2;}
    .logo-sub{font-size:10px;color:var(--text-light);font-weight:400;letter-spacing:0.5px;}
    .nav-label{font-size:10px;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;color:var(--text-light);padding:0 8px;margin-bottom:8px;margin-top:8px;}
    .nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);text-decoration:none;color:var(--text-mid);font-size:14px;font-weight:500;transition:all 0.2s;margin-bottom:2px;}
    .nav-item:hover,.nav-item.active{background:var(--pink-50);color:var(--pink-500);}
    .nav-icon{font-size:16px;width:22px;text-align:center;}
    .user-info{margin-top:auto;padding:12px;background:var(--pink-50);border-radius:var(--radius-sm);font-size:12px;color:var(--text-mid);}
    .user-info strong{display:block;color:var(--text-dark);font-size:13px;}
    .main{margin-left:240px;padding:32px 36px;}
    .page-title{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--text-dark);}
    .page-sub{color:var(--text-light);font-size:14px;margin-top:4px;margin-bottom:24px;}
    .card{background:var(--white);border-radius:var(--radius);border:1px solid var(--pink-100);box-shadow:0 2px 16px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:20px;}
    .card-header{padding:18px 24px;border-bottom:1px solid var(--pink-50);display:flex;align-items:center;gap:10px;}
    .card-title{font-family:'Playfair Display',serif;font-size:16px;font-weight:600;color:var(--text-dark);}
    .card-body{padding:20px 24px;}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    .form-group{display:flex;flex-direction:column;gap:6px;}
    .form-group.full{grid-column:1/-1;}
    label{font-size:12px;font-weight:600;color:var(--text-mid);letter-spacing:0.3px;}
    input[type=text],input[type=number],select{padding:10px 14px;border:1.5px solid var(--pink-100);border-radius:10px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:var(--pink-50);outline:none;transition:border-color 0.2s;}
    input:focus,select:focus{border-color:var(--pink-400);background:var(--white);}
    .btn{padding:10px 20px;border:none;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;}
    .btn-pink{background:linear-gradient(135deg,var(--pink-400),var(--pink-600));color:white;box-shadow:0 3px 12px rgba(240,78,138,0.2);}
    .btn-pink:hover{opacity:0.9;transform:translateY(-1px);}
    .btn-gray{background:var(--nude-100);color:var(--text-mid);}
    .btn-edit{background:var(--pink-50);color:var(--pink-600);padding:6px 14px;font-size:12px;border-radius:8px;}
    .btn-hapus{background:#fff0f0;color:#c0392b;padding:6px 14px;font-size:12px;border-radius:8px;}
    .btn-row{display:flex;gap:10px;margin-top:8px;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th{background:var(--pink-50);color:var(--pink-600);font-weight:600;padding:10px 14px;text-align:center;font-size:12px;border-bottom:2px solid var(--pink-100);}
    th:first-child{text-align:left;}
    td{padding:10px 14px;text-align:center;border-bottom:1px solid var(--nude-100);color:var(--text-dark);}
    td:first-child{text-align:left;font-weight:500;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:var(--pink-50);}
    .alert{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;}
    .alert-success{background:#f0fff4;color:#2d8a4e;border:1px solid #b7f5cc;}
    .tbl-wrap{overflow-x:auto;}
    .pill-cost{display:inline-block;background:#fff5e6;color:#d4700a;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;}
    .pill-benefit{display:inline-block;background:#f0fff8;color:#2d7a4e;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;}
  </style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏠</div>
    <div><div class="logo-text">SPK Kost</div><div class="logo-sub">AHP &amp; TOPSIS</div></div>
  </div>
  <div class="nav-label">Menu Utama</div>
  <a href="index.php" class="nav-item"><span class="nav-icon">📊</span> Dashboard</a>
  <a href="crud_alternatif.php" class="nav-item active"><span class="nav-icon">🏘️</span> Kelola Alternatif</a>
  <a href="crud_responden.php" class="nav-item"><span class="nav-icon">👥</span> Kelola Responden</a>
  <a href="laporan.php" class="nav-item"><span class="nav-icon">📄</span> Cetak Laporan</a>
  <div class="nav-label" style="margin-top:20px">Akun</div>
  <a href="logout.php" class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
  <div class="user-info">
    <strong>👤 <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['user']) ?></strong>
    Administrator
  </div>
</aside>

<main class="main">
  <div class="page-title">Kelola Alternatif Kost</div>
  <div class="page-sub">Tambah, edit, dan hapus data alternatif tempat kost</div>

  <?php if ($msg_text): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg_text) ?></div>
  <?php endif; ?>

  <!-- Form Tambah / Edit -->
  <div class="card">
    <div class="card-header">
      <span><?= $edit_data ? '✏️' : '➕' ?></span>
      <div class="card-title"><?= $edit_data ? 'Edit Data Alternatif' : 'Tambah Alternatif Baru' ?></div>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
        <?php if ($edit_data): ?>
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Kode Alternatif</label>
            <input type="text" name="kode" placeholder="Contoh: A6" value="<?= htmlspecialchars($edit_data['kode'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Nama Kost</label>
            <input type="text" name="nama" placeholder="Contoh: Kost Dahlia" value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>C1 — Harga Kost (ribu/bulan) <span class="pill-cost">COST</span></label>
            <input type="number" name="c1" placeholder="Contoh: 750" step="1" value="<?= $edit_data['c1'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>C2 — Jarak ke Kampus (km) <span class="pill-cost">COST</span></label>
            <input type="number" name="c2" placeholder="Contoh: 2" step="0.1" value="<?= $edit_data['c2'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>C3 — Fasilitas (1–5) <span class="pill-benefit">BENEFIT</span></label>
            <input type="number" name="c3" placeholder="1–5" min="1" max="5" step="1" value="<?= $edit_data['c3'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>C4 — WiFi (1–5) <span class="pill-benefit">BENEFIT</span></label>
            <input type="number" name="c4" placeholder="1–5" min="1" max="5" step="1" value="<?= $edit_data['c4'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>C5 — Keamanan (1–5) <span class="pill-benefit">BENEFIT</span></label>
            <input type="number" name="c5" placeholder="1–5" min="1" max="5" step="1" value="<?= $edit_data['c5'] ?? '' ?>" required>
          </div>
        </div>
        <div class="btn-row" style="margin-top:16px">
          <button type="submit" class="btn btn-pink">
            <?= $edit_data ? '💾 Simpan Perubahan' : '➕ Tambah Alternatif' ?>
          </button>
          <?php if ($edit_data): ?>
          <a href="crud_alternatif.php" class="btn btn-gray">✕ Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Data -->
  <div class="card">
    <div class="card-header">
      <span>📋</span>
      <div class="card-title">Daftar Alternatif Kost</div>
    </div>
    <div class="card-body tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Kode</th><th>Nama Kost</th>
            <th>Harga (rb)</th><th>Jarak (km)</th>
            <th>Fasilitas</th><th>WiFi</th><th>Keamanan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $data->fetch_assoc()): ?>
          <tr>
            <td><?= $row['kode'] ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= number_format($row['c1']) ?></td>
            <td><?= $row['c2'] ?></td>
            <td><?= $row['c3'] ?></td>
            <td><?= $row['c4'] ?></td>
            <td><?= $row['c5'] ?></td>
            <td>
              <a href="?edit=<?= $row['id'] ?>" class="btn btn-edit">✏️ Edit</a>
              <a href="?hapus=<?= $row['id'] ?>" class="btn btn-hapus"
                onclick="return confirm('Yakin hapus <?= htmlspecialchars($row['nama']) ?>?')">🗑️ Hapus</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
