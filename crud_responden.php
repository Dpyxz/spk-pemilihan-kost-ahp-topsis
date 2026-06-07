<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'fungsi_ahp.php';

$msg = '';
$edit_data = null;
$edit_matriks = null;

// ---- HAPUS RESPONDEN ----
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM responden WHERE id=$id");
    $msg = 'success|Responden berhasil dihapus!';
}

// ---- EDIT FORM ----
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit_data = $conn->query("SELECT * FROM responden WHERE id=$id")->fetch_assoc();
    $edit_matriks = ambilMatriksResponden($conn, $id);
}

// ---- TAMBAH RESPONDEN ----
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = $conn->real_escape_string($_POST['nama']);
    $conn->query("INSERT INTO responden (nama) VALUES ('$nama')");
    $resp_id = $conn->insert_id;

    // Simpan matriks 5x5
    for ($i = 1; $i <= 5; $i++) {
        for ($j = 1; $j <= 5; $j++) {
            $val = (float)$_POST["m_{$i}_{$j}"];
            $conn->query("INSERT INTO matriks_ahp (responden_id,baris,kolom,nilai) VALUES ($resp_id,$i,$j,$val)");
        }
    }

    // Hitung CR dan update status
    $matriks = ambilMatriksResponden($conn, $resp_id);
    $hasil = hitungAHP($matriks);
    $status = $hasil['konsisten'] ? 'konsisten' : 'tidak_konsisten';
    $cr = $hasil['CR'];
    $conn->query("UPDATE responden SET status='$status', cr=$cr WHERE id=$resp_id");

    $msg = 'success|Responden berhasil ditambahkan! CR = ' . round($cr, 4) . ' — ' . ($hasil['konsisten'] ? 'KONSISTEN' : 'TIDAK KONSISTEN');
}

// ---- EDIT SIMPAN ----
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id   = (int)$_POST['id'];
    $nama = $conn->real_escape_string($_POST['nama']);
    $conn->query("UPDATE responden SET nama='$nama' WHERE id=$id");
    $conn->query("DELETE FROM matriks_ahp WHERE responden_id=$id");

    for ($i = 1; $i <= 5; $i++) {
        for ($j = 1; $j <= 5; $j++) {
            $val = (float)$_POST["m_{$i}_{$j}"];
            $conn->query("INSERT INTO matriks_ahp (responden_id,baris,kolom,nilai) VALUES ($id,$i,$j,$val)");
        }
    }

    $matriks = ambilMatriksResponden($conn, $id);
    $hasil = hitungAHP($matriks);
    $status = $hasil['konsisten'] ? 'konsisten' : 'tidak_konsisten';
    $cr = $hasil['CR'];
    $conn->query("UPDATE responden SET status='$status', cr=$cr WHERE id=$id");

    $msg = 'success|Data responden diperbarui! CR = ' . round($cr, 4) . ' — ' . ($hasil['konsisten'] ? 'KONSISTEN' : 'TIDAK KONSISTEN');
    $edit_data = null; $edit_matriks = null;
}

// Ambil semua responden
$semua_resp = [];
$res = $conn->query("SELECT * FROM responden ORDER BY id");
while ($r = $res->fetch_assoc()) $semua_resp[] = $r;

list($msg_type, $msg_text) = $msg ? explode('|', $msg, 2) : ['', ''];
$nama_k = ['Harga','Jarak','Fasilitas','WiFi','Keamanan'];

// Default matriks untuk form tambah (identity)
$default_m = [];
for ($i=0;$i<5;$i++) for ($j=0;$j<5;$j++) $default_m[$i][$j] = ($i==$j)?1:0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRUD Responden — SPK Kost</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root{--pink-50:#fff0f5;--pink-100:#ffe0ec;--pink-200:#ffc2d4;--pink-400:#ff6fa3;--pink-500:#f04e8a;--pink-600:#d43070;--text-dark:#2d1b25;--text-mid:#6b4158;--text-light:#b08090;--white:#ffffff;--nude-50:#fdf8f8;--nude-100:#f8f0f2;--radius:16px;--radius-sm:10px;}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:var(--nude-50);color:var(--text-dark);}
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--white);border-right:1px solid var(--pink-100);display:flex;flex-direction:column;padding:28px 20px;z-index:100;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;}
    .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--pink-400),var(--pink-600));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;}
    .logo-text{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text-dark);line-height:1.2;}
    .logo-sub{font-size:10px;color:var(--text-light);}
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
    label{font-size:12px;font-weight:600;color:var(--text-mid);letter-spacing:0.3px;display:block;margin-bottom:5px;}
    input[type=text]{padding:10px 14px;border:1.5px solid var(--pink-100);border-radius:10px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text-dark);background:var(--pink-50);outline:none;width:100%;margin-bottom:14px;}
    input[type=text]:focus{border-color:var(--pink-400);background:var(--white);}
    .matriks-wrap{overflow-x:auto;margin:16px 0;}
    .matriks-table{border-collapse:collapse;font-size:12px;}
    .matriks-table th{background:var(--pink-50);color:var(--pink-600);font-weight:600;padding:8px 10px;text-align:center;border:1px solid var(--pink-100);}
    .matriks-table td{padding:4px;border:1px solid var(--pink-100);text-align:center;}
    .matriks-table input{width:72px;padding:6px 8px;border:1.5px solid var(--pink-100);border-radius:7px;font-size:12px;font-family:'DM Sans',sans-serif;text-align:center;background:var(--pink-50);color:var(--text-dark);}
    .matriks-table input:focus{border-color:var(--pink-400);background:var(--white);outline:none;}
    .matriks-table input[readonly]{background:var(--nude-100);color:var(--text-light);}
    .hint-skala{font-size:11px;color:var(--text-light);margin-bottom:10px;line-height:1.6;}
    .btn{padding:10px 20px;border:none;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;transition:all 0.2s;}
    .btn-pink{background:linear-gradient(135deg,var(--pink-400),var(--pink-600));color:white;box-shadow:0 3px 12px rgba(240,78,138,0.2);}
    .btn-pink:hover{opacity:0.9;}
    .btn-gray{background:var(--nude-100);color:var(--text-mid);}
    .btn-row{display:flex;gap:10px;margin-top:8px;}
    .btn-edit{background:var(--pink-50);color:var(--pink-600);padding:5px 12px;font-size:11px;border-radius:8px;border:none;cursor:pointer;text-decoration:none;}
    .btn-hapus{background:#fff0f0;color:#c0392b;padding:5px 12px;font-size:11px;border-radius:8px;border:none;cursor:pointer;text-decoration:none;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th{background:var(--pink-50);color:var(--pink-600);font-weight:600;padding:10px 14px;text-align:center;font-size:12px;border-bottom:2px solid var(--pink-100);}
    th:first-child{text-align:left;}
    td{padding:10px 14px;text-align:center;border-bottom:1px solid var(--nude-100);color:var(--text-dark);}
    td:first-child{text-align:left;font-weight:500;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:var(--pink-50);}
    .alert{padding:12px 18px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;}
    .alert-success{background:#f0fff4;color:#2d8a4e;border:1px solid #b7f5cc;}
    .pill-green{display:inline-block;background:#f0fff4;color:#2d8a4e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;}
    .pill-red{display:inline-block;background:#fff0f0;color:#c0392b;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;}
    .tbl-wrap{overflow-x:auto;}
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
  <a href="crud_alternatif.php" class="nav-item"><span class="nav-icon">🏘️</span> Kelola Alternatif</a>
  <a href="crud_responden.php" class="nav-item active"><span class="nav-icon">👥</span> Kelola Responden</a>
  <a href="laporan.php" class="nav-item"><span class="nav-icon">📄</span> Cetak Laporan</a>
  <div class="nav-label" style="margin-top:20px">Akun</div>
  <a href="logout.php" class="nav-item"><span class="nav-icon">🚪</span> Logout</a>
  <div class="user-info">
    <strong>👤 <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['user']) ?></strong>
    Administrator
  </div>
</aside>

<main class="main">
  <div class="page-title">Kelola Responden AHP</div>
  <div class="page-sub">Input matriks perbandingan berpasangan tiap responden</div>

  <?php if ($msg_text): ?>
  <div class="alert alert-success">✅ <?= htmlspecialchars($msg_text) ?></div>
  <?php endif; ?>

  <!-- Form Tambah / Edit -->
  <div class="card">
    <div class="card-header">
      <span><?= $edit_data ? '✏️' : '➕' ?></span>
      <div class="card-title"><?= $edit_data ? 'Edit Responden' : 'Tambah Responden Baru' ?></div>
    </div>
    <div class="card-body">
      <div class="hint-skala">
        <strong>Skala AHP:</strong>
        1 = Sama penting &nbsp;|&nbsp; 3 = Sedikit lebih penting &nbsp;|&nbsp; 5 = Lebih penting &nbsp;|&nbsp; 7 = Sangat lebih penting &nbsp;|&nbsp; 9 = Mutlak lebih penting
        <br>Nilai timbal balik otomatis (contoh: jika baris=3 kolom=5 diisi 3, maka baris=5 kolom=3 otomatis 1/3).
        <br><strong>Diagonal wajib = 1.</strong> Isi hanya segitiga atas (di atas diagonal), segitiga bawah otomatis diisi saat submit.
      </div>
      <form method="POST" id="formResponden">
        <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'tambah' ?>">
        <?php if ($edit_data): ?>
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
        <?php endif; ?>
        <label>Nama Responden</label>
        <input type="text" name="nama" placeholder="Contoh: Responden 6" value="<?= htmlspecialchars($edit_data['nama'] ?? '') ?>" required>

        <div class="matriks-wrap">
          <table class="matriks-table">
            <thead>
              <tr>
                <th>Kriteria</th>
                <?php foreach ($nama_k as $nk): ?><th><?= $nk ?></th><?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php
              $use_m = $edit_matriks ?? $default_m;
              for ($i=0; $i<5; $i++):
              ?>
              <tr>
                <th><?= $nama_k[$i] ?></th>
                <?php for ($j=0; $j<5; $j++): ?>
                <td>
                  <?php if ($i == $j): ?>
                    <input type="number" name="m_<?=$i+1?>_<?=$j+1?>" value="1" readonly>
                  <?php elseif ($j > $i): ?>
                    <input type="number" name="m_<?=$i+1?>_<?=$j+1?>"
                      value="<?= number_format($use_m[$i][$j] ?? 1, 3) ?>"
                      step="0.001" min="0.111" max="9"
                      id="m_<?=$i+1?>_<?=$j+1?>"
                      onchange="setReciprocal(<?=$i+1?>,<?=$j+1?>)">
                  <?php else: ?>
                    <input type="number" name="m_<?=$i+1?>_<?=$j+1?>"
                      value="<?= number_format($use_m[$i][$j] ?? 1, 3) ?>"
                      id="m_<?=$i+1?>_<?=$j+1?>" readonly style="color:var(--text-light)">
                  <?php endif; ?>
                </td>
                <?php endfor; ?>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>

        <div class="btn-row">
          <button type="submit" class="btn btn-pink">
            <?= $edit_data ? '💾 Simpan Perubahan' : '➕ Tambah Responden' ?>
          </button>
          <?php if ($edit_data): ?>
          <a href="crud_responden.php" class="btn btn-gray">✕ Batal</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabel Responden -->
  <div class="card">
    <div class="card-header"><span>📋</span><div class="card-title">Daftar Responden</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama Responden</th><th>CR</th><th>Status</th><th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($semua_resp as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= number_format($r['cr'], 4) ?></td>
            <td>
              <?php if ($r['status'] === 'konsisten'): ?>
                <span class="pill-green">✅ Konsisten</span>
              <?php else: ?>
                <span class="pill-red">❌ Tidak Konsisten</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="?edit=<?= $r['id'] ?>" class="btn-edit">✏️ Edit</a>
              <a href="?hapus=<?= $r['id'] ?>" class="btn-hapus"
                onclick="return confirm('Yakin hapus <?= htmlspecialchars($r['nama']) ?>?')">🗑️ Hapus</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<script>
function setReciprocal(i, j) {
  const val = parseFloat(document.getElementById('m_'+i+'_'+j).value);
  if (!isNaN(val) && val > 0) {
    const rec = document.getElementById('m_'+j+'_'+i);
    if (rec) rec.value = (1/val).toFixed(6);
  }
}
// Init semua nilai reciprocal dari edit
<?php if ($edit_matriks): ?>
document.addEventListener('DOMContentLoaded', function() {
  for (let i=1;i<=5;i++) {
    for (let j=i+1;j<=5;j++) {
      setReciprocal(i,j);
    }
  }
});
<?php endif; ?>
</script>
</body>
</html>
