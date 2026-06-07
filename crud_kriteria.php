<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';

$msg = '';
$edit_data = null;

// ---- TAMBAH ----
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $kode  = $conn->real_escape_string(trim($_POST['kode']));
    $nama  = $conn->real_escape_string(trim($_POST['nama']));
    $jenis = $_POST['jenis'] === 'cost' ? 'cost' : 'benefit';
    $conn->query("INSERT INTO kriteria (kode, nama, jenis, bobot) VALUES ('$kode','$nama','$jenis', 0)");
    $msg = 'success|Kriteria berhasil ditambahkan! Bobot akan dihitung ulang otomatis dari AHP.';
}

// ---- EDIT SIMPAN ----
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id    = (int)$_POST['id'];
    $kode  = $conn->real_escape_string(trim($_POST['kode']));
    $nama  = $conn->real_escape_string(trim($_POST['nama']));
    $jenis = $_POST['jenis'] === 'cost' ? 'cost' : 'benefit';
    $conn->query("UPDATE kriteria SET kode='$kode', nama='$nama', jenis='$jenis' WHERE id=$id");
    $msg = 'success|Kriteria berhasil diperbarui!';
    $edit_data = null;
}

// ---- HAPUS ----
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM kriteria WHERE id=$id");
    $msg = 'success|Kriteria berhasil dihapus!';
}

// ---- LOAD EDIT ----
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM kriteria WHERE id=$id")->fetch_assoc();
}

// Ambil semua kriteria
$data = $conn->query("SELECT * FROM kriteria ORDER BY id");
$jumlah = $conn->query("SELECT COUNT(*) as n FROM kriteria")->fetch_assoc()['n'];

list($msg_type, $msg_text) = $msg ? explode('|', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRUD Kriteria — SPK Kost</title>
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

    /* SIDEBAR */
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--white);border-right:1px solid var(--pink-100);display:flex;flex-direction:column;padding:22px 18px;z-index:100;overflow-y:auto;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
    .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--pink-400),var(--pink-600));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;flex-shrink:0;}
    .logo-text{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;color:var(--text-dark);line-height:1.2;}
    .logo-sub{font-size:10px;color:var(--text-light);letter-spacing:0.5px;}
    .nav-label{font-size:9px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--text-light);padding:0 8px;margin:14px 0 5px;}
    .nav-item{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:var(--radius-sm);text-decoration:none;color:var(--text-mid);font-size:13px;font-weight:500;transition:all 0.2s;margin-bottom:1px;}
    .nav-item:hover,.nav-item.active{background:var(--pink-50);color:var(--pink-500);}
    .nav-icon{font-size:15px;width:20px;text-align:center;flex-shrink:0;}
    .user-box{margin-top:auto;padding:11px 13px;background:var(--pink-50);border-radius:var(--radius-sm);border:1px solid var(--pink-100);}
    .user-box-name{font-size:12px;font-weight:600;color:var(--text-dark);margin-bottom:2px;}
    .user-box-role{font-size:10px;color:var(--text-light);}
    .user-box-logout{display:block;margin-top:8px;font-size:11px;color:var(--pink-500);text-decoration:none;font-weight:600;}

    /* MAIN */
    .main{margin-left:240px;padding:28px 32px;}
    .page-title{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:var(--text-dark);}
    .page-sub{color:var(--text-light);font-size:13px;margin-top:3px;margin-bottom:22px;}

    /* INFO BOX */
    .info-box{background:var(--pink-50);border:1px solid var(--pink-100);border-radius:10px;padding:12px 16px;font-size:12.5px;color:var(--text-mid);margin-bottom:18px;line-height:1.7;}
    .info-box strong{color:var(--pink-600);}
    .info-box.warn{background:#fffbf0;border-color:#ffe0a0;color:#7a5a00;}
    .info-box.warn strong{color:#b07800;}

    /* CARD */
    .card{background:var(--white);border-radius:var(--radius);border:1px solid var(--pink-100);box-shadow:0 2px 16px rgba(0,0,0,0.06);overflow:hidden;margin-bottom:18px;}
    .card-header{padding:15px 22px;border-bottom:1px solid var(--pink-50);display:flex;align-items:center;gap:9px;}
    .card-title{font-family:'Playfair Display',serif;font-size:15px;font-weight:600;color:var(--text-dark);}
    .card-badge{background:var(--pink-50);color:var(--pink-500);font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;margin-left:auto;}
    .card-body{padding:18px 22px;}

    /* FORM */
    .form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;align-items:end;}
    .form-group{display:flex;flex-direction:column;gap:6px;}
    label{font-size:12px;font-weight:600;color:var(--text-mid);letter-spacing:0.3px;}
    input[type=text], select{
      padding:10px 14px;border:1.5px solid var(--pink-100);border-radius:10px;
      font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-dark);
      background:var(--pink-50);outline:none;transition:border-color 0.2s;width:100%;
    }
    input[type=text]:focus, select:focus{border-color:var(--pink-400);background:var(--white);}
    select option{background:white;}

    /* BUTTONS */
    .btn{padding:10px 20px;border:none;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
    .btn-pink{background:linear-gradient(135deg,var(--pink-400),var(--pink-600));color:white;box-shadow:0 3px 12px rgba(240,78,138,0.2);}
    .btn-pink:hover{opacity:0.9;transform:translateY(-1px);}
    .btn-gray{background:var(--nude-100);color:var(--text-mid);}
    .btn-gray:hover{background:var(--pink-50);color:var(--pink-500);}
    .btn-row{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap;}
    .btn-edit{background:var(--pink-50);color:var(--pink-600);padding:5px 13px;font-size:11px;font-weight:600;border-radius:8px;border:1px solid var(--pink-100);cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
    .btn-edit:hover{background:var(--pink-100);}
    .btn-hapus{background:#fff0f0;color:#c0392b;padding:5px 13px;font-size:11px;font-weight:600;border-radius:8px;border:1px solid #ffc2c2;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px;}
    .btn-hapus:hover{background:#ffe0e0;}

    /* TABLE */
    .tbl-wrap{overflow-x:auto;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th{background:var(--pink-50);color:var(--pink-600);font-weight:600;padding:10px 14px;text-align:center;font-size:11.5px;border-bottom:2px solid var(--pink-100);}
    th:first-child{text-align:left;}
    td{padding:10px 14px;text-align:center;border-bottom:1px solid var(--nude-100);color:var(--text-dark);}
    td:first-child{text-align:left;font-weight:500;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:var(--pink-50);}

    /* PILLS */
    .pill-cost{display:inline-block;background:#fff5e6;color:#d4700a;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #ffdda0;}
    .pill-benefit{display:inline-block;background:#f0fff8;color:#2d7a4e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;border:1px solid #b0f0d0;}

    /* ALERT */
    .alert{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
    .alert-success{background:#f0fff4;color:#2d8a4e;border:1px solid #b7f5cc;}

    /* BOBOT BAR */
    .bobot-bar-wrap{background:var(--pink-50);border-radius:6px;height:8px;overflow:hidden;width:100px;display:inline-block;vertical-align:middle;margin-right:6px;}
    .bobot-bar{height:100%;background:linear-gradient(90deg,var(--pink-400),var(--pink-600));border-radius:6px;}

    /* STATS ROW */
    .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;}
    .stat-mini{background:var(--white);border:1px solid var(--pink-100);border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:12px;}
    .stat-mini-icon{width:36px;height:36px;border-radius:9px;background:var(--pink-50);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
    .stat-mini-val{font-size:20px;font-weight:700;color:var(--text-dark);line-height:1;}
    .stat-mini-lbl{font-size:11px;color:var(--text-light);margin-top:2px;}

    /* EDIT HIGHLIGHT */
    .form-editing{background:linear-gradient(135deg,#fff8fb,#fff0f5);border:2px solid var(--pink-200);}
    .editing-badge{display:inline-block;background:var(--pink-500);color:white;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;margin-left:8px;vertical-align:middle;}
  </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏠</div>
    <div><div class="logo-text">SPK Kost</div><div class="logo-sub">AHP &amp; TOPSIS</div></div>
  </div>

  <div class="nav-label">Utama</div>
  <a href="index.php" class="nav-item"><span class="nav-icon">📊</span>Dashboard</a>

  <div class="nav-label">Kelola Data</div>
  <a href="crud_kriteria.php" class="nav-item active"><span class="nav-icon">📋</span>Kelola Kriteria</a>
  <a href="crud_alternatif.php" class="nav-item"><span class="nav-icon">🏘️</span>Kelola Alternatif</a>
  <a href="crud_responden.php" class="nav-item"><span class="nav-icon">👥</span>Kelola Responden</a>

  <div class="nav-label">Laporan</div>
  <a href="laporan.php" target="_blank" class="nav-item"><span class="nav-icon">📄</span>Cetak Laporan</a>

  <div class="nav-label">Akun</div>
  <a href="logout.php" class="nav-item"><span class="nav-icon">🚪</span>Logout</a>

  <div class="user-box">
    <div class="user-box-name">👤 <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['user']) ?></div>
    <div class="user-box-role">Administrator</div>
    <a href="logout.php" class="user-box-logout">🚪 Logout</a>
  </div>
</aside>

<!-- ===== MAIN ===== -->
<main class="main">
  <div class="page-title">Kelola Kriteria</div>
  <div class="page-sub">Tambah, edit, dan hapus kriteria penilaian pemilihan tempat kost</div>

  <?php if ($msg_text): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg_text) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-mini">
      <div class="stat-mini-icon">📋</div>
      <div><div class="stat-mini-val"><?= $jumlah ?></div><div class="stat-mini-lbl">Total Kriteria</div></div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon">🔴</div>
      <div>
        <div class="stat-mini-val"><?= $conn->query("SELECT COUNT(*) as n FROM kriteria WHERE jenis='cost'")->fetch_assoc()['n'] ?></div>
        <div class="stat-mini-lbl">Kriteria Cost</div>
      </div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-icon">🟢</div>
      <div>
        <div class="stat-mini-val"><?= $conn->query("SELECT COUNT(*) as n FROM kriteria WHERE jenis='benefit'")->fetch_assoc()['n'] ?></div>
        <div class="stat-mini-lbl">Kriteria Benefit</div>
      </div>
    </div>
  </div>

  <!-- Info -->
  <div class="info-box warn">
    <strong>⚠️ Perhatian:</strong> Menambah atau menghapus kriteria akan mempengaruhi struktur matriks AHP dan perhitungan TOPSIS.
    Jika jumlah kriteria berubah, pastikan data matriks responden juga disesuaikan.
    Untuk proyek ini, <strong>kriteria yang digunakan tetap 5</strong> sesuai perhitungan AHP yang sudah ada.
    Fitur edit nama/jenis aman dilakukan kapan saja.
  </div>

  <!-- Form Tambah / Edit -->
  <div class="card <?= $edit_data ? 'form-editing' : '' ?>">
    <div class="card-header">
      <span><?= $edit_data ? '✏️' : '➕' ?></span>
      <div class="card-title">
        <?= $edit_data ? 'Edit Kriteria' : 'Tambah Kriteria Baru' ?>
        <?php if($edit_data): ?><span class="editing-badge">Mode Edit</span><?php endif; ?>
      </div>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
        <?php if ($edit_data): ?>
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
          <div class="form-group">
            <label>Kode Kriteria</label>
            <input type="text" name="kode"
              placeholder="Contoh: C1, C2, ..."
              value="<?= htmlspecialchars($edit_data['kode'] ?? '') ?>"
              maxlength="10" required>
          </div>
          <div class="form-group">
            <label>Nama Kriteria</label>
            <input type="text" name="nama"
              placeholder="Contoh: Harga Kost, Jarak ke Kampus, ..."
              value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>"
              maxlength="100" required>
          </div>
          <div class="form-group">
            <label>Jenis Kriteria</label>
            <select name="jenis">
              <option value="cost"    <?= (isset($edit_data['jenis']) && $edit_data['jenis']=='cost')    ? 'selected' : '' ?>>
                💰 Cost (semakin kecil semakin baik)
              </option>
              <option value="benefit" <?= (isset($edit_data['jenis']) && $edit_data['jenis']=='benefit') ? 'selected' : '' ?>>
                ⭐ Benefit (semakin besar semakin baik)
              </option>
            </select>
          </div>
        </div>

        <div class="btn-row">
          <button type="submit" class="btn btn-pink">
            <?= $edit_data ? '💾 Simpan Perubahan' : '➕ Tambah Kriteria' ?>
          </button>
          <?php if ($edit_data): ?>
          <a href="crud_kriteria.php" class="btn btn-gray">✕ Batal Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Data Kriteria -->
  <div class="card">
    <div class="card-header">
      <span>📋</span>
      <div class="card-title">Daftar Kriteria</div>
      <span class="card-badge"><?= $jumlah ?> Kriteria</span>
    </div>
    <div class="card-body tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>No</th>
            <th>Kode</th>
            <th>Nama Kriteria</th>
            <th>Jenis</th>
            <th>Bobot AHP</th>
            <th>Visualisasi Bobot</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Reset query
          $data = $conn->query("SELECT * FROM kriteria ORDER BY id");
          $no = 1;
          // Cari bobot max untuk bar
          $max_bobot = $conn->query("SELECT MAX(bobot) as m FROM kriteria")->fetch_assoc()['m'];
          $max_bobot = $max_bobot > 0 ? $max_bobot : 1;
          while ($row = $data->fetch_assoc()):
            $pct = ($row['bobot'] / $max_bobot) * 100;
          ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><strong><?= htmlspecialchars($row['kode']) ?></strong></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td>
              <span class="<?= $row['jenis'] === 'cost' ? 'pill-cost' : 'pill-benefit' ?>">
                <?= $row['jenis'] === 'cost' ? '💰 COST' : '⭐ BENEFIT' ?>
              </span>
            </td>
            <td>
              <strong style="color:var(--pink-600)">
                <?= $row['bobot'] > 0 ? number_format($row['bobot'], 6) : '—' ?>
              </strong>
            </td>
            <td>
              <?php if ($row['bobot'] > 0): ?>
              <div class="bobot-bar-wrap">
                <div class="bobot-bar" style="width:<?= $pct ?>%"></div>
              </div>
              <small style="color:var(--text-light)"><?= number_format($row['bobot']*100, 2) ?>%</small>
              <?php else: ?>
              <small style="color:var(--text-light)">Belum dihitung</small>
              <?php endif; ?>
            </td>
            <td>
              <a href="?edit=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
              <a href="?hapus=<?= $row['id'] ?>"
                 class="btn-hapus"
                 onclick="return confirm('Yakin hapus kriteria <?= htmlspecialchars(addslashes($row['nama'])) ?>?\n\nPerhatian: ini akan mempengaruhi perhitungan AHP & TOPSIS!')">
                🗑️ Hapus
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Info Penjelasan -->
  <div class="info-box">
    <strong>📌 Keterangan Jenis Kriteria:</strong><br>
    <span class="pill-cost">💰 COST</span> &nbsp; Nilai lebih kecil = lebih baik. Contoh: Harga murah, jarak dekat.<br>
    <span class="pill-benefit">⭐ BENEFIT</span> &nbsp; Nilai lebih besar = lebih baik. Contoh: Fasilitas lengkap, WiFi cepat, keamanan tinggi.<br><br>
    <strong>ℹ️ Bobot AHP</strong> dihitung otomatis dari rata-rata geometric mean seluruh responden yang konsisten (CR &lt; 0.1).
    Bobot akan terupdate otomatis setiap kali halaman dashboard dibuka.
  </div>

</main>
</body>
</html>